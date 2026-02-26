<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for ProactiveInsight entities.
 *
 * GAP-AUD-010: Admin list at /admin/content/proactive-insight.
 */
class ProactiveInsightListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['title'] = $this->t('Title');
        $header['insight_type'] = $this->t('Type');
        $header['severity'] = $this->t('Severity');
        $header['target_user'] = $this->t('Target User');
        $header['read_status'] = $this->t('Read');
        $header['created'] = $this->t('Created');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\ProactiveInsightInterface $entity */
        $row['title'] = $entity->getTitle();
        $row['insight_type'] = $entity->getInsightType();
        $row['severity'] = $entity->getSeverity();

        // Target user display.
        $targetUserId = $entity->getTargetUserId();
        $row['target_user'] = $targetUserId > 0 ? "User #{$targetUserId}" : '-';

        $row['read_status'] = $entity->isRead() ? $this->t('Yes') : $this->t('No');
        $row['created'] = \Drupal::service('date.formatter')
            ->format($entity->get('created')->value, 'short');

        return $row + parent::buildRow($entity);
    }

}
