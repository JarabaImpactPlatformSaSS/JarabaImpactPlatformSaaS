<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de empresas del CRM.
 */
class CompanyListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['industry'] = $this->t('Industria');
        $header['size'] = $this->t('Tamaño');
        $header['phone'] = $this->t('Teléfono');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_crm\Entity\Company $entity */
        $row['name'] = $entity->toLink();
        $row['industry'] = $entity->get('industry')->value ?? '-';
        $row['size'] = $entity->get('size')->value ?? '-';
        $row['phone'] = $entity->get('phone')->value ?? '-';
        return $row + parent::buildRow($entity);
    }

}
