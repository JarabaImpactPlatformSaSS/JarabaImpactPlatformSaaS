<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Email Template entities.
 */
class EmailTemplateListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Template');
        $header['category'] = $this->t('Category');
        $header['vertical'] = $this->t('Vertical');
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
        $row['vertical'] = $entity->get('vertical')->value ?? 'all';
        $row['status'] = $entity->get('is_active')->value ? $this->t('Active') : $this->t('Inactive');
        return $row + parent::buildRow($entity);
    }

}
