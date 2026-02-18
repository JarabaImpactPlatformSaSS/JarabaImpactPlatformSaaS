<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de medidas de compliance ENS con badges de estado y nivel.
 *
 * Renderiza la tabla administrativa de EnsCompliance
 * en /admin/content/ens-compliance.
 *
 * Muestra: measure_id, measure_name, category, required_level, current_status.
 *
 * Colores de current_status:
 * - implemented: verde (#198754)
 * - partial: naranja (#fd7e14)
 * - not_implemented: rojo (#dc3545)
 * - not_applicable: gris (#6c757d)
 */
class EnsComplianceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['measure_id'] = $this->t('ID Medida');
    $header['measure_name'] = $this->t('Nombre');
    $header['category'] = $this->t('Categoria');
    $header['required_level'] = $this->t('Nivel Requerido');
    $header['current_status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_security_compliance\Entity\EnsCompliance $entity */

    // Categoria.
    $categoryLabels = [
      'organizational' => $this->t('Marco Organizativo'),
      'operational' => $this->t('Marco Operacional'),
      'protection' => $this->t('Medidas de Proteccion'),
    ];
    $category = $entity->getCategory();
    $categoryLabel = $categoryLabels[$category] ?? $category;

    // Nivel requerido.
    $levelLabels = [
      'basic' => $this->t('Basico'),
      'medium' => $this->t('Medio'),
      'high' => $this->t('Alto'),
    ];
    $level = $entity->getRequiredLevel();
    $levelLabel = $levelLabels[$level] ?? $level;

    // Badge de estado.
    $statusColors = [
      'implemented' => '#198754',
      'partial' => '#fd7e14',
      'not_implemented' => '#dc3545',
      'not_applicable' => '#6c757d',
    ];
    $statusLabels = [
      'implemented' => $this->t('Implementada'),
      'partial' => $this->t('Parcial'),
      'not_implemented' => $this->t('No implementada'),
      'not_applicable' => $this->t('No aplica'),
    ];
    $status = $entity->getCurrentStatus();
    $statusColor = $statusColors[$status] ?? '#6c757d';
    $statusLabel = $statusLabels[$status] ?? $status;

    $row['measure_id'] = [
      'data' => [
        '#markup' => '<code>' . htmlspecialchars($entity->getMeasureId()) . '</code>',
      ],
    ];
    $row['measure_name'] = $entity->getMeasureName();
    $row['category'] = $categoryLabel;
    $row['required_level'] = $levelLabel;
    $row['current_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
