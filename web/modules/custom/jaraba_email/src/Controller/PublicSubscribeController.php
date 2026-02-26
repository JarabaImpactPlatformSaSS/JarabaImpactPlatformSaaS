<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_email\Service\SubscriberService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Public subscribe endpoint for lead magnets and meta-site forms.
 *
 * PROPÓSITO:
 * A diferencia de EmailApiController (que requiere permisos de admin),
 * este controlador expone un endpoint público para la captura de leads
 * desde formularios de meta-sitio como el Kit de Impulso Digital.
 *
 * SEGURIDAD:
 * - Rate limiting: máximo 5 suscripciones por IP por hora (Flood API).
 * - Validación de email.
 * - CSRF token obligatorio.
 * - Tenant ID resuelto desde el cuerpo de la petición (validado contra
 *   Tenant entities existentes) o desde un default configurable.
 *
 * ARQUITECTURA:
 * Este controlador NO duplica lógica: delega toda la operación al
 * SubscriberService existente, que maneja creación, deduplicación y
 * asignación a listas.
 *
 * @see \Drupal\jaraba_email\Service\SubscriberService
 * @see \Drupal\jaraba_email\Controller\EmailApiController::createSubscriber
 */
class PublicSubscribeController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Límite de suscripciones por IP por ventana de tiempo.
     */
    protected const FLOOD_LIMIT = 5;

    /**
     * Ventana de tiempo para el flood control (3600s = 1 hora).
     */
    protected const FLOOD_WINDOW = 3600;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_email\Service\SubscriberService $subscriberService
     *   Servicio de gestión de suscriptores.
     * @param \Drupal\Core\Flood\FloodInterface $flood
     *   Servicio de control de inundación (rate limiting).
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   Canal de log.
     */
    public function __construct(
        protected SubscriberService $subscriberService,
        protected FloodInterface $flood,
        protected LoggerChannelInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_email.subscriber_service'),
            $container->get('flood'),
            $container->get('logger.channel.jaraba_email'),
        );
    }

    /**
     * POST /api/v1/public/subscribe — Suscripción pública con rate limiting.
     *
     * Payload JSON esperado:
     * {
     *   "email": "usuario@ejemplo.com",    // Obligatorio.
     *   "source": "kit_impulso_digital",    // Obligatorio. Identifica el lead magnet.
     *   "tags": ["entrepreneur"],           // Opcional. Tags de segmentación (avatar_type).
     *   "first_name": "María",             // Opcional.
     *   "tenant_id": 5                     // Opcional. Default: config 'jaraba_email.settings' → default_tenant_id.
     * }
     *
     * Respuestas:
     * - 200: {"success": true, "data": {...}}
     * - 400: {"success": false, "error": "..."}
     * - 429: {"success": false, "error": "Demasiados intentos..."}
     * - 500: {"success": false, "error": "Error interno..."}
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp() ?? 'unknown';

        // ─── Rate Limiting ───────────────────────────────────────────────
        if (!$this->flood->isAllowed('jaraba_email.public_subscribe', self::FLOOD_LIMIT, self::FLOOD_WINDOW, $clientIp)) {
            $this->logger->warning('Rate limit exceeded for public subscribe from IP @ip', ['@ip' => $clientIp]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Demasiados intentos. Por favor, inténtalo de nuevo más tarde.')->__toString(),
            ], 429);
        }

        // ─── Parse Body ──────────────────────────────────────────────────
        $body = json_decode($request->getContent(), TRUE) ?? [];
        $email = trim((string) ($body['email'] ?? ''));
        $source = trim((string) ($body['source'] ?? ''));

        // ─── Validar campos obligatorios ─────────────────────────────────
        if (empty($email)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('El campo email es obligatorio.')->__toString(),
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('El email proporcionado no es válido.')->__toString(),
            ], 400);
        }

        if (empty($source)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('El campo source es obligatorio.')->__toString(),
            ], 400);
        }

        // ─── Resolver Tenant ID ──────────────────────────────────────────
        $tenantId = isset($body['tenant_id']) ? (int) $body['tenant_id'] : NULL;
        if (!$tenantId) {
            // Intentar obtener un default desde la configuración del módulo.
            $config = $this->config('jaraba_email.settings');
            $tenantId = $config->get('default_public_tenant_id') ?: 1;
        }

        // Validar que el tenant existe.
        try {
            $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
            $tenant = $tenantStorage->load($tenantId);
            if (!$tenant) {
                $this->logger->warning('Public subscribe: tenant @id not found', ['@id' => $tenantId]);
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('Configuración de suscripción no válida.')->__toString(),
                ], 400);
            }
        } catch (\Exception $e) {
            // Si la entidad tenant no existe en el schema, usar el ID tal cual.
            $this->logger->notice('Tenant entity not available, using raw tenant_id @id', ['@id' => $tenantId]);
        }

        // ─── Crear suscriptor ────────────────────────────────────────────
        try {
            $options = [
                'source' => $source,
                'first_name' => $body['first_name'] ?? NULL,
                'last_name' => $body['last_name'] ?? NULL,
            ];

            // Pasar list_id si se proporciona.
            if (!empty($body['list_id'])) {
                $options['list_id'] = $body['list_id'];
            }

            $result = $this->subscriberService->subscribe($email, $tenantId, $options);

            // Registrar evento en flood para rate limiting.
            $this->flood->register('jaraba_email.public_subscribe', self::FLOOD_WINDOW, $clientIp);

            $this->logger->info('Public subscribe success: @email from @source (tenant @tenant)', [
                '@email' => $email,
                '@source' => $source,
                '@tenant' => $tenantId,
            ]);

            // ─── Auto-enroll en secuencia de bienvenida del meta-sitio ──
            // Sprint 5: si source es kit_impulso_digital, inscribir en
            // SEQ_META_001 (Bienvenida + Kit Impulso Digital) automáticamente.
            if ($source === 'kit_impulso_digital' && !empty($result['id'])) {
              try {
                if (\Drupal::hasService('ecosistema_jaraba_core.metasite_email_sequence')) {
                  /** @var \Drupal\ecosistema_jaraba_core\Service\MetaSiteEmailSequenceService $metaSiteSequence */
                  $metaSiteSequence = \Drupal::service('ecosistema_jaraba_core.metasite_email_sequence');
                  $metaSiteSequence->enrollInWelcome((int) $result['id']);
                }
              }
              catch (\Exception $e) {
                // No debe romper la suscripción principal.
                $this->logger->warning('MetaSite enroll failed for @email: @error', [
                  '@email' => $email,
                  '@error' => $e->getMessage(),
                ]);
              }
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Public subscribe error: @error', ['@error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se produjo un error. Por favor, inténtalo más tarde.')->__toString(),
            ], 500);
        }
    }

}
