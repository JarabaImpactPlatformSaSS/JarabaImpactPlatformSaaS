<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\AuditLogService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Compliance Dashboard (G115-1).
 *
 * Provides a security and compliance overview showing:
 * - Compliance controls status (SOC2, ISO27001, ENS, GDPR)
 * - Recent audit log events
 * - Security headers verification
 * - Aggregate compliance statistics
 *
 * Route: /admin/seguridad
 */
class ComplianceDashboardController extends ControllerBase {

  /**
   * Constructor with dependency injection.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\AuditLogService $auditLog
   *   The audit log service.
   */
  /**
   * Servicio de audit log.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\AuditLogService
   */
  protected AuditLogService $auditLog;

  /**
   * Constructor con inyeccion de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AuditLogService $auditLog,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->auditLog = $auditLog;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.audit_log'),
    );
  }

  /**
   * Renders the compliance dashboard page.
   *
   * @return array
   *   A render array for the compliance dashboard.
   */
  public function dashboard(): array {
    $controls = $this->getControlsStatus();
    $recent_events = $this->getRecentAuditEvents();
    $security_headers = $this->checkSecurityHeaders();
    $stats = $this->getComplianceStats();

    return [
      '#theme' => 'compliance_dashboard',
      '#controls' => $controls,
      '#recent_events' => $recent_events,
      '#security_headers' => $security_headers,
      '#stats' => $stats,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/compliance-dashboard',
        ],
        'drupalSettings' => [
          'complianceDashboard' => [
            'refreshInterval' => 30000,
            'eventsEndpoint' => '/admin/seguridad/api/events',
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 30,
      ],
    ];
  }

  /**
   * Returns compliance controls status for each framework.
   *
   * @return array
   *   Array of control groups keyed by framework identifier.
   */
  protected function getControlsStatus(): array {
    return [
      'soc2' => [
        'label' => $this->t('SOC 2 Type II'),
        'description' => $this->t('Service Organization Control - Trust Services Criteria'),
        'controls' => [
          [
            'id' => 'SOC2-CC6.1',
            'name' => $this->t('Logical and Physical Access Controls'),
            'status' => $this->evaluateControl('access_controls'),
          ],
          [
            'id' => 'SOC2-CC6.2',
            'name' => $this->t('System Access Registration and Authorization'),
            'status' => $this->evaluateControl('user_registration'),
          ],
          [
            'id' => 'SOC2-CC6.3',
            'name' => $this->t('Access Removal'),
            'status' => $this->evaluateControl('access_removal'),
          ],
          [
            'id' => 'SOC2-CC7.2',
            'name' => $this->t('System Monitoring'),
            'status' => $this->evaluateControl('system_monitoring'),
          ],
          [
            'id' => 'SOC2-CC8.1',
            'name' => $this->t('Change Management'),
            'status' => $this->evaluateControl('change_management'),
          ],
        ],
      ],
      'iso27001' => [
        'label' => $this->t('ISO 27001:2022'),
        'description' => $this->t('Information Security Management System'),
        'controls' => [
          [
            'id' => 'A.5.1',
            'name' => $this->t('Information Security Policies'),
            'status' => $this->evaluateControl('security_policies'),
          ],
          [
            'id' => 'A.8.1',
            'name' => $this->t('User Endpoint Devices'),
            'status' => $this->evaluateControl('endpoint_security'),
          ],
          [
            'id' => 'A.8.5',
            'name' => $this->t('Secure Authentication'),
            'status' => $this->evaluateControl('secure_auth'),
          ],
          [
            'id' => 'A.8.9',
            'name' => $this->t('Configuration Management'),
            'status' => $this->evaluateControl('config_management'),
          ],
          [
            'id' => 'A.8.15',
            'name' => $this->t('Logging'),
            'status' => $this->evaluateControl('logging'),
          ],
          [
            'id' => 'A.8.24',
            'name' => $this->t('Use of Cryptography'),
            'status' => $this->evaluateControl('cryptography'),
          ],
        ],
      ],
      'ens' => [
        'label' => $this->t('ENS (Esquema Nacional de Seguridad)'),
        'description' => $this->t('Spanish National Security Framework - Royal Decree 311/2022'),
        'controls' => [
          [
            'id' => 'ENS-OP.ACC',
            'name' => $this->t('Access Control'),
            'status' => $this->evaluateControl('access_controls'),
          ],
          [
            'id' => 'ENS-OP.EXP',
            'name' => $this->t('Operation Exploitation'),
            'status' => $this->evaluateControl('system_monitoring'),
          ],
          [
            'id' => 'ENS-OP.MON',
            'name' => $this->t('Activity Monitoring'),
            'status' => $this->evaluateControl('logging'),
          ],
          [
            'id' => 'ENS-MP.COM',
            'name' => $this->t('Communications Protection'),
            'status' => $this->evaluateControl('transport_security'),
          ],
        ],
      ],
      'gdpr' => [
        'label' => $this->t('GDPR / RGPD'),
        'description' => $this->t('General Data Protection Regulation - EU 2016/679'),
        'controls' => [
          [
            'id' => 'GDPR-Art.5',
            'name' => $this->t('Data Processing Principles'),
            'status' => $this->evaluateControl('data_processing'),
          ],
          [
            'id' => 'GDPR-Art.25',
            'name' => $this->t('Data Protection by Design'),
            'status' => $this->evaluateControl('privacy_by_design'),
          ],
          [
            'id' => 'GDPR-Art.30',
            'name' => $this->t('Records of Processing Activities'),
            'status' => $this->evaluateControl('processing_records'),
          ],
          [
            'id' => 'GDPR-Art.32',
            'name' => $this->t('Security of Processing'),
            'status' => $this->evaluateControl('security_processing'),
          ],
          [
            'id' => 'GDPR-Art.33',
            'name' => $this->t('Breach Notification'),
            'status' => $this->evaluateControl('breach_notification'),
          ],
        ],
      ],
    ];
  }

  /**
   * Evaluates a specific security control.
   *
   * @param string $control_key
   *   The control identifier.
   *
   * @return string
   *   One of: 'pass', 'fail', 'warning'.
   */
  protected function evaluateControl(string $control_key): string {
    switch ($control_key) {
      case 'access_controls':
      case 'user_registration':
      case 'access_removal':
        // Verify role-based access and permission system is active.
        $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
        return count($roles) > 2 ? 'pass' : 'warning';

      case 'system_monitoring':
      case 'logging':
        // Verify audit logging is operational.
        return $this->auditLog->isOperational() ? 'pass' : 'fail';

      case 'change_management':
      case 'config_management':
        // Check if config management is active.
        $config_sync_dir = \Drupal::service('config.storage.sync');
        return !empty($config_sync_dir->listAll()) ? 'pass' : 'warning';

      case 'secure_auth':
        // Check HTTPS enforcement.
        $request = \Drupal::request();
        $is_https = $request->isSecure() || $request->headers->get('X-Forwarded-Proto') === 'https';
        return $is_https ? 'pass' : 'fail';

      case 'cryptography':
      case 'transport_security':
        // Check HSTS header is configured.
        $security_config = \Drupal::config('ecosistema_jaraba_core.security_headers');
        $hsts = $security_config->get('hsts_enabled');
        return $hsts ? 'pass' : 'warning';

      case 'security_policies':
      case 'endpoint_security':
        // These require manual assessment.
        return 'warning';

      case 'data_processing':
      case 'privacy_by_design':
      case 'processing_records':
      case 'security_processing':
        // GDPR controls - check basic privacy settings.
        $has_policy = \Drupal::config('system.site')->get('page.403') !== '';
        return $has_policy ? 'pass' : 'warning';

      case 'breach_notification':
        // Check alerting is configured.
        $alerting_config = \Drupal::config('ecosistema_jaraba_core.alerting');
        $has_webhook = !empty($alerting_config->get('slack_webhook'));
        return $has_webhook ? 'pass' : 'warning';

      default:
        return 'warning';
    }
  }

  /**
   * Retrieves the last 20 audit log events.
   *
   * @return array
   *   Array of recent audit event data.
   */
  protected function getRecentAuditEvents(): array {
    $events = [];

    try {
      $storage = $this->entityTypeManager->getStorage('audit_log');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('created', 'DESC')
        ->range(0, 20);
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          $events[] = [
            'id' => $entity->id(),
            'action' => $entity->get('action')->value ?? '',
            'entity_type' => $entity->get('entity_type_id')->value ?? '',
            'entity_id' => $entity->get('entity_id')->value ?? '',
            'user' => $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('System'),
            'severity' => $entity->get('severity')->value ?? 'info',
            'message' => $entity->get('message')->value ?? '',
            'ip_address' => $entity->get('ip_address')->value ?? '',
            'timestamp' => $entity->get('created')->value ?? 0,
            'timestamp_formatted' => \Drupal::service('date.formatter')->format(
              (int) ($entity->get('created')->value ?? 0),
              'short',
            ),
          ];
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('ecosistema_jaraba_core')->warning(
        'Failed to load audit log events: @error',
        ['@error' => $e->getMessage()],
      );
    }

    return $events;
  }

  /**
   * Checks the current state of security response headers.
   *
   * @return array
   *   Array of header statuses.
   */
  protected function checkSecurityHeaders(): array {
    $headers = [];
    $security_config = \Drupal::config('ecosistema_jaraba_core.security_headers');

    // Content-Security-Policy.
    $csp_enabled = (bool) $security_config->get('csp_enabled');
    $csp_policy = $security_config->get('csp_policy') ?? '';
    $headers['content_security_policy'] = [
      'name' => 'Content-Security-Policy',
      'enabled' => $csp_enabled,
      'status' => $csp_enabled && !empty($csp_policy) ? 'pass' : 'fail',
      'value' => $csp_enabled ? $csp_policy : $this->t('Not configured'),
      'description' => $this->t('Prevents XSS, clickjacking, and code injection attacks.'),
    ];

    // X-Frame-Options.
    $xfo_enabled = (bool) $security_config->get('x_frame_options_enabled');
    $xfo_value = $security_config->get('x_frame_options') ?? 'SAMEORIGIN';
    $headers['x_frame_options'] = [
      'name' => 'X-Frame-Options',
      'enabled' => $xfo_enabled,
      'status' => $xfo_enabled ? 'pass' : 'warning',
      'value' => $xfo_enabled ? $xfo_value : $this->t('Not configured'),
      'description' => $this->t('Prevents clickjacking by controlling iframe embedding.'),
    ];

    // Strict-Transport-Security.
    $hsts_enabled = (bool) $security_config->get('hsts_enabled');
    $hsts_max_age = $security_config->get('hsts_max_age') ?? 31536000;
    $headers['strict_transport_security'] = [
      'name' => 'Strict-Transport-Security',
      'enabled' => $hsts_enabled,
      'status' => $hsts_enabled ? 'pass' : 'fail',
      'value' => $hsts_enabled
        ? sprintf('max-age=%d; includeSubDomains', $hsts_max_age)
        : $this->t('Not configured'),
      'description' => $this->t('Enforces HTTPS connections to prevent downgrade attacks.'),
    ];

    // X-Content-Type-Options.
    $xcto_enabled = (bool) $security_config->get('x_content_type_options_enabled');
    $headers['x_content_type_options'] = [
      'name' => 'X-Content-Type-Options',
      'enabled' => $xcto_enabled,
      'status' => $xcto_enabled ? 'pass' : 'warning',
      'value' => $xcto_enabled ? 'nosniff' : $this->t('Not configured'),
      'description' => $this->t('Prevents MIME type sniffing.'),
    ];

    // Referrer-Policy.
    $rp_enabled = (bool) $security_config->get('referrer_policy_enabled');
    $rp_value = $security_config->get('referrer_policy') ?? 'strict-origin-when-cross-origin';
    $headers['referrer_policy'] = [
      'name' => 'Referrer-Policy',
      'enabled' => $rp_enabled,
      'status' => $rp_enabled ? 'pass' : 'warning',
      'value' => $rp_enabled ? $rp_value : $this->t('Not configured'),
      'description' => $this->t('Controls how much referrer information is included with requests.'),
    ];

    return $headers;
  }

  /**
   * Computes aggregate compliance statistics.
   *
   * @return array
   *   Array with total_audit_logs, critical_events, last_assessment.
   */
  protected function getComplianceStats(): array {
    $stats = [
      'total_audit_logs' => 0,
      'critical_events' => 0,
      'last_assessment' => $this->t('N/A'),
      'controls_passing' => 0,
      'controls_total' => 0,
      'compliance_score' => 0,
    ];

    // Count total audit logs.
    try {
      $storage = $this->entityTypeManager->getStorage('audit_log');
      $stats['total_audit_logs'] = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      // Count critical events (last 30 days).
      $thirty_days_ago = \Drupal::time()->getRequestTime() - (30 * 86400);
      $stats['critical_events'] = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('severity', 'critical')
        ->condition('created', $thirty_days_ago, '>=')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Entity type may not exist yet.
      \Drupal::logger('ecosistema_jaraba_core')->notice(
        'Audit log entity not available for stats: @error',
        ['@error' => $e->getMessage()],
      );
    }

    // Calculate controls summary.
    $controls = $this->getControlsStatus();
    foreach ($controls as $framework) {
      foreach ($framework['controls'] as $control) {
        $stats['controls_total']++;
        if ($control['status'] === 'pass') {
          $stats['controls_passing']++;
        }
      }
    }

    if ($stats['controls_total'] > 0) {
      $stats['compliance_score'] = (int) round(
        ($stats['controls_passing'] / $stats['controls_total']) * 100,
      );
    }

    // Last assessment date.
    $last_assessment = \Drupal::state()->get('jaraba_compliance_last_assessment');
    if ($last_assessment) {
      $stats['last_assessment'] = \Drupal::service('date.formatter')->format(
        (int) $last_assessment,
        'short',
      );
    }

    // Update the assessment timestamp.
    \Drupal::state()->set('jaraba_compliance_last_assessment', \Drupal::time()->getRequestTime());

    return $stats;
  }

}
