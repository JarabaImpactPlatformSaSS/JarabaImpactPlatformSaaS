<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jaraba_workflows\Entity\WorkflowRuleInterface;

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
        $header['tenant_id'] = $this->t('Tenant');
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
        $row['tenant_id'] = $entity->getTenantId() === 0
            ? (string) $this->t('Global')
            : (string) $entity->getTenantId();
        $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
        $row['weight'] = $entity->getWeight();
        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function load(): array
    {
        $entities = parent::load();

        // Sort by status (enabled first), then weight.
        uasort($entities, function (WorkflowRuleInterface $a, WorkflowRuleInterface $b) {
            $statusCmp = (int) $b->status() - (int) $a->status();
            if ($statusCmp !== 0) {
                return $statusCmp;
            }
            return $a->getWeight() <=> $b->getWeight();
        });

        return $entities;
    }

}
