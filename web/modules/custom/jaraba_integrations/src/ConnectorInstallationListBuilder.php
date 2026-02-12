<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para ConnectorInstallation.
 */
class ConnectorInstallationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['connector'] = $this->t('Conector');
    $header['tenant'] = $this->t('Tenant');
    $header['status'] = $this->t('Estado');
    $header['installed_by'] = $this->t('Instalado por');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_integrations\Entity\ConnectorInstallation $entity */
    $connector = $entity->getConnector();
    $row['connector'] = $connector ? $connector->getName() : $this->t('(eliminado)');
    $row['tenant'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';

    $status = $entity->getInstallationStatus();
    $status_classes = [
      'active' => 'badge--success',
      'inactive' => 'badge--warning',
      'error' => 'badge--error',
      'pending_config' => 'badge--info',
    ];
    $badge_class = $status_classes[$status] ?? 'badge--default';
    $row['status'] = [
      '#markup' => '<span class="badge ' . $badge_class . '">' . $status . '</span>',
    ];

    $installed_by = $entity->get('installed_by')->entity;
    $row['installed_by'] = $installed_by ? $installed_by->getDisplayName() : '-';
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
