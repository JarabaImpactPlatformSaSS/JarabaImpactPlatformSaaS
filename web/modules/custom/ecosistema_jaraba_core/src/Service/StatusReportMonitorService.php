<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * STATUS-REPORT-PROACTIVE-001: Proactive Drupal status report monitoring.
 *
 * ESTRUCTURA:
 * Compara los resultados actuales de hook_requirements contra un snapshot
 * anterior almacenado en State API. Detecta errores/warnings nuevos y
 * envia alertas via AlertingService (Slack/Teams) con email como fallback.
 *
 * LOGICA:
 * - Ejecutado cada 6 horas desde hook_cron (six_hour_check_last gate)
 * - Baseline de warnings esperados centralizada como SSOT (STATUS-BASELINE-SSOT-001)
 * - Email fallback si AlertingService no esta configurado (ALERTING-EMAIL-FALLBACK-001)
 *
 * RELACIONES:
 * - StatusReportMonitorService <- hook_cron: invocado por
 * - StatusReportMonitorService -> AlertingService: alerta via (opcional)
 * - StatusReportMonitorService -> MailManager: email fallback
 * - StatusReportMonitorService -> State API: persistencia snapshot
 */
class StatusReportMonitorService {

  /**
   * State key for the last known requirements snapshot.
   */
  protected const STATE_SNAPSHOT = 'jaraba_status_report.last_snapshot';

  /**
   * State key for last check timestamp.
   */
  protected const STATE_LAST_CHECK = 'jaraba_status_report.last_check';

  /**
   * STATUS-BASELINE-SSOT-001: Expected warnings for development environments.
   *
   * Fuente unica de verdad. Referenciada por:
   * - Este servicio (diff en runtime)
   * - scripts/validation/validate-status-report.php
   * - .github/workflows/diagnose-status.yml
   */
  public const EXPECTED_WARNINGS_DEV = [
    'ecosistema_jaraba_base_domain',
    'experimental_modules',
    'update_contrib',
    'update_core',
  ];

  /**
   * STATUS-BASELINE-SSOT-001: Expected warnings for production environments.
   */
  public const EXPECTED_WARNINGS_PROD = [
    'experimental_modules',
  ];

  /**
   * Constructor.
   *
   * OPTIONAL-PARAM-ORDER-001: Parametro opcional (@?) al final.
   */
  public function __construct(
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
    protected MailManagerInterface $mailManager,
    protected ?AlertingService $alerting = NULL,
  ) {
  }

  /**
   * Devuelve la baseline de warnings esperados segun entorno.
   *
   * STATUS-BASELINE-SSOT-001: Metodo estatico para que validate-status-report.php
   * y diagnose-status.yml puedan consultarlo sin bootstrap Drupal completo.
   *
   * @param string $env
   *   Entorno: 'dev' o 'prod'.
   *
   * @return string[]
   *   Array de requirement keys que son warnings esperados.
   */
  public static function getExpectedWarnings(string $env = 'dev'): array {
    return $env === 'prod' ? self::EXPECTED_WARNINGS_PROD : self::EXPECTED_WARNINGS_DEV;
  }

  /**
   * Execute the status report check and alert on changes.
   *
   * @return array{errors: list<array<string, string>>, new_warnings: list<array<string, string>>, resolved: list<array<string, string>>}
   *   Summary of changes detected.
   */
  public function check(): array {
    $current = $this->getCurrentRequirements();
    $previous = $this->getPreviousSnapshot();

    $changes = $this->diff($current, $previous);

    // Persist new snapshot and timestamp.
    $this->state->set(self::STATE_SNAPSHOT, $current);
    $this->state->set(self::STATE_LAST_CHECK, time());

    // Alert if there are new issues.
    if ($changes['errors'] !== [] || $changes['new_warnings'] !== []) {
      $this->alert($changes);
    }

    // Log for audit trail.
    if ($changes['errors'] !== [] || $changes['new_warnings'] !== [] || $changes['resolved'] !== []) {
      $this->logger->notice('Status report monitor: @errors errors, @warnings new warnings, @resolved resolved.', [
        '@errors' => count($changes['errors']),
        '@warnings' => count($changes['new_warnings']),
        '@resolved' => count($changes['resolved']),
      ]);
    }

    return $changes;
  }

  /**
   * Get current requirements from all modules.
   *
   * @return array<string, array{severity: string, title: string, value: string}>
   */
  protected function getCurrentRequirements(): array {
    $requirements = [];

    // Load .install files and invoke hook_requirements for runtime phase.
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $this->moduleHandler->loadInclude($module, 'install');
    }

    try {
      $requirements = $this->moduleHandler->invokeAll('requirements', ['runtime']);
    }
    catch (\Throwable $e) {
      $this->logger->error('Status report monitor: invokeAll requirements failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Normalize to a simple comparable format.
    $normalized = [];
    foreach ($requirements as $key => $item) {
      $severity = $item['severity'] ?? REQUIREMENT_OK;
      $severityLabel = match ((int) $severity) {
        REQUIREMENT_ERROR => 'Error',
        REQUIREMENT_WARNING => 'Warning',
        REQUIREMENT_INFO => 'Info',
        default => 'OK',
      };

      $normalized[$key] = [
        'severity' => $severityLabel,
        'title' => (string) ($item['title'] ?? $key),
        'value' => trim((string) ($item['value'] ?? '')),
      ];
    }

    return $normalized;
  }

  /**
   * Get previous snapshot from State API.
   *
   * @return array<string, array{severity: string, title: string, value: string}>
   */
  protected function getPreviousSnapshot(): array {
    return $this->state->get(self::STATE_SNAPSHOT, []);
  }

  /**
   * Compute diff between current and previous snapshots.
   *
   * Usa EXPECTED_WARNINGS_DEV como baseline en runtime (conservador:
   * mejor ignorar un warning conocido de mas que alertar innecesariamente).
   *
   * @param array<string, array{severity: string, title: string, value: string}> $current
   * @param array<string, array{severity: string, title: string, value: string}> $previous
   *
   * @return array{errors: list<array<string, string>>, new_warnings: list<array<string, string>>, resolved: list<array<string, string>>}
   */
  protected function diff(array $current, array $previous): array {
    $errors = [];
    $newWarnings = [];
    $resolved = [];

    foreach ($current as $key => $item) {
      // Skip expected warnings (STATUS-BASELINE-SSOT-001).
      if ($item['severity'] === 'Warning' && in_array($key, self::EXPECTED_WARNINGS_DEV, TRUE)) {
        continue;
      }

      $prevSeverity = $previous[$key]['severity'] ?? 'OK';

      if ($item['severity'] === 'Error') {
        if ($prevSeverity !== 'Error') {
          $errors[] = $item + ['key' => $key];
        }
      }
      elseif ($item['severity'] === 'Warning') {
        if ($prevSeverity !== 'Warning') {
          $newWarnings[] = $item + ['key' => $key];
        }
      }
    }

    // Detect resolved issues (were Error/Warning, now OK or gone).
    foreach ($previous as $key => $prevItem) {
      if (in_array($prevItem['severity'], ['Error', 'Warning'], TRUE)) {
        $currentSeverity = $current[$key]['severity'] ?? 'OK';
        if (!in_array($currentSeverity, ['Error', 'Warning'], TRUE)) {
          $resolved[] = $prevItem + ['key' => $key];
        }
      }
    }

    return [
      'errors' => $errors,
      'new_warnings' => $newWarnings,
      'resolved' => $resolved,
    ];
  }

  /**
   * Send alerts for new issues.
   *
   * ALERTING-EMAIL-FALLBACK-001: Intenta Slack/Teams primero,
   * luego email como canal de ultimo recurso.
   *
   * @param array{errors: list<array<string, string>>, new_warnings: list<array<string, string>>, resolved: list<array<string, string>>} $changes
   */
  protected function alert(array $changes): void {
    $issueLines = [];

    foreach ($changes['errors'] as $error) {
      $issueLines[] = "[ERROR] {$error['title']}: {$error['value']}";
    }
    foreach ($changes['new_warnings'] as $warning) {
      $issueLines[] = "[WARN] {$warning['title']}: {$warning['value']}";
    }

    $message = implode("\n", $issueLines);
    $type = $changes['errors'] !== [] ? AlertingService::ALERT_ERROR : AlertingService::ALERT_WARNING;

    // Canal 1: AlertingService para Slack/Teams.
    $slackSent = FALSE;
    if ($this->alerting !== NULL) {
      $fields = [];
      foreach ($changes['errors'] as $e) {
        $fields[] = ['title' => $e['title'], 'value' => $e['value']];
      }
      foreach ($changes['new_warnings'] as $w) {
        $fields[] = ['title' => $w['title'], 'value' => $w['value']];
      }

      try {
        $this->alerting->send(
          'Drupal Status Report — New Issues Detected',
          count($changes['errors']) . ' error(s), ' . count($changes['new_warnings']) . ' warning(s) detected.',
          $type,
          $fields,
          '/admin/reports/status'
        );
        $slackSent = TRUE;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Status report monitor: AlertingService failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Canal 2: Email fallback (ALERTING-EMAIL-FALLBACK-001).
    // Envia email si AlertingService no esta disponible o fallo.
    if (!$slackSent) {
      $this->sendEmailAlert($changes, $issueLines);
    }

    // Canal 3: Log (siempre).
    if ($changes['errors'] !== []) {
      $this->logger->error('Status report monitor detected new errors: @message', [
        '@message' => $message,
      ]);
    }
    else {
      $this->logger->warning('Status report monitor detected new warnings: @message', [
        '@message' => $message,
      ]);
    }
  }

  /**
   * Envia alerta por email como fallback.
   *
   * ALERTING-EMAIL-FALLBACK-001: Usa system.site.mail como destinatario.
   * PRESAVE-RESILIENCE-001: Todo el bloque en try-catch.
   *
   * @param array{errors: list<array<string, string>>, new_warnings: list<array<string, string>>, resolved: list<array<string, string>>} $changes
   * @param string[] $issueLines
   *   Lineas formateadas de cada issue.
   */
  protected function sendEmailAlert(array $changes, array $issueLines): void {
    try {
      $siteConfig = $this->configFactory->get('system.site');
      $adminEmail = $siteConfig->get('mail');

      if ($adminEmail === NULL || $adminEmail === '') {
        $this->logger->warning('Status report monitor: no admin email configured for fallback.');
        return;
      }

      $params = [
        'error_count' => count($changes['errors']),
        'warning_count' => count($changes['new_warnings']),
        'issues' => $issueLines,
      ];

      $this->mailManager->mail(
        'ecosistema_jaraba_core',
        'status_report_alert',
        $adminEmail,
        'es',
        $params,
      );

      $this->logger->info('Status report alert email sent to @email.', [
        '@email' => $adminEmail,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Status report monitor: email fallback failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
