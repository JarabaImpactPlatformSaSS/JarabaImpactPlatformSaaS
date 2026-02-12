<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Controller para generación de Sitemap XML dinámico.
 *
 * ESPECIFICACIÓN: Doc 164 - Platform_SEO_GEO_PageBuilder_v1
 *
 * Genera sitemaps XML con:
 * - Prioridades calculadas por tipo de template
 * - Frecuencias según contenido
 * - Imágenes extraídas de content_data
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class SitemapController extends ControllerBase
{

    /**
     * Genera robots.txt dinamico por tenant.
     *
     * P2-02: Directivas de rastreo para motores de busqueda.
     * - Permite paginas publicadas
     * - Bloquea rutas de administracion
     * - Referencia sitemaps del sitio
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Text response con directivas robots.
     */
    public function robots(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Admin routes',
            'Disallow: /admin/',
            'Disallow: /user/',
            'Disallow: /node/',
            'Disallow: /batch',
            'Disallow: /search/',
            '',
            '# API endpoints',
            'Disallow: /api/',
            '',
            '# Page Builder editor',
            'Disallow: /page/*/editor',
            'Disallow: /page-builder/',
            '',
            '# Drupal core',
            'Disallow: /core/',
            'Disallow: /modules/',
            'Disallow: /themes/',
            'Disallow: /profiles/',
            '',
            '# Sitemaps',
            'Sitemap: ' . $baseUrl . '/sitemap.xml',
            '',
        ];

        $content = implode("\n", $lines);

        return new Response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Valida structured data de una pagina.
     *
     * P2-02: Devuelve el JSON-LD que se generaria para una pagina,
     * permitiendo validar con Google Rich Results Test.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     * @param int $id
     *   ID de la pagina a validar.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con schema.org data de la pagina.
     */
    public function validateStructuredData(Request $request, int $id): JsonResponse
    {
        try {
            $page = $this->entityTypeManager()->getStorage('page_content')->load($id);
            if (!$page) {
                return new JsonResponse(['error' => 'Page not found'], 404);
            }

            /** @var \Drupal\jaraba_page_builder\Service\SchemaOrgService $schemaOrgService */
            $schemaOrgService = \Drupal::service('jaraba_page_builder.schema_org');

            $templateId = $page->get('template_id')->value ?? '';
            $contentData = json_decode($page->get('content_data')->value ?? '{}', TRUE) ?: [];

            $schemas = [];

            // Breadcrumb siempre presente.
            $breadcrumbs = [
                ['title' => 'Inicio', 'url' => $request->getSchemeAndHttpHost()],
                ['title' => $page->get('title')->value, 'url' => ''],
            ];
            $schemas[] = json_decode($schemaOrgService->generateBreadcrumbSchema($breadcrumbs), TRUE);

            // Schema por tipo de template.
            $tenantData = ['name' => $request->getHost()];
            $schema = $this->getSchemaForTemplate($schemaOrgService, $templateId, $contentData, $tenantData);
            if ($schema) {
                $schemas[] = $schema;
            }

            return new JsonResponse([
                'page_id' => $id,
                'template' => $templateId,
                'schemas' => $schemas,
                'valid' => TRUE,
            ]);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'valid' => FALSE,
            ], 500);
        }
    }

    /**
     * Genera preview de Open Graph para una pagina.
     *
     * P2-02: Devuelve como se veria la pagina al compartirla
     * en redes sociales (titulo, descripcion, imagen).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     * @param int $id
     *   ID de la pagina.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con preview de Open Graph.
     */
    public function openGraphPreview(Request $request, int $id): JsonResponse
    {
        try {
            $page = $this->entityTypeManager()->getStorage('page_content')->load($id);
            if (!$page) {
                return new JsonResponse(['error' => 'Page not found'], 404);
            }

            $baseUrl = $request->getSchemeAndHttpHost();
            $pathAlias = $page->get('path_alias')->value ?? '/page/' . $page->id();
            $contentData = json_decode($page->get('content_data')->value ?? '{}', TRUE) ?: [];

            $title = $page->get('meta_title')->value ?? $page->get('title')->value;
            $description = $page->get('meta_description')->value ?? '';

            // Extraer primera imagen para og:image.
            $images = $this->extractImages($page);
            $ogImage = !empty($images) ? $images[0]['url'] : NULL;
            if ($ogImage && !str_starts_with($ogImage, 'http')) {
                $ogImage = $baseUrl . $ogImage;
            }

            $templateId = $page->get('template_id')->value ?? '';

            return new JsonResponse([
                'page_id' => $id,
                'og' => [
                    'title' => $title,
                    'description' => $description,
                    'image' => $ogImage,
                    'url' => $baseUrl . $pathAlias,
                    'type' => $this->getOgType($templateId),
                    'site_name' => \Drupal::config('system.site')->get('name') ?? 'Jaraba',
                ],
                'twitter' => [
                    'card' => $ogImage ? 'summary_large_image' : 'summary',
                    'title' => $title,
                    'description' => $description,
                    'image' => $ogImage,
                ],
            ]);
        }
        catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene el tipo OG basado en el template.
     *
     * @param string $templateId
     *   ID del template.
     *
     * @return string
     *   Tipo Open Graph.
     */
    protected function getOgType(string $templateId): string
    {
        $types = [
            'product_detail' => 'product',
            'agro_product_detail' => 'product',
            'blog_post' => 'article',
            'emp_blog_post' => 'article',
            'course_detail' => 'article',
        ];

        return $types[$templateId] ?? 'website';
    }

    /**
     * Genera el schema JSON-LD segun el tipo de template.
     *
     * @param \Drupal\jaraba_page_builder\Service\SchemaOrgService $service
     *   Servicio Schema.org.
     * @param string $templateId
     *   ID del template.
     * @param array $contentData
     *   Datos del contenido.
     * @param array $tenantData
     *   Datos del tenant.
     *
     * @return array|null
     *   Array decoded del JSON-LD o NULL si no aplica.
     */
    protected function getSchemaForTemplate($service, string $templateId, array $contentData, array $tenantData): ?array
    {
        $schemaJson = NULL;

        if (str_contains($templateId, 'job')) {
            $schemaJson = $service->generateJobPostingSchema($contentData, $tenantData);
        }
        elseif (str_contains($templateId, 'course')) {
            $schemaJson = $service->generateCourseSchema($contentData, $tenantData);
        }
        elseif (str_contains($templateId, 'product') || str_contains($templateId, 'agro')) {
            $schemaJson = $service->generateProductSchema($contentData, $tenantData);
        }
        elseif (str_contains($templateId, 'service') || str_contains($templateId, 'srv')) {
            $schemaJson = $service->generateLocalBusinessSchema($contentData);
        }
        elseif (str_contains($templateId, 'faq')) {
            $faqItems = $contentData['faqs'] ?? $contentData['items'] ?? [];
            if (!empty($faqItems)) {
                $schemaJson = $service->generateFAQSchema($faqItems);
            }
        }

        if ($schemaJson) {
            return json_decode($schemaJson, TRUE);
        }

        return NULL;
    }

    /**
     * Genera sitemap index principal.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   XML response con sitemapindex.
     */
    public function index(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Sitemap de páginas del Page Builder.
        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . $baseUrl . '/sitemap-pages.xml</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $xml .= '  </sitemap>' . "\n";

        // Aquí se pueden añadir más sitemaps (jobs, courses, products, etc.).
        $additionalSitemaps = [
            'sitemap-articles.xml',
            'sitemap-static.xml',
        ];

        foreach ($additionalSitemaps as $sitemap) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . $baseUrl . '/' . $sitemap . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '  </sitemap>' . "\n";
        }

        $xml .= '</sitemapindex>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /**
     * Genera sitemap de páginas del Page Builder.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   XML response con urlset.
     */
    public function pages(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        try {
            $storage = $this->entityTypeManager()->getStorage('page_content');
            $query = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 1)
                ->sort('changed', 'DESC');

            $ids = $query->execute();
            $pages = $storage->loadMultiple($ids);

            foreach ($pages as $page) {
                $xml .= $this->generateUrlEntry($page, $baseUrl);
            }
        } catch (\Exception $e) {
            // Log error pero devolver sitemap vacío válido.
            \Drupal::logger('jaraba_page_builder')->error('Sitemap error: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Genera entrada URL para una página.
     *
     * @param mixed $page
     *   Entidad PageContent.
     * @param string $baseUrl
     *   URL base del sitio.
     *
     * @return string
     *   XML de la entrada URL.
     */
    protected function generateUrlEntry($page, string $baseUrl): string
    {
        $pathAlias = $page->get('path_alias')->value ?? '';
        $changed = $page->get('changed')->value ?? time();
        $templateId = $page->get('template_id')->value ?? '';

        // Construir URL completa.
        if (!empty($pathAlias)) {
            $url = $baseUrl . $pathAlias;
        } else {
            try {
                $url = Url::fromRoute('entity.page_content.canonical', [
                    'page_content' => $page->id(),
                ])->setAbsolute()->toString();
            } catch (\Exception $e) {
                $url = $baseUrl . '/page/' . $page->id();
            }
        }

        $xml = '  <url>' . "\n";
        $xml .= '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>' . "\n";
        $xml .= '    <lastmod>' . date('Y-m-d', (int) $changed) . '</lastmod>' . "\n";
        $xml .= '    <changefreq>' . $this->calculateChangefreq($templateId) . '</changefreq>' . "\n";
        $xml .= '    <priority>' . $this->calculatePriority($templateId) . '</priority>' . "\n";

        // Añadir imágenes si existen.
        $images = $this->extractImages($page);
        foreach ($images as $image) {
            $xml .= '    <image:image>' . "\n";
            $xml .= '      <image:loc>' . htmlspecialchars($image['url'], ENT_XML1) . '</image:loc>' . "\n";
            if (!empty($image['title'])) {
                $xml .= '      <image:title>' . htmlspecialchars($image['title'], ENT_XML1) . '</image:title>' . "\n";
            }
            $xml .= '    </image:image>' . "\n";
        }

        // Añadir hreflang si hay traducciones.
        if ($page->isTranslatable()) {
            $translations = $page->getTranslationLanguages(TRUE);
            if (count($translations) > 1) {
                foreach ($translations as $langcode => $language) {
                    $translatedPage = $page->getTranslation($langcode);
                    $translatedAlias = $translatedPage->get('path_alias')->value ?? '';
                    if (!empty($translatedAlias)) {
                        $translatedUrl = $baseUrl . '/' . $langcode . $translatedAlias;
                    } else {
                        $translatedUrl = $baseUrl . '/' . $langcode . '/page/' . $page->id();
                    }
                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . $langcode . '" href="' . htmlspecialchars($translatedUrl, ENT_XML1) . '" />' . "\n";
                }
                // Añadir x-default.
                $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($url, ENT_XML1) . '" />' . "\n";
            }
        }

        $xml .= '  </url>' . "\n";

        return $xml;
    }

    /**
     * Calcula prioridad basada en tipo de template.
     *
     * @param string $templateId
     *   ID del template.
     *
     * @return string
     *   Prioridad (0.0 - 1.0).
     */
    protected function calculatePriority(string $templateId): string
    {
        $priorities = [
            // Landings principales.
            'landing_main' => '1.0',
            'landing_vertical' => '0.9',
            'emp_landing_main' => '0.9',
            'agro_landing_main' => '0.9',
            'com_landing_main' => '0.9',
            'srv_landing_main' => '0.9',
            'ent_landing_main' => '0.9',
            // Detalle de servicios/productos.
            'service_detail' => '0.8',
            'product_detail' => '0.8',
            'srv_service_detail' => '0.8',
            'agro_product_detail' => '0.8',
            // Empleos y cursos.
            'job_detail' => '0.7',
            'course_detail' => '0.7',
            'emp_course_detail' => '0.7',
            // Blog.
            'blog_post' => '0.6',
            'emp_blog_post' => '0.6',
            // Páginas informativas.
            'about' => '0.5',
            'gen_about' => '0.5',
            'contact' => '0.5',
            'gen_contact' => '0.5',
            'faq' => '0.4',
            'gen_faq' => '0.4',
            // Legal.
            'terms' => '0.3',
            'gen_terms' => '0.3',
            'privacy' => '0.3',
            'gen_privacy' => '0.3',
        ];

        return $priorities[$templateId] ?? '0.5';
    }

    /**
     * Calcula frecuencia de cambio.
     *
     * @param string $templateId
     *   ID del template.
     *
     * @return string
     *   Frecuencia (always, hourly, daily, weekly, monthly, yearly, never).
     */
    protected function calculateChangefreq(string $templateId): string
    {
        $frequencies = [
            // Cambio frecuente.
            'landing_main' => 'weekly',
            'landing_vertical' => 'weekly',
            'job_detail' => 'daily',
            'product_detail' => 'weekly',
            // Cambio moderado.
            'blog_post' => 'monthly',
            'course_detail' => 'weekly',
            'service_detail' => 'monthly',
            // Cambio raro.
            'about' => 'monthly',
            'gen_about' => 'monthly',
            'contact' => 'monthly',
            'faq' => 'monthly',
            // Casi nunca cambian.
            'terms' => 'yearly',
            'gen_terms' => 'yearly',
            'privacy' => 'yearly',
            'gen_privacy' => 'yearly',
        ];

        return $frequencies[$templateId] ?? 'monthly';
    }

    /**
     * Extrae imágenes del content_data.
     *
     * @param mixed $page
     *   Entidad PageContent.
     *
     * @return array
     *   Array de imágenes con 'url' y 'title'.
     */
    protected function extractImages($page): array
    {
        $images = [];
        $contentData = $page->get('content_data')->value ?? '{}';
        $content = json_decode($contentData, TRUE) ?: [];

        if (empty($content)) {
            return $images;
        }

        // Recorrer recursivamente buscando campos de imagen.
        $imageKeys = [
            'image',
            'background_image',
            'og_image',
            'avatar',
            'hero_image',
            'thumbnail',
            'logo',
            'photo',
        ];

        $this->extractImagesRecursive($content, $imageKeys, $images);

        // Limitar a 10 imágenes.
        return array_slice($images, 0, 10);
    }

    /**
     * Extrae imágenes recursivamente del array.
     *
     * @param array $data
     *   Datos a recorrer.
     * @param array $imageKeys
     *   Keys que indican campos de imagen.
     * @param array &$images
     *   Array de imágenes encontradas.
     */
    protected function extractImagesRecursive(array $data, array $imageKeys, array &$images): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->extractImagesRecursive($value, $imageKeys, $images);
            } elseif (is_string($value) && in_array($key, $imageKeys, TRUE)) {
                if (!empty($value) && (str_starts_with($value, 'http') || str_starts_with($value, '/'))) {
                    $images[] = [
                        'url' => $value,
                        'title' => '',
                    ];
                }
            }
        }
    }

    /**
     * Genera sitemap de artículos del Content Hub.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   XML response con urlset.
     */
    public function articles(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        try {
            // Intentar cargar artículos si existe el módulo Content Hub.
            if ($this->entityTypeManager()->hasDefinition('article')) {
                $storage = $this->entityTypeManager()->getStorage('article');
                $query = $storage->getQuery()
                    ->accessCheck(TRUE)
                    ->condition('status', 1)
                    ->sort('changed', 'DESC')
                    ->range(0, 1000);

                $ids = $query->execute();
                $articles = $storage->loadMultiple($ids);

                foreach ($articles as $article) {
                    $url = Url::fromRoute('entity.article.canonical', [
                        'article' => $article->id(),
                    ])->setAbsolute()->toString();

                    $changed = $article->get('changed')->value ?? time();

                    $xml .= '  <url>' . "\n";
                    $xml .= '    <loc>' . htmlspecialchars($url, ENT_XML1) . '</loc>' . "\n";
                    $xml .= '    <lastmod>' . date('Y-m-d', (int) $changed) . '</lastmod>' . "\n";
                    $xml .= '    <changefreq>weekly</changefreq>' . "\n";
                    $xml .= '    <priority>0.7</priority>' . "\n";
                    $xml .= '  </url>' . "\n";
                }
            }
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->warning('Articles sitemap: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * Genera sitemap de páginas estáticas.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   XML response con urlset.
     */
    public function staticPages(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Páginas estáticas conocidas.
        $staticPages = [
            ['path' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['path' => '/about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['path' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['path' => '/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['path' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $baseUrl . $page['path'] . '</loc>' . "\n";
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

}
