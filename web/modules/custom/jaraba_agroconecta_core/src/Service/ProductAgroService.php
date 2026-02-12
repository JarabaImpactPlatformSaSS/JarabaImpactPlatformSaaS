<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestión de productos agroalimentarios.
 *
 * Proporciona métodos para consultar, filtrar y gestionar productos
 * con soporte multi-tenant.
 */
class ProductAgroService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene productos publicados con paginación.
     *
     * @param int $limit
     *   Número de productos a obtener.
     * @param int $offset
     *   Offset para paginación.
     * @param string|null $tenantId
     *   ID del tenant para filtrar (opcional).
     *
     * @return array
     *   Array de entidades ProductAgro.
     */
    public function getPublishedProducts(int $limit = 12, int $offset = 0, ?string $tenantId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $query = $storage->getQuery()
            ->condition('status', 1)
            ->sort('created', 'DESC')
            ->range($offset, $limit)
            ->accessCheck(TRUE);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Cuenta el total de productos publicados.
     *
     * @param string|null $tenantId
     *   ID del tenant para filtrar (opcional).
     *
     * @return int
     *   Total de productos publicados.
     */
    public function countPublishedProducts(?string $tenantId = NULL): int
    {
        $query = $this->entityTypeManager->getStorage('product_agro')
            ->getQuery()
            ->condition('status', 1)
            ->count()
            ->accessCheck(TRUE);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        return (int) $query->execute();
    }

    /**
     * Obtiene productos de un productor específico.
     *
     * @param int $producerId
     *   ID del perfil del productor.
     *
     * @return array
     *   Array de entidades ProductAgro.
     */
    public function getProductsByProducer(int $producerId): array
    {
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $ids = $storage->getQuery()
            ->condition('producer_id', $producerId)
            ->condition('status', 1)
            ->sort('name', 'ASC')
            ->accessCheck(TRUE)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Busca productos por texto.
     *
     * @param string $search
     *   Texto de búsqueda.
     * @param int $limit
     *   Máximo de resultados.
     *
     * @return array
     *   Array de entidades ProductAgro.
     */
    public function searchProducts(string $search, int $limit = 20): array
    {
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $ids = $storage->getQuery()
            ->condition('status', 1)
            ->condition('name', '%' . $search . '%', 'LIKE')
            ->sort('name', 'ASC')
            ->range(0, $limit)
            ->accessCheck(TRUE)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

}
