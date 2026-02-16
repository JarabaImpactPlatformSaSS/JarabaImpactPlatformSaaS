<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion y envio de digest semanal del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Servicio que genera resumenes semanales personalizados de resoluciones
 * juridicas indexadas en los ultimos 7 dias que coinciden con las areas
 * de practica de cada profesional. El digest incluye un maximo de 10
 * resoluciones ordenadas por relevancia (importance_level y fecha).
 *
 * LOGICA:
 * El metodo sendDigests() se invoca desde hook_cron() los lunes 07:00 UTC.
 * Carga todos los usuarios con alertas activas (que han expresado interes
 * en seguimiento juridico), genera un digest personalizado para cada uno
 * usando generateWeeklyDigest(), renderiza el email con el template
 * legal-digest-email.html.twig y lo envia via MailManager.
 *
 * La personalizacion se basa en los filtros de las alertas del usuario:
 * se agregan los filter_sources, filter_topics y filter_jurisdictions
 * de todas sus alertas activas para construir un perfil de intereses.
 * Si el usuario no tiene filtros especificos, recibe las resoluciones
 * mas relevantes de la semana (importance_level = 1 o 2).
 *
 * RELACIONES:
 * - LegalDigestService -> EntityTypeManagerInterface: carga usuarios,
 *   alertas y resoluciones para construir el digest.
 * - LegalDigestService -> LegalSearchService: (reservado para busqueda
 *   semantica de resoluciones relevantes en futuras iteraciones).
 * - LegalDigestService -> MailManagerInterface: envia emails de digest.
 * - LegalDigestService -> TenantContextService: contexto de tenant.
 * - LegalDigestService -> ConfigFactoryInterface: lee configuracion.
 * - LegalDigestService <- hook_cron(): invocado via sendDigests().
 * - LegalDigestService <- LegalSearchController::apiDigestPreview():
 *   preview del digest via API REST.
 *
 * SINTAXIS:
 * Servicio registrado como jaraba_legal_intelligence.digest.
 * Inyecta entity_type.manager, search, plugin.manager.mail,
 * tenant_context, config.factory y logger.
 */
class LegalDigestService {

  /**
   * Maximo de resoluciones por digest.
   */
  private const MAX_RESOLUTIONS_PER_DIGEST = 10;

  /**
   * Periodo del digest en dias (7 = semanal).
   */
  private const DIGEST_PERIOD_DAYS = 7;

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de busqueda semantica.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalSearchService
   */
  protected LegalSearchService $searchService;

  /**
   * Gestor de plugins de mail de Drupal.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Factoria de configuracion de Drupal.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de LegalDigestService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder a alertas, resoluciones y usuarios.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalSearchService $searchService
   *   Servicio de busqueda semantica (reservado para futuras iteraciones).
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de plugins de mail para envio de emails de digest.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto de tenant.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer parametros del modulo.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LegalSearchService $searchService,
    MailManagerInterface $mailManager,
    TenantContextService $tenantContext,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->searchService = $searchService;
    $this->mailManager = $mailManager;
    $this->tenantContext = $tenantContext;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Envia digest semanal a todos los usuarios con alertas activas.
   *
   * Punto de entrada principal, invocado desde hook_cron() los lunes
   * 07:00 UTC. Identifica todos los usuarios con al menos una alerta
   * activa, genera un digest personalizado y lo envia por email.
   */
  public function sendDigests(): void {
    $providerIds = $this->getDigestRecipients();

    if (empty($providerIds)) {
      $this->logger->info('LegalDigestService: No hay destinatarios para el digest semanal.');
      return;
    }

    $periodEnd = date('Y-m-d');
    $periodStart = date('Y-m-d', strtotime('-' . self::DIGEST_PERIOD_DAYS . ' days'));
    $sentCount = 0;

    foreach ($providerIds as $providerId) {
      $digest = $this->generateWeeklyDigest($providerId, $periodStart, $periodEnd);

      if (empty($digest['resolutions'])) {
        continue;
      }

      $sent = $this->sendDigestEmail($providerId, $digest, $periodStart, $periodEnd);
      if ($sent) {
        $sentCount++;
      }
    }

    $this->logger->info('LegalDigestService: Digest semanal enviado a @count profesionales.', [
      '@count' => $sentCount,
    ]);
  }

  /**
   * Genera el digest semanal personalizado para un profesional.
   *
   * Construye un perfil de intereses a partir de las alertas activas del
   * usuario (fuentes, temas, jurisdicciones). Consulta las resoluciones
   * indexadas en el periodo que coincidan con ese perfil. Ordena por
   * importancia y fecha, limitando a MAX_RESOLUTIONS_PER_DIGEST.
   *
   * @param int $providerId
   *   ID del usuario profesional.
   * @param string|null $periodStart
   *   Fecha inicio del periodo (Y-m-d). NULL = hace 7 dias.
   * @param string|null $periodEnd
   *   Fecha fin del periodo (Y-m-d). NULL = hoy.
   *
   * @return array
   *   Array con claves:
   *   - resolutions: array de arrays con datos de resoluciones.
   *   - provider_name: string — Nombre del profesional.
   *   - period_start: string — Fecha inicio.
   *   - period_end: string — Fecha fin.
   */
  public function generateWeeklyDigest(int $providerId, ?string $periodStart = NULL, ?string $periodEnd = NULL): array {
    $periodEnd = $periodEnd ?? date('Y-m-d');
    $periodStart = $periodStart ?? date('Y-m-d', strtotime('-' . self::DIGEST_PERIOD_DAYS . ' days'));

    // Obtener nombre del usuario.
    $providerName = $this->getProviderName($providerId);

    // Construir perfil de intereses a partir de las alertas activas.
    $profile = $this->buildInterestProfile($providerId);

    // Consultar resoluciones del periodo que coincidan con el perfil.
    $resolutions = $this->queryRelevantResolutions($profile, $periodStart, $periodEnd);

    return [
      'resolutions' => $resolutions,
      'provider_name' => $providerName,
      'period_start' => $periodStart,
      'period_end' => $periodEnd,
    ];
  }

  // =========================================================================
  // METODOS PRIVADOS: Destinatarios y perfil de intereses.
  // =========================================================================

  /**
   * Obtiene los IDs de usuarios que deben recibir el digest.
   *
   * Identifica usuarios con al menos una alerta activa, indicando que
   * tienen interes en seguimiento juridico. Agrupa por provider_id
   * unico para evitar enviar multiples digest al mismo usuario.
   *
   * @return int[]
   *   Array de user IDs unicos.
   */
  private function getDigestRecipients(): array {
    try {
      $alertStorage = $this->entityTypeManager->getStorage('legal_alert');
      $ids = $alertStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $alerts = $alertStorage->loadMultiple($ids);
      $providerIds = [];

      foreach ($alerts as $alert) {
        $pid = (int) $alert->get('provider_id')->target_id;
        if ($pid > 0) {
          $providerIds[$pid] = $pid;
        }
      }

      return array_values($providerIds);
    }
    catch (\Exception $e) {
      $this->logger->error('LegalDigestService: Error obteniendo destinatarios: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye perfil de intereses a partir de las alertas del usuario.
   *
   * Agrega los filtros de todas las alertas activas del usuario para
   * crear un perfil unificado de fuentes, temas y jurisdicciones de
   * interes. Si no hay filtros especificos, el perfil queda vacio
   * indicando interes en todas las fuentes.
   *
   * @param int $providerId
   *   ID del usuario.
   *
   * @return array
   *   Array con claves:
   *   - sources: string[] — Fuentes de interes.
   *   - topics: string[] — Temas de interes.
   *   - jurisdictions: string[] — Jurisdicciones de interes.
   */
  private function buildInterestProfile(int $providerId): array {
    $profile = [
      'sources' => [],
      'topics' => [],
      'jurisdictions' => [],
    ];

    try {
      $alertStorage = $this->entityTypeManager->getStorage('legal_alert');
      $ids = $alertStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->condition('is_active', TRUE)
        ->execute();

      if (empty($ids)) {
        return $profile;
      }

      $alerts = $alertStorage->loadMultiple($ids);

      foreach ($alerts as $alert) {
        $sources = $alert->getFilterSources();
        $topics = $alert->getFilterTopics();
        $jurisdictions = $alert->getFilterJurisdictions();

        $profile['sources'] = array_unique(array_merge($profile['sources'], $sources));
        $profile['topics'] = array_unique(array_merge($profile['topics'], $topics));
        $profile['jurisdictions'] = array_unique(array_merge($profile['jurisdictions'], $jurisdictions));
      }
    }
    catch (\Exception $e) {
      $this->logger->error('LegalDigestService: Error construyendo perfil de intereses para @uid: @msg', [
        '@uid' => $providerId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $profile;
  }

  /**
   * Consulta resoluciones relevantes para el digest segun perfil e intervalo.
   *
   * Filtra por rango de fechas y, si el perfil tiene fuentes o
   * jurisdicciones especificas, aplica esos filtros. Si el perfil esta
   * vacio, devuelve las resoluciones mas relevantes (importance_level 1-2).
   * Ordena por importancia (ASC, 1=mas importante) y fecha (DESC).
   *
   * @param array $profile
   *   Perfil de intereses del usuario.
   * @param string $periodStart
   *   Fecha inicio del periodo (Y-m-d).
   * @param string $periodEnd
   *   Fecha fin del periodo (Y-m-d).
   *
   * @return array
   *   Array de arrays con datos de cada resolucion (max 10).
   */
  private function queryRelevantResolutions(array $profile, string $periodStart, string $periodEnd): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('date_issued', $periodStart, '>=')
        ->condition('date_issued', $periodEnd, '<=')
        ->sort('importance_level', 'ASC')
        ->sort('date_issued', 'DESC')
        ->range(0, self::MAX_RESOLUTIONS_PER_DIGEST * 3);

      // Filtrar por fuentes del perfil.
      if (!empty($profile['sources'])) {
        $query->condition('source_id', $profile['sources'], 'IN');
      }

      // Filtrar por jurisdicciones del perfil.
      if (!empty($profile['jurisdictions'])) {
        $query->condition('jurisdiction', $profile['jurisdictions'], 'IN');
      }

      // Si no hay filtros especificos, solo mostrar resoluciones importantes.
      if (empty($profile['sources']) && empty($profile['jurisdictions'])) {
        $query->condition('importance_level', 2, '<=');
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $resolutions = [];

      foreach ($entities as $entity) {
        // Si el perfil tiene temas, verificar interseccion.
        if (!empty($profile['topics'])) {
          $entityTopics = $entity->getTopics();
          $intersection = array_intersect(
            array_map('mb_strtolower', $profile['topics']),
            array_map('mb_strtolower', $entityTopics)
          );
          if (empty($intersection) && !empty($entityTopics)) {
            continue;
          }
        }

        $resolutions[] = [
          'id' => (int) $entity->id(),
          'title' => $entity->get('title')->value ?? '',
          'external_ref' => $entity->get('external_ref')->value ?? '',
          'issuing_body' => $entity->get('issuing_body')->value ?? '',
          'source_id' => $entity->get('source_id')->value ?? '',
          'date_issued' => $entity->get('date_issued')->value ?? '',
          'resolution_type' => $entity->get('resolution_type')->value ?? '',
          'jurisdiction' => $entity->get('jurisdiction')->value ?? '',
          'abstract_ai' => $entity->get('abstract_ai')->value ?? '',
          'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
          'status_legal' => $entity->get('status_legal')->value ?? 'vigente',
          'original_url' => $entity->get('original_url')->value ?? '',
        ];

        // Limitar a MAX_RESOLUTIONS_PER_DIGEST.
        if (count($resolutions) >= self::MAX_RESOLUTIONS_PER_DIGEST) {
          break;
        }
      }

      return $resolutions;
    }
    catch (\Exception $e) {
      $this->logger->error('LegalDigestService: Error consultando resoluciones para digest: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  // =========================================================================
  // METODOS PRIVADOS: Envio de email.
  // =========================================================================

  /**
   * Envia el email de digest semanal al profesional.
   *
   * Renderiza el template legal-digest-email.html.twig con las resoluciones
   * y lo envia via MailManager con la clave 'legal_digest'.
   *
   * @param int $providerId
   *   ID del usuario destinatario.
   * @param array $digest
   *   Datos del digest (resolutions, provider_name, period_start, period_end).
   * @param string $periodStart
   *   Fecha inicio del periodo.
   * @param string $periodEnd
   *   Fecha fin del periodo.
   *
   * @return bool
   *   TRUE si el email se envio correctamente.
   */
  private function sendDigestEmail(int $providerId, array $digest, string $periodStart, string $periodEnd): bool {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $userStorage->load($providerId);

      if (!$user || !$user->getEmail()) {
        return FALSE;
      }

      // Renderizar el template del digest email.
      $renderer = \Drupal::service('renderer');
      $renderArray = [
        '#theme' => 'legal_digest_email',
        '#provider_name' => $digest['provider_name'],
        '#resolutions' => $digest['resolutions'],
        '#period_start' => $periodStart,
        '#period_end' => $periodEnd,
      ];
      $body = (string) $renderer->renderInIsolation($renderArray);

      $subject = sprintf(
        'Digest Legal Semanal (%s a %s) — %d resoluciones',
        $periodStart,
        $periodEnd,
        count($digest['resolutions'])
      );

      $result = $this->mailManager->mail(
        'jaraba_legal_intelligence',
        'legal_digest',
        $user->getEmail(),
        $user->getPreferredLangcode(),
        [
          'subject' => $subject,
          'body' => $body,
        ]
      );

      return !empty($result['result']);
    }
    catch (\Exception $e) {
      $this->logger->error('LegalDigestService: Error enviando digest a usuario @uid: @msg', [
        '@uid' => $providerId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene el nombre del profesional para el saludo del digest.
   *
   * @param int $providerId
   *   ID del usuario.
   *
   * @return string
   *   Nombre del profesional o nombre de usuario como fallback.
   */
  private function getProviderName(int $providerId): string {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $userStorage->load($providerId);

      if (!$user) {
        return 'Profesional';
      }

      // Intentar campo display_name o field_nombre si existe.
      if ($user->hasField('field_nombre') && !$user->get('field_nombre')->isEmpty()) {
        return $user->get('field_nombre')->value;
      }

      return $user->getDisplayName() ?: 'Profesional';
    }
    catch (\Exception $e) {
      return 'Profesional';
    }
  }

}
