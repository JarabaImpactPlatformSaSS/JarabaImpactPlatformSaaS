<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

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

}
