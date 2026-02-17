<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Listado de agentes autonomos en la interfaz de administracion.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave del agente con badge de estado activo/inactivo.
 */
class AutonomousAgentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Nombre');
    $header['agent_type'] = $this->t('Tipo');
    $header['vertical'] = $this->t('Vertical');
    $header['autonomy_level'] = $this->t('Nivel de autonomia');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para los tipos de agente.
    $type_labels = [
      'enrollment' => $this->t('Matriculacion'),
      'planning' => $this->t('Planificacion'),
      'support' => $this->t('Soporte'),
      'marketing' => $this->t('Marketing'),
      'analytics' => $this->t('Analitica'),
    ];

    // Etiquetas traducibles para los niveles de autonomia.
    $level_labels = [
      'l0_disabled' => $this->t('L0 — Desactivado'),
      'l1_suggestion' => $this->t('L1 — Sugerencia'),
      'l2_auto_low' => $this->t('L2 — Auto (bajo riesgo)'),
      'l3_auto_medium' => $this->t('L3 — Auto (medio riesgo)'),
      'l4_full_auto' => $this->t('L4 — Autonomia total'),
    ];

    $agent_type = $entity->get('agent_type')->value ?? '';
    $autonomy_level = $entity->get('autonomy_level')->value ?? '';
    $is_active = (bool) ($entity->get('is_active')->value ?? FALSE);

    $row['id'] = $entity->id();
    $row['name'] = $entity->get('name')->value ?? '';
    $row['agent_type'] = $type_labels[$agent_type] ?? $agent_type;
    $row['vertical'] = $entity->get('vertical')->value ?? '';
    $row['autonomy_level'] = $level_labels[$autonomy_level] ?? $autonomy_level;
    $row['is_active'] = [
      'data' => [
        '#markup' => $is_active
          ? '<span class="badge badge--success">' . $this->t('Si') . '</span>'
          : '<span class="badge badge--inactive">' . $this->t('No') . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay agentes autonomos configurados.');
    return $build;
  }

}
