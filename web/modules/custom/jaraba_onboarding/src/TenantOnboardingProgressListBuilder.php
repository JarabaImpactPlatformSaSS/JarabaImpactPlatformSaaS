<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para TenantOnboardingProgress.
 *
 * Fase 5 — Doc 179.
 */
class TenantOnboardingProgressListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['tenant'] = $this->t('Tenant');
    $header['vertical'] = $this->t('Vertical');
    $header['step'] = $this->t('Paso Actual');
    $header['progress'] = $this->t('Progreso');
    $header['started'] = $this->t('Inicio');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_onboarding\Entity\TenantOnboardingProgress $entity */
    $row['id'] = $entity->id();
    $row['tenant'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $row['step'] = sprintf('%d/7', (int) $entity->get('current_step')->value);
    $row['progress'] = $entity->getProgressPercentage() . '%';
    $row['started'] = $entity->get('started_at')->value
      ? date('Y-m-d H:i', (int) $entity->get('started_at')->value)
      : '-';
    return $row + parent::buildRow($entity);
  }

}
