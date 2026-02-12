<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de contactos del CRM.
 */
class ContactListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['email'] = $this->t('Email');
        $header['company'] = $this->t('Empresa');
        $header['engagement'] = $this->t('Engagement');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_crm\Entity\Contact $entity */
        $row['name'] = $entity->toLink();
        $row['email'] = $entity->get('email')->value ?? '-';

        $company = $entity->get('company_id')->entity;
        $row['company'] = $company ? $company->label() : '-';

        $score = $entity->get('engagement_score')->value ?? 0;
        $row['engagement'] = $score . '%';

        return $row + parent::buildRow($entity);
    }

}
