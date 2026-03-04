<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for FairUsePolicy ConfigEntity.
 */
class FairUsePolicyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['tier'] = $this->t('Tier');
    $header['burst'] = $this->t('Burst %');
    $header['grace'] = $this->t('Grace (h)');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ecosistema_jaraba_core\Entity\FairUsePolicy $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['tier'] = $entity->getTier();
    $row['burst'] = $entity->getBurstTolerancePct() . '%';
    $row['grace'] = $entity->getGracePeriodHours() . 'h';
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
