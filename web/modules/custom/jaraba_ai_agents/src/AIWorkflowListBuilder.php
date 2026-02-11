<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for AI Workflow entities.
 */
class AIWorkflowListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['label'] = $this->t('Workflow');
        $header['description'] = $this->t('Description');
        $header['steps'] = $this->t('Steps');
        $header['status'] = $this->t('Status');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_ai_agents\Entity\AIWorkflow $entity */
        $row['label'] = $entity->label();
        $row['description'] = $entity->getDescription();
        $row['steps'] = count($entity->getSteps());
        $row['status'] = $entity->status() ? '✅ Active' : '❌ Disabled';
        return $row + parent::buildRow($entity);
    }

}
