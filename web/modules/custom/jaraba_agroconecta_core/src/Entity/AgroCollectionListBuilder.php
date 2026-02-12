<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para AgroCollection.
 *
 * Muestra las columnas: nombre, tipo, ¿destacada?, ¿activa?, posición.
 */
class AgroCollectionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['slug'] = $this->t('Slug');
        $header['type'] = $this->t('Tipo');
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
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCollection $entity */
        $row['name'] = $entity->label();
        $row['slug'] = $entity->getSlug();
        $row['type'] = $entity->getTypeLabel();
        $row['is_featured'] = $entity->isFeatured() ? '⭐' : '—';
        $row['is_active'] = $entity->isActive() ? '✅' : '❌';
        $row['position'] = (int) $entity->get('position')->value;
        return $row + parent::buildRow($entity);
    }

}
