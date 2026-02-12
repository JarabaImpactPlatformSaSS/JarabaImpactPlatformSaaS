<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para AgroCategory.
 *
 * Muestra las columnas: nombre, slug, padre, productos, destacada, activa, posición.
 */
class AgroCategoryListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['slug'] = $this->t('Slug');
        $header['parent'] = $this->t('Padre');
        $header['product_count'] = $this->t('Productos');
        $header['is_featured'] = $this->t('Destacada');
        $header['is_active'] = $this->t('Activa');
        $header['position'] = $this->t('Posición');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCategory $entity */
        $row['name'] = $entity->label();
        $row['slug'] = $entity->getSlug();
        $parent = $entity->getParent();
        $row['parent'] = $parent ? $parent->label() : '—';
        $row['product_count'] = $entity->getProductCount();
        $row['is_featured'] = $entity->isFeatured() ? '⭐' : '—';
        $row['is_active'] = $entity->isActive() ? '✅' : '❌';
        $row['position'] = (int) $entity->get('position')->value;
        return $row + parent::buildRow($entity);
    }

}
