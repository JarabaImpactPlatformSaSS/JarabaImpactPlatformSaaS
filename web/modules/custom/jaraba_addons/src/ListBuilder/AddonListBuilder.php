<?php

namespace Drupal\jaraba_addons\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de add-ons en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/addons.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre,
 *   tipo, precio mensual, precio anual y estado de activación.
 *
 * RELACIONES:
 * - AddonListBuilder -> Addon entity (lista)
 * - AddonListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AddonListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Nombre');
    $header['addon_type'] = $this->t('Tipo');
    $header['price_monthly'] = $this->t('Precio Mensual');
    $header['price_yearly'] = $this->t('Precio Anual');
    $header['is_active'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'feature' => $this->t('Feature'),
      'storage' => $this->t('Storage'),
      'api_calls' => $this->t('API Calls'),
      'support' => $this->t('Soporte'),
      'custom' => $this->t('Personalizado'),
    ];

    $type = $entity->get('addon_type')->value;
    $monthly = $entity->get('price_monthly')->value;
    $yearly = $entity->get('price_yearly')->value;
    $is_active = (bool) $entity->get('is_active')->value;

    $row['label'] = $entity->label();
    $row['addon_type'] = $type_labels[$type] ?? $type;
    $row['price_monthly'] = number_format((float) ($monthly ?? 0), 2) . ' EUR/mes';
    $row['price_yearly'] = number_format((float) ($yearly ?? 0), 2) . ' EUR/año';
    $row['is_active'] = $is_active ? $this->t('Activo') : $this->t('Inactivo');
    return $row + parent::buildRow($entity);
  }

}
