<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad FundingApplication.
 *
 * Estructura: Tabla de listado de solicitudes de fondos.
 *
 * Logica: Muestra numero de solicitud, convocatoria asociada,
 *   importe solicitado, estado y fecha de creacion.
 */
class FundingApplicationListBuilder extends EntityListBuilder {

  /**
   * Mapa de labels de estado.
   */
  private const STATUS_LABELS = [
    'draft' => 'Borrador',
    'submitted' => 'Presentada',
    'approved' => 'Aprobada',
    'rejected' => 'Rechazada',
    'justifying' => 'Justificando',
    'closed' => 'Cerrada',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['application_number'] = new TranslatableMarkup('Numero');
    $header['opportunity'] = new TranslatableMarkup('Convocatoria');
    $header['amount_requested'] = new TranslatableMarkup('Importe solicitado');
    $header['status'] = new TranslatableMarkup('Estado');
    $header['created'] = new TranslatableMarkup('Creada');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['application_number'] = $entity->get('application_number')->value ?? '-';

    $opp = $entity->get('opportunity_id')->entity;
    $row['opportunity'] = $opp ? ($opp->label() ?? '-') : '-';

    $amount = $entity->get('amount_requested')->value;
    $row['amount_requested'] = $amount ? number_format((float) $amount, 2, ',', '.') . ' EUR' : '-';

    $status = $entity->get('status')->value ?? '';
    $row['status'] = self::STATUS_LABELS[$status] ?? $status;

    $created = $entity->get('created')->value;
    $row['created'] = $created ? date('d/m/Y', (int) $created) : '-';

    return $row + parent::buildRow($entity);
  }

}
