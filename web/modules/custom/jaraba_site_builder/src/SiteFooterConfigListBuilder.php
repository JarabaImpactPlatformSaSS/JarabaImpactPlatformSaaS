<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad SiteFooterConfig.
 */
class SiteFooterConfigListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['footer_type'] = $this->t('Tipo');
    $header['newsletter'] = $this->t('Newsletter');
    $header['social'] = $this->t('Social');
    $header['changed'] = $this->t('Modificado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_site_builder\Entity\SiteFooterConfig $entity */
    $tenant = $entity->get('tenant_id')->entity;
    $row['tenant'] = $tenant ? $tenant->label() : '-';
    $row['footer_type'] = $entity->getFooterType();
    $row['newsletter'] = $entity->showNewsletter() ? $this->t('Sí') : $this->t('No');
    $row['social'] = $entity->showSocial() ? $this->t('Sí') : $this->t('No');

    $changed = $entity->get('changed')->value;
    $row['changed'] = $changed ? \Drupal::service('date.formatter')->format((int) $changed, 'short') : '-';

    return $row + parent::buildRow($entity);
  }

}
