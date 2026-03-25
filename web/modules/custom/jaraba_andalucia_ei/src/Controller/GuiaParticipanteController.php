<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\jaraba_email\Service\SubscriberService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lead magnet — Guía del Participante Andalucía +ei.
 *
 * Pipeline completo de captura de leads:
 * 1. Validación + rate limiting (Flood API)
 * 2. Envío de email con resumen del programa (MailManager)
 * 3. Persistencia en email_subscriber (SubscriberService)
 * 4. Enrolamiento en secuencia SEQ_AEI_006 (AndaluciaEiEmailSequenceService)
 * 5. Creación de contacto CRM con dedup por email (ContactService)
 * 6. Log estructurado para trazabilidad.
 *
 * Cada paso posterior al envío es non-blocking (try-catch con
 * \Throwable). El email al usuario es la operación crítica; las
 * integraciones CRM/subscriber son best-effort para no bloquear
 * la experiencia del usuario si un subsistema falla.
 *
 * OPTIONAL-CROSSMODULE-001: SubscriberService y ContactService son
 * inyectados como opcionales (@?) porque jaraba_email y jaraba_crm
 * son módulos independientes que podrían no estar activos.
 */
class GuiaParticipanteController extends ControllerBase {

  /**
   * Rate limit: max submissions per IP per hour.
   */
  protected const FLOOD_LIMIT = 5;

  /**
   * Rate limit: time window in seconds (1 hour).
   */
  protected const FLOOD_WINDOW = 3600;

  /**
   * Flood event identifier.
   */
  protected const FLOOD_EVENT = 'jaraba_andalucia_ei.guia_download';

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The flood service.
   */
  protected FloodInterface $flood;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * The subscriber service (optional — jaraba_email module).
   */
  protected ?SubscriberService $subscriberService;

  /**
   * The CRM contact service (optional — jaraba_crm module).
   */
  protected ?ContactService $contactService;

  /**
   * The tenant context service (optional — ecosistema_jaraba_core).
   */
  protected ?TenantContextService $tenantContext;

  /**
   * The date formatter.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->flood = $container->get('flood');
    $instance->logger = $container->get('logger.channel.jaraba_andalucia_ei');
    $instance->dateFormatter = $container->get('date.formatter');

    // OPTIONAL-CROSSMODULE-001: servicios cross-módulo opcionales.
    $instance->subscriberService = $container->has('jaraba_email.subscriber_service')
      ? $container->get('jaraba_email.subscriber_service')
      : NULL;
    $instance->contactService = $container->has('jaraba_crm.contact')
      ? $container->get('jaraba_crm.contact')
      : NULL;
    $instance->tenantContext = $container->has('ecosistema_jaraba_core.tenant_context')
      ? $container->get('ecosistema_jaraba_core.tenant_context')
      : NULL;

    return $instance;
  }

  /**
   * Renders the guide download page.
   *
   * @return array
   *   Render array.
   */
  public function guia(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    // ROUTE-LANGPREFIX-001: Pass API URL via drupalSettings for JS.
    $guiaDownloadUrl = Url::fromRoute('jaraba_andalucia_ei.guia_download_api')->toString();

    return [
      '#theme' => 'andalucia_ei_guia_participante',
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/guia',
        ],
        'drupalSettings' => [
          'jarabaAndaluciaEi' => [
            'guiaDownloadUrl' => $guiaDownloadUrl,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Renders the leads dashboard for Guía del Participante downloads.
   *
   * Shows a unified table of subscribers captured through the guía lead
   * magnet, with CRM contact cross-reference. Accessible only for
   * coordinadores with 'administer andalucia ei' permission.
   *
   * TENANT-001: All queries filtered by tenant_id.
   *
   * @return array
   *   Render array with table of leads.
   */
  public function leadsGuia(): array {
    // TENANT-001: SIEMPRE filtrar por tenant del coordinador autenticado.
    $tenantId = $this->resolveCurrentTenantId();
    $leads = $this->loadGuiaLeads($tenantId);

    // Enriquecer leads con URLs CRM.
    foreach ($leads as &$lead) {
      if ($lead['crm_id']) {
        try {
          $lead['crm_url'] = Url::fromRoute('entity.crm_contact.canonical', ['crm_contact' => $lead['crm_id']])->toString();
        }
        catch (\Throwable) {
          $lead['crm_url'] = '';
        }
      }
    }
    unset($lead);

    // Stats para las tarjetas de resumen.
    $totalLeads = count($leads);
    $thisWeek = 0;
    $thisMonth = 0;
    $now = time();
    foreach ($leads as $lead) {
      $created = $lead['created_raw'] ?? 0;
      if ($created > $now - 604800) {
        $thisWeek++;
      }
      if ($created > $now - 2592000) {
        $thisMonth++;
      }
    }

    $guiaUrl = Url::fromRoute('jaraba_andalucia_ei.guia_participante')->toString();

    return [
      '#theme' => 'andalucia_ei_leads_guia',
      '#leads' => $leads,
      '#stats' => [
        'total' => $totalLeads,
        'this_week' => $thisWeek,
        'this_month' => $thisMonth,
      ],
      '#guia_url' => $guiaUrl,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['email_subscriber_list', 'crm_contact_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Loads guía leads from email_subscriber with CRM cross-reference.
   *
   * TENANT-001: tenantId es obligatorio para queries autenticadas.
   * Solo se permite NULL en el contexto de persistencia pública
   * (handleGuiaDownload), donde resolvePublicTenantId() lo resuelve.
   *
   * @param int|null $tenantId
   *   The tenant (group) ID for TENANT-001 filtering. NULL returns empty.
   *
   * @return array<int, array{name: string, email: string, date: string, source_detail: string, status: string, crm_id: int|null}>
   */
  protected function loadGuiaLeads(?int $tenantId): array {
    // TENANT-001: Sin tenant no cargamos datos — previene fuga cross-tenant.
    if (!$tenantId) {
      $this->logger->warning('loadGuiaLeads() called without tenantId — returning empty.');
      return [];
    }

    $leads = [];

    try {
      $storage = $this->entityTypeManager()->getStorage('email_subscriber');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('source_detail', 'guia_participante_aei')
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC');

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      // Pre-cargar contactos CRM por email para cross-reference.
      $crmMap = $this->buildCrmEmailMap($tenantId);

      foreach ($storage->loadMultiple($ids) as $subscriber) {
        $email = $subscriber->get('email')->value ?? '';
        $firstName = $subscriber->get('first_name')->value ?? '';
        $lastName = $subscriber->get('last_name')->value ?? '';
        $created = $subscriber->get('created')->value;

        $leads[] = [
          'name' => trim($firstName . ' ' . $lastName),
          'email' => $email,
          'date' => $created ? $this->dateFormatter->format((int) $created, 'short') : '—',
          'created_raw' => (int) ($created ?? 0),
          'source_detail' => 'Guía Participante',
          'status' => $subscriber->get('status')->value ?? 'active',
          'crm_id' => $crmMap[strtolower($email)] ?? NULL,
          'crm_url' => '',
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading guia leads: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $leads;
  }

  /**
   * Builds email→crm_contact_id map for cross-reference.
   *
   * TENANT-001: tenantId obligatorio — sin él devuelve mapa vacío.
   *
   * @param int|null $tenantId
   *   The tenant ID. NULL returns empty map.
   *
   * @return array<string, int>
   *   Map of lowercase email → contact ID.
   */
  protected function buildCrmEmailMap(?int $tenantId): array {
    // TENANT-001: Sin tenant no cargamos datos cross-tenant.
    if (!$tenantId) {
      return [];
    }

    $map = [];

    try {
      if (!$this->entityTypeManager()->hasDefinition('crm_contact')) {
        return [];
      }

      $storage = $this->entityTypeManager()->getStorage('crm_contact');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('source', 'website')
        ->condition('tenant_id', $tenantId);

      $ids = $query->execute();
      foreach ($storage->loadMultiple($ids) as $contact) {
        $email = $contact->get('email')->value ?? '';
        if ($email) {
          $map[strtolower($email)] = (int) $contact->id();
        }
      }
    }
    catch (\Throwable) {
      // CRM module unavailable — no cross-reference.
    }

    return $map;
  }

  /**
   * Resolves the current tenant Group ID (for authenticated coordinadores).
   *
   * @return int|null
   *   The group ID, or NULL.
   */
  protected function resolveCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Handles the guia download API request.
   *
   * Full lead capture pipeline:
   * validate → rate-limit → send email → subscriber → sequence → CRM.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function handleGuiaDownload(Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!is_array($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Invalid request.'),
      ], 400);
    }

    $nombre = trim($data['nombre'] ?? '');
    $email = trim($data['email'] ?? '');
    $clientIp = $request->getClientIp() ?? '0.0.0.0';

    // ─── 1. Validación de campos ───────────────────────────────────────
    if (empty($nombre)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('El nombre es obligatorio.'),
      ], 422);
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Introduce un email válido.'),
      ], 422);
    }

    // ─── 2. Rate limiting (Flood API) ──────────────────────────────────
    if (!$this->flood->isAllowed(self::FLOOD_EVENT, self::FLOOD_LIMIT, self::FLOOD_WINDOW, $clientIp)) {
      $this->logger->warning('Guia download rate limit exceeded for IP @ip', [
        '@ip' => $clientIp,
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Has superado el límite de solicitudes. Inténtalo más tarde.'),
      ], 429);
    }

    // ─── 3. Envío de email (operación crítica) ─────────────────────────
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar', [], ['absolute' => TRUE])->toString();

    try {
      $result = $this->mailManager->mail(
        'jaraba_andalucia_ei',
        'guia_download',
        $email,
        'es',
        [
          'nombre' => $nombre,
          'solicitar_url' => $solicitarUrl,
        ],
      );

      if (!$result || empty($result['result'])) {
        $this->logger->error('Guia email send failed for @email', [
          '@email' => $email,
        ]);
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('No hemos podido enviar el correo. Inténtalo de nuevo o escríbenos por WhatsApp.'),
        ], 500);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Guia email exception for @email: @msg', [
        '@email' => $email,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al enviar el correo. Inténtalo de nuevo.'),
      ], 500);
    }

    // Registrar en Flood tras envío exitoso.
    $this->flood->register(self::FLOOD_EVENT, self::FLOOD_WINDOW, $clientIp);

    // ─── 4. Resolver tenant para persistencia (TENANT-001) ─────────────
    $tenantId = $this->resolvePublicTenantId();

    // ─── 5. Persistencia en email_subscriber (non-blocking) ────────────
    $subscriberId = $this->persistSubscriber($email, $nombre, $clientIp, $tenantId);

    // ─── 6. Enrolamiento en secuencia SEQ_AEI_006 (non-blocking) ──────
    if ($subscriberId) {
      $this->enrollInSequence($subscriberId);
    }

    // ─── 7. Creación de contacto CRM (non-blocking, con dedup) ─────────
    $this->persistCrmContact($nombre, $email, $tenantId);

    // ─── 8. Log estructurado ───────────────────────────────────────────
    $this->logger->info('Guia lead captured: @nombre (@email) [subscriber=@sid, tenant=@tid]', [
      '@nombre' => $nombre,
      '@email' => $email,
      '@sid' => $subscriberId ?: 'none',
      '@tid' => $tenantId ?: 'none',
    ]);

    return new JsonResponse([
      'success' => TRUE,
      'message' => $this->t('¡Guía enviada! Revisa tu bandeja de correo en los próximos minutos.'),
    ]);
  }

  /**
   * Persists the lead as an email_subscriber entity.
   *
   * PRESAVE-RESILIENCE-001: Non-blocking — failure here does NOT prevent
   * the user from receiving their email.
   *
   * @param string $email
   *   The email address.
   * @param string $nombre
   *   The subscriber name.
   * @param string $clientIp
   *   The client IP address.
   * @param int|null $tenantId
   *   The tenant (group) ID. TENANT-001 compliance.
   *
   * @return int|null
   *   The subscriber entity ID, or NULL on failure.
   */
  protected function persistSubscriber(string $email, string $nombre, string $clientIp, ?int $tenantId): ?int {
    if (!$this->subscriberService) {
      return NULL;
    }

    try {
      // Separar nombre/apellido si es posible.
      $parts = explode(' ', $nombre, 2);
      $firstName = $parts[0];
      $lastName = $parts[1] ?? '';

      // SubscriberService::subscribe() maneja dedup internamente:
      // si el email ya existe, añade a la lista sin duplicar.
      $subscriber = $this->subscriberService->subscribe(
        $email,
        $this->resolveDefaultListId(),
        [
          'first_name' => $firstName,
          'last_name' => $lastName,
          'source' => 'lead_magnet',
          'source_detail' => 'guia_participante_aei',
          'gdpr_consent' => TRUE,
          'confirmed' => TRUE,
          'ip_address' => $clientIp,
          'tenant_id' => $tenantId,
        ],
      );

      return (int) $subscriber->id();
    }
    catch (\Throwable $e) {
      $this->logger->warning('Subscriber persistence failed for @email: @msg', [
        '@email' => $email,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Enrolls the subscriber in the SEQ_AEI_006 upsell sequence.
   *
   * Uses AndaluciaEiEmailSequenceService which auto-creates the sequence
   * if it doesn't exist yet. The service resolves sequence keys to entity
   * IDs and delegates to SequenceManagerService.
   *
   * @param int $subscriberId
   *   The email_subscriber entity ID.
   */
  protected function enrollInSequence(int $subscriberId): void {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.andalucia_ei_email_sequence')) {
        return;
      }

      /** @var \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiEmailSequenceService $sequenceService */
      $sequenceService = \Drupal::service('ecosistema_jaraba_core.andalucia_ei_email_sequence');
      $enrolled = $sequenceService->enroll($subscriberId, 'SEQ_AEI_006');

      if ($enrolled) {
        $this->logger->info('Subscriber @id enrolled in SEQ_AEI_006', [
          '@id' => $subscriberId,
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Sequence enrollment failed for subscriber @id: @msg', [
        '@id' => $subscriberId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Creates a CRM contact for the lead, with deduplication by email.
   *
   * PRESAVE-RESILIENCE-001: Non-blocking — if jaraba_crm is unavailable
   * or the contact already exists, this is silently skipped.
   *
   * @param string $nombre
   *   The full name.
   * @param string $email
   *   The email address.
   * @param int|null $tenantId
   *   The tenant (group) ID. TENANT-001 compliance.
   */
  protected function persistCrmContact(string $nombre, string $email, ?int $tenantId): void {
    if (!$this->contactService) {
      return;
    }

    try {
      // Dedup: buscar por email antes de crear.
      $existing = $this->contactService->search($email, 1);
      if (!empty($existing)) {
        return;
      }

      $parts = explode(' ', $nombre, 2);

      $this->contactService->create([
        'first_name' => $parts[0],
        'last_name' => $parts[1] ?? '',
        'email' => $email,
        'source' => 'website',
        'notes' => 'Lead magnet: Guía del Participante Andalucía +ei',
        'tenant_id' => $tenantId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('CRM contact creation failed for @email: @msg', [
        '@email' => $email,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Resolves the tenant (group) ID for public anonymous form submissions.
   *
   * TENANT-001: Every entity MUST have a tenant_id. For anonymous public
   * forms we cannot use TenantContextService (returns NULL for anonymous
   * users), so we resolve from module config or fall back to the meta-site
   * default group.
   *
   * Resolution cascade:
   * 1. Module config: jaraba_andalucia_ei.settings → default_tenant_id
   * 2. Email module config: jaraba_email.settings → default_public_tenant_id
   * 3. Hardcoded fallback: group 7 ("Plataforma de Ecosistemas Digitales")
   *
   * @return int|null
   *   The group ID, or NULL if resolution fails entirely.
   */
  protected function resolvePublicTenantId(): ?int {
    try {
      // 1. Config propia del módulo Andalucía +ei.
      $aeiConfig = $this->config('jaraba_andalucia_ei.settings');
      $tenantId = $aeiConfig->get('default_tenant_id');
      if ($tenantId) {
        return (int) $tenantId;
      }

      // 2. Config del módulo email (patrón PublicSubscribeController).
      $emailConfig = $this->config('jaraba_email.settings');
      $tenantId = $emailConfig->get('default_public_tenant_id');
      if ($tenantId) {
        return (int) $tenantId;
      }

      // 3. Fallback: meta-site group.
      $this->logger->warning('resolvePublicTenantId(): using hardcoded fallback group 7. Configure default_tenant_id in module settings.');
      return 7;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Tenant resolution failed, using fallback: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return 7;
    }
  }

  /**
   * Resolves the default email list ID for Andalucía +ei leads.
   *
   * Busca una lista con nombre 'andalucia_ei_leads' o usa la primera
   * lista disponible. Si no existe ninguna, devuelve 1 como fallback.
   *
   * @return int
   *   The email_list entity ID.
   */
  protected function resolveDefaultListId(): int {
    try {
      $listStorage = $this->entityTypeManager()->getStorage('email_list');

      // Buscar lista específica para Andalucía +ei.
      $ids = $listStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('name', 'andalucia_ei_leads')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        return (int) reset($ids);
      }

      // Fallback: primera lista activa.
      $ids = $listStorage->getQuery()
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      return !empty($ids) ? (int) reset($ids) : 1;
    }
    catch (\Throwable $e) {
      return 1;
    }
  }

}
