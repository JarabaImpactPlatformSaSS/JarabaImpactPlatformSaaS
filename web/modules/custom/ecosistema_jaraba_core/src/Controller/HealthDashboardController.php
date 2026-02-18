<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the Platform Health Dashboard.
 *
 * Provides a premium UX dashboard showing:
 * - Real-time service status (Database, Qdrant, Cache)
 * - Recent health check results
 * - System metrics
 * - Quick actions for recovery
 */
class HealthDashboardController extends ControllerBase
{

    /**
     * Renders the health dashboard page.
     *
     * @return array
     *   A render array for the health dashboard.
     */
    public function dashboard()
    {
        $health_status = $this->getHealthStatus();

        return [
            '#theme' => 'health_dashboard',
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/health-dashboard',
                ],
            ],
            '#services' => $health_status['services'],
            '#metrics' => $health_status['metrics'],
            '#recent_checks' => $health_status['recent_checks'],
            '#last_updated' => date('Y-m-d H:i:s'),
            '#cache' => [
                'max-age' => 30, // Cache for 30 seconds
            ],
        ];
    }

    /**
     * API endpoint for real-time health status.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with current health status.
     */
    public function healthApi()
    {
        $health_status = $this->getHealthStatus();
        return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $health_status, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Gets the current health status of all services.
     *
     * @return array
     *   Array with services, metrics, and recent checks.
     */
    protected function getHealthStatus(): array
    {
        $services = [];
        $metrics = [];

        // Check Database
        $services['database'] = $this->checkDatabase();

        // Check Qdrant
        $services['qdrant'] = $this->checkQdrant();

        // Check Drupal Cache
        $services['cache'] = $this->checkCache();

        // Check Site Response
        $services['site'] = $this->checkSiteResponse();

        // Calculate overall health
        $healthy_count = count(array_filter($services, fn($s) => $s['status'] === 'healthy'));
        $total_count = count($services);

        $metrics['overall_health'] = round(($healthy_count / $total_count) * 100);
        $metrics['services_up'] = $healthy_count;
        $metrics['services_total'] = $total_count;
        $metrics['uptime'] = $this->getUptime();

        // Log health checks to database and check for alerts
        $this->logAndAlertHealthChecks($services, $metrics['overall_health']);

        // Get recent health checks from logs
        $recent_checks = $this->getRecentHealthChecks();

        return [
            'services' => $services,
            'metrics' => $metrics,
            'recent_checks' => $recent_checks,
            'timestamp' => time(),
        ];
    }

    /**
     * Log health checks and send alerts if status changed to critical.
     */
    protected function logAndAlertHealthChecks(array $services, int $overall_health): void
    {
        $state = \Drupal::state();
        $previous_states = $state->get('jaraba_health_previous_states', []);
        $alerts_to_send = [];

        foreach ($services as $key => $service) {
            // Log the check
            $this->logHealthCheck(
                $key,
                $service['status'],
                $service['latency'] ?? null,
                $service['message'] ?? null,
                $overall_health
            );

            // Check for status change to critical (for alerts)
            $previous_status = $previous_states[$key] ?? 'healthy';
            if ($service['status'] === 'critical' && $previous_status !== 'critical') {
                $alerts_to_send[] = [
                    'service' => $service['name'],
                    'status' => $service['status'],
                    'message' => $service['message'],
                ];
            }

            // Update previous state
            $previous_states[$key] = $service['status'];
        }

        // Save previous states
        $state->set('jaraba_health_previous_states', $previous_states);

        // Send alerts if any
        if (!empty($alerts_to_send)) {
            $this->sendHealthAlerts($alerts_to_send);
        }
    }

    /**
     * Send email alerts for critical service failures.
     */
    protected function sendHealthAlerts(array $alerts): void
    {
        // Rate limiting - don't send more than 1 alert per 5 minutes
        $state = \Drupal::state();
        $last_alert = $state->get('jaraba_health_last_alert', 0);

        if (time() - $last_alert < 300) {
            // Rate limited, skip
            return;
        }

        // Get site email
        $site_mail = \Drupal::config('system.site')->get('mail');
        if (empty($site_mail)) {
            return;
        }

        // Build alert message
        $alert_messages = [];
        foreach ($alerts as $alert) {
            $alert_messages[] = sprintf(
                "- %s: %s (%s)",
                $alert['service'],
                $alert['status'],
                $alert['message']
            );
        }

        $body = $this->t("Health Alert - Critical services detected:\n\n@alerts\n\nTimestamp: @time\n\nVisit /admin/health for details.", [
            '@alerts' => implode("\n", $alert_messages),
            '@time' => date('Y-m-d H:i:s'),
        ]);

        // Send email using Drupal mail system
        try {
            $mailManager = \Drupal::service('plugin.manager.mail');
            $langcode = \Drupal::currentUser()->getPreferredLangcode();

            $params = [
                'subject' => $this->t('[ALERT] Jaraba Platform Health Issue'),
                'body' => $body,
            ];

            $mailManager->mail(
                'ecosistema_jaraba_core',
                'health_alert',
                $site_mail,
                $langcode,
                $params,
                NULL,
                TRUE
            );

            // Update last alert time
            $state->set('jaraba_health_last_alert', time());

            \Drupal::logger('ecosistema_jaraba_core')->warning('Health alert sent: @services', [
                '@services' => implode(', ', array_column($alerts, 'service')),
            ]);

        } catch (\Exception $e) {
            \Drupal::logger('ecosistema_jaraba_core')->error('Failed to send health alert: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Logs a single health check to the database.
     */
    protected function logHealthCheck(string $service_key, string $status, ?float $latency, ?string $message, int $overall_health): void
    {
        try {
            $database = \Drupal::database();

            // Ensure the table exists. If not, create it.
            if (!$database->schema()->tableExists('health_check_log')) {
                $schema = [
                    'description' => 'Stores health check results for monitoring.',
                    'fields' => [
                        'id' => [
                            'type' => 'serial',
                            'not null' => TRUE,
                            'unsigned' => TRUE,
                            'description' => 'Primary Key: Unique log ID.',
                        ],
                        'timestamp' => [
                            'type' => 'int',
                            'not null' => TRUE,
                            'unsigned' => TRUE,
                            'description' => 'Timestamp of the check.',
                        ],
                        'service' => [
                            'type' => 'varchar',
                            'length' => 64,
                            'not null' => TRUE,
                            'description' => 'Name of the service checked.',
                        ],
                        'status' => [
                            'type' => 'varchar',
                            'length' => 16,
                            'not null' => TRUE,
                            'description' => 'Status (healthy, warning, critical).',
                        ],
                        'latency' => [
                            'type' => 'float',
                            'not null' => FALSE,
                            'description' => 'Latency in milliseconds.',
                        ],
                        'message' => [
                            'type' => 'varchar',
                            'length' => 255,
                            'not null' => FALSE,
                            'description' => 'Detailed message.',
                        ],
                        'overall_health' => [
                            'type' => 'int',
                            'not null' => TRUE,
                            'unsigned' => TRUE,
                            'description' => 'Overall health percentage at the time of check.',
                        ],
                    ],
                    'primary key' => ['id'],
                    'indexes' => [
                        'timestamp' => ['timestamp'],
                        'service_status' => ['service', 'status'],
                    ],
                ];
                $database->schema()->createTable('health_check_log', $schema);
            }

            $database->insert('health_check_log')
                ->fields([
                    'timestamp' => time(),
                    'service' => $service_key,
                    'status' => $status,
                    'latency' => $latency,
                    'message' => $message,
                    'overall_health' => $overall_health,
                ])
                ->execute();
        } catch (\Exception $e) {
            \Drupal::logger('ecosistema_jaraba_core')->error('Failed to log health check for @service: @error', [
                '@service' => $service_key,
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            $connection = \Drupal::database();
            $start = microtime(true);
            $connection->query('SELECT 1')->fetchField();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'name' => $this->t('Database'),
                'status' => 'healthy',
                'icon' => 'database',
                'latency' => $latency,
                'message' => $this->t('Connected (@latency ms)', ['@latency' => $latency]),
            ];
        } catch (\Exception $e) {
            return [
                'name' => $this->t('Database'),
                'status' => 'critical',
                'icon' => 'database',
                'latency' => null,
                'message' => $this->t('Connection failed'),
            ];
        }
    }

    /**
     * Check Qdrant connectivity.
     */
    protected function checkQdrant(): array
    {
        $qdrant_url = 'http://qdrant:6333/';

        try {
            $client = \Drupal::httpClient();
            $start = microtime(true);
            $response = $client->get($qdrant_url, [
                'timeout' => 3,
                'connect_timeout' => 2,
            ]);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($response->getStatusCode() === 200) {
                return [
                    'name' => $this->t('Qdrant (Vector DB)'),
                    'status' => 'healthy',
                    'icon' => 'brain',
                    'latency' => $latency,
                    'message' => $this->t('Connected (@latency ms)', ['@latency' => $latency]),
                ];
            }
        } catch (\Exception $e) {
            // Silent fail - Qdrant might not be available
        }

        return [
            'name' => $this->t('Qdrant (Vector DB)'),
            'status' => 'warning',
            'icon' => 'brain',
            'latency' => null,
            'message' => $this->t('Not available'),
        ];
    }

    /**
     * Check Drupal cache status.
     */
    protected function checkCache(): array
    {
        try {
            $cache = \Drupal::cache();
            $test_key = 'health_check_' . time();

            $start = microtime(true);
            $cache->set($test_key, 'test', time() + 60);
            $result = $cache->get($test_key);
            $cache->delete($test_key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($result && $result->data === 'test') {
                return [
                    'name' => $this->t('Cache System'),
                    'status' => 'healthy',
                    'icon' => 'bolt',
                    'latency' => $latency,
                    'message' => $this->t('Working (@latency ms)', ['@latency' => $latency]),
                ];
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return [
            'name' => $this->t('Cache System'),
            'status' => 'warning',
            'icon' => 'bolt',
            'latency' => null,
            'message' => $this->t('Cache test failed'),
        ];
    }

    /**
     * Check site response time.
     */
    protected function checkSiteResponse(): array
    {
        global $base_url;

        try {
            $client = \Drupal::httpClient();
            $start = microtime(true);
            $response = $client->get($base_url, [
                'timeout' => 10,
                'verify' => false,
            ]);
            $latency = round((microtime(true) - $start) * 1000, 2);

            $status = 'healthy';
            if ($latency > 3000) {
                $status = 'critical';
            } elseif ($latency > 1000) {
                $status = 'warning';
            }

            return [
                'name' => $this->t('Site Response'),
                'status' => $status,
                'icon' => 'globe',
                'latency' => $latency,
                'message' => $this->t('HTTP 200 (@latency ms)', ['@latency' => $latency]),
            ];
        } catch (\Exception $e) {
            return [
                'name' => $this->t('Site Response'),
                'status' => 'critical',
                'icon' => 'globe',
                'latency' => null,
                'message' => $this->t('Site unreachable'),
            ];
        }
    }

    /**
     * Get system uptime estimate.
     */
    protected function getUptime(): string
    {
        $state = \Drupal::state();
        $first_seen = $state->get('jaraba_first_health_check');

        if (!$first_seen) {
            $first_seen = time();
            $state->set('jaraba_first_health_check', $first_seen);
        }

        $uptime_seconds = time() - $first_seen;

        if ($uptime_seconds < 3600) {
            return round($uptime_seconds / 60) . ' min';
        } elseif ($uptime_seconds < 86400) {
            return round($uptime_seconds / 3600, 1) . ' hours';
        } else {
            return round($uptime_seconds / 86400, 1) . ' days';
        }
    }

    /**
     * Get recent health check results from database.
     */
    protected function getRecentHealthChecks(): array
    {
        $checks = [];

        try {
            $database = \Drupal::database();

            // Check if table exists
            if (!$database->schema()->tableExists('health_check_log')) {
                // Return fallback data if table doesn't exist yet
                return $this->getFallbackHealthChecks();
            }

            $results = $database->select('health_check_log', 'h')
                ->fields('h', ['timestamp', 'service', 'status', 'message'])
                ->orderBy('timestamp', 'DESC')
                ->range(0, 10)
                ->execute()
                ->fetchAll();

            foreach ($results as $row) {
                $result = 'pass';
                if ($row->status === 'critical') {
                    $result = 'fail';
                } elseif ($row->status === 'warning') {
                    $result = 'recovery';
                }

                $checks[] = [
                    'time' => date('H:i:s', $row->timestamp),
                    'type' => $this->t('Health Check'),
                    'result' => $result,
                    'message' => $row->message ?? $this->t('@service: @status', [
                        '@service' => $row->service,
                        '@status' => $row->status,
                    ]),
                ];
            }

            // If no records, return fallback
            if (empty($checks)) {
                return $this->getFallbackHealthChecks();
            }

        } catch (\Exception $e) {
            // Fallback to simulated data on error
            return $this->getFallbackHealthChecks();
        }

        return $checks;
    }

    /**
     * Fallback health checks when DB table not available.
     */
    protected function getFallbackHealthChecks(): array
    {
        return [
            [
                'time' => date('H:i:s'),
                'type' => $this->t('System Start'),
                'result' => 'pass',
                'message' => $this->t('Health monitoring initialized'),
            ],
        ];
    }

}


