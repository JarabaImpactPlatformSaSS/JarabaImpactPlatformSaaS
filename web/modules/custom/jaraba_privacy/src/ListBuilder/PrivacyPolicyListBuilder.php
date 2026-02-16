<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de políticas de privacidad en admin.
 *
 * Muestra versión, vertical, tenant, estado activo y fecha de publicación.
 */
class PrivacyPolicyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['version'] = $this->t('Versión');
    $header['vertical'] = $this->t('Vertical');
    $header['tenant_id'] = $this->t('Tenant');
    $header['is_active'] = $this->t('Activa');
    $header['published_at'] = $this->t('Publicada');
    $header['created'] = $this->t('Creada');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $published_at = $entity->get('published_at')->value;

    $row['version'] = $entity->get('version')->value ?? '-';
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    $row['published_at'] = $published_at ? date('d/m/Y H:i', (int) $published_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
