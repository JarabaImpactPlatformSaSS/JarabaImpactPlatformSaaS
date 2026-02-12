<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad SiteHeaderConfig.
 */
class SiteHeaderConfigListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant'] = $this->t('Tenant');
    $header['header_type'] = $this->t('Tipo');
    $header['sticky'] = $this->t('Sticky');
    $header['cta'] = $this->t('CTA');
    $header['changed'] = $this->t('Modificado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_site_builder\Entity\SiteHeaderConfig $entity */
    $tenant = $entity->get('tenant_id')->entity;
    $row['tenant'] = $tenant ? $tenant->label() : '-';
    $row['header_type'] = $entity->getHeaderType();
    $row['sticky'] = $entity->isSticky() ? $this->t('Sí') : $this->t('No');
    $row['cta'] = $entity->showCta() ? ($entity->get('cta_text')->value ?? '-') : $this->t('Desactivado');

    $changed = $entity->get('changed')->value;
    $row['changed'] = $changed ? \Drupal::service('date.formatter')->format((int) $changed, 'short') : '-';

    return $row + parent::buildRow($entity);
  }

}
