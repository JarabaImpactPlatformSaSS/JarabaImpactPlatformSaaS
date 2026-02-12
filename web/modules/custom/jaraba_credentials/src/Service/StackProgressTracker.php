<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\CredentialStack;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Entity\UserStackProgress;
use Psr\Log\LoggerInterface;

/**
 * Rastrea y actualiza el progreso incremental de los stacks.
 */
class StackProgressTracker {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials');
  }

  /**
   * Crea o actualiza el progreso de un usuario en un stack.
   */
  public function updateProgress(CredentialStack $stack, int $uid, array $completedTemplateIds): UserStackProgress {
    $progress = $this->getOrCreateProgress($stack, $uid);

    $requiredIds = $stack->getRequiredTemplateIds();
    $matched = array_values(array_intersect($requiredIds, $completedTemplateIds));
    $total = count($requiredIds);
    $percent = $total > 0 ? (int) round((count($matched) / $total) * 100) : 0;

    $progress->set('completed_templates', json_encode($matched));
    $progress->set('progress_percent', min($percent, 100));

    if ($percent >= 100) {
      $progress->set('status', UserStackProgress::STATUS_COMPLETED);
      $progress->set('completed_at', date('Y-m-d\TH:i:s'));
    }

    $progress->save();

    return $progress;
  }

  /**
   * Obtiene todos los stacks con progreso del usuario.
   */
  public function getProgressForUser(int $uid): array {
    $ids = $this->entityTypeManager->getStorage('user_stack_progress')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $uid)
      ->sort('progress_percent', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $progressEntities = $this->entityTypeManager->getStorage('user_stack_progress')->loadMultiple($ids);
    $result = [];

    foreach ($progressEntities as $progress) {
      /** @var \Drupal\jaraba_credentials\Entity\UserStackProgress $progress */
      $stackId = $progress->get('stack_id')->target_id ?? NULL;
      if (!$stackId) {
        continue;
      }

      $stack = $this->entityTypeManager->getStorage('credential_stack')->load($stackId);
      if (!$stack) {
        continue;
      }

      $result[] = [
        'stack' => $stack,
        'progress' => $progress,
        'percent' => $progress->getProgressPercent(),
        'completed_templates' => $progress->getCompletedTemplateIds(),
        'status' => $progress->get('status')->value,
      ];
    }

    return $result;
  }

  /**
   * Obtiene stacks recomendados (mayor progreso, próximos a completar).
   */
  public function getRecommendedStacks(int $uid): array {
    $allProgress = $this->getProgressForUser($uid);

    // Filtrar solo los que están en progreso (no completados).
    $inProgress = array_filter($allProgress, function ($item) {
      return $item['status'] === UserStackProgress::STATUS_IN_PROGRESS;
    });

    // Ordenar por porcentaje descendente (los más cerca de completar primero).
    usort($inProgress, function ($a, $b) {
      return $b['percent'] - $a['percent'];
    });

    // También agregar stacks sin progreso que el usuario podría empezar.
    $existingStackIds = array_map(function ($item) {
      return $item['stack']->id();
    }, $allProgress);

    $allStackIds = $this->entityTypeManager->getStorage('credential_stack')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->execute();

    $userTemplateIds = $this->getUserTemplateIds($uid);

    foreach ($allStackIds as $stackId) {
      if (in_array($stackId, $existingStackIds)) {
        continue;
      }

      $stack = $this->entityTypeManager->getStorage('credential_stack')->load($stackId);
      if (!$stack) {
        continue;
      }

      $requiredIds = $stack->getRequiredTemplateIds();
      $matched = array_intersect($requiredIds, $userTemplateIds);
      if (!empty($matched)) {
        $percent = (int) round((count($matched) / count($requiredIds)) * 100);
        $inProgress[] = [
          'stack' => $stack,
          'progress' => NULL,
          'percent' => $percent,
          'completed_templates' => array_values($matched),
          'status' => 'potential',
        ];
      }
    }

    usort($inProgress, function ($a, $b) {
      return $b['percent'] - $a['percent'];
    });

    return array_slice($inProgress, 0, 10);
  }

  /**
   * Obtiene o crea un UserStackProgress.
   */
  protected function getOrCreateProgress(CredentialStack $stack, int $uid): UserStackProgress {
    $ids = $this->entityTypeManager->getStorage('user_stack_progress')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('stack_id', $stack->id())
      ->condition('user_id', $uid)
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      return $this->entityTypeManager->getStorage('user_stack_progress')->load(reset($ids));
    }

    /** @var \Drupal\jaraba_credentials\Entity\UserStackProgress $progress */
    $progress = $this->entityTypeManager->getStorage('user_stack_progress')->create([
      'stack_id' => $stack->id(),
      'user_id' => $uid,
      'started_at' => date('Y-m-d\TH:i:s'),
    ]);

    return $progress;
  }

  /**
   * Obtiene los template IDs del usuario.
   */
  protected function getUserTemplateIds(int $uid): array {
    $ids = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $credentials = $this->entityTypeManager->getStorage('issued_credential')->loadMultiple($ids);
    $templateIds = [];
    foreach ($credentials as $credential) {
      $tid = $credential->get('template_id')->target_id ?? NULL;
      if ($tid) {
        $templateIds[] = (int) $tid;
      }
    }

    return array_unique($templateIds);
  }

}
