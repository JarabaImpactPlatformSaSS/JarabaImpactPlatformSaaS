<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Endpoint público de health check para CI/CD y monitoring externo.
 *
 * Ruta: /health (acceso anónimo, sin caché).
 * Diseñado para ser consumido por:
 * - GitHub Actions deploy.yml smoke test
 * - UptimeRobot / Pingdom / similar
 * - Load balancer health checks (Blue-Green deploy)
 *
 * Devuelve HTTP 200 si los componentes críticos están operativos,
 * HTTP 503 si algún componente crítico falla.
 *
 * @see \Drupal\ecosistema_jaraba_core\Controller\HealthDashboardController
 *   Para el dashboard administrativo completo en /admin/health.
 */
class HealthCheckController extends ControllerBase
{

    /**
     * Ejecuta health checks ligeros y devuelve estado JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con status, timestamp y componentes verificados.
     *   HTTP 200 si healthy, HTTP 503 si algún componente crítico falla.
     */
    public function check(): JsonResponse
    {
        $components = [];
        $healthy = TRUE;

        // Componente 1: Base de datos (crítico).
        $components['database'] = $this->checkDatabase();
        if ($components['database']['status'] !== 'ok') {
            $healthy = FALSE;
        }

        // Componente 2: Sistema de archivos (crítico).
        $components['filesystem'] = $this->checkFilesystem();
        if ($components['filesystem']['status'] !== 'ok') {
            $healthy = FALSE;
        }

        // Componente 3: Caché Drupal (no crítico).
        $components['cache'] = $this->checkCache();

        // Componente 4: Qdrant vectorial (no crítico).
        $components['qdrant'] = $this->checkQdrant();

        // Componente 5: AI Copilot service chain (no crítico).
        // AI-SERVICE-CHAIN-001: Detect broken transitive dependencies
        // that cause 503 "El servicio de IA no está disponible".
        $components['ai_copilot'] = $this->checkAiCopilot();

        $response = [
            'status' => $healthy ? 'ok' : 'degraded',
            'timestamp' => gmdate('c'),
            'version' => \Drupal::VERSION,
            'components' => $components,
        ];

        $statusCode = $healthy ? 200 : 503;

        // AUDIT-CONS-N08: Standardized JSON envelope.
        return new JsonResponse([
            'success' => $healthy,
            'data' => $response,
            'meta' => ['timestamp' => time()],
        ], $statusCode, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Health-Check' => 'jaraba-saas',
        ]);
    }

    /**
     * Verifica conectividad a la base de datos.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(TRUE);
            \Drupal::database()->query('SELECT 1')->fetchField();
            $latency = round((microtime(TRUE) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
        }
    }

    /**
     * Verifica escritura en el sistema de archivos público.
     */
    protected function checkFilesystem(): array
    {
        try {
            $fileSystem = \Drupal::service('file_system');
            $tempFile = $fileSystem->getTempDirectory() . '/health_check_' . time();
            file_put_contents($tempFile, 'ok');
            $content = file_get_contents($tempFile);
            @unlink($tempFile);

            return [
                'status' => ($content === 'ok') ? 'ok' : 'error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Filesystem write failed',
            ];
        }
    }

    /**
     * Verifica el sistema de caché de Drupal.
     */
    protected function checkCache(): array
    {
        try {
            $cache = \Drupal::cache();
            $testKey = 'health_check_' . time();
            $cache->set($testKey, 'ok', time() + 60);
            $item = $cache->get($testKey);
            $cache->delete($testKey);

            return [
                'status' => ($item && $item->data === 'ok') ? 'ok' : 'warning',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Cache unavailable',
            ];
        }
    }

    /**
     * Verifica conectividad con Qdrant (no crítico).
     *
     * Soporta Qdrant Cloud (HTTPS + Api-Key) leyendo config de Drupal.
     * Si el servicio jaraba_rag.qdrant_client existe, usa su método ping().
     * Fallback: file_get_contents con stream context HTTPS.
     */
    protected function checkQdrant(): array
    {
        try {
            $config = \Drupal::config('jaraba_rag.settings');
            $disabled = $config->get('disabled');

            if ($disabled) {
                return [
                    'status' => 'skipped',
                    'message' => 'RAG/Qdrant disabled by configuration',
                ];
            }

            $host = $config->get('vector_db.host');
            if (empty($host)) {
                return [
                    'status' => 'skipped',
                    'message' => 'Qdrant host not configured',
                ];
            }

            $start = microtime(TRUE);

            // Try using the QdrantDirectClient service if available.
            if (\Drupal::hasService('jaraba_rag.qdrant_client')) {
                /** @var \Drupal\jaraba_rag\Client\QdrantDirectClient $client */
                $client = \Drupal::service('jaraba_rag.qdrant_client');
                $ok = $client->ping();
                $latency = round((microtime(TRUE) - $start) * 1000, 2);

                return [
                    'status' => $ok ? 'ok' : 'warning',
                    'latency_ms' => $latency,
                ];
            }

            // Fallback: direct HTTPS request with Api-Key header.
            $url = rtrim($host, '/') . '/';
            $apiKey = $config->get('vector_db.api_key') ?: '';
            $headers = "Content-Type: application/json\r\n";
            if (!empty($apiKey)) {
                $headers .= "Api-Key: {$apiKey}\r\n";
            }

            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET',
                    'header' => $headers,
                ],
                'ssl' => [
                    'verify_peer' => TRUE,
                ],
            ]);

            $result = @file_get_contents($url, FALSE, $context);
            $latency = round((microtime(TRUE) - $start) * 1000, 2);

            return [
                'status' => ($result !== FALSE) ? 'ok' : 'warning',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Qdrant unavailable',
            ];
        }
    }

    /**
     * Verifica la cadena de servicios del copilot de IA (no crítico).
     *
     * AI-SERVICE-CHAIN-001: Un argumento faltante en cualquier dependencia
     * transitiva (ej: LegalSearchService sin @current_user) rompe toda la
     * cadena del orquestador → 503 para todos los usuarios.
     */
    protected function checkAiCopilot(): array
    {
        $services = [
            'jaraba_copilot_v2.copilot_orchestrator',
            'jaraba_copilot_v2.streaming_orchestrator',
        ];
        $broken = [];

        foreach ($services as $serviceId) {
            try {
                if (\Drupal::hasService($serviceId)) {
                    \Drupal::service($serviceId);
                }
                // Optional services that are not registered are OK.
            } catch (\Throwable $e) {
                $broken[] = $serviceId;
            }
        }

        if (!empty($broken)) {
            return [
                'status' => 'warning',
                'message' => 'Broken: ' . implode(', ', $broken),
            ];
        }

        return ['status' => 'ok'];
    }

    /**
     * STATUS-REPORT-PROACTIVE-001 Layer 3: Status report API endpoint.
     *
     * Token-protected: requires X-Admin-Token header matching
     * JARABA_ADMIN_TOKEN environment variable.
     *
     * Returns full Drupal status report as JSON for remote AI agents.
     */
    public function statusReport(Request $request): JsonResponse {
        // Token authentication (SECRET-MGMT-001: token from env, not config).
        $expectedToken = getenv('JARABA_ADMIN_TOKEN');
        $providedToken = $request->headers->get('X-Admin-Token', '');

        if (!$expectedToken || !hash_equals($expectedToken, $providedToken)) {
            return new JsonResponse([
                'error' => 'Unauthorized',
                'message' => 'Valid X-Admin-Token header required.',
            ], 403);
        }

        // Baseline of expected warnings (same as validate-status-report.php).
        $baseline = [
            'ecosistema_jaraba_base_domain',
            'experimental_modules',
            'update_contrib',
            'update_core',
        ];

        // Invoke hook_requirements for runtime phase.
        $moduleHandler = \Drupal::moduleHandler();
        foreach (array_keys($moduleHandler->getModuleList()) as $module) {
            $moduleHandler->loadInclude($module, 'install');
        }

        $requirements = [];
        try {
            $requirements = $moduleHandler->invokeAll('requirements', ['runtime']);
        }
        catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Requirements invocation failed',
                'message' => $e->getMessage(),
            ], 500);
        }

        // Classify results.
        $errors = [];
        $unexpectedWarnings = [];
        $baselineWarnings = [];
        $okCount = 0;

        foreach ($requirements as $key => $item) {
            $severity = $item['severity'] ?? REQUIREMENT_OK;

            if ($severity === REQUIREMENT_ERROR) {
                $errors[] = [
                    'key' => $key,
                    'title' => (string) ($item['title'] ?? $key),
                    'value' => trim((string) ($item['value'] ?? '')),
                    'description' => trim((string) ($item['description'] ?? '')),
                ];
            }
            elseif ($severity === REQUIREMENT_WARNING) {
                if (in_array($key, $baseline, TRUE)) {
                    $baselineWarnings[] = (string) ($item['title'] ?? $key);
                }
                else {
                    $unexpectedWarnings[] = [
                        'key' => $key,
                        'title' => (string) ($item['title'] ?? $key),
                        'value' => trim((string) ($item['value'] ?? '')),
                    ];
                }
            }
            else {
                $okCount++;
            }
        }

        $status = $errors !== [] ? 'errors' : ($unexpectedWarnings !== [] ? 'warnings' : 'clean');

        return new JsonResponse([
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'status' => $status,
            'total_checks' => count($requirements),
            'ok_count' => $okCount,
            'errors' => $errors,
            'unexpected_warnings' => $unexpectedWarnings,
            'baseline_warnings' => $baselineWarnings,
        ], $errors !== [] ? 503 : 200);
    }

}
