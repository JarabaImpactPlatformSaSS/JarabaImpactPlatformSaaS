<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de programacion y envio de informes.
 *
 * PROPOSITO:
 * Gestiona la programacion de informes de analytics, incluyendo la obtencion
 * de informes pendientes, el procesamiento de envios programados y la
 * generacion de datos de informe.
 *
 * LOGICA:
 * - getScheduledReports: lista informes activos filtrados por tenant.
 * - processScheduledReports: ejecuta informes cuyo next_send ha pasado,
 *   envia emails a destinatarios y actualiza timestamps.
 * - generateReport: genera los datos de un informe especifico.
 */
class ReportSchedulerService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MailManagerInterface $mail_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
  }

  /**
   * Gets scheduled reports, optionally filtered by tenant.
   *
   * @param int|null $tenantId
   *   Optional tenant ID to filter by.
   *
   * @return array
   *   Array of serialized report data.
   */
  public function getScheduledReports(?int $tenantId = NULL): array {
    try {
      $storage = $this->entityTypeManager->getStorage('scheduled_report');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC');

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      $reports = $storage->loadMultiple($ids);

      $results = [];
      /** @var \Drupal\jaraba_analytics\Entity\ScheduledReport $report */
      foreach ($reports as $report) {
        $results[] = [
          'id' => (int) $report->id(),
          'name' => $report->getName(),
          'schedule_type' => $report->getScheduleType(),
          'report_status' => $report->getReportStatus(),
          'recipients' => $report->getRecipients(),
          'last_sent' => $report->getLastSent(),
          'next_send' => $report->getNextSend(),
          'tenant_id' => $report->getTenantId(),
          'created' => (int) $report->get('created')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load scheduled reports: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Processes all due scheduled reports.
   *
   * Finds reports whose next_send timestamp has passed and are active,
   * generates the report data, sends emails to recipients, and updates
   * the last_sent and next_send timestamps.
   *
   * @return int
   *   Number of reports successfully processed.
   */
  public function processScheduledReports(): int {
    $processed = 0;

    try {
      $storage = $this->entityTypeManager->getStorage('scheduled_report');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('report_status', 'active')
        ->condition('next_send', time(), '<=')
        ->sort('next_send', 'ASC');

      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $reports = $storage->loadMultiple($ids);

      /** @var \Drupal\jaraba_analytics\Entity\ScheduledReport $report */
      foreach ($reports as $report) {
        try {
          $reportData = $this->generateReport((int) $report->id());

          if (empty($reportData)) {
            $this->logger->warning('Empty report data for report @id, skipping.', [
              '@id' => $report->id(),
            ]);
            continue;
          }

          // Send email to each recipient.
          $recipients = $report->getRecipients();
          $sentCount = 0;

          foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $this->logger->warning('Invalid email @email in report @id recipients.', [
                '@email' => $email,
                '@id' => $report->id(),
              ]);
              continue;
            }

            $params = [
              'report_name' => $report->getName(),
              'report_data' => $reportData,
              'subject' => 'Scheduled Report: ' . $report->getName(),
            ];

            $result = $this->mailManager->mail(
              'jaraba_analytics',
              'scheduled_report',
              $email,
              'en',
              $params
            );

            if ($result['result']) {
              $sentCount++;
            }
          }

          // Update timestamps.
          $report->set('last_sent', time());
          $report->set('next_send', $this->calculateNextSend($report));
          $report->save();

          $this->logger->info('Report @name sent to @count recipients.', [
            '@name' => $report->getName(),
            '@count' => $sentCount,
          ]);

          $processed++;
        }
        catch (\Exception $e) {
          $this->logger->error('Failed to process report @id: @message', [
            '@id' => $report->id(),
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to query scheduled reports: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $processed;
  }

  /**
   * Generates report data for a specific report.
   *
   * @param int $reportId
   *   The scheduled report entity ID.
   *
   * @return array
   *   Generated report data array with keys: title, generated_at, data, summary.
   */
  public function generateReport(int $reportId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('scheduled_report');
      /** @var \Drupal\jaraba_analytics\Entity\ScheduledReport|null $report */
      $report = $storage->load($reportId);

      if (!$report) {
        $this->logger->warning('Report @id not found for generation.', [
          '@id' => $reportId,
        ]);
        return [];
      }

      $config = $report->getReportConfig();

      return [
        'title' => $report->getName(),
        'generated_at' => time(),
        'schedule_type' => $report->getScheduleType(),
        'config' => $config,
        'data' => [],
        'summary' => [
          'status' => 'generated',
          'report_id' => $reportId,
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to generate report @id: @message', [
        '@id' => $reportId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calculates the next send timestamp based on schedule configuration.
   *
   * @param \Drupal\jaraba_analytics\Entity\ScheduledReport $report
   *   The scheduled report entity.
   *
   * @return int
   *   The next send timestamp.
   */
  protected function calculateNextSend($report): int {
    $scheduleType = $report->getScheduleType();
    $now = time();

    return match ($scheduleType) {
      'daily' => $now + 86400,
      'weekly' => $now + (7 * 86400),
      'monthly' => $now + (30 * 86400),
      default => $now + (7 * 86400),
    };
  }

}
