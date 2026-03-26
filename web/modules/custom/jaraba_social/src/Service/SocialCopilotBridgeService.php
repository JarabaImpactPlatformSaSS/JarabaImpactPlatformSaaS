<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Social Copilot Bridge — contextualiza el copilot con datos de redes sociales.
 */
class SocialCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'social',
      'has_social_data' => FALSE,
      'scheduled_posts' => 0,
      'published_posts' => 0,
      'connected_accounts' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('social_post')) {
        $scheduled = $this->entityTypeManager
          ->getStorage('social_post')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'scheduled')
          ->count()
          ->execute();
        $context['scheduled_posts'] = $scheduled;

        $published = $this->entityTypeManager
          ->getStorage('social_post')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'published')
          ->count()
          ->execute();
        $context['published_posts'] = $published;
      }

      if ($this->entityTypeManager->hasDefinition('social_account')) {
        $accounts = $this->entityTypeManager
          ->getStorage('social_account')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'active')
          ->count()
          ->execute();
        $context['connected_accounts'] = $accounts;
      }

      $context['has_social_data'] = $context['published_posts'] > 0 || $context['scheduled_posts'] > 0;

      $context['_system_prompt_addition'] = sprintf(
        "Datos de redes sociales: %d posts publicados, %d programados, %d cuentas conectadas. "
        . "Ayuda a crear contenido social optimizado, sugerir horarios de publicacion y analizar engagement. "
        . "NUNCA inventes metricas de redes sociales.",
        $context['published_posts'],
        $context['scheduled_posts'],
        $context['connected_accounts'],
      );

    }
    catch (\Exception $e) {
      $this->logger->warning('Social CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    return NULL;
  }

}
