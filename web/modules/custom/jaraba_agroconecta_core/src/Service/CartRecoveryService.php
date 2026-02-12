<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de recuperación automatizada de carritos abandonados.
 *
 * PROPÓSITO:
 * Detecta carritos abandonados y ejecuta una secuencia temporizada de
 * mensajes de recuperación vía email/push con incentivos progresivos.
 * Integrado con hook_cron() y Drupal Queue API.
 *
 * FLUJO:
 * 1. detectAbandonedCarts() → busca conversaciones con intent add_to_cart inactivas
 * 2. Encola items en 'jaraba_agroconecta_cart_recovery'
 * 3. processRecoveryQueue() → genera mensaje personalizado y envía
 * 4. Intervalos: 1h (sin descuento), 24h (5%), 72h (10%), 7d (10% + envío gratis)
 *
 * Referencia: Doc 68 — Sales Agent v1, Fase 5.
 */
class CartRecoveryService
{

    /**
     * Intervalos de recuperación en segundos.
     */
    private const RECOVERY_INTERVALS = [
        '1h' => 3600,
        '24h' => 86400,
        '72h' => 259200,
        '7d' => 604800,
    ];

    /**
     * Porcentaje de descuento por intervalo.
     */
    private const DISCOUNT_SCHEDULE = [
        '1h' => 0,
        '24h' => 5,
        '72h' => 10,
        '7d' => 10,
    ];

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected MailManagerInterface $mailManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Detecta carritos abandonados y programa recuperación.
     *
     * Llamado desde hook_cron(). Busca conversaciones con intent add_to_cart
     * que llevan más de 1 hora inactivas y no han sido recuperadas.
     *
     * @return int
     *   Número de carritos abandonados detectados.
     */
    public function detectAbandonedCarts(): int
    {
        $storage = $this->entityTypeManager->getStorage('sales_conversation_agro');
        $threshold = \Drupal::time()->getRequestTime() - self::RECOVERY_INTERVALS['1h'];

        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('state', 'active')
            ->condition('last_intent', 'add_to_cart')
            ->condition('changed', $threshold, '<')
            ->execute();

        if (empty($ids)) {
            return 0;
        }

        $conversations = $storage->loadMultiple($ids);
        $count = 0;

        /** @var \Drupal\Core\Queue\QueueInterface $queue */
        $queue = \Drupal::queue('jaraba_agroconecta_cart_recovery');

        foreach ($conversations as $conversation) {
            // Verificar que tiene cart_id y no ha sido recuperado.
            $cartId = $conversation->get('cart_id')->value ?? NULL;
            if (empty($cartId)) {
                continue;
            }

            $metadata = json_decode($conversation->get('metadata')->value ?? '{}', TRUE);
            if (!empty($metadata['recovery_completed'])) {
                continue;
            }

            // Determinar el intervalo de recuperación según tiempo transcurrido.
            $elapsed = \Drupal::time()->getRequestTime() - (int) $conversation->get('changed')->value;
            $interval = $this->determineInterval($elapsed);

            // Verificar que no se ha enviado ya este intervalo.
            $sentIntervals = $metadata['recovery_sent_intervals'] ?? [];
            if (in_array($interval, $sentIntervals, TRUE)) {
                continue;
            }

            $queue->createItem([
                'cart_id' => (int) $cartId,
                'conversation_id' => (int) $conversation->id(),
                'interval' => $interval,
                'customer_id' => $conversation->get('customer_id')->value,
                'tenant_id' => $conversation->get('tenant_id')->value,
            ]);
            $count++;
        }

        if ($count > 0) {
            $this->logger->info('Carritos abandonados detectados: @count.', ['@count' => $count]);
        }

        return $count;
    }

    /**
     * Genera mensaje de recuperación personalizado.
     *
     * @param int $cartId
     *   Cart ID.
     * @param string $interval
     *   Recovery interval (1h, 24h, 72h, 7d).
     *
     * @return array
     *   Mensaje estructurado con keys: subject, message, incentive_type,
     *   discount_percent, code, products.
     */
    public function generateRecoveryMessage(int $cartId, string $interval = '1h'): array
    {
        // Cargar conversación asociada al carrito.
        $storage = $this->entityTypeManager->getStorage('sales_conversation_agro');
        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('cart_id', $cartId)
            ->sort('changed', 'DESC')
            ->range(0, 1)
            ->execute();

        $products = [];
        if (!empty($ids)) {
            $conversation = $storage->load(reset($ids));
            $products = $this->getCartProducts($conversation);
        }

        // Calcular descuento según intervalo.
        $discountPercent = self::DISCOUNT_SCHEDULE[$interval] ?? 0;

        // Generar mensaje personalizado según intervalo.
        $messageData = match ($interval) {
            '1h' => [
                'subject' => '¿Te olvidaste algo? Tus productos te esperan',
                'message' => '¡Hola! Hemos visto que dejaste algunos productos en tu carrito. '
                    . 'Tus productos artesanales favoritos siguen disponibles y te están esperando. '
                    . '¿Te gustaría completar tu pedido?',
                'incentive_type' => 'none',
                'discount_percent' => 0,
                'code' => '',
            ],
            '24h' => [
                'subject' => 'Tu carrito sigue esperando + 5% descuento',
                'message' => '¡Hola de nuevo! Tu carrito lleva un día esperándote. '
                    . 'Para animarte a completar tu compra, te ofrecemos un 5% de descuento. '
                    . 'Usa el código que te adjuntamos antes de que expire.',
                'incentive_type' => 'discount',
                'discount_percent' => 5,
                'code' => $this->generateDiscountCode('VUELVE5', 5),
            ],
            '72h' => [
                'subject' => 'Última oportunidad + 10% descuento',
                'message' => '¡No dejes escapar esta oportunidad! Tus productos artesanales '
                    . 'favoritos aún están disponibles, pero las existencias son limitadas. '
                    . 'Te ofrecemos un 10% de descuento exclusivo para que completes tu pedido.',
                'incentive_type' => 'discount',
                'discount_percent' => 10,
                'code' => $this->generateDiscountCode('VUELVE10', 10),
            ],
            '7d' => [
                'subject' => '10% + envío gratis por tiempo limitado',
                'message' => '¡Última llamada! Te ofrecemos un 10% de descuento Y envío gratis '
                    . 'en tu carrito abandonado. Esta oferta es por tiempo muy limitado. '
                    . 'No dejes pasar esta oportunidad de llevarte productos artesanales de calidad.',
                'incentive_type' => 'discount_and_shipping',
                'discount_percent' => 10,
                'code' => $this->generateDiscountCode('ENVIOGRATIS', 10),
            ],
            default => [
                'subject' => '¿Te olvidaste algo? Tus productos te esperan',
                'message' => 'Tienes productos esperando en tu carrito. ¿Te gustaría completar tu pedido?',
                'incentive_type' => 'none',
                'discount_percent' => 0,
                'code' => '',
            ],
        };

        $messageData['products'] = $products;

        return $messageData;
    }

    /**
     * Envía mensajes de recuperación pendientes.
     *
     * Procesado por QueueWorker o cron. Valida que el carrito aún existe
     * y no ha sido convertido, genera el mensaje y lo envía vía email.
     *
     * @param array $item
     *   Item de la cola con keys: cart_id, conversation_id, interval,
     *   customer_id, tenant_id.
     */
    public function processRecoveryQueue(array $item): void
    {
        $cartId = (int) ($item['cart_id'] ?? 0);
        $conversationId = (int) ($item['conversation_id'] ?? 0);
        $interval = $item['interval'] ?? '1h';
        $customerId = $item['customer_id'] ?? NULL;

        if ($cartId <= 0 || $conversationId <= 0) {
            $this->logger->warning('Item de recuperación inválido: cart_id=@cart, conversation=@conv.', [
                '@cart' => $cartId,
                '@conv' => $conversationId,
            ]);
            return;
        }

        // Verificar que la conversación aún existe y no ha sido convertida.
        $convStorage = $this->entityTypeManager->getStorage('sales_conversation_agro');
        $conversation = $convStorage->load($conversationId);

        if (!$conversation) {
            $this->logger->info('Conversación @id no encontrada, omitiendo recuperación.', [
                '@id' => $conversationId,
            ]);
            return;
        }

        $state = $conversation->get('state')->value;
        if ($state === 'converted' || $state === 'closed') {
            $this->logger->info('Carrito @cart ya convertido/cerrado, omitiendo recuperación.', [
                '@cart' => $cartId,
            ]);
            return;
        }

        // Generar mensaje personalizado.
        $messageData = $this->generateRecoveryMessage($cartId, $interval);

        // Enviar vía email si hay customer_id.
        if (!empty($customerId)) {
            $this->sendRecoveryEmail((int) $customerId, $messageData);
        }

        // Registrar intento de recuperación en metadata de la conversación.
        $metadata = json_decode($conversation->get('metadata')->value ?? '{}', TRUE);
        $attempts = (int) ($metadata['recovery_attempts'] ?? 0);
        $metadata['recovery_attempts'] = $attempts + 1;
        $metadata['last_recovery_interval'] = $interval;
        $metadata['last_recovery_at'] = \Drupal::time()->getRequestTime();

        $sentIntervals = $metadata['recovery_sent_intervals'] ?? [];
        $sentIntervals[] = $interval;
        $metadata['recovery_sent_intervals'] = $sentIntervals;

        // Marcar como completado si se ha enviado el último intervalo.
        if ($interval === '7d') {
            $metadata['recovery_completed'] = TRUE;
        }

        $conversation->set('metadata', json_encode($metadata, JSON_UNESCAPED_UNICODE));
        $conversation->save();

        $this->logger->info('Recuperación de carrito @cart enviada (intervalo: @interval, intento: @attempt).', [
            '@cart' => $cartId,
            '@interval' => $interval,
            '@attempt' => $attempts + 1,
        ]);
    }

    /**
     * Genera un código de descuento único para recuperación.
     *
     * @param string $prefix
     *   Prefijo del código (ej: VUELVE5, VUELVE10, ENVIOGRATIS).
     * @param float $discountPercent
     *   Porcentaje de descuento asociado.
     *
     * @return string
     *   Código de descuento único.
     */
    public function generateDiscountCode(string $prefix, float $discountPercent): string
    {
        return $prefix . strtoupper(substr(md5(uniqid((string) $discountPercent, TRUE)), 0, 6));
    }

    /**
     * Obtiene estadísticas de recuperación de carritos.
     *
     * @param int $days
     *   Número de días a analizar (por defecto 30).
     *
     * @return array
     *   Estadísticas con keys: total_abandoned, recovered, recovery_rate,
     *   avg_recovery_value, top_recovery_interval.
     */
    public function getRecoveryStats(int $days = 30): array
    {
        $storage = $this->entityTypeManager->getStorage('sales_conversation_agro');
        $since = \Drupal::time()->getRequestTime() - ($days * 86400);

        // Total de carritos abandonados en el período.
        $totalAbandoned = (int) $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('last_intent', 'add_to_cart')
            ->condition('created', $since, '>=')
            ->count()
            ->execute();

        // Carritos recuperados (state cambió a 'converted' después de intento de recuperación).
        $recoveredIds = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('last_intent', 'add_to_cart')
            ->condition('state', 'converted')
            ->condition('created', $since, '>=')
            ->execute();

        $recovered = 0;
        $recoveryValues = [];
        $intervalCounts = [];

        if (!empty($recoveredIds)) {
            $conversations = $storage->loadMultiple($recoveredIds);
            foreach ($conversations as $conv) {
                $metadata = json_decode($conv->get('metadata')->value ?? '{}', TRUE);
                if (!empty($metadata['recovery_attempts']) && (int) $metadata['recovery_attempts'] > 0) {
                    $recovered++;

                    // Registrar valor de recuperación si está disponible.
                    $value = (float) ($metadata['recovery_value'] ?? 0);
                    if ($value > 0) {
                        $recoveryValues[] = $value;
                    }

                    // Contabilizar intervalo de recuperación exitosa.
                    $lastInterval = $metadata['last_recovery_interval'] ?? 'unknown';
                    $intervalCounts[$lastInterval] = ($intervalCounts[$lastInterval] ?? 0) + 1;
                }
            }
        }

        // Calcular tasa de recuperación.
        $recoveryRate = $totalAbandoned > 0
            ? round(($recovered / $totalAbandoned) * 100, 2)
            : 0.0;

        // Calcular valor promedio de recuperación.
        $avgRecoveryValue = !empty($recoveryValues)
            ? round(array_sum($recoveryValues) / count($recoveryValues), 2)
            : 0.0;

        // Determinar el intervalo más efectivo.
        $topInterval = 'none';
        if (!empty($intervalCounts)) {
            arsort($intervalCounts);
            $topInterval = array_key_first($intervalCounts);
        }

        return [
            'total_abandoned' => $totalAbandoned,
            'recovered' => $recovered,
            'recovery_rate' => $recoveryRate,
            'avg_recovery_value' => $avgRecoveryValue,
            'top_recovery_interval' => $topInterval,
        ];
    }

    /**
     * Determina el intervalo de recuperación según el tiempo transcurrido.
     *
     * @param int $elapsed
     *   Segundos transcurridos desde el abandono.
     *
     * @return string
     *   Clave del intervalo (1h, 24h, 72h, 7d).
     */
    protected function determineInterval(int $elapsed): string
    {
        if ($elapsed >= self::RECOVERY_INTERVALS['7d']) {
            return '7d';
        }
        if ($elapsed >= self::RECOVERY_INTERVALS['72h']) {
            return '72h';
        }
        if ($elapsed >= self::RECOVERY_INTERVALS['24h']) {
            return '24h';
        }

        return '1h';
    }

    /**
     * Obtiene productos del carrito desde los mensajes de la conversación.
     *
     * @param object $conversation
     *   Entidad de conversación.
     *
     * @return array
     *   Array de productos con id y name.
     */
    protected function getCartProducts(object $conversation): array
    {
        $messageStorage = $this->entityTypeManager->getStorage('sales_message_agro');
        $messageIds = $messageStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('conversation_id', $conversation->id())
            ->condition('role', 'assistant')
            ->sort('created', 'DESC')
            ->range(0, 5)
            ->execute();

        $products = [];
        if (!empty($messageIds)) {
            $messages = $messageStorage->loadMultiple($messageIds);
            foreach ($messages as $message) {
                $productsShown = $message->get('products_shown')->value ?? '';
                if (!empty($productsShown)) {
                    $decoded = json_decode($productsShown, TRUE);
                    if (is_array($decoded)) {
                        foreach ($decoded as $product) {
                            $products[] = [
                                'id' => (int) ($product['id'] ?? 0),
                                'name' => $product['name'] ?? '',
                            ];
                        }
                    }
                }
            }
        }

        // Eliminar duplicados por ID.
        $seen = [];
        $unique = [];
        foreach ($products as $product) {
            if ($product['id'] > 0 && !isset($seen[$product['id']])) {
                $seen[$product['id']] = TRUE;
                $unique[] = $product;
            }
        }

        return $unique;
    }

    /**
     * Envía email de recuperación a un cliente.
     *
     * @param int $customerId
     *   ID del usuario cliente.
     * @param array $messageData
     *   Datos del mensaje generado por generateRecoveryMessage().
     */
    protected function sendRecoveryEmail(int $customerId, array $messageData): void
    {
        try {
            $userStorage = $this->entityTypeManager->getStorage('user');
            /** @var \Drupal\user\UserInterface|null $user */
            $user = $userStorage->load($customerId);

            if (!$user) {
                $this->logger->warning('Usuario @uid no encontrado para recuperación de carrito.', [
                    '@uid' => $customerId,
                ]);
                return;
            }

            $email = $user->getEmail();
            if (!$email) {
                $this->logger->warning('Usuario @uid sin email para recuperación de carrito.', [
                    '@uid' => $customerId,
                ]);
                return;
            }

            $params = [
                'subject' => $messageData['subject'],
                'body' => $messageData['message'],
                'incentive_type' => $messageData['incentive_type'],
                'discount_code' => $messageData['code'],
                'discount_percent' => $messageData['discount_percent'],
                'products' => $messageData['products'],
            ];

            $result = $this->mailManager->mail(
                'jaraba_agroconecta_core',
                'cart_recovery',
                $email,
                $user->getPreferredLangcode(),
                $params,
                NULL,
                TRUE
            );

            if (empty($result['result'])) {
                $this->logger->error('Error al enviar email de recuperación a @email.', [
                    '@email' => $email,
                ]);
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Excepción al enviar email de recuperación: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
