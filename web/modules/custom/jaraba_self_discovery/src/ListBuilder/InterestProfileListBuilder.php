<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para listar perfiles de intereses RIASEC.
 */
class InterestProfileListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['riasec_code'] = $this->t('Codigo RIASEC');
        $header['created'] = $this->t('Fecha');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_self_discovery\Entity\InterestProfile $entity */
        $row['id'] = $entity->id();

        $owner = $entity->getOwner();
        $row['user'] = $owner ? $owner->getDisplayName() : $this->t('Desconocido');

        $row['riasec_code'] = $entity->getRiasecCode();

        $created = $entity->get('created')->value;
        $row['created'] = \Drupal::service('date.formatter')->format($created, 'short');

        return $row + parent::buildRow($entity);
    }

}
