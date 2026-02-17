<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de presupuestos en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave de presupuestos.
 */
class QuoteListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['quote_number'] = $this->t('Numero');
    $header['title'] = $this->t('Titulo');
    $header['client_name'] = $this->t('Cliente');
    $header['total'] = $this->t('Total');
    $header['status'] = $this->t('Estado');
    $header['valid_until'] = $this->t('Valido hasta');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'sent' => $this->t('Enviado'),
      'viewed' => $this->t('Visto'),
      'accepted' => $this->t('Aceptado'),
      'rejected' => $this->t('Rechazado'),
      'expired' => $this->t('Expirado'),
    ];

    $status = $entity->get('status')->value;
    $total = (float) ($entity->get('total')->value ?? 0);

    $row['quote_number'] = $entity->get('quote_number')->value ?? '-';
    $row['title'] = $entity->get('title')->value ?? '';
    $row['client_name'] = $entity->get('client_name')->value ?? '';
    $row['total'] = number_format($total, 2, ',', '.') . ' EUR';
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['valid_until'] = $entity->get('valid_until')->value ?? '-';

    return $row + parent::buildRow($entity);
  }

}
