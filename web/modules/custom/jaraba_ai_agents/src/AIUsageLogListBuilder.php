<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for AI Usage Log entities.
 */
class AIUsageLogListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['created'] = $this->t('Date');
        $header['agent_id'] = $this->t('Agent');
        $header['action'] = $this->t('Action');
        $header['tier'] = $this->t('Tier');
        $header['cost'] = $this->t('Cost');
        $header['duration_ms'] = $this->t('Duration');
        $header['success'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\AIUsageLog $entity */
        $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
        $row['agent_id'] = $entity->getAgentId();
        $row['action'] = $entity->getAction();
        $row['tier'] = $entity->getTier();
        $row['cost'] = '$' . number_format($entity->getCost(), 6);
        $row['duration_ms'] = $entity->get('duration_ms')->value . 'ms';
        $row['success'] = $entity->isSuccessful() ? '✅' : '❌';
        return $row + parent::buildRow($entity);
    }

}
