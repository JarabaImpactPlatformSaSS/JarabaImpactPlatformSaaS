<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Procesa pedidos entrantes vía WhatsApp.
 *
 * Detecta intención de compra en mensajes de WhatsApp, crea pedidos
 * en AgroConecta y genera payment links de Stripe para cobro.
 */
class WhatsAppOrderService {

  /**
   * Keywords que indican intención de compra.
   *
   * @var string[]
   */
  protected const PURCHASE_KEYWORDS = [
    'comprar', 'pedir', 'quiero', 'precio', 'disponible',
    'cuanto', 'cuánto', 'envio', 'envío', 'carrito',
    'añadir', 'agregar', 'reservar', 'encargar',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?WhatsAppApiService $whatsAppApi,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa un pedido estructurado de WhatsApp (catálogo nativo).
   *
   * Meta envía mensajes tipo 'order' cuando el cliente usa el catálogo
   * nativo de WhatsApp Business y pulsa "Enviar pedido". El payload
   * incluye product_items[] con retailer_id, quantity y item_price.
   *
   * @param string $senderPhone
   *   Teléfono del remitente (formato +34xxx).
   * @param array<int, array{retailer_id: string, quantity: int, item_price: float, currency: string}> $productItems
   *   Items del pedido del catálogo WhatsApp.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array{is_purchase: bool, response_sent: bool, order_id: int|null}
   *   Resultado del procesamiento.
   */
  public function processStructuredOrder(string $senderPhone, array $productItems, int $tenantId): array {
    $result = ['is_purchase' => TRUE, 'response_sent' => FALSE, 'order_id' => NULL];

    if ($productItems === []) {
      return $result;
    }

    try {
      // Resolver productos desde retailer_id (formato: agro_{entity_id}).
      $products = [];
      $total = 0.0;
      foreach ($productItems as $item) {
        $retailerId = $item['retailer_id'] ?? '';
        $quantity = (int) ($item['quantity'] ?? 1);
        $itemPrice = (float) ($item['item_price'] ?? 0);

        // Extraer entity ID del retailer_id (agro_123 → 123).
        $entityId = str_starts_with($retailerId, 'agro_')
          ? (int) substr($retailerId, 5)
          : 0;

        if ($entityId > 0) {
          $product = $this->entityTypeManager->getStorage('product_agro')->load($entityId);
          if ($product !== NULL) {
            $products[] = ['product' => $product, 'quantity' => $quantity];
          }
        }

        $total += $itemPrice * $quantity;
      }

      if ($total <= 0 && $products !== []) {
        foreach ($products as $item) {
          $total += (float) ($item['product']->get('price')->value ?? 0) * $item['quantity'];
        }
      }

      // Crear pedido.
      $order = $this->createOrderFromTotal($total, $senderPhone, $tenantId, 'whatsapp_catalog');
      if ($order !== NULL) {
        $result['order_id'] = (int) $order->id();

        // Enviar payment link por WhatsApp.
        $paymentUrl = $this->generatePaymentLink($order);
        if ($this->whatsAppApi !== NULL && $paymentUrl !== '') {
          $formattedTotal = number_format($total, 2, ',', '.');
          $itemCount = count($productItems);
          $this->whatsAppApi->sendTextMessage(
            $senderPhone,
            "¡Pedido recibido! ({$itemCount} productos)\n\n" .
            "Total: {$formattedTotal} EUR\n\n" .
            "Paga de forma segura aquí:\n{$paymentUrl}\n\n" .
            "El enlace es válido durante 24 horas."
          );
          $result['response_sent'] = TRUE;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp structured order error: @msg', ['@msg' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Procesa un mensaje entrante de WhatsApp para detectar intención de compra.
   *
   * @param string $senderPhone
   *   Teléfono del remitente (formato +34xxx).
   * @param string $messageText
   *   Texto del mensaje.
   * @param int $tenantId
   *   ID del tenant al que pertenece el negocio.
   *
   * @return array{is_purchase: bool, response_sent: bool, order_id: int|null}
   *   Resultado del procesamiento.
   */
  public function processIncomingMessage(string $senderPhone, string $messageText, int $tenantId): array {
    $result = ['is_purchase' => FALSE, 'response_sent' => FALSE, 'order_id' => NULL];

    if (!$this->detectPurchaseIntent($messageText)) {
      return $result;
    }

    $result['is_purchase'] = TRUE;

    try {
      // Buscar productos que coincidan con el mensaje.
      $products = $this->searchProducts($messageText, $tenantId);

      if ($products === []) {
        // Sin productos encontrados — responder con catálogo general.
        if ($this->whatsAppApi !== null) {
          $this->whatsAppApi->sendTextMessage(
            $senderPhone,
            "¡Hola! No encontré productos específicos para tu consulta. " .
            "Visita nuestra tienda online para ver todo el catálogo. " .
            "¿Puedo ayudarte con algo más?"
          );
          $result['response_sent'] = TRUE;
        }
        return $result;
      }

      // Crear pedido con los productos encontrados.
      $order = $this->createOrderFromProducts($products, $senderPhone, $tenantId);
      if ($order !== null) {
        $result['order_id'] = (int) $order->id();

        // Generar payment link y enviar por WhatsApp.
        $paymentUrl = $this->generatePaymentLink($order);
        if ($this->whatsAppApi !== null && $paymentUrl !== '') {
          $total = number_format((float) ($order->get('total')->value ?? 0), 2, ',', '.');
          $this->whatsAppApi->sendTextMessage(
            $senderPhone,
            "¡Tu pedido está listo!\n\n" .
            "Total: " . $total . " EUR\n\n" .
            "Paga de forma segura aquí:\n" . $paymentUrl . "\n\n" .
            "El enlace es válido durante 24 horas."
          );
          $result['response_sent'] = TRUE;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp order processing error: @msg', ['@msg' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Detecta intención de compra en un mensaje.
   */
  protected function detectPurchaseIntent(string $message): bool {
    $lower = mb_strtolower($message);
    foreach (self::PURCHASE_KEYWORDS as $keyword) {
      if (str_contains($lower, $keyword)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Busca productos que coincidan con el texto del mensaje.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array de productos encontrados (máximo 5).
   */
  protected function searchProducts(string $query, int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('product_agro');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->condition('tenant_id', $tenantId)
        ->range(0, 5)
        ->execute();

      return $ids !== [] ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable) {
      return [];
    }
  }

  /**
   * Crea un pedido a partir de productos seleccionados.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   El pedido creado o NULL.
   */
  protected function createOrderFromProducts(array $products, string $phone, int $tenantId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('order_agro');
      $total = 0.0;

      foreach ($products as $product) {
        $total += (float) ($product->get('price')->value ?? 0);
      }

      $order = $storage->create([
        'tenant_id' => $tenantId,
        'status' => 'pending_payment',
        'total' => $total,
        'currency' => 'EUR',
        'customer_phone' => $phone,
        'source' => 'whatsapp',
      ]);
      $order->save();

      $this->logger->info('WhatsApp order @id created for @phone (@total EUR).', [
        '@id' => $order->id(),
        '@phone' => $phone,
        '@total' => $total,
      ]);

      return $order;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error creating WhatsApp order: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Crea un pedido a partir de un total calculado.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   El pedido creado o NULL.
   */
  protected function createOrderFromTotal(float $total, string $phone, int $tenantId, string $source = 'whatsapp'): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('order_agro');
      $order = $storage->create([
        'tenant_id' => $tenantId,
        'status' => 'pending_payment',
        'total' => $total,
        'currency' => 'EUR',
        'customer_phone' => $phone,
        'source' => $source,
      ]);
      $order->save();

      $this->logger->info('WhatsApp order @id created from @source for @phone (@total EUR).', [
        '@id' => $order->id(),
        '@source' => $source,
        '@phone' => $phone,
        '@total' => $total,
      ]);

      return $order;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error creating order from total: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Genera un payment link de Stripe para un pedido.
   */
  protected function generatePaymentLink(object $order): string {
    try {
      // Usar Stripe Checkout hosted (no embedded) para links externos.
      if (!\Drupal::hasService('jaraba_foc.stripe_connect')) {
        return '';
      }

      $stripe = \Drupal::service('jaraba_foc.stripe_connect');
      $total = (float) ($order->get('total')->value ?? 0);
      $amountCents = (int) round($total * 100);

      $successUrl = Url::fromRoute('jaraba_agroconecta_core.checkout.success', [], [
        'absolute' => TRUE,
      ])->toString();

      $session = $stripe->stripeRequest('POST', '/checkout/sessions', [
        'mode' => 'payment',
        'line_items' => [
          [
            'price_data' => [
              'currency' => 'eur',
              'product_data' => [
                'name' => 'Pedido WhatsApp #' . $order->id(),
              ],
              'unit_amount' => $amountCents,
            ],
            'quantity' => 1,
          ],
        ],
        'success_url' => $successUrl . '?order_id=' . $order->id(),
        'cancel_url' => $successUrl . '?cancelled=1',
        'metadata' => [
          'order_id' => (string) $order->id(),
          'source' => 'whatsapp',
        ],
      ]);

      return $session['url'] ?? '';
    }
    catch (\Throwable $e) {
      $this->logger->error('Payment link generation error: @msg', ['@msg' => $e->getMessage()]);
      return '';
    }
  }

}
