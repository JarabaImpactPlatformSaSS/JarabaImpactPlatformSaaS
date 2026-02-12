<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para UserStackProgress.
 */
class UserStackProgressListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user'] = $this->t('Usuario');
    $header['stack'] = $this->t('Stack');
    $header['progress'] = $this->t('Progreso');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_credentials\Entity\UserStackProgress $entity */
    $user = $entity->getOwner();
    $row['user'] = $user ? $user->getDisplayName() : '-';

    $stackId = $entity->get('stack_id')->target_id ?? NULL;
    if ($stackId) {
      $stack = \Drupal::entityTypeManager()->getStorage('credential_stack')->load($stackId);
      $row['stack'] = $stack ? $stack->get('name')->value : '#' . $stackId;
    }
    else {
      $row['stack'] = '-';
    }

    $row['progress'] = $entity->getProgressPercent() . '%';

    $status = $entity->get('status')->value ?? '';
    $row['status'] = match ($status) {
      'in_progress' => $this->t('En progreso'),
      'completed' => $this->t('Completado'),
      default => $status,
    };

    return $row + parent::buildRow($entity);
  }

}
