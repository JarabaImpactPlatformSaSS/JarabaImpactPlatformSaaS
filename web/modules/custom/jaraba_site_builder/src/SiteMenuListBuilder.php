<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad SiteMenu.
 */
class SiteMenuListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Nombre');
    $header['machine_name'] = $this->t('Nombre máquina');
    $header['tenant'] = $this->t('Tenant');
    $header['changed'] = $this->t('Modificado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_site_builder\Entity\SiteMenu $entity */
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->getMachineName();

    $tenant = $entity->get('tenant_id')->entity;
    $row['tenant'] = $tenant ? $tenant->label() : '-';

    $changed = $entity->get('changed')->value;
    $row['changed'] = $changed ? \Drupal::service('date.formatter')->format((int) $changed, 'short') : '-';

    return $row + parent::buildRow($entity);
  }

}
