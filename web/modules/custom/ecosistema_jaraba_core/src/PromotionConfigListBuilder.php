<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder para PromotionConfig entities.
 */
class PromotionConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildHeader(): array {
    $header = [];
    $header['label'] = $this->t('Promoción');
    $header['vertical'] = $this->t('Vertical');
    $header['type'] = $this->t('Tipo');
    $header['priority'] = $this->t('Prioridad');
    $header['dates'] = $this->t('Vigencia');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\PromotionConfigInterface $entity */
    $row = [];
    $row['label'] = $entity->label();
    $row['vertical'] = $entity->getVertical();
    $row['type'] = $entity->getType();
    $row['priority'] = (string) $entity->getPriority();

    $start = $entity->getDateStart() !== '' ? $entity->getDateStart() : '∞';
    $end = $entity->getDateEnd() !== '' ? $entity->getDateEnd() : '∞';
    $row['dates'] = $start . ' → ' . $end;

    $row['status'] = $entity->isCurrentlyActive()
      ? $this->t('Activa')
      : $this->t('Inactiva');

    return $row + parent::buildRow($entity);
  }

}
