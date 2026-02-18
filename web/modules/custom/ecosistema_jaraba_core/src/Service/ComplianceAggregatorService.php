<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio agregador de KPIs de compliance cross-modulo.
 *
 * ESTRUCTURA:
 * Agrega metricas de cumplimiento de los tres modulos del Stack Compliance
 * Legal N1 (jaraba_privacy, jaraba_legal, jaraba_dr) en un panel unificado.
 * Los servicios de modulos satelite se inyectan condicionalmente via ~NULL.
 *
 * LOGICA:
 * - Cada modulo aporta 3 KPIs (total 9 KPIs).
 * - Si un modulo no esta instalado, sus KPIs se reportan como 'not_available'.
 * - Calcula un compliance_score global (0-100) ponderado equitativamente.
 * - Genera alertas cuando KPIs caen por debajo de umbrales criticos.
 *
 * RELACIONES:
 * - jaraba_privacy: DpaManagerService, DataRightsHandlerService, CookieConsentManagerService
 * - jaraba_legal: TosManagerService, SlaCalculatorService, AupEnforcerService
 * - jaraba_dr: BackupVerifierService, DrTestRunnerService, StatusPageManagerService
 * - CompliancePanelController (consumidor en /admin/jaraba/compliance)
 *
 * Spec: Plan Stack Compliance Legal N1 — FASE 12.
 */
class ComplianceAggregatorService {

  use StringTranslationTrait;

  /**
   * Umbral critico: por debajo de este % se genera alerta.
   */
  protected const CRITICAL_THRESHOLD = 50;

  /**
   * Umbral warning: por debajo de este % se genera warning.
   */
  protected const WARNING_THRESHOLD = 80;

  /**
   * Construye el servicio agregador de compliance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   * @param object|null $dpaManager
   *   DpaManagerService de jaraba_privacy (NULL si no instalado).
   * @param object|null $dataRightsHandler
   *   DataRightsHandlerService de jaraba_privacy (NULL si no instalado).
   * @param object|null $cookieConsentManager
   *   CookieConsentManagerService de jaraba_privacy (NULL si no instalado).
   * @param object|null $tosManager
   *   TosManagerService de jaraba_legal (NULL si no instalado).
   * @param object|null $slaCalculator
   *   SlaCalculatorService de jaraba_legal (NULL si no instalado).
   * @param object|null $aupEnforcer
   *   AupEnforcerService de jaraba_legal (NULL si no instalado).
   * @param object|null $backupVerifier
   *   BackupVerifierService de jaraba_dr (NULL si no instalado).
   * @param object|null $drTestRunner
   *   DrTestRunnerService de jaraba_dr (NULL si no instalado).
   * @param object|null $statusPageManager
   *   StatusPageManagerService de jaraba_dr (NULL si no instalado).
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $dpaManager = NULL,
    protected ?object $dataRightsHandler = NULL,
    protected ?object $cookieConsentManager = NULL,
    protected ?object $tosManager = NULL,
    protected ?object $slaCalculator = NULL,
    protected ?object $aupEnforcer = NULL,
    protected ?object $backupVerifier = NULL,
    protected ?object $drTestRunner = NULL,
    protected ?object $statusPageManager = NULL,
  ) {}

  /**
   * Obtiene todos los KPIs agregados del panel de compliance.
   *
   * @return array
   *   Array con:
   *   - score (int): Score global 0-100.
   *   - grade (string): A/B/C/D/F segun score.
   *   - kpis (array): Los 9 KPIs organizados por modulo.
   *   - alerts (array): Alertas activas.
   *   - modules (array): Estado de instalacion de cada modulo.
   */
  public function getComplianceOverview(): array {
    $kpis = $this->collectAllKpis();
    $flatKpis = [];
    foreach ($kpis as $moduleKpis) {
      foreach ($moduleKpis as $kpi) {
        $flatKpis[] = $kpi;
      }
    }

    $score = $this->calculateGlobalScore($kpis);
    $alerts = $this->generateAlerts($kpis);

    return [
      'score' => $score,
      'grade' => $this->scoreToGrade($score),
      'kpis' => $flatKpis,
      'alerts' => $alerts,
      'modules' => [
        ['key' => 'jaraba_privacy', 'installed' => $this->dpaManager !== NULL],
        ['key' => 'jaraba_legal', 'installed' => $this->tosManager !== NULL],
        ['key' => 'jaraba_dr', 'installed' => $this->backupVerifier !== NULL],
      ],
      'generated_at' => time(),
    ];
  }

  /**
   * Recopila todos los KPIs de los tres modulos.
   *
   * @return array
   *   KPIs organizados por modulo.
   */
  public function collectAllKpis(): array {
    return [
      'privacy' => $this->collectPrivacyKpis(),
      'legal' => $this->collectLegalKpis(),
      'dr' => $this->collectDrKpis(),
    ];
  }

  /**
   * Recopila KPIs del modulo jaraba_privacy.
   *
   * @return array
   *   3 KPIs: dpa_coverage, arco_pol_sla, cookie_consent_rate.
   */
  protected function collectPrivacyKpis(): array {
    if (!$this->dpaManager) {
      return $this->unavailableKpis(['dpa_coverage', 'arco_pol_sla', 'cookie_consent_rate'], 'jaraba_privacy');
    }

    return [
      'dpa_coverage' => $this->calculateDpaCoverage(),
      'arco_pol_sla' => $this->calculateArcopolSla(),
      'cookie_consent_rate' => $this->calculateCookieConsentRate(),
    ];
  }

  /**
   * Recopila KPIs del modulo jaraba_legal.
   *
   * @return array
   *   3 KPIs: tos_acceptance_rate, sla_compliance, aup_violations.
   */
  protected function collectLegalKpis(): array {
    if (!$this->tosManager) {
      return $this->unavailableKpis(['tos_acceptance_rate', 'sla_compliance', 'aup_violations'], 'jaraba_legal');
    }

    return [
      'tos_acceptance_rate' => $this->calculateTosAcceptanceRate(),
      'sla_compliance' => $this->calculateSlaCompliance(),
      'aup_violations' => $this->calculateAupViolations(),
    ];
  }

  /**
   * Recopila KPIs del modulo jaraba_dr.
   *
   * @return array
   *   3 KPIs: backup_health, dr_test_coverage, status_page_uptime.
   */
  protected function collectDrKpis(): array {
    if (!$this->backupVerifier) {
      return $this->unavailableKpis(['backup_health', 'dr_test_coverage', 'status_page_uptime'], 'jaraba_dr');
    }

    return [
      'backup_health' => $this->calculateBackupHealth(),
      'dr_test_coverage' => $this->calculateDrTestCoverage(),
      'status_page_uptime' => $this->calculateStatusPageUptime(),
    ];
  }

  /**
   * Calcula % de tenants con DPA vigente.
   */
  protected function calculateDpaCoverage(): array {
    try {
      $totalTenants = $this->countActiveTenants();
      if ($totalTenants === 0) {
        return $this->kpi('dpa_coverage', 100, (string) $this->t('DPA Coverage'), 'jaraba_privacy');
      }

      $tenantsWithDpa = 0;
      $tenantIds = $this->getActiveTenantIds();
      foreach ($tenantIds as $tenantId) {
        if ($this->dpaManager->hasDpa((int) $tenantId)) {
          $tenantsWithDpa++;
        }
      }

      $percentage = $totalTenants > 0
        ? (int) round(($tenantsWithDpa / $totalTenants) * 100)
        : 0;

      return $this->kpi('dpa_coverage', $percentage, (string) $this->t('DPA Coverage'), 'jaraba_privacy');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando DPA coverage: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('dpa_coverage', 0, (string) $this->t('DPA Coverage'), 'jaraba_privacy', 'error');
    }
  }

  /**
   * Calcula % de solicitudes ARCO-POL dentro de plazo.
   */
  protected function calculateArcopolSla(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('data_rights_request');
      $totalQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->count();
      $total = (int) $totalQuery->execute();

      if ($total === 0) {
        return $this->kpi('arco_pol_sla', 100, (string) $this->t('ARCO-POL SLA'), 'jaraba_privacy');
      }

      $withinDeadline = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      $expired = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'expired')
        ->count()
        ->execute();

      $resolved = $withinDeadline + $expired;
      $percentage = $resolved > 0
        ? (int) round(($withinDeadline / $resolved) * 100)
        : 100;

      return $this->kpi('arco_pol_sla', $percentage, (string) $this->t('ARCO-POL SLA'), 'jaraba_privacy');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando ARCO-POL SLA: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('arco_pol_sla', 0, (string) $this->t('ARCO-POL SLA'), 'jaraba_privacy', 'error');
    }
  }

  /**
   * Calcula % de usuarios con consentimiento de cookies.
   */
  protected function calculateCookieConsentRate(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('cookie_consent');
      $totalConsents = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $activeConsents = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->count()
        ->execute();

      $percentage = $totalConsents > 0
        ? (int) round(($activeConsents / $totalConsents) * 100)
        : 0;

      return $this->kpi('cookie_consent_rate', $percentage, (string) $this->t('Cookie Consent Rate'), 'jaraba_privacy');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando cookie consent rate: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('cookie_consent_rate', 0, (string) $this->t('Cookie Consent Rate'), 'jaraba_privacy', 'error');
    }
  }

  /**
   * Calcula % de tenants con ToS vigente aceptado.
   */
  protected function calculateTosAcceptanceRate(): array {
    try {
      $totalTenants = $this->countActiveTenants();
      if ($totalTenants === 0) {
        return $this->kpi('tos_acceptance_rate', 100, (string) $this->t('ToS Acceptance Rate'), 'jaraba_legal');
      }

      $storage = $this->entityTypeManager->getStorage('service_agreement');
      $acceptedCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->count()
        ->execute();

      $percentage = (int) round(($acceptedCount / $totalTenants) * 100);
      $percentage = min($percentage, 100);

      return $this->kpi('tos_acceptance_rate', $percentage, (string) $this->t('ToS Acceptance Rate'), 'jaraba_legal');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando ToS acceptance rate: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('tos_acceptance_rate', 0, (string) $this->t('ToS Acceptance Rate'), 'jaraba_legal', 'error');
    }
  }

  /**
   * Calcula % de meses con uptime >= target SLA.
   */
  protected function calculateSlaCompliance(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('sla_record');
      $totalRecords = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalRecords === 0) {
        return $this->kpi('sla_compliance', 100, (string) $this->t('SLA Compliance'), 'jaraba_legal');
      }

      $compliantRecords = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'compliant')
        ->count()
        ->execute();

      $percentage = (int) round(($compliantRecords / $totalRecords) * 100);

      return $this->kpi('sla_compliance', $percentage, (string) $this->t('SLA Compliance'), 'jaraba_legal');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando SLA compliance: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('sla_compliance', 0, (string) $this->t('SLA Compliance'), 'jaraba_legal', 'error');
    }
  }

  /**
   * Calcula violaciones AUP activas este mes.
   */
  protected function calculateAupViolations(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('aup_violation');
      $startOfMonth = strtotime('first day of this month midnight');

      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('detected_at', $startOfMonth, '>=')
        ->count()
        ->execute();

      // Para AUP violations, menor es mejor. Invertir para score.
      $score = $count === 0 ? 100 : max(0, 100 - ($count * 10));

      return $this->kpi('aup_violations', $score, (string) $this->t('AUP Violations'), 'jaraba_legal', 'ok');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando AUP violations: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('aup_violations', 0, (string) $this->t('AUP Violations'), 'jaraba_legal', 'error');
    }
  }

  /**
   * Calcula % de backups verificados exitosamente.
   */
  protected function calculateBackupHealth(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('backup_verification');
      $totalVerifications = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($totalVerifications === 0) {
        return $this->kpi('backup_health', 100, (string) $this->t('Backup Health'), 'jaraba_dr');
      }

      $passedVerifications = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('passed', TRUE)
        ->count()
        ->execute();

      $percentage = (int) round(($passedVerifications / $totalVerifications) * 100);

      return $this->kpi('backup_health', $percentage, (string) $this->t('Backup Health'), 'jaraba_dr');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando backup health: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('backup_health', 0, (string) $this->t('Backup Health'), 'jaraba_dr', 'error');
    }
  }

  /**
   * Calcula tests DR ejecutados vs calendario.
   */
  protected function calculateDrTestCoverage(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('dr_test_result');
      $sixMonthsAgo = strtotime('-6 months');

      $executedTests = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('executed_at', $sixMonthsAgo, '>=')
        ->count()
        ->execute();

      // Objetivo: al menos 2 tests por semestre (1 failover + 1 recovery).
      $targetTests = 2;
      $percentage = min(100, (int) round(($executedTests / $targetTests) * 100));

      return $this->kpi('dr_test_coverage', $percentage, (string) $this->t('DR Test Coverage'), 'jaraba_dr');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando DR test coverage: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('dr_test_coverage', 0, (string) $this->t('DR Test Coverage'), 'jaraba_dr', 'error');
    }
  }

  /**
   * Calcula uptime promedio de todos los componentes de status page.
   */
  protected function calculateStatusPageUptime(): array {
    try {
      if ($this->statusPageManager && method_exists($this->statusPageManager, 'getStatusOverview')) {
        $overview = $this->statusPageManager->getStatusOverview();
        $components = $overview['components'] ?? [];

        if (empty($components)) {
          return $this->kpi('status_page_uptime', 100, (string) $this->t('Status Page Uptime'), 'jaraba_dr');
        }

        $totalUptime = 0;
        foreach ($components as $component) {
          $uptime = $component['uptime'] ?? 100;
          $totalUptime += $uptime;
        }

        $avgUptime = (int) round($totalUptime / count($components));
        return $this->kpi('status_page_uptime', $avgUptime, (string) $this->t('Status Page Uptime'), 'jaraba_dr');
      }

      return $this->kpi('status_page_uptime', 100, (string) $this->t('Status Page Uptime'), 'jaraba_dr');
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculando status page uptime: @error', ['@error' => $e->getMessage()]);
      return $this->kpi('status_page_uptime', 0, (string) $this->t('Status Page Uptime'), 'jaraba_dr', 'error');
    }
  }

  /**
   * Calcula el score global ponderado de compliance (0-100).
   *
   * @param array $kpis
   *   KPIs agrupados por modulo.
   *
   * @return int
   *   Score global 0-100.
   */
  public function calculateGlobalScore(array $kpis): int {
    $totalScore = 0;
    $totalKpis = 0;
    $installedModules = 0;

    if ($this->dpaManager !== NULL) $installedModules++;
    if ($this->tosManager !== NULL) $installedModules++;
    if ($this->backupVerifier !== NULL) $installedModules++;

    if ($installedModules === 0) {
      return 0;
    }

    foreach ($kpis as $moduleKpis) {
      foreach ($moduleKpis as $kpi) {
        if ($kpi['status'] !== 'not_available') {
          $totalScore += $kpi['value'];
          $totalKpis++;
        }
      }
    }

    if ($totalKpis === 0) {
      return 100;
    }

    return (int) round($totalScore / $totalKpis);
  }

  /**
   * Genera alertas cuando KPIs caen por debajo de umbrales.
   *
   * @param array $kpis
   *   KPIs agrupados por modulo.
   *
   * @return array
   *   Lista de alertas con severity, message, kpi_id.
   */
  public function generateAlerts(array $kpis): array {
    $alerts = [];

    foreach ($kpis as $module => $moduleKpis) {
      foreach ($moduleKpis as $kpiId => $kpi) {
        if ($kpi['status'] === 'not_available' || $kpi['status'] === 'error') {
          continue;
        }

        if ($kpi['value'] < self::CRITICAL_THRESHOLD) {
          $alerts[] = [
            'severity' => 'critical',
            'module' => $module,
            'kpi_id' => $kpiId,
            'label' => $kpi['label'],
            'score' => $kpi['value'],
            'message' => (string) $this->t('@label esta en estado critico: @score%', [
              '@label' => $kpi['label'],
              '@score' => $kpi['value'],
            ]),
          ];
        }
        elseif ($kpi['value'] < self::WARNING_THRESHOLD) {
          $alerts[] = [
            'severity' => 'warning',
            'module' => $module,
            'kpi_id' => $kpiId,
            'label' => $kpi['label'],
            'score' => $kpi['value'],
            'message' => (string) $this->t('@label requiere atencion: @score%', [
              '@label' => $kpi['label'],
              '@score' => $kpi['value'],
            ]),
          ];
        }
      }
    }

    // Ordenar: criticos primero.
    usort($alerts, static fn(array $a, array $b) => $a['score'] <=> $b['score']);

    return $alerts;
  }

  /**
   * Convierte un score numerico en grado A-F.
   */
  protected function scoreToGrade(int $score): string {
    return match (TRUE) {
      $score >= 90 => 'A',
      $score >= 75 => 'B',
      $score >= 60 => 'C',
      $score >= 40 => 'D',
      default => 'F',
    };
  }

  /**
   * Construye un array de KPI estandarizado.
   */
  protected function kpi(string $id, int $score, string $label, string $module, string $status = 'ok'): array {
    if ($status === 'ok') {
      $status = $score >= self::WARNING_THRESHOLD ? 'ok'
        : ($score >= self::CRITICAL_THRESHOLD ? 'warning' : 'critical');
    }

    return [
      'key' => $id,
      'value' => $score,
      'label' => $label,
      'status' => $status,
      'module' => $module,
    ];
  }

  /**
   * Genera KPIs con estado 'not_available' para modulos no instalados.
   */
  protected function unavailableKpis(array $kpiIds, string $module): array {
    $result = [];
    foreach ($kpiIds as $id) {
      $result[$id] = [
        'key' => $id,
        'value' => 0,
        'label' => $id,
        'status' => 'not_available',
        'module' => $module,
      ];
    }
    return $result;
  }

  /**
   * Cuenta los tenants activos de la plataforma.
   */
  protected function countActiveTenants(): int {
    try {
      return (int) $this->entityTypeManager->getStorage('group')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Obtiene IDs de tenants activos.
   *
   * @return array
   *   Array de IDs de tenants.
   */
  protected function getActiveTenantIds(): array {
    try {
      return $this->entityTypeManager->getStorage('group')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->execute();
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
