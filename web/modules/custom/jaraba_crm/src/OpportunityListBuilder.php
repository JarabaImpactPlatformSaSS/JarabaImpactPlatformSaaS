<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de oportunidades del CRM (pipeline).
 */
class OpportunityListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Título');
        $header['contact'] = $this->t('Contacto');
        $header['value'] = $this->t('Valor');
        $header['stage'] = $this->t('Etapa');
        $header['probability'] = $this->t('Prob.');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_crm\Entity\Opportunity $entity */
        $row['title'] = $entity->toLink();

        $contact = $entity->get('contact_id')->entity;
        $row['contact'] = $contact ? $contact->label() : '-';

        $value = $entity->get('value')->value;
        $row['value'] = $value ? number_format((float) $value, 2, ',', '.') . ' €' : '-';

        $row['stage'] = $entity->get('stage')->value ?? '-';
        $row['probability'] = ($entity->get('probability')->value ?? 0) . '%';

        return $row + parent::buildRow($entity);
    }

}
