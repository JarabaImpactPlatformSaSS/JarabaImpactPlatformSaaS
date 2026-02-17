<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad TechnicalReport.
 *
 * Estructura: Tabla de listado de memorias tecnicas.
 *
 * Logica: Muestra titulo, solicitud asociada, tipo de memoria,
 *   estado y si fue generada con IA.
 */
class TechnicalReportListBuilder extends EntityListBuilder {

  /**
   * Mapa de labels de tipo de memoria.
   */
  private const TYPE_LABELS = [
    'initial' => 'Memoria inicial',
    'progress' => 'Informe de progreso',
    'final' => 'Memoria final',
    'justification' => 'Informe de justificacion',
  ];

  /**
   * Mapa de labels de estado.
   */
  private const STATUS_LABELS = [
    'draft' => 'Borrador',
    'review' => 'En revision',
    'approved' => 'Aprobada',
    'submitted' => 'Presentada',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = new TranslatableMarkup('Titulo');
    $header['application'] = new TranslatableMarkup('Solicitud');
    $header['report_type'] = new TranslatableMarkup('Tipo');
    $header['status'] = new TranslatableMarkup('Estado');
    $header['ai_generated'] = new TranslatableMarkup('IA');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->label() ?? '';

    $app = $entity->get('application_id')->entity;
    $row['application'] = $app ? ($app->get('application_number')->value ?? '-') : '-';

    $type = $entity->get('report_type')->value ?? '';
    $row['report_type'] = self::TYPE_LABELS[$type] ?? $type;

    $status = $entity->get('status')->value ?? '';
    $row['status'] = self::STATUS_LABELS[$status] ?? $status;

    $ai = (bool) $entity->get('ai_generated')->value;
    $row['ai_generated'] = $ai ? (string) new TranslatableMarkup('Si') : (string) new TranslatableMarkup('No');

    return $row + parent::buildRow($entity);
  }

}
