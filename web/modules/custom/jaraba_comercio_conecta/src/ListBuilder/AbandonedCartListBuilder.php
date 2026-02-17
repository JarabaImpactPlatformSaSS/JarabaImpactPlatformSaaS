<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class AbandonedCartListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['email'] = $this->t('Email');
    $header['value'] = $this->t('Valor');
    $header['recovery_sent'] = $this->t('Email enviado');
    $header['recovered'] = $this->t('Recuperado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['email'] = $entity->get('email')->value ?? '-';
    $row['value'] = number_format((float) $entity->get('cart_value')->value, 2, ',', '.') . ' EUR';
    $row['recovery_sent'] = $entity->get('recovery_sent')->value ? $this->t('Si') : $this->t('No');
    $row['recovered'] = $entity->get('recovered')->value ? $this->t('Si') : $this->t('No');
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
