<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de templates de onboarding en admin.
 */
class OnboardingTemplateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['vertical'] = $this->t('Vertical');
    $header['tenant_id'] = $this->t('Tenant');
    $header['status'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value ?? '-';
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['status'] = $entity->get('status')->value ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
