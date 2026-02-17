<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad FundingOpportunity.
 *
 * Estructura: Construye la tabla de listado de convocatorias en admin.
 *   Define cabeceras y renderiza filas con datos clave.
 *
 * Logica: Muestra nombre, organismo, programa, importe maximo,
 *   deadline y estado con badge de color segun el estado.
 */
class FundingOpportunityListBuilder extends EntityListBuilder {

  /**
   * Mapa de labels de estado.
   */
  private const STATUS_LABELS = [
    'upcoming' => 'Proxima',
    'open' => 'Abierta',
    'closed' => 'Cerrada',
    'resolved' => 'Resuelta',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = new TranslatableMarkup('Nombre');
    $header['funding_body'] = new TranslatableMarkup('Organismo');
    $header['program'] = new TranslatableMarkup('Programa');
    $header['max_amount'] = new TranslatableMarkup('Importe max.');
    $header['deadline'] = new TranslatableMarkup('Fecha limite');
    $header['status'] = new TranslatableMarkup('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['name'] = $entity->label() ?? '';
    $row['funding_body'] = $entity->get('funding_body')->value ?? '';
    $row['program'] = $entity->get('program')->value ?? '-';

    $amount = $entity->get('max_amount')->value;
    $row['max_amount'] = $amount ? number_format((float) $amount, 2, ',', '.') . ' EUR' : '-';

    $row['deadline'] = $entity->get('deadline')->value ?? '-';

    $status = $entity->get('status')->value ?? '';
    $row['status'] = self::STATUS_LABELS[$status] ?? $status;

    return $row + parent::buildRow($entity);
  }

}
