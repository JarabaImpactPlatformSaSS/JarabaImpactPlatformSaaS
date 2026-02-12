<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class AnalyticsDailyAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['date'] = $this->t('Fecha');
        $header['gmv'] = $this->t('GMV');
        $header['orders'] = $this->t('Pedidos');
        $header['aov'] = $this->t('AOV');
        $header['buyers'] = $this->t('Compradores');
        $header['rate'] = $this->t('Conv. %');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AnalyticsDailyAgro $entity */
        $row['date'] = $entity->get('date')->value ?? '—';
        $row['gmv'] = sprintf('%.2f €', $entity->getGmv());
        $row['orders'] = $entity->getOrdersCount();
        $row['aov'] = sprintf('%.2f €', $entity->getAov());
        $row['buyers'] = $entity->get('unique_buyers')->value ?? 0;
        $row['rate'] = sprintf('%.2f%%', $entity->getConversionRate());
        return $row + parent::buildRow($entity);
    }
}
