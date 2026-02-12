<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class IntegrityProofAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['batch'] = $this->t('Lote');
        $header['type'] = $this->t('Anclaje');
        $header['events'] = $this->t('Eventos');
        $header['status'] = $this->t('Verificación');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\IntegrityProofAgro $entity */
        $batch = $entity->get('batch_id')->entity;
        $row['batch'] = $batch ? $batch->label() : '—';
        $row['type'] = $entity->getAnchorType();
        $row['events'] = $entity->get('event_count')->value ?? 0;
        $statuses = ['pending' => '⏳', 'verified' => '✅', 'failed' => '❌'];
        $s = $entity->get('verification_status')->value ?? 'pending';
        $row['status'] = $statuses[$s] ?? $s;
        return $row + parent::buildRow($entity);
    }
}
