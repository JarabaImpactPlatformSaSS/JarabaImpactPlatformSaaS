<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class TraceEventAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['seq'] = $this->t('#');
        $header['type'] = $this->t('Tipo');
        $header['batch'] = $this->t('Lote');
        $header['actor'] = $this->t('Actor');
        $header['timestamp'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\TraceEventAgro $entity */
        $row['seq'] = $entity->getSequence();
        $row['type'] = $entity->getEventType();
        $batch = $entity->get('batch_id')->entity;
        $row['batch'] = $batch ? $batch->label() : '—';
        $row['actor'] = $entity->get('actor')->value ?? '—';
        $row['timestamp'] = $entity->get('event_timestamp')->value ?? '—';
        return $row + parent::buildRow($entity);
    }
}
