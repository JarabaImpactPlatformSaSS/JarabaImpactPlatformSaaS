<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de convocatorias de subvenciones en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/funding-calls.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: titulo,
 *   BDNS ID, region, tipo de convocatoria, plazo y estado.
 *
 * RELACIONES:
 * - FundingCallListBuilder -> FundingCall entity (lista)
 * - FundingCallListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class FundingCallListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['bdns_id'] = $this->t('BDNS ID');
    $header['region'] = $this->t('Region');
    $header['call_type'] = $this->t('Tipo');
    $header['deadline'] = $this->t('Plazo');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $call_type_labels = [
      'subvencion' => $this->t('Subvencion'),
      'ayuda' => $this->t('Ayuda'),
      'prestamo' => $this->t('Prestamo'),
      'incentivo' => $this->t('Incentivo'),
      'premio' => $this->t('Premio'),
    ];

    $status_labels = [
      'open' => $this->t('Abierta'),
      'closed' => $this->t('Cerrada'),
      'pending_resolution' => $this->t('Pendiente Resolucion'),
      'resolved' => $this->t('Resuelta'),
      'draft' => $this->t('Borrador'),
    ];

    $callType = $entity->get('call_type')->value;
    $status = $entity->get('status')->value;
    $deadline = $entity->get('deadline')->value;

    $row['title'] = $entity->get('title')->value ?? '-';
    $row['bdns_id'] = $entity->get('bdns_id')->value ?? '-';
    $row['region'] = $entity->get('region')->value ?? '-';
    $row['call_type'] = $call_type_labels[$callType] ?? $callType;
    $row['deadline'] = $deadline ? date('Y-m-d', (int) $deadline) : '-';
    $row['status'] = $status_labels[$status] ?? $status;
    return $row + parent::buildRow($entity);
  }

}
