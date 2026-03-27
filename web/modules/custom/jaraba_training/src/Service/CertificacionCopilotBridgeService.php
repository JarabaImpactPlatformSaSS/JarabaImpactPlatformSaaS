<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;

/**
 * CopilotBridge para certificaciones del Método Jaraba.
 *
 * AI-COVERAGE-001: Permite al copiloto IA consultar/crear certificaciones
 * conversacionalmente y proveer contexto relevante al usuario.
 */
class CertificacionCopilotBridgeService implements CopilotBridgeInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  /** @return array<string, mixed> */
  public function getRelevantContext(int $userId): array {
    $context = [
      'active_certifications' => 0,
      'pending_evaluations' => 0,
      'overall_level' => NULL,
      'recent_certifications' => [],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('user_certification');

      // Certificaciones activas del usuario.
      $active = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('certification_status', 'completed')
        ->count()
        ->execute();
      $context['active_certifications'] = $active;

      // En evaluación.
      $pending = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('certification_status', 'in_progress')
        ->count()
        ->execute();
      $context['pending_evaluations'] = $pending;

      // Nivel más alto alcanzado.
      $certs = $storage->loadByProperties([
        'user_id' => $userId,
        'certification_status' => 'completed',
      ]);
      $maxLevel = 0;
      foreach ($certs as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $level = (int) ($cert->get('overall_level')->value ?? 0);
        if ($level > $maxLevel) {
          $maxLevel = $level;
        }
      }
      if ($maxLevel > 0) {
        $context['overall_level'] = $maxLevel;
      }
    }
    catch (\Throwable) {
      // Silently fail — copilot degrades gracefully.
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   */
  /** @return array<string, mixed>|null */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('user_certification');
      $pending = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('certification_status', 'in_progress')
        ->count()
        ->execute();

      if ($pending > 0) {
        return [
          'type' => 'certification_progress',
          'message' => (string) $this->t('Tienes @count certificación(es) en progreso. ¿Quieres revisar tu portfolio?', [
            '@count' => $pending,
          ]),
          'action_label' => (string) $this->t('Ver mi certificación'),
          'action_route' => 'entity.user_certification.collection',
        ];
      }
    }
    catch (\Throwable) {
      // Silently fail.
    }

    return NULL;
  }

}
