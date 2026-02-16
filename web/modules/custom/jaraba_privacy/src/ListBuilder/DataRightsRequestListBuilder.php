<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de solicitudes de derechos ARCO-POL en admin.
 *
 * Muestra solicitante, tipo de derecho, estado, fecha límite y responsable.
 * Destaca visualmente las solicitudes próximas a vencer.
 */
class DataRightsRequestListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['requester_name'] = $this->t('Solicitante');
    $header['right_type'] = $this->t('Derecho');
    $header['status'] = $this->t('Estado');
    $header['identity_verified'] = $this->t('Verificado');
    $header['deadline'] = $this->t('Fecha límite');
    $header['handler_id'] = $this->t('Responsable');
    $header['created'] = $this->t('Recibida');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $right_labels = [
      'access' => $this->t('Acceso'),
      'rectification' => $this->t('Rectificación'),
      'erasure' => $this->t('Supresión'),
      'restriction' => $this->t('Limitación'),
      'portability' => $this->t('Portabilidad'),
      'objection' => $this->t('Oposición'),
    ];

    $status_labels = [
      'received' => $this->t('Recibida'),
      'pending_verification' => $this->t('Verificación'),
      'in_progress' => $this->t('En proceso'),
      'completed' => $this->t('Completada'),
      'rejected' => $this->t('Rechazada'),
      'expired' => $this->t('Vencida'),
    ];

    $right_type = $entity->get('right_type')->value;
    $status = $entity->get('status')->value;
    $deadline = $entity->get('deadline')->value;
    $handler = $entity->get('handler_id')->entity;

    $row['requester_name'] = $entity->get('requester_name')->value ?? '-';
    $row['right_type'] = $right_labels[$right_type] ?? $right_type;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['identity_verified'] = $entity->get('identity_verified')->value ? $this->t('Sí') : $this->t('No');
    $row['deadline'] = $deadline ? date('d/m/Y', (int) $deadline) : '-';
    $row['handler_id'] = $handler ? $handler->getAccountName() : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
