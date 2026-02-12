<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestión de perfiles de productor.
 *
 * Proporciona métodos para consultar y gestionar productores
 * con soporte multi-tenant.
 */
class ProducerProfileService
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
     * Obtiene productores activos.
     *
     * @param int $limit
     *   Número de productores a obtener.
     * @param int $offset
     *   Offset para paginación.
     *
     * @return array
     *   Array de entidades ProducerProfile.
     */
    public function getActiveProducers(int $limit = 20, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('producer_profile');
        $ids = $storage->getQuery()
            ->condition('status', 1)
            ->sort('farm_name', 'ASC')
            ->range($offset, $limit)
            ->accessCheck(TRUE)
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Obtiene el perfil de productor del usuario actual.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\ProducerProfile|null
     *   El perfil del productor o NULL si no existe.
     */
    public function getCurrentUserProfile(): mixed
    {
        $storage = $this->entityTypeManager->getStorage('producer_profile');
        $ids = $storage->getQuery()
            ->condition('uid', $this->currentUser->id())
            ->condition('status', 1)
            ->range(0, 1)
            ->accessCheck(TRUE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Verifica si el usuario actual tiene un perfil de productor.
     *
     * @return bool
     *   TRUE si el usuario tiene perfil de productor.
     */
    public function currentUserIsProducer(): bool
    {
        return $this->getCurrentUserProfile() !== NULL;
    }

}
