<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for WorkflowRule config entities.
 */
class WorkflowRuleListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['label'] = $this->t('Rule');
        $header['trigger_type'] = $this->t('Trigger');
        $header['status'] = $this->t('Status');
        $header['weight'] = $this->t('Weight');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_workflows\Entity\WorkflowRuleInterface $entity */
        $row['label'] = $entity->label();
        $row['trigger_type'] = $entity->getTriggerType();
        $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
        $row['weight'] = $entity->getWeight();
        return $row + parent::buildRow($entity);
    }

}
