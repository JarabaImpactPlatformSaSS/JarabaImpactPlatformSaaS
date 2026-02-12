<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CrossVerticalProgress.
 */
class CrossVerticalProgressListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user'] = $this->t('Usuario');
    $header['rule'] = $this->t('Regla');
    $header['progress'] = $this->t('Progreso (%)');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_credentials_cross_vertical\Entity\CrossVerticalProgress $entity */
    $user = $entity->getOwner();
    $row['user'] = $user ? $user->getDisplayName() : '-';

    $ruleId = $entity->get('rule_id')->target_id ?? NULL;
    if ($ruleId) {
      $rule = \Drupal::entityTypeManager()->getStorage('cross_vertical_rule')->load($ruleId);
      $row['rule'] = $rule ? $rule->get('name')->value : '#' . $ruleId;
    }
    else {
      $row['rule'] = '-';
    }

    $row['progress'] = $entity->get('overall_percent')->value . '%';

    $status = $entity->get('status')->value ?? '';
    $row['status'] = match ($status) {
      'tracking' => $this->t('En seguimiento'),
      'completed' => $this->t('Completado'),
      default => $status,
    };

    return $row + parent::buildRow($entity);
  }

}
