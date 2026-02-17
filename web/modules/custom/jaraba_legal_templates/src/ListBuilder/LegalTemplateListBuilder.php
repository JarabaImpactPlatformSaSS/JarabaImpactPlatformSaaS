<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Admin list builder para LegalTemplate.
 */
class LegalTemplateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['template_type'] = $this->t('Tipo');
    $header['is_system'] = $this->t('Sistema');
    $header['is_active'] = $this->t('Activa');
    $header['usage_count'] = $this->t('Usos');
    $header['created'] = $this->t('Creada');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->get('name')->value ?? '';
    $row['template_type'] = $entity->get('template_type')->value ?? '';
    $row['is_system'] = $entity->get('is_system')->value ? $this->t('Si') : $this->t('No');
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    $row['usage_count'] = (string) ($entity->get('usage_count')->value ?? 0);
    $row['created'] = $entity->get('created')->value
      ? date('d/m/Y', (int) $entity->get('created')->value)
      : '';
    return $row + parent::buildRow($entity);
  }

}
