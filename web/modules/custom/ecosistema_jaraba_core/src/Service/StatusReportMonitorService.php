<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * STATUS-REPORT-PROACTIVE-001: Proactive Drupal status report monitoring.
 *
 * Compares current hook_requirements results against last known snapshot.
 * Detects new Errors/Warnings and triggers alerts via AlertingService.
 *
 * Designed for hook_cron integration (every 6 hours).
 * Uses State API for snapshot persistence (same pattern as 42 other cron hooks).
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
   * Interval between checks in seconds (6 hours).
   */
  protected const CHECK_INTERVAL = 21600;

  /**
   * Requirement keys that are expected warnings (not actionable).
   */
  protected const EXPECTED_WARNINGS = [
    'ecosistema_jaraba_base_domain',
    'experimental_modules',
    'update_contrib',
    'update_core',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected ModuleHandlerInterface $moduleHandler,
    protected ?AlertingService $alerting = NULL,
  ) {
  }

  /**
   * Check if it's time to run the monitor.
   */
  public function shouldRun(): bool {
    $lastCheck = (int) $this->state->get(self::STATE_LAST_CHECK, 0);
    return (time() - $lastCheck) >= self::CHECK_INTERVAL;
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
    // Each module's .install is loaded before invoking its hook.
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
      // Skip expected warnings.
      if ($item['severity'] === 'Warning' && in_array($key, self::EXPECTED_WARNINGS, TRUE)) {
        continue;
      }

      $prevSeverity = $previous[$key]['severity'] ?? 'OK';

      if ($item['severity'] === 'Error') {
        if ($prevSeverity !== 'Error') {
          // New error.
          $errors[] = $item + ['key' => $key];
        }
      }
      elseif ($item['severity'] === 'Warning') {
        if ($prevSeverity !== 'Warning') {
          // New warning.
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

    // AlertingService for Slack/Teams.
    if ($this->alerting !== NULL) {
      $fields = [];
      foreach ($changes['errors'] as $e) {
        $fields[] = ['title' => $e['title'], 'value' => $e['value']];
      }
      foreach ($changes['new_warnings'] as $w) {
        $fields[] = ['title' => $w['title'], 'value' => $w['value']];
      }

      $this->alerting->send(
        'Drupal Status Report — New Issues Detected',
        count($changes['errors']) . ' error(s), ' . count($changes['new_warnings']) . ' warning(s) detected.',
        $type,
        $fields,
        '/admin/reports/status'
      );
    }

    // Log at appropriate level for watchdog/syslog.
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

}
