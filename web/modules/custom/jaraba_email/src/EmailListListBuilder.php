<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Email List entities.
 */
class EmailListListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Name');
        $header['type'] = $this->t('Type');
        $header['subscribers'] = $this->t('Subscribers');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['name'] = $entity->toLink($entity->label());
        $row['type'] = $entity->get('type')->value ?? '-';
        $row['subscribers'] = $entity->get('subscriber_count')->value ?? 0;
        $row['status'] = $entity->get('is_active')->value ? $this->t('Active') : $this->t('Inactive');
        return $row + parent::buildRow($entity);
    }

}
