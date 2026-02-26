<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_site_builder\Entity\SeoPageConfig;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generación de Schema.org JSON-LD.
 *
 * Genera datos estructurados JSON-LD para 7 tipos de Schema.org:
 * - WebPage, Article, BlogPosting, FAQPage, Product, LocalBusiness, Organization.
 *
 * Fase 4 Doc 179: Schema Generator.
 */
class SchemaGeneratorService
{

    use StringTranslationTrait;

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera JSON-LD completo para una página.
     *
     * @param \Drupal\jaraba_site_builder\Entity\SeoPageConfig $config
     *   Configuración SEO de la página.
     * @param string $baseUrl
     *   URL base del sitio.
     *
     * @return array
     *   Array con el JSON-LD y el tipo de schema.
     */
    public function generateForPage(SeoPageConfig $config, string $baseUrl = ''): array
    {
        $schemaType = $config->getSchemaType();
        $customJson = $config->getSchemaCustomJson();

        // Si hay JSON-LD personalizado, usarlo directamente.
        if (!empty($customJson)) {
            return [
                'type' => 'custom',
                'json_ld' => $customJson,
                'script' => $this->wrapInScript($customJson),
            ];
        }

        $jsonLd = match ($schemaType) {
            'Article', 'BlogPosting' => $this->generateArticle($config, $schemaType, $baseUrl),
            'FAQPage' => $this->generateFaqPage($config, $baseUrl),
            'Product' => $this->generateProduct($config, $baseUrl),
            'LocalBusiness' => $this->generateLocalBusiness($config, $baseUrl),
            'Organization' => $this->generateOrganization($config, $baseUrl),
            default => $this->generateWebPage($config, $baseUrl),
        };

        return [
            'type' => $schemaType,
            'json_ld' => $jsonLd,
            'script' => $this->wrapInScript($jsonLd),
        ];
    }

    /**
     * Genera Schema.org WebPage.
     */
    protected function generateWebPage(SeoPageConfig $config, string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $config->getMetaTitle() ?: ($siteConfig['site_name'] ?? ''),
            'description' => $config->getMetaDescription(),
        ];

        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $schema['url'] = $this->resolveUrl($canonical, $baseUrl);
        }

        if (!empty($siteConfig['site_name'])) {
            $schema['isPartOf'] = [
                '@type' => 'WebSite',
                'name' => $siteConfig['site_name'],
                'url' => $baseUrl,
            ];
        }

        $this->addBreadcrumbList($schema, $config, $baseUrl);

        return $schema;
    }

    /**
     * Genera Schema.org Article / BlogPosting.
     */
    protected function generateArticle(SeoPageConfig $config, string $type, string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => $config->getMetaTitle(),
            'description' => $config->getMetaDescription(),
        ];

        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $schema['url'] = $this->resolveUrl($canonical, $baseUrl);
        }

        // Publisher (organización del tenant).
        if (!empty($siteConfig['site_name'])) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => $siteConfig['site_name'],
            ];
            if (!empty($siteConfig['logo_url'])) {
                $schema['publisher']['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $siteConfig['logo_url'],
                ];
            }
        }

        // Keywords.
        $keywords = $config->getKeywordsArray();
        if (!empty($keywords)) {
            $schema['keywords'] = implode(', ', $keywords);
        }

        // GAP-AUD-006: Speakable specification for AI search engines.
        $schema['speakable'] = [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => [
                '.article-body__content h2',
                '.article-body__content p:first-of-type',
            ],
        ];

        return $schema;
    }

    /**
     * GAP-AUD-006: Genera Schema.org SoftwareApplication para el SaaS.
     *
     * @param string $baseUrl
     *   URL base del sitio.
     *
     * @return array
     *   Schema.org SoftwareApplication.
     */
    public function generateSoftwareApplication(string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $siteConfig['site_name'] ?? 'Jaraba Impact Platform',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $baseUrl,
            'description' => $siteConfig['site_tagline'] ?? 'Multi-vertical SaaS platform for impact ecosystems.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'EUR',
                'description' => 'Freemium tier available',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteConfig['site_name'] ?? 'Jaraba Impact Platform',
            ],
        ];
    }

    /**
     * Genera Schema.org FAQPage.
     */
    protected function generateFaqPage(SeoPageConfig $config, string $baseUrl): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'name' => $config->getMetaTitle(),
            'description' => $config->getMetaDescription(),
        ];

        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $schema['url'] = $this->resolveUrl($canonical, $baseUrl);
        }

        // mainEntity se pobla desde el contenido de la página (FAQ blocks).
        // Se añade vía schema_custom_json si existe contenido FAQ.
        $schema['mainEntity'] = [];

        return $schema;
    }

    /**
     * Genera Schema.org Product.
     */
    protected function generateProduct(SeoPageConfig $config, string $baseUrl): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $config->getMetaTitle(),
            'description' => $config->getMetaDescription(),
        ];

        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $schema['url'] = $this->resolveUrl($canonical, $baseUrl);
        }

        return $schema;
    }

    /**
     * Genera Schema.org LocalBusiness con datos de geo-targeting.
     */
    protected function generateLocalBusiness(SeoPageConfig $config, string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $config->getMetaTitle() ?: ($siteConfig['site_name'] ?? ''),
            'description' => $config->getMetaDescription(),
        ];

        $canonical = $config->getCanonicalUrl();
        if (!empty($canonical)) {
            $schema['url'] = $this->resolveUrl($canonical, $baseUrl);
        }

        // Geo coordinates.
        $geoPosition = $config->getGeoPosition();
        if (!empty($geoPosition)) {
            $parts = explode(';', $geoPosition);
            if (count($parts) === 2) {
                $schema['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => trim($parts[0]),
                    'longitude' => trim($parts[1]),
                ];
            }
        }

        // GAP-AUD-006: Enhanced areaServed with multiple regions.
        $geoRegion = $config->getGeoRegion();
        if (!empty($geoRegion)) {
            // Support comma-separated regions.
            $regions = array_map('trim', explode(',', $geoRegion));
            if (count($regions) === 1) {
                $schema['areaServed'] = [
                    '@type' => 'AdministrativeArea',
                    'name' => $regions[0],
                ];
            }
            else {
                $schema['areaServed'] = array_map(function (string $region) {
                    return [
                        '@type' => 'AdministrativeArea',
                        'name' => $region,
                    ];
                }, $regions);
            }
        }

        // GAP-AUD-006: Google Maps link if available via custom JSON.
        $customJson = $config->getSchemaCustomJson();
        if (!empty($customJson) && isset($customJson['hasMap'])) {
            $schema['hasMap'] = $customJson['hasMap'];
        }

        // Datos de contacto desde SiteConfig.
        if (!empty($siteConfig['contact_phone'])) {
            $schema['telephone'] = $siteConfig['contact_phone'];
        }
        if (!empty($siteConfig['contact_email'])) {
            $schema['email'] = $siteConfig['contact_email'];
        }
        if (!empty($siteConfig['contact_address'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $siteConfig['contact_address'],
            ];
        }

        return $schema;
    }

    /**
     * Genera Schema.org Organization.
     */
    protected function generateOrganization(SeoPageConfig $config, string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteConfig['site_name'] ?? $config->getMetaTitle(),
            'description' => $config->getMetaDescription(),
            'url' => $baseUrl,
        ];

        if (!empty($siteConfig['logo_url'])) {
            $schema['logo'] = $siteConfig['logo_url'];
        }

        // Social profiles desde sameAs.
        if (!empty($siteConfig['social_links'])) {
            $sameAs = [];
            $socialData = is_string($siteConfig['social_links'])
                ? json_decode($siteConfig['social_links'], TRUE) ?? []
                : (array) $siteConfig['social_links'];
            foreach ($socialData as $url) {
                if (is_string($url) && !empty($url)) {
                    $sameAs[] = $url;
                }
            }
            if (!empty($sameAs)) {
                $schema['sameAs'] = $sameAs;
            }
        }

        if (!empty($siteConfig['contact_phone'])) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $siteConfig['contact_phone'],
                'contactType' => 'customer service',
            ];
        }

        return $schema;
    }

    /**
     * Genera Schema.org WebSite (nivel sitio completo).
     *
     * @param string $baseUrl
     *   URL base del sitio.
     *
     * @return array
     *   Schema.org WebSite.
     */
    public function generateWebSite(string $baseUrl): array
    {
        $siteConfig = $this->getSiteConfig();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteConfig['site_name'] ?? '',
            'url' => $baseUrl,
        ];

        if (!empty($siteConfig['site_tagline'])) {
            $schema['description'] = $siteConfig['site_tagline'];
        }

        // SearchAction para Google Sitelinks Search Box.
        $schema['potentialAction'] = [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $baseUrl . '/buscar?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ];

        return $schema;
    }

    /**
     * Genera Schema.org BreadcrumbList.
     */
    public function generateBreadcrumbList(array $breadcrumbs, string $baseUrl): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        $position = 1;
        foreach ($breadcrumbs as $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['title'] ?? $crumb['name'] ?? '',
            ];
            if (!empty($crumb['url'])) {
                $item['item'] = $this->resolveUrl($crumb['url'], $baseUrl);
            }
            $schema['itemListElement'][] = $item;
            $position++;
        }

        return $schema;
    }

    /**
     * Añade BreadcrumbList al schema existente si procede.
     */
    protected function addBreadcrumbList(array &$schema, SeoPageConfig $config, string $baseUrl): void
    {
        // Se genera desde la estructura de páginas del Site Builder.
        $pageId = $config->getPageId();
        if (!$pageId) {
            return;
        }

        try {
            $treeStorage = $this->entityTypeManager->getStorage('site_page_tree');
            $treeNodes = $treeStorage->loadByProperties([
                'page_id' => $pageId,
                'tenant_id' => $config->getTenantId(),
            ]);

            if (empty($treeNodes)) {
                return;
            }

            $treeNode = reset($treeNodes);
            $path = $treeNode->get('path')->value ?? '';
            if (empty($path)) {
                return;
            }

            $schema['breadcrumb'] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Inicio',
                        'item' => $baseUrl,
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $config->getMetaTitle(),
                        'item' => $this->resolveUrl($path, $baseUrl),
                    ],
                ],
            ];
        }
        catch (\Exception $e) {
            // Silently fail - breadcrumbs are optional.
        }
    }

    /**
     * Obtiene la configuración del sitio del tenant actual.
     */
    protected function getSiteConfig(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return [];
        }

        try {
            $storage = $this->entityTypeManager->getStorage('site_config');
            $configs = $storage->loadByProperties(['tenant_id' => $tenant->id()]);
            if (empty($configs)) {
                return [];
            }

            $config = reset($configs);
            $data = [
                'site_name' => $config->get('site_name')->value ?? '',
                'site_tagline' => $config->get('site_tagline')->value ?? '',
                'contact_email' => $config->hasField('contact_email') ? ($config->get('contact_email')->value ?? '') : '',
                'contact_phone' => $config->hasField('contact_phone') ? ($config->get('contact_phone')->value ?? '') : '',
                'contact_address' => $config->hasField('contact_address') ? ($config->get('contact_address')->value ?? '') : '',
                'social_links' => $config->hasField('social_links') ? ($config->get('social_links')->value ?? '') : '',
            ];

            // Logo URL.
            if ($config->hasField('site_logo') && !empty($config->get('site_logo')->target_id)) {
                $file = $config->get('site_logo')->entity;
                if ($file) {
                    $data['logo_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                }
            }

            return $data;
        }
        catch (\Exception $e) {
            $this->logger->warning('Error cargando SiteConfig: @error', ['@error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Envuelve un array JSON-LD en una etiqueta script.
     */
    public function wrapInScript(array $jsonLd): string
    {
        $json = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . $json . '</script>';
    }

    /**
     * Resuelve una URL relativa a absoluta.
     */
    protected function resolveUrl(string $url, string $baseUrl): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

}
