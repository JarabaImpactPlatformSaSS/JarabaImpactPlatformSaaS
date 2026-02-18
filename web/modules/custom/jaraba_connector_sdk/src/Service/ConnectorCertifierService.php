<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_integrations\Entity\Connector;
use Psr\Log\LoggerInterface;

/**
 * Connector certification pipeline service.
 *
 * Manages the full certification lifecycle for third-party connectors:
 * submission, automated testing, approval/rejection, and suspension.
 *
 * Certification statuses stored in the Connector entity's publish_status
 * field use extended values: 'testing', 'certified', 'suspended' in
 * addition to the base 'draft' / 'published' / 'deprecated'.
 */
class ConnectorCertifierService {

  /**
   * Certification-specific status constants.
   */
  public const STATUS_TESTING = 'testing';
  public const STATUS_CERTIFIED = 'certified';
  public const STATUS_SUSPENDED = 'suspended';

  /**
   * Constructs the ConnectorCertifierService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Submits a connector for certification.
   *
   * Changes the connector status to 'testing' and validates its manifest.
   *
   * @param int $connectorId
   *   The connector entity ID.
   * @param int $developerId
   *   The developer (user) ID submitting the connector.
   *
   * @return array
   *   Certification request details with keys:
   *   - connector_id: int
   *   - developer_id: int
   *   - status: string
   *   - manifest_valid: bool
   *   - submitted_at: int (timestamp)
   */
  public function submitForCertification(int $connectorId, int $developerId): array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'connector_id' => $connectorId,
        'developer_id' => $developerId,
        'status' => 'error',
        'error' => 'Connector not found.',
      ];
    }

    // Validate the manifest before accepting submission.
    $manifest = $this->extractManifest($connector);
    $manifestErrors = $this->validateManifest($manifest);
    $manifestValid = empty($manifestErrors);

    // Transition status to testing.
    $connector->set('publish_status', self::STATUS_TESTING);
    $connector->save();

    $this->logger->notice('Connector @name submitted for certification by developer @dev.', [
      '@name' => $connector->getName(),
      '@dev' => $developerId,
    ]);

    return [
      'connector_id' => $connectorId,
      'developer_id' => $developerId,
      'status' => self::STATUS_TESTING,
      'manifest_valid' => $manifestValid,
      'manifest_errors' => $manifestErrors,
      'submitted_at' => time(),
    ];
  }

  /**
   * Runs automated certification tests on a connector.
   *
   * Tests include security scan, performance test, and API compliance.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array
   *   Test results with keys:
   *   - connector_id: int
   *   - tests: array of [name => string, passed => bool, details => string]
   *   - all_passed: bool
   *   - executed_at: int (timestamp)
   */
  public function runTests(int $connectorId): array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'connector_id' => $connectorId,
        'tests' => [],
        'all_passed' => FALSE,
        'error' => 'Connector not found.',
      ];
    }

    $tests = [];

    // Security scan: verify no dangerous patterns in config schema.
    $tests[] = $this->runSecurityScan($connector);

    // Performance test: verify manifest declares reasonable limits.
    $tests[] = $this->runPerformanceTest($connector);

    // API compliance: verify required manifest fields are present.
    $tests[] = $this->runApiComplianceTest($connector);

    $allPassed = !in_array(FALSE, array_column($tests, 'passed'), TRUE);

    $this->logger->notice('Certification tests for @name: @result.', [
      '@name' => $connector->getName(),
      '@result' => $allPassed ? 'ALL PASSED' : 'SOME FAILED',
    ]);

    return [
      'connector_id' => $connectorId,
      'tests' => $tests,
      'all_passed' => $allPassed,
      'executed_at' => time(),
    ];
  }

  /**
   * Certifies a connector (marks it as certified and published).
   *
   * Only succeeds if all automated tests pass.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return bool
   *   TRUE if the connector was certified.
   */
  public function certify(int $connectorId): bool {
    $testResults = $this->runTests($connectorId);

    if (!$testResults['all_passed']) {
      $this->logger->warning('Cannot certify connector @id: tests did not pass.', [
        '@id' => $connectorId,
      ]);
      return FALSE;
    }

    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    $connector->set('publish_status', self::STATUS_CERTIFIED);
    $connector->save();

    $this->logger->notice('Connector @name certified successfully.', [
      '@name' => $connector->getName(),
    ]);

    return TRUE;
  }

  /**
   * Suspends a certified connector.
   *
   * @param int $connectorId
   *   The connector entity ID.
   * @param string $reason
   *   The suspension reason.
   *
   * @return bool
   *   TRUE if the connector was suspended.
   */
  public function suspend(int $connectorId, string $reason): bool {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return FALSE;
    }

    $connector->set('publish_status', self::STATUS_SUSPENDED);
    $connector->save();

    $this->logger->warning('Connector @name suspended. Reason: @reason.', [
      '@name' => $connector->getName(),
      '@reason' => $reason,
    ]);

    return TRUE;
  }

  /**
   * Returns the current certification status of a connector.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array
   *   Status array with keys:
   *   - connector_id: int
   *   - status: string
   *   - name: string
   *   - version: string
   */
  public function getCertificationStatus(int $connectorId): array {
    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'connector_id' => $connectorId,
        'status' => 'not_found',
      ];
    }

    return [
      'connector_id' => $connectorId,
      'status' => $connector->getPublishStatus(),
      'name' => $connector->getName(),
      'version' => $connector->get('version')->value ?? '0.0.0',
    ];
  }

  /**
   * Loads a Connector entity by ID.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector|null
   *   The connector entity, or NULL if not found.
   */
  protected function loadConnector(int $connectorId): ?Connector {
    $storage = $this->entityTypeManager->getStorage('connector');
    $entity = $storage->load($connectorId);
    return $entity instanceof Connector ? $entity : NULL;
  }

  /**
   * Extracts a manifest array from a Connector entity.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   The connector entity.
   *
   * @return array
   *   The manifest data.
   */
  protected function extractManifest(Connector $connector): array {
    return [
      'connector' => [
        'machine_name' => $connector->get('machine_name')->value ?? '',
        'display_name' => $connector->getName(),
        'version' => $connector->get('version')->value ?? '',
        'category' => $connector->getCategory(),
      ],
      'sdk_version' => '1.0.0',
    ];
  }

  /**
   * Validates a connector manifest.
   *
   * @param array $manifest
   *   The manifest data.
   *
   * @return array
   *   Array of error strings (empty if valid).
   */
  protected function validateManifest(array $manifest): array {
    $errors = [];

    if (empty($manifest['connector']['machine_name'])) {
      $errors[] = 'Machine name is required.';
    }

    if (empty($manifest['connector']['display_name'])) {
      $errors[] = 'Display name is required.';
    }

    if (empty($manifest['connector']['version'])) {
      $errors[] = 'Version is required.';
    }

    if (!isset($manifest['sdk_version'])) {
      $errors[] = 'SDK version is required.';
    }

    return $errors;
  }

  /**
   * Runs the security scan certification test.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   The connector entity.
   *
   * @return array
   *   Test result with keys: name, passed, details.
   */
  protected function runSecurityScan(Connector $connector): array {
    $configSchema = $connector->getConfigSchema();
    $passed = TRUE;
    $details = 'No security issues detected.';

    // Check for dangerous patterns in config schema values.
    $schemaJson = json_encode($configSchema);
    if ($schemaJson !== FALSE) {
      $dangerousPatterns = ['eval(', 'exec(', 'system(', 'passthru(', 'shell_exec('];
      foreach ($dangerousPatterns as $pattern) {
        if (str_contains($schemaJson, $pattern)) {
          $passed = FALSE;
          $details = 'Dangerous function pattern detected in config schema: ' . $pattern;
          break;
        }
      }
    }

    return [
      'name' => 'security_scan',
      'passed' => $passed,
      'details' => $details,
    ];
  }

  /**
   * Runs the performance certification test.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   The connector entity.
   *
   * @return array
   *   Test result with keys: name, passed, details.
   */
  protected function runPerformanceTest(Connector $connector): array {
    $configSchema = $connector->getConfigSchema();
    $fieldCount = is_array($configSchema) ? count($configSchema) : 0;

    // Connectors with excessively large config schemas may be problematic.
    $passed = $fieldCount <= 50;
    $details = $passed
      ? 'Config schema has ' . $fieldCount . ' fields (within limits).'
      : 'Config schema has ' . $fieldCount . ' fields (exceeds 50 field limit).';

    return [
      'name' => 'performance_test',
      'passed' => $passed,
      'details' => $details,
    ];
  }

  /**
   * Runs the API compliance certification test.
   *
   * @param \Drupal\jaraba_integrations\Entity\Connector $connector
   *   The connector entity.
   *
   * @return array
   *   Test result with keys: name, passed, details.
   */
  protected function runApiComplianceTest(Connector $connector): array {
    $errors = [];

    if (empty($connector->getName())) {
      $errors[] = 'Missing connector name.';
    }

    if (empty($connector->get('machine_name')->value)) {
      $errors[] = 'Missing machine name.';
    }

    if (empty($connector->get('version')->value)) {
      $errors[] = 'Missing version.';
    }

    if (empty($connector->getCategory())) {
      $errors[] = 'Missing category.';
    }

    $passed = empty($errors);
    $details = $passed
      ? 'All required API fields present.'
      : 'Missing fields: ' . implode(', ', $errors);

    return [
      'name' => 'api_compliance',
      'passed' => $passed,
      'details' => $details,
    ];
  }

}
