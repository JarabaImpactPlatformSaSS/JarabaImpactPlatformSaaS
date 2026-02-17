<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Constructor de listado para la entidad Programa Institucional.
 *
 * Estructura: Extiende EntityListBuilder para mostrar programas
 *   en tabla administrativa con columnas clave y clases de estado.
 *
 * Logica: Muestra ID, nombre, tipo, codigo, entidad financiadora,
 *   estado (con clases de color segun fase) y operaciones.
 */
class InstitutionalProgramListBuilder extends EntityListBuilder {

  /**
   * Mapa de clases CSS segun el estado del programa.
   */
  protected const STATUS_CLASSES = [
    'draft' => 'institutional-status--neutral',
    'active' => 'institutional-status--success',
    'reporting' => 'institutional-status--warning',
    'closed' => 'institutional-status--info',
    'audited' => 'institutional-status--muted',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Nombre');
    $header['program_type'] = $this->t('Tipo');
    $header['program_code'] = $this->t('Codigo');
    $header['funding_entity'] = $this->t('Entidad financiadora');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_institutional\Entity\InstitutionalProgram $entity */
    $status = $entity->get('status')->value ?? 'draft';
    $statusClass = self::STATUS_CLASSES[$status] ?? 'institutional-status--neutral';

    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['program_type'] = $entity->get('program_type')->value ?? '';
    $row['program_code'] = $entity->get('program_code')->value ?? '';
    $row['funding_entity'] = $entity->get('funding_entity')->value ?? '';
    $row['status'] = [
      'data' => [
        '#markup' => '<span class="institutional-status ' . $statusClass . '">' . $this->t('@status', ['@status' => $status]) . '</span>',
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
