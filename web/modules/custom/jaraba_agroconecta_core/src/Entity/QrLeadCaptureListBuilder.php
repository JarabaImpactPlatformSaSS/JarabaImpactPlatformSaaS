<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class QrLeadCaptureListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['email'] = $this->t('Email');
        $header['name'] = $this->t('Nombre');
        $header['source'] = $this->t('Origen');
        $header['discount'] = $this->t('Cupón');
        $header['consent'] = $this->t('RGPD');
        $header['date'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\QrLeadCapture $entity */
        $row['email'] = $entity->label();
        $row['name'] = $entity->get('name')->value ?? '—';
        $row['source'] = $entity->get('source')->value ?? '—';
        $row['discount'] = $entity->get('discount_code')->value ?? '—';
        $row['consent'] = $entity->get('consent_given')->value ? '✅' : '❌';
        $row['date'] = date('Y-m-d', (int) $entity->get('created')->value);
        return $row + parent::buildRow($entity);
    }
}
