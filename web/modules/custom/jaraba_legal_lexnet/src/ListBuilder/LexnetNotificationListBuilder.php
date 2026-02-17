<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de notificaciones LexNET en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/lexnet-notifications.
 *
 * Logica: Muestra columnas clave: tipo, asunto, organo judicial,
 *   procedimiento, estado, fecha recepcion y plazo.
 */
class LexnetNotificationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['notification_type'] = $this->t('Tipo');
    $header['subject'] = $this->t('Asunto');
    $header['court'] = $this->t('Organo Judicial');
    $header['procedure_number'] = $this->t('Procedimiento');
    $header['status'] = $this->t('Estado');
    $header['received_at'] = $this->t('Recibida');
    $header['computed_deadline'] = $this->t('Plazo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'resolucion' => $this->t('Resolucion'),
      'comunicacion' => $this->t('Comunicacion'),
      'requerimiento' => $this->t('Requerimiento'),
      'citacion' => $this->t('Citacion'),
      'emplazamiento' => $this->t('Emplazamiento'),
      'notificacion_electronica' => $this->t('Notificacion Electronica'),
    ];

    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'read' => $this->t('Leida'),
      'linked' => $this->t('Vinculada'),
      'archived' => $this->t('Archivada'),
    ];

    $type = $entity->get('notification_type')->value;
    $status = $entity->get('status')->value;
    $received = $entity->get('received_at')->value;
    $deadline = $entity->get('computed_deadline')->value;

    $row['notification_type'] = $type_labels[$type] ?? $type;
    $row['subject'] = $entity->get('subject')->value ?? '';
    $row['court'] = $entity->get('court')->value ?? '';
    $row['procedure_number'] = $entity->get('procedure_number')->value ?? '';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['received_at'] = $received ? date('d/m/Y H:i', strtotime($received)) : '';
    $row['computed_deadline'] = $deadline ? date('d/m/Y', strtotime($deadline)) : '-';
    return $row + parent::buildRow($entity);
  }

}
