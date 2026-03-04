<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class AgroBatchListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['batch_code'] = $this->t('Lote');
        $header['product'] = $this->t('Producto');
        $header['origin'] = $this->t('Origen');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroBatch $entity */
        $row['batch_code'] = $entity->label();
        $product = $entity->get('product_id')->entity;
        $row['product'] = $product ? $product->label() : '—';
        $row['origin'] = $entity->get('origin')->value ?? '—';
        $statuses = ['active' => $this->t('Activo'), 'sealed' => $this->t('Sellado'), 'archived' => $this->t('Archivado')];
        $row['status'] = $statuses[$entity->getStatus()] ?? $entity->getStatus();
        return $row + parent::buildRow($entity);
    }
}
