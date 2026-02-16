<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de consultas juridicas en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-inquiries.
 *
 * Logica: Muestra columnas clave: numero, asunto, estado,
 *   origen, consultante y fecha de creacion.
 */
class ClientInquiryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['inquiry_number'] = $this->t('Referencia');
    $header['subject'] = $this->t('Asunto');
    $header['status'] = $this->t('Estado');
    $header['source'] = $this->t('Origen');
    $header['client_name'] = $this->t('Consultante');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'triaged' => $this->t('Triada'),
      'assigned' => $this->t('Asignada'),
      'converted' => $this->t('Convertida'),
      'rejected' => $this->t('Rechazada'),
    ];
    $source_labels = [
      'web_form' => $this->t('Web'),
      'phone' => $this->t('Telefono'),
      'email' => $this->t('Email'),
      'referral' => $this->t('Referido'),
      'lexnet' => $this->t('LexNET'),
    ];

    $status = $entity->get('status')->value;
    $source = $entity->get('source')->value;
    $created = $entity->get('created')->value;

    $row['inquiry_number'] = $entity->get('inquiry_number')->value ?? '';
    $row['subject'] = $entity->get('subject')->value ?? '';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['source'] = $source_labels[$source] ?? $source;
    $row['client_name'] = $entity->get('client_name')->value ?? '';
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';
    return $row + parent::buildRow($entity);
  }

}
