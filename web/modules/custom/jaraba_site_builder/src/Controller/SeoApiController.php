<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_site_builder\Service\GeoTargetingService;
use Drupal\jaraba_site_builder\Service\HreflangManagerService;
use Drupal\jaraba_site_builder\Service\SchemaGeneratorService;
use Drupal\jaraba_site_builder\Service\SeoAiAssistantService;
use Drupal\jaraba_site_builder\Service\SeoManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller REST API para SEO/GEO avanzado.
 *
 * Proporciona endpoints para gestión de configuración SEO, Schema.org,
 * hreflang, geo-targeting y sugerencias IA por página.
 *
 * API-NAMING-001: store() en vez de create() para POST.
 * DIR-15: Errores genéricos al frontend, detallados en log.
 *
 * Fase 4 Doc 179.
 */
class SeoApiController extends ControllerBase
{

    public function __construct(
        protected SeoManagerService $seoManager,
        protected SchemaGeneratorService $schemaGenerator,
        protected HreflangManagerService $hreflangManager,
        protected GeoTargetingService $geoTargeting,
        protected SeoAiAssistantService $seoAiAssistant,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_site_builder.seo_manager'),
            $container->get('jaraba_site_builder.schema_generator'),
            $container->get('jaraba_site_builder.hreflang_manager'),
            $container->get('jaraba_site_builder.geo_targeting'),
            $container->get('jaraba_site_builder.seo_ai_assistant'),
        );
    }

    // =========================================================================
    // SEO CONFIG ENDPOINTS
    // =========================================================================

    /**
     * GET /api/v1/seo/config/{page_id}
     *
     * Obtiene la configuración SEO de una página.
     */
    public function getSeoConfig(int $page_id): JsonResponse
    {
        try {
            $config = $this->seoManager->getOrCreateConfig($page_id);
            if (!$config) {
                return $this->errorResponse('Configuración SEO no disponible.', 404);
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => $this->seoManager->serializeConfig($config),
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'getSeoConfig', $page_id);
        }
    }

    /**
     * PUT /api/v1/seo/config/{page_id}
     *
     * Actualiza la configuración SEO de una página.
     */
    public function updateSeoConfig(int $page_id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            if (!is_array($data)) {
                return $this->errorResponse('Datos inválidos.', 400);
            }

            $config = $this->seoManager->updateConfig($page_id, $data);
            if (!$config) {
                return $this->errorResponse('No se pudo actualizar la configuración.', 500);
            }

            return new JsonResponse([
                'success' => TRUE,
                'data' => $this->seoManager->serializeConfig($config),
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'updateSeoConfig', $page_id);
        }
    }

    // =========================================================================
    // AI SUGGESTIONS ENDPOINT
    // =========================================================================

    /**
     * POST /api/v1/seo/suggestions
     *
     * Genera sugerencias de meta tags usando IA.
     */
    public function suggestMetaTags(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            $pageId = (int) ($data['page_id'] ?? 0);

            if ($pageId <= 0) {
                return $this->errorResponse('Se requiere page_id válido.', 400);
            }

            $suggestions = $this->seoAiAssistant->suggestMetaTags($pageId);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $suggestions,
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'suggestMetaTags');
        }
    }

    // =========================================================================
    // AUDIT ENDPOINTS
    // =========================================================================

    /**
     * POST /api/v1/seo/audit/{page_id}
     *
     * Ejecuta auditoría SEO de una página.
     */
    public function auditPage(int $page_id): JsonResponse
    {
        try {
            $result = $this->seoManager->auditPage($page_id);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $result,
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'auditPage', $page_id);
        }
    }

    /**
     * POST /api/v1/seo/bulk-audit
     *
     * Ejecuta auditoría SEO masiva.
     */
    public function bulkAudit(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            $pageIds = $data['page_ids'] ?? [];

            if (empty($pageIds) || !is_array($pageIds)) {
                return $this->errorResponse('Se requiere un array de page_ids.', 400);
            }

            // Limitar a 50 páginas por solicitud.
            $pageIds = array_slice(array_map('intval', $pageIds), 0, 50);

            $results = [];
            foreach ($pageIds as $pageId) {
                $results[$pageId] = $this->seoManager->auditPage($pageId);
            }

            // Calcular promedio.
            $scores = array_column($results, 'score');
            $averageScore = !empty($scores) ? (int) round(array_sum($scores) / count($scores)) : 0;

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'results' => $results,
                    'average_score' => $averageScore,
                    'total_pages' => count($results),
                ],
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'bulkAudit');
        }
    }

    // =========================================================================
    // SCHEMA.ORG ENDPOINT
    // =========================================================================

    /**
     * GET /api/v1/seo/schema/{page_id}
     *
     * Obtiene el Schema.org JSON-LD generado para una página.
     */
    public function getSchema(int $page_id, Request $request): JsonResponse
    {
        try {
            $config = $this->seoManager->getConfigByPageId($page_id);
            if (!$config) {
                return $this->errorResponse('Configuración SEO no encontrada.', 404);
            }

            $baseUrl = $request->getSchemeAndHttpHost();
            $schema = $this->schemaGenerator->generateForPage($config, $baseUrl);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $schema,
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'getSchema', $page_id);
        }
    }

    // =========================================================================
    // HREFLANG ENDPOINTS
    // =========================================================================

    /**
     * GET /api/v1/seo/hreflang/{page_id}
     *
     * Obtiene la configuración hreflang de una página.
     */
    public function getHreflang(int $page_id): JsonResponse
    {
        try {
            $config = $this->hreflangManager->getHreflangConfig($page_id);
            $languages = $this->hreflangManager->getAvailableLanguages();

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'entries' => $config,
                    'available_languages' => $languages,
                ],
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'getHreflang', $page_id);
        }
    }

    /**
     * PUT /api/v1/seo/hreflang/{page_id}
     *
     * Actualiza la configuración hreflang de una página.
     */
    public function updateHreflang(int $page_id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            $entries = $data['entries'] ?? [];

            if (!is_array($entries)) {
                return $this->errorResponse('Se requiere un array de entries.', 400);
            }

            $success = $this->hreflangManager->updateHreflangConfig($page_id, $entries);

            return new JsonResponse([
                'success' => $success,
                'data' => $success
                    ? $this->hreflangManager->getHreflangConfig($page_id)
                    : [],
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'updateHreflang', $page_id);
        }
    }

    // =========================================================================
    // GEO-TARGETING ENDPOINTS
    // =========================================================================

    /**
     * GET /api/v1/seo/geo/{page_id}
     *
     * Obtiene la configuración de geo-targeting de una página.
     */
    public function getGeoTargeting(int $page_id): JsonResponse
    {
        try {
            $geoConfig = $this->geoTargeting->getGeoConfig($page_id);
            $regions = $this->geoTargeting->getSpanishRegions();

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'config' => $geoConfig,
                    'available_regions' => $regions,
                ],
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'getGeoTargeting', $page_id);
        }
    }

    /**
     * PUT /api/v1/seo/geo/{page_id}
     *
     * Actualiza la configuración de geo-targeting de una página.
     */
    public function updateGeoTargeting(int $page_id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);
            if (!is_array($data)) {
                return $this->errorResponse('Datos inválidos.', 400);
            }

            $success = $this->geoTargeting->updateGeoConfig($page_id, $data);

            return new JsonResponse([
                'success' => $success,
                'data' => $success
                    ? $this->geoTargeting->getGeoConfig($page_id)
                    : [],
            ]);
        }
        catch (\Exception $e) {
            return $this->handleException($e, 'updateGeoTargeting', $page_id);
        }
    }

    // =========================================================================
    // ERROR HANDLING (DIR-15)
    // =========================================================================

    /**
     * Maneja excepciones con log detallado y respuesta genérica.
     */
    protected function handleException(\Exception $e, string $operation, int $pageId = 0): JsonResponse
    {
        $requestId = uniqid('seo_');

        $this->getLogger('jaraba_site_builder')->error(
            'Error en SEO API @op: @msg [Request: @id, Page: @page]',
            [
                '@op' => $operation,
                '@msg' => $e->getMessage(),
                '@id' => $requestId,
                '@page' => $pageId,
            ]
        );

        return $this->errorResponse(
            'Error procesando la solicitud SEO. Referencia: ' . $requestId,
            500
        );
    }

    /**
     * Genera una respuesta de error JSON.
     */
    protected function errorResponse(string $message, int $statusCode): JsonResponse
    {
        return new JsonResponse(
            ['success' => FALSE, 'error' => $message],
            $statusCode
        );
    }

}
