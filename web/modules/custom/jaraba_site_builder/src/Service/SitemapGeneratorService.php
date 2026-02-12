<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio para generación de Sitemap XML.
 */
class SitemapGeneratorService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SiteStructureService $structureService,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected FileUrlGeneratorInterface $fileUrlGenerator,
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * Genera el sitemap XML para un tenant.
     *
     * @param int|null $tenantId
     *   ID del tenant.
     * @param string $baseUrl
     *   URL base del sitio.
     *
     * @return string
     *   Contenido XML del sitemap.
     */
    public function generateXML(?int $tenantId = NULL, string $baseUrl = ''): string
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantId = $tenantId ?? ($tenant ? (int) $tenant->id() : null);

        if (empty($baseUrl)) {
            $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
        }

        $pages = $this->structureService->getNavigation($tenantId, 'sitemap');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Procesar páginas recursivamente.
        $xml .= $this->processPages($pages, $baseUrl);

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }

    /**
     * Procesa páginas recursivamente generando URLs.
     */
    protected function processPages(array $pages, string $baseUrl): string
    {
        $xml = '';

        foreach ($pages as $page) {
            if (empty($page['page_url']) && empty($page['external_url'])) {
                continue;
            }

            // No incluir enlaces externos.
            if ($page['is_external']) {
                continue;
            }

            $url = $baseUrl . $page['page_url'];
            $lastmod = date('Y-m-d');
            $priority = $this->calculatePriority($page['depth']);

            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;

            // Procesar hijos.
            if (!empty($page['children'])) {
                $xml .= $this->processPages($page['children'], $baseUrl);
            }
        }

        return $xml;
    }

    /**
     * Calcula la prioridad basada en profundidad.
     */
    protected function calculatePriority(int $depth): string
    {
        $priorities = [
            0 => '1.0',
            1 => '0.8',
            2 => '0.6',
            3 => '0.4',
        ];

        return $priorities[$depth] ?? '0.3';
    }

    /**
     * Obtiene datos del sitemap para visualización en admin.
     *
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Datos estructurados para la UI.
     */
    public function getVisualData(?int $tenantId = NULL): array
    {
        $pages = $this->structureService->getNavigation($tenantId, 'sitemap');

        return [
            'pages' => $pages,
            'total_pages' => $this->countPages($pages),
            'last_generated' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Cuenta páginas en el árbol.
     */
    protected function countPages(array $pages): int
    {
        $count = count($pages);

        foreach ($pages as $page) {
            if (!empty($page['children'])) {
                $count += $this->countPages($page['children']);
            }
        }

        return $count;
    }

}
