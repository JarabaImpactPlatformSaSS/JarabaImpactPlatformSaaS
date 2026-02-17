<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService;
use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestión de productos agroalimentarios.
 *
 * Proporciona métodos para consultar, filtrar y gestionar productos
 * con soporte multi-tenant. Integra FeatureGateService para verificar
 * limites de plan antes de crear productos o subir fotos.
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
        protected AgroConectaFeatureGateService $featureGate,
    ) {
    }

    /**
     * Verifica si el usuario puede crear un nuevo producto.
     *
     * Consulta el FeatureGateService para verificar el limite de productos
     * segun el plan del usuario. Si denegado, dispara upgrade trigger.
     *
     * @param int $userId
     *   El ID del usuario productor.
     *
     * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
     *   Resultado de la verificacion con allowed, remaining, upgradeMessage.
     */
    public function canCreateProduct(int $userId): FeatureGateResult {
        return $this->featureGate->checkAndFire($userId, 'products', [
            'current_count' => $this->countProductsByUser($userId),
        ]);
    }

    /**
     * Verifica si el usuario puede subir otra foto a un producto.
     *
     * @param int $userId
     *   El ID del usuario productor.
     * @param int $productId
     *   El ID del producto.
     *
     * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
     *   Resultado de la verificacion.
     */
    public function canUploadPhoto(int $userId, int $productId): FeatureGateResult {
        $photoCount = $this->countPhotosForProduct($productId);
        $plan = $this->featureGate->getUserPlan($userId);
        $result = $this->featureGate->check($userId, 'photos_per_product', $plan);

        // Para photos_per_product, comparamos contra fotos del producto especifico.
        if ($result->isAllowed() && $result->limit > 0) {
            $remaining = max(0, $result->limit - $photoCount);
            if ($remaining <= 0) {
                return FeatureGateResult::denied(
                    'photos_per_product',
                    $plan,
                    $result->limit,
                    $photoCount,
                    $result->upgradeMessage ?: 'Has alcanzado el limite de fotos por producto.',
                    $result->upgradePlan ?: 'starter',
                );
            }
        }

        return $result;
    }

    /**
     * Cuenta los productos activos de un usuario.
     *
     * @param int $userId
     *   El ID del usuario.
     *
     * @return int
     *   Numero de productos activos.
     */
    public function countProductsByUser(int $userId): int {
        $count = $this->entityTypeManager->getStorage('product_agro')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('user_id', $userId)
            ->condition('status', 1)
            ->count()
            ->execute();
        return (int) $count;
    }

    /**
     * Cuenta las fotos de un producto.
     *
     * @param int $productId
     *   El ID del producto.
     *
     * @return int
     *   Numero de fotos.
     */
    protected function countPhotosForProduct(int $productId): int {
        try {
            $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
            if ($product && $product->hasField('images')) {
                return count($product->get('images')->getValue());
            }
        }
        catch (\Exception $e) {
            // Producto no encontrado o campo no disponible.
        }
        return 0;
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
