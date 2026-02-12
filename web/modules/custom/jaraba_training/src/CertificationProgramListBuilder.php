<?php

declare(strict_types=1);

namespace Drupal\jaraba_training;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad CertificationProgram.
 */
class CertificationProgramListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header = [
            'title' => $this->t('Programa'),
            'type' => $this->t('Tipo'),
            'entry_fee' => $this->t('Fee Activación'),
            'annual_fee' => $this->t('Cuota Anual'),
            'royalty' => $this->t('% Royalty'),
            'status' => $this->t('Estado'),
        ];
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row = [
            'title' => $entity->get('title')->value ?? '',
            'type' => $entity->get('certification_type')->value ?? '',
            'entry_fee' => '€' . number_format((float) ($entity->get('entry_fee')->value ?? 0), 2),
            'annual_fee' => '€' . number_format((float) ($entity->get('annual_fee')->value ?? 0), 2),
            'royalty' => ((float) ($entity->get('royalty_percent')->value ?? 0)) . '%',
            'status' => $entity->get('status')->value ? $this->t('Activo') : $this->t('Inactivo'),
        ];
        return $row + parent::buildRow($entity);
    }

}
