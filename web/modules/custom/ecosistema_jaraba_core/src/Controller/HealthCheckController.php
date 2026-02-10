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

        return new JsonResponse($response, $statusCode, [
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
     */
    protected function checkQdrant(): array
    {
        try {
            $qdrantHost = getenv('QDRANT_HOST') ?: 'localhost';
            $qdrantPort = getenv('QDRANT_PORT') ?: '6333';
            $url = "http://{$qdrantHost}:{$qdrantPort}/healthz";

            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'method' => 'GET',
                ],
            ]);

            $result = @file_get_contents($url, FALSE, $context);

            return [
                'status' => ($result !== FALSE) ? 'ok' : 'warning',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Qdrant unavailable',
            ];
        }
    }

}
