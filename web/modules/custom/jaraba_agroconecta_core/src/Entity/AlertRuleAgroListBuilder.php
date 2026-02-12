<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class AlertRuleAgroListBuilder extends EntityListBuilder
{
    public function buildHeader(): array
    {
        $header['name'] = $this->t('Nombre');
        $header['metric'] = $this->t('Métrica');
        $header['condition'] = $this->t('Condición');
        $header['threshold'] = $this->t('Umbral');
        $header['severity'] = $this->t('Severidad');
        $header['triggers'] = $this->t('Activaciones');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AlertRuleAgro $entity */
        $row['name'] = $entity->getName();
        $row['metric'] = $entity->getMetric();
        $conditions = ['lt' => '<', 'lte' => '≤', 'gt' => '>', 'gte' => '≥', 'drop_pct' => '↓%'];
        $row['condition'] = $conditions[$entity->getCondition()] ?? $entity->getCondition();
        $row['threshold'] = $entity->getThreshold();
        $severities = ['info' => 'ℹ', 'warning' => '⚠', 'critical' => '🔴'];
        $row['severity'] = $severities[$entity->getSeverity()] ?? $entity->getSeverity();
        $row['triggers'] = $entity->get('trigger_count')->value ?? 0;
        $row['status'] = $entity->isActive() ? '✅' : '⏸';
        return $row + parent::buildRow($entity);
    }
}
