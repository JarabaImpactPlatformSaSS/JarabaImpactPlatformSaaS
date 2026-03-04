<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Content Hub.
 *
 * Provee al copilot IA metricas editoriales: articulos publicados,
 * visitas, puntuacion SEO media, y sugerencias de contenido.
 */
class ContentHubCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'jaraba_content_hub';
  }

  /**
   * Obtiene contexto editorial relevante para el copilot.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical con metricas editoriales.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'jaraba_content_hub',
      'published_articles' => 0,
      'draft_articles' => 0,
      'total_views' => 0,
      'articles_this_month' => 0,
      'categories_used' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('content_article');

      $context['published_articles'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author', $userId)
        ->condition('status', 'published')
        ->count()
        ->execute();

      $context['draft_articles'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author', $userId)
        ->condition('status', 'draft')
        ->count()
        ->execute();

      // Articles published this month.
      $firstOfMonth = strtotime('first day of this month midnight');
      $context['articles_this_month'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author', $userId)
        ->condition('status', 'published')
        ->condition('created', $firstOfMonth, '>=')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->warning('ContentHubCopilotBridge context error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Sugerencia soft de mejora editorial.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Sugerencia o NULL si no aplica.
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $context = $this->getRelevantContext($userId);

      if ($context['published_articles'] === 0) {
        return [
          'message' => 'Publica tu primer articulo para posicionar tu marca en buscadores.',
          'cta' => ['label' => 'Crear articulo', 'route' => 'jaraba_content_hub.articles.add.frontend'],
          'trigger' => 'no_articles',
        ];
      }

      if ($context['draft_articles'] > 0) {
        return [
          'message' => 'Tienes ' . $context['draft_articles'] . ' borrador(es) pendiente(s) de publicar.',
          'cta' => ['label' => 'Ver borradores', 'route' => 'jaraba_content_hub.articles.frontend'],
          'trigger' => 'pending_drafts',
        ];
      }

      if ($context['articles_this_month'] === 0 && $context['published_articles'] > 0) {
        return [
          'message' => 'No has publicado este mes. La constancia editorial mejora el SEO.',
          'cta' => ['label' => 'Crear articulo', 'route' => 'jaraba_content_hub.articles.add.frontend'],
          'trigger' => 'no_monthly_content',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('ContentHubCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Insights del ecosistema editorial.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas del ecosistema.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_articles' => 0,
      'total_published' => 0,
      'user_articles' => 0,
      'content_share_pct' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('content_article');

      $insights['total_published'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'published')
        ->count()
        ->execute();

      $insights['user_articles'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author', $userId)
        ->condition('status', 'published')
        ->count()
        ->execute();

      if ($insights['total_published'] > 0) {
        $insights['content_share_pct'] = round(
          ($insights['user_articles'] / $insights['total_published']) * 100,
          1
        );
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('ContentHubCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
