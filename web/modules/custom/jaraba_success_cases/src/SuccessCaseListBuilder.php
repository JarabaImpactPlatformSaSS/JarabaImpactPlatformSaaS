<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Success Case entities.
 *
 * Renders the admin table at /admin/content/success-cases with columns
 * for name, profession, vertical, published status, featured flag, and actions.
 */
class SuccessCaseListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Name');
        $header['profession'] = $this->t('Profession');
        $header['vertical'] = $this->t('Vertical');
        $header['status'] = $this->t('Published');
        $header['featured'] = $this->t('Featured');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_success_cases\Entity\SuccessCase $entity */
        $row['name'] = $entity->toLink();
        $row['profession'] = $entity->get('profession')->value ?? '—';
        $row['vertical'] = $entity->get('vertical')->value ?? '—';
        $row['status'] = $entity->get('status')->value ? $this->t('Yes') : $this->t('No');
        $row['featured'] = $entity->get('featured')->value ? '⭐' : '—';
        return $row + parent::buildRow($entity);
    }

}
