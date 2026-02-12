<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Email Sequence entities.
 */
class EmailSequenceListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Sequence');
        $header['category'] = $this->t('Category');
        $header['trigger'] = $this->t('Trigger');
        $header['enrolled'] = $this->t('Currently Enrolled');
        $header['completed'] = $this->t('Completed');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['name'] = $entity->toLink($entity->label());
        $row['category'] = $entity->get('category')->value ?? '-';
        $row['trigger'] = $entity->get('trigger_type')->value ?? '-';
        $row['enrolled'] = $entity->get('currently_enrolled')->value ?? 0;
        $row['completed'] = sprintf(
            '%d (%.1f%%)',
            $entity->get('completed')->value ?? 0,
            $entity->getCompletionRate()
        );
        $row['status'] = $entity->get('is_active')->value ? $this->t('Active') : $this->t('Inactive');
        return $row + parent::buildRow($entity);
    }

}
