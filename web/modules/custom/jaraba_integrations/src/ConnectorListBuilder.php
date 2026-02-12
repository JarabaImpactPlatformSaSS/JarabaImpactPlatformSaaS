<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad Connector.
 *
 * PROPÓSITO:
 * Tabla administrativa en /admin/content/connectors con columnas
 * de nombre, categoría, tipo de auth, estado e instalaciones.
 */
class ConnectorListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['category'] = $this->t('Categoría');
    $header['auth_type'] = $this->t('Autenticación');
    $header['version'] = $this->t('Versión');
    $header['install_count'] = $this->t('Instalaciones');
    $header['publish_status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_integrations\Entity\Connector $entity */
    $row['name'] = $entity->toLink();
    $row['category'] = $entity->get('category')->view(['label' => 'hidden']);
    $row['auth_type'] = $entity->get('auth_type')->view(['label' => 'hidden']);
    $row['version'] = $entity->get('version')->value ?? '1.0.0';

    $install_count = (int) ($entity->get('install_count')->value ?? 0);
    $row['install_count'] = [
      '#markup' => '<span class="badge badge--info">' . $install_count . '</span>',
    ];

    // Badge de estado con colores semánticos.
    $status = $entity->getPublishStatus();
    $status_classes = [
      'draft' => 'badge--warning',
      'published' => 'badge--success',
      'deprecated' => 'badge--error',
    ];
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'published' => $this->t('Publicado'),
      'deprecated' => $this->t('Obsoleto'),
    ];
    $badge_class = $status_classes[$status] ?? 'badge--default';
    $badge_label = $status_labels[$status] ?? $status;

    $row['publish_status'] = [
      '#markup' => '<span class="badge ' . $badge_class . '">' . $badge_label . '</span>',
    ];

    return $row + parent::buildRow($entity);
  }

}
