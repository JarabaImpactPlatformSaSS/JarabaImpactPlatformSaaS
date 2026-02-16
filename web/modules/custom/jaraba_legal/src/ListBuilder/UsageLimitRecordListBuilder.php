<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de Usage Limit Records en admin.
 *
 * Muestra tenant, tipo de límite, valor, uso actual, periodo, excedido y acciones.
 */
class UsageLimitRecordListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_id'] = $this->t('Tenant');
    $header['limit_type'] = $this->t('Tipo');
    $header['limit_value'] = $this->t('Límite');
    $header['current_usage'] = $this->t('Uso actual');
    $header['period'] = $this->t('Periodo');
    $header['exceeded_at'] = $this->t('Excedido');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'api_calls' => $this->t('API calls'),
      'storage_mb' => $this->t('Storage (MB)'),
      'bandwidth_mb' => $this->t('Bandwidth (MB)'),
      'users' => $this->t('Usuarios'),
      'pages' => $this->t('Páginas'),
      'products' => $this->t('Productos'),
    ];

    $period_labels = [
      'daily' => $this->t('Diario'),
      'monthly' => $this->t('Mensual'),
      'yearly' => $this->t('Anual'),
    ];

    $type = $entity->get('limit_type')->value;
    $period = $entity->get('period')->value;
    $exceeded_at = $entity->get('exceeded_at')->value;

    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['limit_type'] = $type_labels[$type] ?? $type;
    $row['limit_value'] = $entity->get('limit_value')->value ?? '0';
    $row['current_usage'] = $entity->get('current_usage')->value ?? '0';
    $row['period'] = $period_labels[$period] ?? $period;
    $row['exceeded_at'] = $exceeded_at ? date('d/m/Y H:i', (int) $exceeded_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
