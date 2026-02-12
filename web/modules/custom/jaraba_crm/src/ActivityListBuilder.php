<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Proporciona una lista de actividades del CRM.
 */
class ActivityListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['subject'] = $this->t('Asunto');
        $header['type'] = $this->t('Tipo');
        $header['contact'] = $this->t('Contacto');
        $header['date'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_crm\Entity\Activity $entity */
        $row['subject'] = $entity->toLink();
        $row['type'] = $entity->get('type')->value ?? '-';

        $contact = $entity->get('contact_id')->entity;
        $row['contact'] = $contact ? $contact->label() : '-';

        $date = $entity->get('activity_date')->value;
        $row['date'] = $date ? date('d/m/Y H:i', strtotime($date)) : '-';

        return $row + parent::buildRow($entity);
    }

}
