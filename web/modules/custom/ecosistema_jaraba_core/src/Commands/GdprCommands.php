<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\AuditLogService;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * Drush commands for GDPR compliance operations.
 *
 * Provides data export (Art. 15), anonymization (Art. 17),
 * and compliance reporting for the Jaraba Impact Platform.
 */
class GdprCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The audit log service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\AuditLogService
   */
  protected $auditLog;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   * @param \Drupal\ecosistema_jaraba_core\Service\AuditLogService $audit_log
   *   The audit log service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    AuditLogService $audit_log,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->auditLog = $audit_log;
  }

  /**
   * Export personal data for a user (GDPR Art. 15 - Right of Access).
   *
   * Collects data from user entity, profile entities, billing records,
   * AI interaction logs, and analytics events. Outputs a JSON file to
   * the temporary directory.
   *
   * @command gdpr:export
   * @aliases gdpr-ex
   * @option output Custom output directory for the export file.
   * @usage gdpr:export 42
   *   Export all personal data for user 42.
   * @usage gdpr:export 42 --output=/tmp/exports
   *   Export to a custom directory.
   */
  public function export(string $uid, array $options = ['output' => '']): void {
    $uid = (int) $uid;

    $this->io()->title("GDPR Data Export (Art. 15) - User $uid");

    // Load the user entity.
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user) {
      $this->io()->error("User with UID $uid not found.");
      return;
    }

    $this->io()->text("Collecting personal data for: {$user->getAccountName()} ({$user->getEmail()})");

    $exportData = [
      'export_metadata' => [
        'uid' => $uid,
        'generated_at' => date('c'),
        'platform' => 'Jaraba Impact Platform SaaS',
        'legal_basis' => 'GDPR Art. 15 - Right of Access',
      ],
    ];

    // 1. User entity data.
    $this->io()->text('  Collecting user account data...');
    $exportData['user_account'] = [
      'uid' => (int) $user->id(),
      'username' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'status' => $user->isActive() ? 'active' : 'blocked',
      'created' => date('c', (int) $user->getCreatedTime()),
      'last_access' => $user->getLastAccessedTime() ? date('c', (int) $user->getLastAccessedTime()) : NULL,
      'last_login' => $user->getLastLoginTime() ? date('c', (int) $user->getLastLoginTime()) : NULL,
      'roles' => $user->getRoles(),
      'timezone' => $user->getTimeZone(),
      'language' => $user->getPreferredLangcode(),
    ];

    // 2. Profile entities.
    $this->io()->text('  Collecting profile data...');
    $exportData['profiles'] = $this->collectProfileData($uid);

    // 3. Billing records.
    $this->io()->text('  Collecting billing records...');
    $exportData['billing'] = $this->collectBillingData($uid);

    // 4. AI interaction logs.
    $this->io()->text('  Collecting AI interaction logs...');
    $exportData['ai_interactions'] = $this->collectAiInteractionData($uid);

    // 5. Analytics events.
    $this->io()->text('  Collecting analytics events...');
    $exportData['analytics'] = $this->collectAnalyticsData($uid);

    // 6. Audit log entries for this user.
    $this->io()->text('  Collecting audit log entries...');
    $exportData['audit_log'] = $this->collectAuditLogData($uid);

    // Write the JSON file.
    $outputDir = $options['output'] ?: \Drupal::service('file_system')->getTempDirectory();
    $filename = "gdpr_export_user_{$uid}_" . date('Ymd_His') . '.json';
    $filepath = $outputDir . '/' . $filename;

    $jsonOutput = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($jsonOutput === FALSE) {
      $this->io()->error('Failed to encode export data as JSON.');
      return;
    }

    if (file_put_contents($filepath, $jsonOutput) === FALSE) {
      $this->io()->error("Failed to write export file to: $filepath");
      return;
    }

    // Log the export action in audit log.
    $this->auditLog->log('gdpr.data_export', [
      'target_type' => 'user',
      'target_id' => $uid,
      'severity' => 'info',
      'details' => [
        'file' => $filename,
        'sections' => array_keys($exportData),
      ],
    ]);

    $this->logger->info('GDPR data export completed for user @uid, file: @file', [
      '@uid' => $uid,
      '@file' => $filepath,
    ]);

    $recordCounts = [];
    foreach ($exportData as $section => $data) {
      if ($section === 'export_metadata') {
        continue;
      }
      $recordCounts[] = [$section, is_array($data) ? count($data) : 0];
    }

    $this->io()->table(['Section', 'Records'], $recordCounts);
    $this->io()->success("Export saved to: $filepath");
  }

  /**
   * Anonymize a user's personal data (GDPR Art. 17 - Right to Erasure).
   *
   * Replaces personally identifiable information with anonymized values
   * while preserving anonymous statistical data. Deletes AI conversation
   * logs and personal documents. The user entity is NOT deleted.
   *
   * @command gdpr:anonymize
   * @aliases gdpr-anon
   * @option force Skip confirmation prompt.
   * @usage gdpr:anonymize 42
   *   Anonymize all personal data for user 42.
   * @usage gdpr:anonymize 42 --force
   *   Anonymize without confirmation prompt.
   */
  public function anonymize(string $uid, array $options = ['force' => FALSE]): void {
    $uid = (int) $uid;

    $this->io()->title("GDPR Anonymization (Art. 17) - User $uid");

    // Load the user entity.
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user) {
      $this->io()->error("User with UID $uid not found.");
      return;
    }

    // Prevent anonymizing UID 0 (anonymous) or UID 1 (admin).
    if ($uid <= 1) {
      $this->io()->error('Cannot anonymize the anonymous user (UID 0) or the admin user (UID 1).');
      return;
    }

    $this->io()->warning("This will irreversibly anonymize ALL personal data for:");
    $this->io()->listing([
      "UID: $uid",
      "Username: {$user->getAccountName()}",
      "Email: {$user->getEmail()}",
    ]);

    $this->io()->text('The following actions will be performed:');
    $this->io()->listing([
      'Replace username with anonymized identifier',
      'Replace email with anonymized address',
      'Clear all profile fields',
      'Anonymize billing references',
      'Delete AI conversation logs',
      'Delete personal documents',
      'Preserve anonymous statistical data',
    ]);

    // Confirmation prompt.
    if (!$options['force']) {
      $confirm = $this->io()->confirm(
        'This action is IRREVERSIBLE. Are you sure you want to proceed?',
        FALSE
      );
      if (!$confirm) {
        $this->io()->text('Anonymization cancelled.');
        return;
      }
    }

    $anonymizedId = 'anon_' . hash('sha256', (string) $uid . microtime());
    $anonymizedId = substr($anonymizedId, 0, 32);
    $stats = [
      'user_fields' => 0,
      'profiles' => 0,
      'billing_refs' => 0,
      'ai_logs_deleted' => 0,
      'documents_deleted' => 0,
    ];

    // 1. Anonymize user entity fields.
    $this->io()->text('  Anonymizing user account...');
    $user->setUsername($anonymizedId);
    $user->setEmail($anonymizedId . '@anonymized.invalid');
    $user->block();
    // Clear optional user fields if they exist.
    foreach (['field_first_name', 'field_last_name', 'field_phone', 'field_address', 'field_bio'] as $field) {
      if ($user->hasField($field)) {
        $user->set($field, NULL);
        $stats['user_fields']++;
      }
    }
    $user->save();
    $stats['user_fields'] += 3; // username + email + status

    // 2. Anonymize profile entities.
    $this->io()->text('  Anonymizing profile data...');
    $stats['profiles'] = $this->anonymizeProfileData($uid, $anonymizedId);

    // 3. Anonymize billing references.
    $this->io()->text('  Anonymizing billing references...');
    $stats['billing_refs'] = $this->anonymizeBillingData($uid, $anonymizedId);

    // 4. Delete AI conversation logs.
    $this->io()->text('  Deleting AI conversation logs...');
    $stats['ai_logs_deleted'] = $this->deleteAiLogs($uid);

    // 5. Delete personal documents.
    $this->io()->text('  Deleting personal documents...');
    $stats['documents_deleted'] = $this->deletePersonalDocuments($uid);

    // Log the anonymization in audit log.
    $this->auditLog->log('gdpr.anonymization', [
      'target_type' => 'user',
      'target_id' => $uid,
      'severity' => 'warning',
      'details' => [
        'anonymized_id' => $anonymizedId,
        'stats' => $stats,
      ],
    ]);

    $this->logger->notice('GDPR anonymization completed for user @uid (now @anon_id)', [
      '@uid' => $uid,
      '@anon_id' => $anonymizedId,
    ]);

    $this->io()->table(
      ['Category', 'Items Processed'],
      [
        ['User fields anonymized', $stats['user_fields']],
        ['Profiles anonymized', $stats['profiles']],
        ['Billing references anonymized', $stats['billing_refs']],
        ['AI logs deleted', $stats['ai_logs_deleted']],
        ['Documents deleted', $stats['documents_deleted']],
      ]
    );

    $this->io()->success("User $uid has been anonymized. Anonymized ID: $anonymizedId");
  }

  /**
   * Generate a GDPR compliance report.
   *
   * Shows statistics about users, data requests, and anonymization
   * activity across the platform.
   *
   * @command gdpr:report
   * @aliases gdpr-rpt
   * @option format Output format (table or json).
   * @usage gdpr:report
   *   Display GDPR compliance overview.
   * @usage gdpr:report --format=json
   *   Output report as JSON.
   */
  public function report(array $options = ['format' => 'table']): void {
    $this->io()->title('GDPR Compliance Report');
    $this->io()->text('Generated: ' . date('Y-m-d H:i:s T'));
    $this->io()->newLine();

    $userStorage = $this->entityTypeManager->getStorage('user');

    // Count total users (excluding anonymous UID 0).
    $totalUsers = (int) $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', 0, '>')
      ->count()
      ->execute();

    // Count active users.
    $activeUsers = (int) $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', 0, '>')
      ->condition('status', 1)
      ->count()
      ->execute();

    // Count anonymized users (by email pattern).
    $anonymizedUsers = (int) $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('mail', '%@anonymized.invalid', 'LIKE')
      ->count()
      ->execute();

    // Collect audit log data for GDPR events.
    $exportRequests = 0;
    $anonymizationRequests = 0;
    $lastAnonymizationDate = 'N/A';
    $pendingRequests = [];

    try {
      $auditStorage = $this->entityTypeManager->getStorage('audit_log');

      // Count export requests.
      $exportRequests = (int) $auditStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'gdpr.data_export')
        ->count()
        ->execute();

      // Count anonymization requests.
      $anonymizationRequests = (int) $auditStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'gdpr.anonymization')
        ->count()
        ->execute();

      // Get last anonymization date.
      $lastAnonymizationIds = $auditStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'gdpr.anonymization')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($lastAnonymizationIds)) {
        $lastAnonymization = $auditStorage->load(reset($lastAnonymizationIds));
        if ($lastAnonymization && method_exists($lastAnonymization, 'getCreatedTime')) {
          $lastAnonymizationDate = date('Y-m-d H:i:s', (int) $lastAnonymization->getCreatedTime());
        }
      }

      // Pending data requests (export requests in the last 30 days
      // for users not yet anonymized).
      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);
      $recentExportIds = $auditStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'gdpr.data_export')
        ->condition('created', $thirtyDaysAgo, '>=')
        ->sort('created', 'DESC')
        ->range(0, 20)
        ->execute();

      if (!empty($recentExportIds)) {
        $recentExports = $auditStorage->loadMultiple($recentExportIds);
        foreach ($recentExports as $export) {
          $targetId = NULL;
          if (method_exists($export, 'get') && $export->hasField('target_id')) {
            $targetId = $export->get('target_id')->value;
          }
          if ($targetId) {
            $exportUser = $userStorage->load($targetId);
            if ($exportUser && strpos($exportUser->getEmail(), '@anonymized.invalid') === FALSE) {
              $createdTime = method_exists($export, 'getCreatedTime')
                ? date('Y-m-d', (int) $export->getCreatedTime())
                : 'unknown';
              $pendingRequests[] = [
                'uid' => $targetId,
                'date' => $createdTime,
                'status' => 'Exported (not anonymized)',
              ];
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->io()->note('Audit log entity not available. Some statistics may be incomplete.');
      $this->logger->warning('GDPR report: audit_log query failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    if ($options['format'] === 'json') {
      $reportData = [
        'generated_at' => date('c'),
        'users' => [
          'total' => $totalUsers,
          'active' => $activeUsers,
          'blocked' => $totalUsers - $activeUsers,
          'anonymized' => $anonymizedUsers,
        ],
        'gdpr_activity' => [
          'export_requests' => $exportRequests,
          'anonymization_requests' => $anonymizationRequests,
          'last_anonymization' => $lastAnonymizationDate,
        ],
        'pending_requests' => $pendingRequests,
      ];
      $this->output()->writeln(json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      return;
    }

    // User statistics table.
    $this->io()->section('User Statistics');
    $this->io()->table(
      ['Metric', 'Count'],
      [
        ['Total users', $totalUsers],
        ['Active users', $activeUsers],
        ['Blocked users', $totalUsers - $activeUsers],
        ['Anonymized users', $anonymizedUsers],
      ]
    );

    // GDPR activity table.
    $this->io()->section('GDPR Activity');
    $this->io()->table(
      ['Metric', 'Value'],
      [
        ['Data export requests (total)', $exportRequests],
        ['Anonymization requests (total)', $anonymizationRequests],
        ['Last anonymization date', $lastAnonymizationDate],
      ]
    );

    // Pending requests.
    $this->io()->section('Pending Data Requests (last 30 days)');
    if (!empty($pendingRequests)) {
      $this->io()->table(
        ['UID', 'Export Date', 'Status'],
        array_map(function ($r) {
          return [$r['uid'], $r['date'], $r['status']];
        }, $pendingRequests)
      );
    }
    else {
      $this->io()->text('No pending data requests.');
    }

    $this->io()->success('GDPR compliance report generated successfully.');
  }

  /**
   * Collect profile entity data for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of profile data.
   */
  protected function collectProfileData(int $uid): array {
    $profiles = [];

    // Check for profile entities (profile module).
    try {
      $profileStorage = $this->entityTypeManager->getStorage('profile');
      $profileIds = $profileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->execute();

      if (!empty($profileIds)) {
        $profileEntities = $profileStorage->loadMultiple($profileIds);
        foreach ($profileEntities as $profile) {
          $profileData = [
            'profile_id' => (int) $profile->id(),
            'type' => $profile->bundle(),
            'status' => $profile->isPublished() ? 'active' : 'inactive',
            'created' => date('c', (int) $profile->getCreatedTime()),
            'fields' => [],
          ];

          // Export all field values.
          foreach ($profile->getFields() as $fieldName => $field) {
            if (strpos($fieldName, 'field_') === 0) {
              $profileData['fields'][$fieldName] = $field->getValue();
            }
          }

          $profiles[] = $profileData;
        }
      }
    }
    catch (\Exception $e) {
      // Profile module may not be installed.
      $this->logger->info('GDPR export: profile entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $profiles;
  }

  /**
   * Collect billing data for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of billing records.
   */
  protected function collectBillingData(int $uid): array {
    $billing = [];

    // Billing invoices.
    try {
      $invoiceStorage = $this->entityTypeManager->getStorage('billing_invoice');
      $invoiceIds = $invoiceStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($invoiceIds)) {
        $invoices = $invoiceStorage->loadMultiple($invoiceIds);
        foreach ($invoices as $invoice) {
          $billing[] = [
            'type' => 'invoice',
            'id' => (int) $invoice->id(),
            'status' => $invoice->get('status')->value ?? 'unknown',
            'amount' => $invoice->get('amount')->value ?? NULL,
            'created' => method_exists($invoice, 'getCreatedTime')
              ? date('c', (int) $invoice->getCreatedTime())
              : NULL,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR export: billing_invoice entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    // Billing usage records.
    try {
      $usageStorage = $this->entityTypeManager->getStorage('billing_usage_record');
      $usageIds = $usageStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($usageIds)) {
        $usageRecords = $usageStorage->loadMultiple($usageIds);
        foreach ($usageRecords as $record) {
          $billing[] = [
            'type' => 'usage_record',
            'id' => (int) $record->id(),
            'metric' => $record->get('metric')->value ?? 'unknown',
            'quantity' => $record->get('quantity')->value ?? NULL,
            'created' => method_exists($record, 'getCreatedTime')
              ? date('c', (int) $record->getCreatedTime())
              : NULL,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR export: billing_usage_record entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $billing;
  }

  /**
   * Collect AI interaction data for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of AI interaction records.
   */
  protected function collectAiInteractionData(int $uid): array {
    $interactions = [];

    // AI conversation sessions (collaboration_session entity).
    try {
      $sessionStorage = $this->entityTypeManager->getStorage('collaboration_session');
      $sessionIds = $sessionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($sessionIds)) {
        $sessions = $sessionStorage->loadMultiple($sessionIds);
        foreach ($sessions as $session) {
          $interactions[] = [
            'type' => 'ai_session',
            'id' => (int) $session->id(),
            'status' => $session->get('status')->value ?? 'unknown',
            'created' => method_exists($session, 'getCreatedTime')
              ? date('c', (int) $session->getCreatedTime())
              : NULL,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR export: collaboration_session entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $interactions;
  }

  /**
   * Collect analytics event data for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of analytics events.
   */
  protected function collectAnalyticsData(int $uid): array {
    $analytics = [];

    // Check for analytics entities (funnel, cohort membership, etc).
    $entityTypes = ['funnel_definition', 'cohort_definition', 'custom_report'];

    foreach ($entityTypes as $entityType) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityType);
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $uid)
          ->execute();

        if (!empty($ids)) {
          $entities = $storage->loadMultiple($ids);
          foreach ($entities as $entity) {
            $analytics[] = [
              'type' => $entityType,
              'id' => (int) $entity->id(),
              'label' => method_exists($entity, 'label') ? $entity->label() : NULL,
              'created' => method_exists($entity, 'getCreatedTime')
                ? date('c', (int) $entity->getCreatedTime())
                : NULL,
            ];
          }
        }
      }
      catch (\Exception $e) {
        // Entity type may not exist - this is expected.
      }
    }

    return $analytics;
  }

  /**
   * Collect audit log entries for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of audit log entries.
   */
  protected function collectAuditLogData(int $uid): array {
    $entries = [];

    try {
      $auditStorage = $this->entityTypeManager->getStorage('audit_log');
      $auditIds = $auditStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('actor_id', $uid)
        ->sort('created', 'DESC')
        ->range(0, 500)
        ->execute();

      if (!empty($auditIds)) {
        $auditEntities = $auditStorage->loadMultiple($auditIds);
        foreach ($auditEntities as $entry) {
          $entries[] = [
            'id' => (int) $entry->id(),
            'event_type' => $entry->get('event_type')->value ?? 'unknown',
            'severity' => $entry->get('severity')->value ?? 'info',
            'ip_address' => $entry->get('ip_address')->value ?? NULL,
            'created' => method_exists($entry, 'getCreatedTime')
              ? date('c', (int) $entry->getCreatedTime())
              : NULL,
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR export: audit_log query failed for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $entries;
  }

  /**
   * Anonymize profile data for a user.
   *
   * @param int $uid
   *   The user ID.
   * @param string $anonymizedId
   *   The anonymized identifier.
   *
   * @return int
   *   Number of profiles anonymized.
   */
  protected function anonymizeProfileData(int $uid, string $anonymizedId): int {
    $count = 0;

    try {
      $profileStorage = $this->entityTypeManager->getStorage('profile');
      $profileIds = $profileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->execute();

      if (!empty($profileIds)) {
        $profiles = $profileStorage->loadMultiple($profileIds);
        foreach ($profiles as $profile) {
          // Clear all custom fields.
          foreach ($profile->getFields() as $fieldName => $field) {
            if (strpos($fieldName, 'field_') === 0) {
              $profile->set($fieldName, NULL);
            }
          }
          $profile->save();
          $count++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR anonymize: profile entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $count;
  }

  /**
   * Anonymize billing references for a user.
   *
   * @param int $uid
   *   The user ID.
   * @param string $anonymizedId
   *   The anonymized identifier.
   *
   * @return int
   *   Number of billing records anonymized.
   */
  protected function anonymizeBillingData(int $uid, string $anonymizedId): int {
    $count = 0;

    // Anonymize billing customer entity if it exists.
    try {
      $customerStorage = $this->entityTypeManager->getStorage('billing_customer');
      $customerIds = $customerStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($customerIds)) {
        $customers = $customerStorage->loadMultiple($customerIds);
        foreach ($customers as $customer) {
          // Remove PII from billing customer but preserve Stripe reference
          // for financial audit trail (anonymized).
          if ($customer->hasField('customer_name')) {
            $customer->set('customer_name', $anonymizedId);
          }
          if ($customer->hasField('customer_email')) {
            $customer->set('customer_email', $anonymizedId . '@anonymized.invalid');
          }
          if ($customer->hasField('billing_address')) {
            $customer->set('billing_address', NULL);
          }
          if ($customer->hasField('phone')) {
            $customer->set('phone', NULL);
          }
          $customer->save();
          $count++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR anonymize: billing_customer entity not available for user @uid', [
        '@uid' => $uid,
      ]);
    }

    // Anonymize invoice references.
    try {
      $invoiceStorage = $this->entityTypeManager->getStorage('billing_invoice');
      $invoiceIds = $invoiceStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($invoiceIds)) {
        $invoices = $invoiceStorage->loadMultiple($invoiceIds);
        foreach ($invoices as $invoice) {
          if ($invoice->hasField('customer_name')) {
            $invoice->set('customer_name', $anonymizedId);
          }
          if ($invoice->hasField('customer_email')) {
            $invoice->set('customer_email', $anonymizedId . '@anonymized.invalid');
          }
          $invoice->save();
          $count++;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR anonymize: billing_invoice anonymization not possible for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $count;
  }

  /**
   * Delete AI conversation logs for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return int
   *   Number of AI logs deleted.
   */
  protected function deleteAiLogs(int $uid): int {
    $count = 0;

    try {
      $sessionStorage = $this->entityTypeManager->getStorage('collaboration_session');
      $sessionIds = $sessionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($sessionIds)) {
        $sessions = $sessionStorage->loadMultiple($sessionIds);
        $sessionStorage->delete($sessions);
        $count = count($sessions);
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR anonymize: collaboration_session deletion not possible for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $count;
  }

  /**
   * Delete personal documents for a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return int
   *   Number of documents deleted.
   */
  protected function deletePersonalDocuments(int $uid): int {
    $count = 0;

    // Delete tenant documents owned by the user.
    try {
      $docStorage = $this->entityTypeManager->getStorage('tenant_document');
      $docIds = $docStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $uid)
        ->execute();

      if (!empty($docIds)) {
        $documents = $docStorage->loadMultiple($docIds);
        $docStorage->delete($documents);
        $count = count($documents);
      }
    }
    catch (\Exception $e) {
      $this->logger->info('GDPR anonymize: tenant_document deletion not possible for user @uid', [
        '@uid' => $uid,
      ]);
    }

    return $count;
  }

}
