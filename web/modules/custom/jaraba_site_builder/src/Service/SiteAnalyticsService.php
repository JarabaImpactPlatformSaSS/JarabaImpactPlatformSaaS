<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Servicio de analíticas del Site Builder.
 *
 * Proporciona KPIs agregados para el dashboard premium:
 * - Total de páginas en el árbol
 * - Páginas publicadas vs borradores
 * - SEO score medio (delegado a SEO Auditor)
 * - Última actualización del sitio
 *
 * Sprint B1: Dashboard Premium.
 */
class SiteAnalyticsService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected SiteStructureService $structureService,
        protected SeoAuditorService $seoAuditor,
    ) {
    }

    /**
     * Obtiene los KPIs del dashboard para el tenant actual.
     *
     * @param int|null $tenantId
     *   ID del tenant. Si es NULL, usa el tenant actual.
     *
     * @return array
     *   Array con las estadísticas:
     *   - total_pages: Total de páginas en el árbol.
     *   - published_pages: Páginas con estado publicado.
     *   - draft_pages: Páginas en borrador.
     *   - seo_score: Score SEO medio (null si no disponible).
     *   - last_updated: Timestamp de la última actualización.
     *   - last_updated_formatted: Fecha formateada de la última actualización.
     */
    public function getStats(?int $tenantId = NULL): array
    {
        if ($tenantId === NULL) {
            $tenant = $this->tenantContext->getCurrentTenant();
            $tenantId = $tenant ? (int) $tenant->id() : NULL;
        }

        if (!$tenantId) {
            return $this->getEmptyStats();
        }

        $storage = $this->entityTypeManager->getStorage('site_page_tree');

        // Total de páginas en el árbol.
        $totalPages = (int) $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Páginas publicadas.
        $publishedPages = (int) $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('status', 'published')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // Última actualización: obtener el timestamp más reciente.
        $lastUpdated = $this->getLastUpdatedTimestamp($tenantId);

        // Sprint B2: SEO Score medio de las páginas publicadas.
        $seoScore = $this->computeAverageSeoScore($tenantId);

        return [
            'total_pages' => $totalPages,
            'published_pages' => $publishedPages,
            'draft_pages' => $totalPages - $publishedPages,
            'seo_score' => $seoScore,
            'last_updated' => $lastUpdated,
            'last_updated_formatted' => $lastUpdated
                ? \Drupal::service('date.formatter')->format($lastUpdated, 'short')
                : t('Nunca'),
        ];
    }

    /**
     * Obtiene el timestamp de la última actualización de una página del tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return int|null
     *   Timestamp UNIX o NULL si no hay páginas.
     */
    protected function getLastUpdatedTimestamp(int $tenantId): ?int
    {
        $storage = $this->entityTypeManager->getStorage('site_page_tree');

        $ids = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->sort('changed', 'DESC')
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        $entity = $storage->load(reset($ids));
        if ($entity && $entity->hasField('changed')) {
            return (int) $entity->get('changed')->value;
        }

        return NULL;
    }

    /**
     * Devuelve estadísticas vacías cuando no hay tenant.
     *
     * @return array
     *   Array con todos los KPIs en cero/null.
     */
    protected function getEmptyStats(): array
    {
        return [
            'total_pages' => 0,
            'published_pages' => 0,
            'draft_pages' => 0,
            'seo_score' => NULL,
            'last_updated' => NULL,
            'last_updated_formatted' => t('Nunca'),
        ];
    }

    /**
     * Calcula el SEO score medio de las páginas del tenant.
     *
     * Solo audita páginas publicadas vinculadas al árbol.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return int|null
     *   Score medio 0-100, o NULL si no hay páginas.
     */
    protected function computeAverageSeoScore(int $tenantId): ?int
    {
        // Obtener IDs de páginas del árbol.
        $storage = $this->entityTypeManager->getStorage('site_page_tree');
        $treeNodes = $storage->loadByProperties([
            'tenant_id' => $tenantId,
            'status' => 'published',
        ]);

        if (empty($treeNodes)) {
            return NULL;
        }

        // Extraer page_ids.
        $pageIds = [];
        foreach ($treeNodes as $node) {
            $pageId = $node->get('page_id')->target_id;
            if ($pageId) {
                $pageIds[] = $pageId;
            }
        }

        return $this->seoAuditor->getAverageScore($pageIds);
    }

}
