<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a listing of Financial Projection entities.
 */
class FinancialProjectionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Título');
        $header['scenario'] = $this->t('Escenario');
        $header['period'] = $this->t('Período');
        $header['roi'] = $this->t('ROI');
        $header['owner'] = $this->t('Propietario');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_business_tools\Entity\FinancialProjection $entity */
        $row['id'] = $entity->id();
        $row['title'] = $entity->toLink();
        $row['scenario'] = $entity->getScenario();
        $row['period'] = $entity->getPeriodMonths() . ' meses';
        $row['roi'] = number_format($entity->getRoi(), 1) . '%';
        $row['owner'] = $entity->getOwner()?->getDisplayName() ?? '-';
        return $row + parent::buildRow($entity);
    }

}
