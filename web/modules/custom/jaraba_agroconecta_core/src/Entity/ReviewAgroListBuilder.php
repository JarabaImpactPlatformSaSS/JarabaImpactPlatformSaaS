<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * ListBuilder para ReviewAgro.
 *
 * Muestra las columnas: tipo, producto/productor, autor, rating, estado, fecha.
 */
class ReviewAgroListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['type'] = $this->t('Tipo');
        $header['target'] = $this->t('Objetivo');
        $header['author'] = $this->t('Autor');
        $header['rating'] = $this->t('Valoración');
        $header['verified'] = $this->t('Verificada');
        $header['state'] = $this->t('Estado');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $entity */
        $row['type'] = $entity->getTypeLabel();
        $row['target'] = $entity->get('target_entity_type')->value . ':' . $entity->get('target_entity_id')->value;
        $row['author'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('Anónimo');
        $row['rating'] = $entity->getRatingStars();
        $row['verified'] = $entity->get('verified_purchase')->value ? '✅' : '—';
        $row['state'] = $entity->getStateLabel();
        $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
        return $row + parent::buildRow($entity);
    }

}
