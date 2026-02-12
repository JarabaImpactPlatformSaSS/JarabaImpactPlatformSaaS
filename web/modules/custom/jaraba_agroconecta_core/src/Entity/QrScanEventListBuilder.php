<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class QrScanEventListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['qr'] = $this->t('QR');
        $header['device'] = $this->t('Dispositivo');
        $header['country'] = $this->t('País');
        $header['city'] = $this->t('Ciudad');
        $header['unique'] = $this->t('Único');
        $header['date'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\QrScanEvent $entity */
        $qr = $entity->get('qr_code_id')->entity;
        $row['qr'] = $qr ? $qr->label() : '#' . $entity->get('qr_code_id')->target_id;
        $row['device'] = $entity->get('device_type')->value ?? '?';
        $row['country'] = $entity->get('country')->value ?? '—';
        $row['city'] = $entity->get('city')->value ?? '—';
        $row['unique'] = $entity->get('is_unique')->value ? '✔' : '—';
        $row['date'] = date('Y-m-d H:i', (int) $entity->get('scan_timestamp')->value);
        return $row + parent::buildRow($entity);
    }
}
