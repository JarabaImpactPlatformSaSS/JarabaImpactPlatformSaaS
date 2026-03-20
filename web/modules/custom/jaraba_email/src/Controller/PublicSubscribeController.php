<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\jaraba_crm\Service\OpportunityService;
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
        protected ?ContactService $contactService = NULL,
        protected ?OpportunityService $opportunityService = NULL,
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
            // OPTIONAL-CROSSMODULE-001: jaraba_crm may not be installed.
            // @phpstan-ignore ternary.alwaysTrue
            $container->has('jaraba_crm.contact') ? $container->get('jaraba_crm.contact') : NULL,
            // @phpstan-ignore ternary.alwaysTrue
            $container->has('jaraba_crm.opportunity') ? $container->get('jaraba_crm.opportunity') : NULL,
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
        } catch (\Throwable $e) {
            // Si la entidad tenant no existe en el schema, usar el ID tal cual.
            $this->logger->notice('Tenant entity not available, using raw tenant_id @id', ['@id' => $tenantId]);
        }

        // ─── Crear suscriptor ────────────────────────────────────────────
        try {
            // Map 'name' field from JS to 'first_name' (JS sends 'name').
            $firstName = $body['first_name'] ?? $body['name'] ?? NULL;

            $listId = !empty($body['list_id']) ? (int) $body['list_id'] : 1;

            $options = [
                'source' => $source,
                'first_name' => $firstName,
                'last_name' => $body['last_name'] ?? NULL,
                'tenant_id' => $tenantId,
            ];

            $result = $this->subscriberService->subscribe($email, $listId, $options);

            // Registrar evento en flood para rate limiting.
            $this->flood->register('jaraba_email.public_subscribe', self::FLOOD_WINDOW, $clientIp);

            $this->logger->info('Public subscribe success: @email from @source (tenant @tenant)', [
                '@email' => $email,
                '@source' => $source,
                '@tenant' => $tenantId,
            ]);

            // ─── Auto-enroll en secuencia de bienvenida del meta-sitio ──
            if ($source === 'kit_impulso_digital') {
              try {
                if (\Drupal::hasService('ecosistema_jaraba_core.metasite_email_sequence')) {
                  /** @var \Drupal\ecosistema_jaraba_core\Service\MetaSiteEmailSequenceService $metaSiteSequence */
                  $metaSiteSequence = \Drupal::service('ecosistema_jaraba_core.metasite_email_sequence');
                  $metaSiteSequence->enrollInWelcome((int) $result->id());
                }
              }
              catch (\Throwable $e) {
                $this->logger->warning('MetaSite enroll failed for @email: @error', [
                  '@email' => $email,
                  '@error' => $e->getMessage(),
                ]);
              }
            }

            // ─── Auto-create CRM Contact + Opportunity for lead magnets ──
            if (str_starts_with($source, 'lead_magnet_')) {
                $this->createCrmLead($email, $firstName ?? '', $source, $body);
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => ['id' => (int) $result->id()],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Public subscribe error: @error', ['@error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Se produjo un error. Por favor, inténtalo más tarde.')->__toString(),
            ], 500);
        }
    }

    /**
     * Crea un Contact + Opportunity en el CRM para leads de lead magnet.
     *
     * Sigue el mismo patron que VerticalQuizService::createCrmLead().
     * Servicios CRM son opcionales (OPTIONAL-CROSSMODULE-001).
     *
     * @param string $email
     *   Email del lead.
     * @param string $name
     *   Nombre del lead.
     * @param string $source
     *   Source identifier (lead_magnet_{vertical}).
     * @param array<string, mixed> $body
     *   Payload original del request.
     */
    protected function createCrmLead(string $email, string $name, string $source, array $body): void
    {
        if ($this->contactService === NULL || $this->opportunityService === NULL) {
            return;
        }

        try {
            // Extraer vertical del source: lead_magnet_agroconecta → agroconecta.
            $vertical = str_replace('lead_magnet_', '', $source);

            $contact = $this->contactService->create([
                'first_name' => $name !== '' ? $name : 'Visitante',
                'last_name' => '',
                'email' => $email,
                'source' => 'lead_magnet',
                'engagement_score' => 30,
                'notes' => (string) $this->t('Lead magnet: @vertical. Recurso: @url', [
                    '@vertical' => $vertical,
                    '@url' => $body['resource_url'] ?? '',
                ]),
            ]);

            $this->opportunityService->create([
                'title' => (string) $this->t('Lead Magnet — @v', ['@v' => $vertical]),
                'contact_id' => $contact->id(),
                'stage' => 'mql',
                'probability' => 20,
                'bant_need' => 'identified',
            ]);

            $this->logger->info('CRM lead created from lead magnet: @email (@vertical)', [
                '@email' => $email,
                '@vertical' => $vertical,
            ]);
        }
        catch (\Throwable $e) {
            // Non-blocking: CRM failure should not break the subscription.
            $this->logger->warning('CRM lead creation failed for @email: @error', [
                '@email' => $email,
                '@error' => $e->getMessage(),
            ]);
        }
    }

}
