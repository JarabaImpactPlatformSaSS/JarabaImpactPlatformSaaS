<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_site_builder\Entity\SeoPageConfig;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión SEO centralizado.
 *
 * Proporciona CRUD para SeoPageConfig, gestión de meta tags,
 * canonical URLs y directivas robots por página.
 *
 * Fase 4 Doc 179: SEO Manager.
 */
class SeoManagerService
{

    use StringTranslationTrait;

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected SeoAuditorService $seoAuditor,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene el ID del tenant actual.
     */
    protected function getTenantId(): ?int
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        return $tenant ? (int) $tenant->id() : NULL;
    }

    /**
     * Obtiene o crea la configuración SEO de una página.
     *
     * @param int $pageId
     *   ID de la página (page_content).
     *
     * @return \Drupal\jaraba_site_builder\Entity\SeoPageConfig|null
     *   La configuración SEO o NULL si no hay tenant.
     */
    public function getOrCreateConfig(int $pageId): ?SeoPageConfig
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            return NULL;
        }

        $config = $this->getConfigByPageId($pageId);
        if ($config) {
            return $config;
        }

        // Crear configuración por defecto.
        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        /** @var \Drupal\jaraba_site_builder\Entity\SeoPageConfig $config */
        $config = $storage->create([
            'tenant_id' => $tenantId,
            'page_id' => $pageId,
            'robots' => 'index,follow',
            'schema_type' => 'WebPage',
            'twitter_card' => 'summary_large_image',
        ]);

        // Intentar poblar desde la página asociada.
        $page = $this->entityTypeManager->getStorage('page_content')->load($pageId);
        if ($page) {
            if ($page->hasField('meta_title') && !empty($page->get('meta_title')->value)) {
                $config->set('meta_title', $page->get('meta_title')->value);
            }
            if ($page->hasField('meta_description') && !empty($page->get('meta_description')->value)) {
                $config->set('meta_description', $page->get('meta_description')->value);
            }
            if ($page->hasField('path_alias') && !empty($page->get('path_alias')->value)) {
                $config->set('canonical_url', $page->get('path_alias')->value);
            }
        }

        $config->save();
        $this->logger->info('SeoPageConfig creada para página @page del tenant @tenant.', [
            '@page' => $pageId,
            '@tenant' => $tenantId,
        ]);

        return $config;
    }

    /**
     * Obtiene la configuración SEO de una página por ID.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return \Drupal\jaraba_site_builder\Entity\SeoPageConfig|null
     *   La configuración SEO o NULL.
     */
    public function getConfigByPageId(int $pageId): ?SeoPageConfig
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        $results = $storage->loadByProperties([
            'page_id' => $pageId,
            'tenant_id' => $tenantId,
        ]);

        if (empty($results)) {
            return NULL;
        }

        return reset($results);
    }

    /**
     * Actualiza la configuración SEO de una página.
     *
     * @param int $pageId
     *   ID de la página.
     * @param array $data
     *   Datos a actualizar.
     *
     * @return \Drupal\jaraba_site_builder\Entity\SeoPageConfig|null
     *   La configuración actualizada.
     */
    public function updateConfig(int $pageId, array $data): ?SeoPageConfig
    {
        $config = $this->getOrCreateConfig($pageId);
        if (!$config) {
            return NULL;
        }

        $allowedFields = [
            'meta_title', 'meta_description', 'canonical_url', 'robots',
            'keywords', 'og_title', 'og_description', 'twitter_card',
            'schema_type', 'schema_custom_json', 'hreflang_config',
            'geo_region', 'geo_position',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $config->set($field, $data[$field]);
            }
        }

        $config->save();
        return $config;
    }

    /**
     * Ejecuta auditoría SEO de una página y actualiza el score.
     *
     * @param int $pageId
     *   ID de la página (page_content).
     *
     * @return array
     *   Resultado de la auditoría con score, issues, checks.
     */
    public function auditPage(int $pageId): array
    {
        $page = $this->entityTypeManager->getStorage('page_content')->load($pageId);
        if (!$page) {
            return ['score' => 0, 'issues' => [], 'checks' => []];
        }

        $result = $this->seoAuditor->audit($page);

        // Actualizar el score en la configuración SEO.
        $config = $this->getOrCreateConfig($pageId);
        if ($config) {
            $config->set('last_audit_score', $result['score']);
            $config->set('last_audit_date', \Drupal::time()->getRequestTime());
            $config->save();
        }

        return $result;
    }

    /**
     * Genera los meta tags HTML para inyectar en <head>.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Array de meta tags para #attached html_head.
     */
    public function generateMetaTags(int $pageId): array
    {
        $config = $this->getConfigByPageId($pageId);
        if (!$config) {
            return [];
        }

        $tags = [];

        // Meta description.
        $metaDesc = $config->getMetaDescription();
        if (!empty($metaDesc)) {
            $tags[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => ['name' => 'description', 'content' => $metaDesc],
                ],
                'seo_description',
            ];
        }

        // Robots.
        $robots = $config->getRobots();
        if ($robots !== 'index,follow') {
            $tags[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => ['name' => 'robots', 'content' => $robots],
                ],
                'seo_robots',
            ];
        }

        // Keywords.
        $keywords = $config->get('keywords')->value;
        if (!empty($keywords)) {
            $tags[] = [
                [
                    '#tag' => 'meta',
                    '#attributes' => ['name' => 'keywords', 'content' => $keywords],
                ],
                'seo_keywords',
            ];
        }

        // Canonical.
        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $tags[] = [
                [
                    '#tag' => 'link',
                    '#attributes' => ['rel' => 'canonical', 'href' => $canonical],
                ],
                'seo_canonical',
            ];
        }

        // Open Graph.
        $ogTitle = $config->getOgTitle() ?: $config->getMetaTitle();
        if (!empty($ogTitle)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['property' => 'og:title', 'content' => $ogTitle]],
                'seo_og_title',
            ];
        }

        $ogDesc = $config->getOgDescription() ?: $config->getMetaDescription();
        if (!empty($ogDesc)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['property' => 'og:description', 'content' => $ogDesc]],
                'seo_og_description',
            ];
        }

        $tags[] = [
            ['#tag' => 'meta', '#attributes' => ['property' => 'og:type', 'content' => 'website']],
            'seo_og_type',
        ];

        // Twitter Card.
        $twitterCard = $config->getTwitterCard();
        $tags[] = [
            ['#tag' => 'meta', '#attributes' => ['name' => 'twitter:card', 'content' => $twitterCard]],
            'seo_twitter_card',
        ];

        // Geo meta tags.
        $geoRegion = $config->getGeoRegion();
        if (!empty($geoRegion)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'geo.region', 'content' => $geoRegion]],
                'seo_geo_region',
            ];
        }

        $geoPosition = $config->getGeoPosition();
        if (!empty($geoPosition)) {
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'geo.position', 'content' => $geoPosition]],
                'seo_geo_position',
            ];
            // ICBM meta tag (legacy but still used).
            $tags[] = [
                ['#tag' => 'meta', '#attributes' => ['name' => 'ICBM', 'content' => str_replace(';', ', ', $geoPosition)]],
                'seo_icbm',
            ];
        }

        return $tags;
    }

    /**
     * Genera las etiquetas hreflang para inyectar en <head>.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Array de link tags para #attached html_head_link.
     */
    public function generateHreflangTags(int $pageId): array
    {
        $config = $this->getConfigByPageId($pageId);
        if (!$config) {
            return [];
        }

        $hreflangEntries = $config->getHreflangConfig();
        if (empty($hreflangEntries)) {
            return [];
        }

        $tags = [];
        foreach ($hreflangEntries as $entry) {
            if (!empty($entry['lang']) && !empty($entry['url'])) {
                $tags[] = [
                    [
                        '#tag' => 'link',
                        '#attributes' => [
                            'rel' => 'alternate',
                            'hreflang' => $entry['lang'],
                            'href' => $entry['url'],
                        ],
                    ],
                    'seo_hreflang_' . $entry['lang'],
                ];
            }
        }

        return $tags;
    }

    /**
     * Lista todas las configuraciones SEO del tenant actual.
     *
     * @return array
     *   Array de SeoPageConfig serializadas.
     */
    public function listConfigs(): array
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            return [];
        }

        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

        $result = [];
        foreach ($configs as $config) {
            /** @var \Drupal\jaraba_site_builder\Entity\SeoPageConfig $config */
            $result[] = $this->serializeConfig($config);
        }

        return $result;
    }

    /**
     * Serializa una configuración SEO para respuesta API.
     *
     * @param \Drupal\jaraba_site_builder\Entity\SeoPageConfig $config
     *   La configuración.
     *
     * @return array
     *   Datos serializados.
     */
    public function serializeConfig(SeoPageConfig $config): array
    {
        return [
            'id' => (int) $config->id(),
            'page_id' => $config->getPageId(),
            'meta_title' => $config->getMetaTitle(),
            'meta_description' => $config->getMetaDescription(),
            'canonical_url' => $config->getCanonicalUrl(),
            'robots' => $config->getRobots(),
            'keywords' => $config->getKeywordsArray(),
            'og_title' => $config->getOgTitle(),
            'og_description' => $config->getOgDescription(),
            'twitter_card' => $config->getTwitterCard(),
            'schema_type' => $config->getSchemaType(),
            'schema_custom_json' => $config->getSchemaCustomJson(),
            'hreflang_config' => $config->getHreflangConfig(),
            'geo_region' => $config->getGeoRegion(),
            'geo_position' => $config->getGeoPosition(),
            'last_audit_score' => $config->getLastAuditScore(),
            'last_audit_date' => $config->get('last_audit_date')->value,
            'changed' => $config->get('changed')->value,
        ];
    }

}
