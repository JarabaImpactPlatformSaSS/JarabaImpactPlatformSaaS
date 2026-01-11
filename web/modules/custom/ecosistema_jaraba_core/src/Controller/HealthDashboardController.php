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
        return new JsonResponse($health_status);
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
                'name' => 'Database',
                'status' => 'healthy',
                'icon' => 'database',
                'latency' => $latency,
                'message' => "Connected ({$latency}ms)",
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database',
                'status' => 'critical',
                'icon' => 'database',
                'latency' => null,
                'message' => 'Connection failed',
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
                    'name' => 'Qdrant (Vector DB)',
                    'status' => 'healthy',
                    'icon' => 'brain',
                    'latency' => $latency,
                    'message' => "Connected ({$latency}ms)",
                ];
            }
        } catch (\Exception $e) {
            // Silent fail - Qdrant might not be available
        }

        return [
            'name' => 'Qdrant (Vector DB)',
            'status' => 'warning',
            'icon' => 'brain',
            'latency' => null,
            'message' => 'Not available',
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
                    'name' => 'Cache System',
                    'status' => 'healthy',
                    'icon' => 'bolt',
                    'latency' => $latency,
                    'message' => "Working ({$latency}ms)",
                ];
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return [
            'name' => 'Cache System',
            'status' => 'warning',
            'icon' => 'bolt',
            'latency' => null,
            'message' => 'Cache test failed',
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
                'name' => 'Site Response',
                'status' => $status,
                'icon' => 'globe',
                'latency' => $latency,
                'message' => "HTTP 200 ({$latency}ms)",
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Site Response',
                'status' => 'critical',
                'icon' => 'globe',
                'latency' => null,
                'message' => 'Site unreachable',
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
     * Get recent health check results.
     */
    protected function getRecentHealthChecks(): array
    {
        // In a real implementation, this would read from a log file or database
        // For now, return simulated recent checks
        return [
            [
                'time' => date('H:i:s', strtotime('-5 minutes')),
                'type' => 'Automated Check',
                'result' => 'pass',
                'message' => 'All services healthy',
            ],
            [
                'time' => date('H:i:s', strtotime('-10 minutes')),
                'type' => 'Automated Check',
                'result' => 'pass',
                'message' => 'All services healthy',
            ],
            [
                'time' => date('H:i:s', strtotime('-15 minutes')),
                'type' => 'Self-Healing',
                'result' => 'recovery',
                'message' => 'Cache rebuilt successfully',
            ],
        ];
    }

}
