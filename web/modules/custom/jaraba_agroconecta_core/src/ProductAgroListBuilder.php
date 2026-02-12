<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ProductAgro.
 */
class ProductAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['sku'] = $this->t('SKU');
        $header['price'] = $this->t('Precio');
        $header['stock'] = $this->t('Stock');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductAgro $entity */
        $row['name'] = $entity->get('name')->value;
        $row['sku'] = $entity->get('sku')->value ?? '-';
        $row['price'] = $entity->getFormattedPrice();
        $row['stock'] = $entity->get('stock')->value ?? '0';
        $row['status'] = $entity->isPublished() ? $this->t('Publicado') : $this->t('Borrador');
        return $row + parent::buildRow($entity);
    }

}
