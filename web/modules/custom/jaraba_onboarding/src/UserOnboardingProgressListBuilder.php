<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de progresos de onboarding en admin.
 */
class UserOnboardingProgressListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user_id'] = $this->t('Usuario');
    $header['template_id'] = $this->t('Template');
    $header['current_step'] = $this->t('Paso Actual');
    $header['progress_percentage'] = $this->t('Progreso');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['user_id'] = $entity->get('user_id')->target_id ?? '-';
    $row['template_id'] = $entity->get('template_id')->target_id ?? '-';
    $row['current_step'] = $entity->get('current_step')->value ?? '0';
    $row['progress_percentage'] = ($entity->get('progress_percentage')->value ?? 0) . '%';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    return $row + parent::buildRow($entity);
  }

}
