<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class QrCodeAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['label'] = $this->t('QR');
        $header['type'] = $this->t('Tipo');
        $header['scans'] = $this->t('Escaneos');
        $header['unique'] = $this->t('Únicos');
        $header['conv'] = $this->t('Conv.');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\QrCodeAgro $entity */
        $row['label'] = $entity->label();
        $row['type'] = $entity->getQrType();
        $row['scans'] = $entity->getScanCount();
        $row['unique'] = $entity->get('unique_scan_count')->value ?? 0;
        $row['conv'] = $entity->get('conversion_count')->value ?? 0;
        $row['status'] = $entity->isActive() ? '✅' : '⏸';
        return $row + parent::buildRow($entity);
    }
}
