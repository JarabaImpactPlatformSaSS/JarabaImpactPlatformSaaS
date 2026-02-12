<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Digital Kit entities.
 */
class DigitalKitListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['name'] = $this->t('Nombre');
        $header['category'] = $this->t('Categoría');
        $header['access'] = $this->t('Acceso');
        $header['downloads'] = $this->t('Descargas');
        $header['rating'] = $this->t('Valoración');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_resources\Entity\DigitalKit $entity */
        $row['id'] = $entity->id();
        $row['name'] = $entity->toLink();
        $row['category'] = $entity->getCategory();
        $row['access'] = $entity->getAccessLevel();
        $row['downloads'] = $entity->getDownloadCount();
        $row['rating'] = number_format($entity->getRating(), 1) . ' ★';
        $row['status'] = $entity->get('status')->value;
        return $row + parent::buildRow($entity);
    }

}
