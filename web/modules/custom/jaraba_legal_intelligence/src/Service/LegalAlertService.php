<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_intelligence\Entity\LegalAlert;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de alertas inteligentes del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Servicio central que evalua resoluciones nuevas o con cambio de estado
 * contra las alertas configuradas por los profesionales. Gestiona el ciclo
 * completo: carga de alertas activas, matching de filtros (fuentes, temas,
 * jurisdicciones), determinacion de severidad, despacho de notificaciones
 * por canal (in_app, email, push) y actualizacion de estadisticas de la
 * alerta (trigger_count, last_triggered).
 *
 * LOGICA:
 * Dos puntos de entrada principales:
 * 1. checkNewResolutionImpact(): Invocado desde hook_entity_insert cuando
 *    se indexa una nueva resolucion. Evalua si alguna alerta activa coincide
 *    segun su alert_type y filtros.
 * 2. handleStatusChange(): Invocado desde hook_entity_update cuando cambia
 *    el status_legal de una resolucion (vigente -> anulada/derogada/superada).
 *    Dispara alertas de tipo resolution_annulled o legislation_modified.
 *
 * El matching de filtros usa interseccion: si una alerta tiene filtros
 * definidos, la resolucion debe coincidir con AL MENOS uno de los valores
 * del filtro. Si el filtro esta vacio (JSON []), acepta todas las
 * resoluciones (sin restriccion).
 *
 * RELACIONES:
 * - LegalAlertService -> LegalAlert entity: carga y actualiza alertas.
 * - LegalAlertService -> LegalResolution entity: lee metadatos para matching.
 * - LegalAlertService -> MailManagerInterface: envia emails de alerta.
 * - LegalAlertService -> TenantContextService: verifica plan para limites.
 * - LegalAlertService <- hook_entity_insert(): via checkNewResolutionImpact().
 * - LegalAlertService <- hook_entity_update(): via handleStatusChange().
 * - LegalAlertService <- LegalSearchController::apiAlerts(): CRUD de alertas.
 *
 * SINTAXIS:
 * Servicio registrado como jaraba_legal_intelligence.alerts.
 * Inyecta entity_type.manager, tenant_context, plugin.manager.mail y logger.
 */
class LegalAlertService {

  /**
   * Mapeo de alert_type a tipos de resolucion que los disparan.
   *
   * Cada clave es un alert_type de LegalAlert y el valor es un array con
   * los source_id o resolution_type que pueden disparar ese tipo de alerta.
   * Se usa para pre-filtrar antes de evaluar los filtros JSON.
   *
   * @var array<string, array<string, string[]>>
   */
  private const ALERT_TYPE_TRIGGERS = [
    'resolution_annulled' => [
      'status_change' => ['anulada'],
    ],
    'criteria_change' => [
      'status_change' => ['superada'],
    ],
    'new_relevant_doctrine' => [
      'resolution_type' => ['sentencia', 'sentencia_tjue', 'auto'],
    ],
    'legislation_modified' => [
      'status_change' => ['derogada', 'parcialmente_derogada'],
      'resolution_type' => ['ley', 'real_decreto', 'directiva', 'reglamento'],
    ],
    'procedural_deadline' => [
      'resolution_type' => ['auto', 'providencia'],
    ],
    'tjue_spain_impact' => [
      'source_id' => ['tjue'],
    ],
    'tedh_spain' => [
      'source_id' => ['tedh'],
    ],
    'edpb_guideline' => [
      'source_id' => ['edpb'],
    ],
    'transposition_deadline' => [
      'resolution_type' => ['directiva'],
    ],
    'ag_conclusions' => [
      'resolution_type' => ['opinion_ag'],
    ],
  ];

  /**
   * Mapeo de alert_type a severidad por defecto cuando no se define.
   *
   * @var array<string, string>
   */
  private const SEVERITY_DEFAULTS = [
    'resolution_annulled' => 'critical',
    'criteria_change' => 'high',
    'legislation_modified' => 'high',
    'tedh_spain' => 'high',
    'tjue_spain_impact' => 'high',
    'new_relevant_doctrine' => 'medium',
    'procedural_deadline' => 'medium',
    'edpb_guideline' => 'medium',
    'transposition_deadline' => 'medium',
    'ag_conclusions' => 'low',
  ];

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para verificar plan y limites.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Gestor de plugins de mail de Drupal.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de LegalAlertService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder a legal_alert y legal_resolution.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto de tenant para verificar limites del plan.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de plugins de mail para envio de notificaciones por email.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    TenantContextService $tenantContext,
    MailManagerInterface $mailManager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->tenantContext = $tenantContext;
    $this->mailManager = $mailManager;
    $this->logger = $logger;
  }

  /**
   * Verifica si una nueva resolucion indexada dispara alertas activas.
   *
   * Se invoca desde hook_entity_insert() cada vez que se crea una nueva
   * entidad LegalResolution. Carga todas las alertas activas y evalua
   * si la resolucion coincide con los criterios de cada alerta segun
   * su alert_type y filtros configurados.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entidad LegalResolution recien creada.
   */
  public function checkNewResolutionImpact(EntityInterface $entity): void {
    if (!$entity instanceof LegalResolution) {
      return;
    }

    $activeAlerts = $this->loadActiveAlerts();
    if (empty($activeAlerts)) {
      return;
    }

    $sourceId = $entity->get('source_id')->value ?? '';
    $resolutionType = $entity->get('resolution_type')->value ?? '';
    $jurisdiction = $entity->get('jurisdiction')->value ?? '';
    $topics = $entity->getTopics();

    foreach ($activeAlerts as $alert) {
      $alertType = $alert->get('alert_type')->value ?? '';

      // Pre-filtrar por alert_type: verificar si la resolucion puede
      // disparar este tipo de alerta por source_id o resolution_type.
      if (!$this->matchesAlertType($alertType, $sourceId, $resolutionType, NULL)) {
        continue;
      }

      // Evaluar filtros JSON de la alerta.
      if (!$this->matchesFilters($alert, $sourceId, $topics, $jurisdiction)) {
        continue;
      }

      // La resolucion coincide: disparar la alerta.
      $this->triggerAlert($alert, $entity, $alertType);
    }
  }

  /**
   * Maneja cambio de estado legal en una resolucion existente.
   *
   * Se invoca desde hook_entity_update() cuando el campo status_legal
   * cambia de valor (ej: vigente -> anulada, vigente -> derogada).
   * Dispara alertas de tipo resolution_annulled, criteria_change o
   * legislation_modified segun el nuevo estado.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entidad LegalResolution con el estado actualizado.
   * @param string $originalStatus
   *   Estado legal anterior (vigente, derogada, etc.).
   * @param string $newStatus
   *   Nuevo estado legal.
   */
  public function handleStatusChange(EntityInterface $entity, string $originalStatus, string $newStatus): void {
    if (!$entity instanceof LegalResolution) {
      return;
    }

    $activeAlerts = $this->loadActiveAlerts();
    if (empty($activeAlerts)) {
      return;
    }

    $sourceId = $entity->get('source_id')->value ?? '';
    $resolutionType = $entity->get('resolution_type')->value ?? '';
    $jurisdiction = $entity->get('jurisdiction')->value ?? '';
    $topics = $entity->getTopics();

    foreach ($activeAlerts as $alert) {
      $alertType = $alert->get('alert_type')->value ?? '';

      // Pre-filtrar: verificar si el cambio de estado dispara este alert_type.
      if (!$this->matchesAlertType($alertType, $sourceId, $resolutionType, $newStatus)) {
        continue;
      }

      // Evaluar filtros JSON.
      if (!$this->matchesFilters($alert, $sourceId, $topics, $jurisdiction)) {
        continue;
      }

      $this->triggerAlert($alert, $entity, $alertType);
    }
  }

  /**
   * Crea una nueva alerta para el usuario actual.
   *
   * Verifica el limite de alertas del plan SaaS antes de crear.
   * Devuelve la entidad creada o NULL si se excede el limite.
   *
   * @param array $data
   *   Datos de la alerta:
   *   - label: string — Nombre descriptivo.
   *   - alert_type: string — Tipo de alerta.
   *   - severity: string — Severidad (critical/high/medium/low).
   *   - filter_sources: array — Filtro de fuentes.
   *   - filter_topics: array — Filtro de temas.
   *   - filter_jurisdictions: array — Filtro de jurisdicciones.
   *   - channels: array — Canales de notificacion.
   * @param int $providerId
   *   ID del usuario propietario de la alerta.
   *
   * @return array
   *   ['success' => bool, 'alert' => LegalAlert|null, 'error' => string|null].
   */
  public function createAlert(array $data, int $providerId): array {
    // Verificar limite de alertas del plan.
    $limitCheck = $this->checkAlertLimit($providerId);
    if (!$limitCheck['allowed']) {
      return [
        'success' => FALSE,
        'alert' => NULL,
        'error' => $limitCheck['message'],
      ];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      $entity = $storage->create([
        'label' => $data['label'] ?? '',
        'provider_id' => $providerId,
        'alert_type' => $data['alert_type'] ?? 'new_relevant_doctrine',
        'severity' => $data['severity'] ?? self::SEVERITY_DEFAULTS[$data['alert_type'] ?? ''] ?? 'medium',
        'filter_sources' => !empty($data['filter_sources']) ? json_encode($data['filter_sources']) : NULL,
        'filter_topics' => !empty($data['filter_topics']) ? json_encode($data['filter_topics']) : NULL,
        'filter_jurisdictions' => !empty($data['filter_jurisdictions']) ? json_encode($data['filter_jurisdictions']) : NULL,
        'channels' => !empty($data['channels']) ? json_encode($data['channels']) : '["in_app"]',
        'is_active' => TRUE,
      ]);
      $entity->save();

      $this->logger->info('LegalAlertService: Alerta @id creada por usuario @uid (tipo: @type).', [
        '@id' => $entity->id(),
        '@uid' => $providerId,
        '@type' => $data['alert_type'] ?? 'unknown',
      ]);

      return [
        'success' => TRUE,
        'alert' => $entity,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error creando alerta: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'alert' => NULL,
        'error' => 'Error creating alert.',
      ];
    }
  }

  /**
   * Lista las alertas del usuario indicado.
   *
   * @param int $providerId
   *   ID del usuario propietario.
   *
   * @return \Drupal\jaraba_legal_intelligence\Entity\LegalAlert[]
   *   Array de entidades LegalAlert del usuario.
   */
  public function listAlerts(int $providerId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return $storage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error listando alertas de usuario @uid: @msg', [
        '@uid' => $providerId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Alterna el estado activo/inactivo de una alerta.
   *
   * @param int $alertId
   *   ID de la alerta.
   * @param bool $isActive
   *   Nuevo estado activo.
   * @param int $providerId
   *   ID del usuario propietario (para verificacion de propiedad).
   *
   * @return bool
   *   TRUE si la operacion fue exitosa.
   */
  public function toggleAlert(int $alertId, bool $isActive, int $providerId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalAlert|null $alert */
      $alert = $storage->load($alertId);

      if (!$alert || (int) $alert->get('provider_id')->target_id !== $providerId) {
        return FALSE;
      }

      $alert->set('is_active', $isActive);
      $alert->save();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error toggling alerta @id: @msg', [
        '@id' => $alertId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Elimina una alerta del usuario.
   *
   * @param int $alertId
   *   ID de la alerta.
   * @param int $providerId
   *   ID del usuario propietario (para verificacion de propiedad).
   *
   * @return bool
   *   TRUE si la operacion fue exitosa.
   */
  public function deleteAlert(int $alertId, int $providerId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalAlert|null $alert */
      $alert = $storage->load($alertId);

      if (!$alert || (int) $alert->get('provider_id')->target_id !== $providerId) {
        return FALSE;
      }

      $alert->delete();

      $this->logger->info('LegalAlertService: Alerta @id eliminada por usuario @uid.', [
        '@id' => $alertId,
        '@uid' => $providerId,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error eliminando alerta @id: @msg', [
        '@id' => $alertId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  // =========================================================================
  // METODOS PRIVADOS: Matching de alertas.
  // =========================================================================

  /**
   * Verifica si un alert_type coincide con la resolucion por source/type/status.
   *
   * Usa la tabla ALERT_TYPE_TRIGGERS para determinar si la resolucion
   * cumple los requisitos basicos del tipo de alerta. Es un pre-filtro
   * rapido antes de evaluar los filtros JSON mas costosos.
   *
   * @param string $alertType
   *   Tipo de alerta (resolution_annulled, criteria_change, etc.).
   * @param string $sourceId
   *   source_id de la resolucion.
   * @param string $resolutionType
   *   resolution_type de la resolucion.
   * @param string|null $newStatus
   *   Nuevo status_legal si es un cambio de estado, NULL para inserciones.
   *
   * @return bool
   *   TRUE si la resolucion puede disparar este tipo de alerta.
   */
  private function matchesAlertType(string $alertType, string $sourceId, string $resolutionType, ?string $newStatus): bool {
    $triggers = self::ALERT_TYPE_TRIGGERS[$alertType] ?? [];

    if (empty($triggers)) {
      return FALSE;
    }

    // Verificar por cambio de estado.
    if (isset($triggers['status_change'])) {
      if ($newStatus !== NULL && in_array($newStatus, $triggers['status_change'], TRUE)) {
        return TRUE;
      }
      // Si este alert_type SOLO se dispara por status_change y no hay cambio, no coincide.
      if (!isset($triggers['source_id']) && !isset($triggers['resolution_type'])) {
        return FALSE;
      }
    }

    // Verificar por source_id.
    if (isset($triggers['source_id']) && in_array($sourceId, $triggers['source_id'], TRUE)) {
      return TRUE;
    }

    // Verificar por resolution_type.
    if (isset($triggers['resolution_type']) && in_array($resolutionType, $triggers['resolution_type'], TRUE)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Evalua los filtros JSON de una alerta contra una resolucion.
   *
   * Semantica de filtros:
   * - Array vacio o NULL: acepta todo (sin restriccion).
   * - Array con valores: la resolucion debe coincidir con AL MENOS uno.
   *
   * Los filtros son conjuntivos (AND): si la alerta tiene filtros de
   * fuentes Y temas, la resolucion debe cumplir ambos.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalAlert $alert
   *   Entidad de alerta con los filtros a evaluar.
   * @param string $sourceId
   *   source_id de la resolucion.
   * @param array $topics
   *   Topics clasificados de la resolucion (array de strings).
   * @param string $jurisdiction
   *   Jurisdiccion de la resolucion.
   *
   * @return bool
   *   TRUE si la resolucion pasa todos los filtros de la alerta.
   */
  private function matchesFilters(LegalAlert $alert, string $sourceId, array $topics, string $jurisdiction): bool {
    // Filtro de fuentes.
    $filterSources = $alert->getFilterSources();
    if (!empty($filterSources) && !in_array($sourceId, $filterSources, TRUE)) {
      return FALSE;
    }

    // Filtro de temas: interseccion con los topics de la resolucion.
    $filterTopics = $alert->getFilterTopics();
    if (!empty($filterTopics) && !empty($topics)) {
      $intersection = array_intersect(
        array_map('mb_strtolower', $filterTopics),
        array_map('mb_strtolower', $topics)
      );
      if (empty($intersection)) {
        return FALSE;
      }
    }
    elseif (!empty($filterTopics) && empty($topics)) {
      // La alerta filtra por temas, pero la resolucion no tiene topics.
      return FALSE;
    }

    // Filtro de jurisdicciones.
    $filterJurisdictions = $alert->getFilterJurisdictions();
    if (!empty($filterJurisdictions) && !in_array($jurisdiction, $filterJurisdictions, TRUE)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Carga todas las alertas activas de todos los usuarios.
   *
   * @return \Drupal\jaraba_legal_intelligence\Entity\LegalAlert[]
   *   Array de entidades LegalAlert con is_active = TRUE.
   */
  private function loadActiveAlerts(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return $storage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error cargando alertas activas: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  // =========================================================================
  // METODOS PRIVADOS: Disparo de alertas y notificaciones.
  // =========================================================================

  /**
   * Dispara una alerta: actualiza estadisticas y envia notificaciones.
   *
   * Incrementa trigger_count, actualiza last_triggered, y despacha
   * notificaciones por los canales configurados en la alerta.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalAlert $alert
   *   Entidad de alerta que se ha disparado.
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $resolution
   *   Resolucion que ha disparado la alerta.
   * @param string $alertType
   *   Tipo de alerta disparada (para logging).
   */
  private function triggerAlert(LegalAlert $alert, LegalResolution $resolution, string $alertType): void {
    // Actualizar estadisticas de la alerta.
    $triggerCount = (int) ($alert->get('trigger_count')->value ?? 0);
    $alert->set('trigger_count', $triggerCount + 1);
    $alert->set('last_triggered', time());

    try {
      $alert->save();
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error actualizando stats de alerta @id: @msg', [
        '@id' => $alert->id(),
        '@msg' => $e->getMessage(),
      ]);
    }

    // Despachar notificaciones por canal.
    $channels = $alert->getChannels();
    $providerId = (int) $alert->get('provider_id')->target_id;

    foreach ($channels as $channel) {
      match ($channel) {
        'email' => $this->sendEmailNotification($alert, $resolution, $providerId),
        'in_app' => $this->createInAppNotification($alert, $resolution, $providerId),
        'push' => $this->sendPushNotification($alert, $resolution, $providerId),
        default => NULL,
      };
    }

    $this->logger->info('LegalAlertService: Alerta @id (@type) disparada por resolucion @ref. Canales: @channels.', [
      '@id' => $alert->id(),
      '@type' => $alertType,
      '@ref' => $resolution->get('external_ref')->value ?? 'unknown',
      '@channels' => implode(', ', $channels),
    ]);
  }

  /**
   * Envia notificacion por email al profesional.
   *
   * Usa hook_mail con la clave 'legal_alert' para construir el mensaje.
   * El subject incluye la severidad y el tipo de alerta.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalAlert $alert
   *   Alerta disparada.
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $resolution
   *   Resolucion que disparo la alerta.
   * @param int $providerId
   *   ID del usuario destinatario.
   */
  private function sendEmailNotification(LegalAlert $alert, LegalResolution $resolution, int $providerId): void {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $userStorage->load($providerId);

      if (!$user || !$user->getEmail()) {
        return;
      }

      $severity = $alert->get('severity')->value ?? 'medium';
      $alertLabel = $alert->get('label')->value ?? 'Legal Alert';
      $refId = $resolution->get('external_ref')->value ?? '';
      $title = $resolution->get('title')->value ?? '';

      $severityLabels = [
        'critical' => 'CRITICA',
        'high' => 'ALTA',
        'medium' => 'MEDIA',
        'low' => 'BAJA',
      ];

      $subject = sprintf(
        '[%s] %s — %s',
        $severityLabels[$severity] ?? 'INFO',
        $alertLabel,
        $refId
      );

      $body = sprintf(
        "Alerta: %s\nSeveridad: %s\n\nResolucion: %s\nReferencia: %s\nOrgano: %s\nFecha: %s\n\n%s\n\nGestiona tus alertas en el Legal Intelligence Hub.",
        $alertLabel,
        $severityLabels[$severity] ?? $severity,
        $title,
        $refId,
        $resolution->get('issuing_body')->value ?? '',
        $resolution->get('date_issued')->value ?? '',
        $resolution->get('abstract_ai')->value ?? ''
      );

      $this->mailManager->mail(
        'jaraba_legal_intelligence',
        'legal_alert',
        $user->getEmail(),
        $user->getPreferredLangcode(),
        [
          'subject' => $subject,
          'body' => $body,
        ]
      );
    }
    catch (\Exception $e) {
      $this->logger->error('LegalAlertService: Error enviando email de alerta @id: @msg', [
        '@id' => $alert->id(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Crea una notificacion in-app para el profesional.
   *
   * Almacena la notificacion en la tabla de mensajes del ecosistema
   * para que aparezca en la bandeja del profesional.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalAlert $alert
   *   Alerta disparada.
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $resolution
   *   Resolucion que disparo la alerta.
   * @param int $providerId
   *   ID del usuario destinatario.
   */
  private function createInAppNotification(LegalAlert $alert, LegalResolution $resolution, int $providerId): void {
    // La notificacion in-app se implementara con el sistema de mensajes
    // del ecosistema (FASE 6 - Integration ServiciosConecta).
    // Por ahora, solo se registra en el log.
    $this->logger->debug('LegalAlertService: Notificacion in-app para usuario @uid (alerta @id).', [
      '@uid' => $providerId,
      '@id' => $alert->id(),
    ]);
  }

  /**
   * Envia notificacion push al profesional.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalAlert $alert
   *   Alerta disparada.
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $resolution
   *   Resolucion que disparo la alerta.
   * @param int $providerId
   *   ID del usuario destinatario.
   */
  private function sendPushNotification(LegalAlert $alert, LegalResolution $resolution, int $providerId): void {
    // Push notifications se integraran con el servicio de push del ecosistema
    // (FASE 6 - Integration ServiciosConecta).
    $this->logger->debug('LegalAlertService: Push notification para usuario @uid (alerta @id).', [
      '@uid' => $providerId,
      '@id' => $alert->id(),
    ]);
  }

  // =========================================================================
  // METODOS PRIVADOS: Limites por plan.
  // =========================================================================

  /**
   * Verifica si el usuario puede crear mas alertas segun su plan.
   *
   * Consulta el numero actual de alertas del usuario y lo compara
   * con el limite max_alerts del plan SaaS del tenant.
   * 0 = ilimitado (Enterprise).
   *
   * @param int $providerId
   *   ID del usuario.
   *
   * @return array
   *   ['allowed' => bool, 'message' => string|null].
   */
  private function checkAlertLimit(int $providerId): array {
    // Contar alertas actuales del usuario.
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Si no se puede contar, permitir (fail-open).
      return ['allowed' => TRUE, 'message' => NULL];
    }

    // Obtener limite del plan.
    $maxAlerts = $this->getMaxAlertsForCurrentPlan();

    // 0 = ilimitado.
    if ($maxAlerts === 0) {
      return ['allowed' => TRUE, 'message' => NULL];
    }

    if ($count >= $maxAlerts) {
      return [
        'allowed' => FALSE,
        'message' => sprintf(
          'You have reached the maximum number of alerts for your plan (%d). Upgrade your plan for more alerts.',
          $maxAlerts
        ),
      ];
    }

    return ['allowed' => TRUE, 'message' => NULL];
  }

  /**
   * Obtiene el limite de alertas del plan actual del tenant.
   *
   * @return int
   *   Numero maximo de alertas permitidas. 0 = ilimitado.
   */
  private function getMaxAlertsForCurrentPlan(): int {
    $tenant = $this->tenantContext->getCurrentTenant();

    $planId = 'starter';
    if ($tenant && method_exists($tenant, 'getSubscriptionPlan') && $tenant->getSubscriptionPlan()) {
      $planId = $tenant->getSubscriptionPlan()->id() ?? 'starter';
    }

    $configPlan = match ($planId) {
      'starter' => 'starter',
      'profesional', 'pro' => 'pro',
      'business', 'enterprise' => 'enterprise',
      default => 'starter',
    };

    // Leer limites desde config.
    $config = \Drupal::config('jaraba_legal_intelligence.settings');
    $limits = $config->get('limits');
    $planLimits = $limits[$configPlan] ?? $limits['starter'] ?? [];

    return (int) ($planLimits['max_alerts'] ?? 3);
  }

}
