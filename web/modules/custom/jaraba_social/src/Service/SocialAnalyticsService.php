<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_social\Entity\SocialPost;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analiticas para redes sociales.
 *
 * PROPOSITO:
 * Proporciona metricas de rendimiento de las publicaciones sociales
 * del tenant: engagement, alcance, clicks, compartidos y rendimiento
 * comparativo entre plataformas.
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Gestión de entidades SocialPost y SocialPostVariant.
 * - logger: Registro de eventos de analitica.
 */
class SocialAnalyticsService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger para registro de eventos.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene metricas generales de redes sociales para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $period
   *   Periodo de tiempo: '7d', '30d', '90d', '365d'.
   *
   * @return array
   *   Metricas agregadas con claves:
   *   - total_posts: Numero total de posts en el periodo.
   *   - published_posts: Posts publicados.
   *   - total_impressions: Impresiones totales.
   *   - total_engagements: Interacciones totales.
   *   - total_clicks: Clicks totales.
   *   - total_shares: Compartidos totales.
   *   - avg_engagement_rate: Tasa de engagement promedio.
   */
  public function getMetricsForTenant(int $tenantId, string $period = '30d'): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
      $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
      $since = new \DateTime("-{$days} days");

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('created', $since->getTimestamp(), '>=');

      $totalPosts = (clone $query)->count()->execute();

      $publishedPosts = (clone $query)
        ->condition('status', SocialPost::STATUS_PUBLISHED)
        ->count()->execute();

      // Obtener metricas de variantes para el tenant.
      $variantStorage = $this->entityTypeManager->getStorage('social_post_variant');
      $variantQuery = $variantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('created', $since->getTimestamp(), '>=');

      $variantIds = $variantQuery->execute();
      $metrics = [
        'total_posts' => (int) $totalPosts,
        'published_posts' => (int) $publishedPosts,
        'total_impressions' => 0,
        'total_engagements' => 0,
        'total_clicks' => 0,
        'total_shares' => 0,
        'avg_engagement_rate' => 0.0,
      ];

      if (!empty($variantIds)) {
        $variants = $variantStorage->loadMultiple($variantIds);
        foreach ($variants as $variant) {
          $metrics['total_impressions'] += (int) ($variant->get('impressions')->value ?? 0);
          $metrics['total_engagements'] += (int) ($variant->get('engagements')->value ?? 0);
          $metrics['total_clicks'] += (int) ($variant->get('clicks')->value ?? 0);
          $metrics['total_shares'] += (int) ($variant->get('shares')->value ?? 0);
        }

        if ($metrics['total_impressions'] > 0) {
          $metrics['avg_engagement_rate'] = round(
            ($metrics['total_engagements'] / $metrics['total_impressions']) * 100,
            4
          );
        }
      }

      return $metrics;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo metricas para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'total_posts' => 0,
        'published_posts' => 0,
        'total_impressions' => 0,
        'total_engagements' => 0,
        'total_clicks' => 0,
        'total_shares' => 0,
        'avg_engagement_rate' => 0.0,
      ];
    }
  }

  /**
   * Obtiene el rendimiento detallado de un post especifico.
   *
   * @param int $postId
   *   ID del post social.
   *
   * @return array
   *   Metricas del post con claves:
   *   - post_id: ID del post.
   *   - title: Titulo del post.
   *   - status: Estado actual.
   *   - variants: Array de variantes con sus metricas.
   *   - best_variant: ID de la variante con mejor engagement.
   */
  public function getPostPerformance(int $postId): array {
    try {
      $postStorage = $this->entityTypeManager->getStorage('social_post');
      $post = $postStorage->load($postId);

      if (!$post) {
        return [];
      }

      $variantStorage = $this->entityTypeManager->getStorage('social_post_variant');
      $variantIds = $variantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('post_id', $postId)
        ->execute();

      $variants = [];
      $bestVariantId = NULL;
      $bestRate = 0.0;

      if (!empty($variantIds)) {
        $variantEntities = $variantStorage->loadMultiple($variantIds);
        foreach ($variantEntities as $variant) {
          $rate = (float) ($variant->get('engagement_rate')->value ?? 0);
          $variants[] = [
            'id' => (int) $variant->id(),
            'variant_name' => $variant->get('variant_name')->value ?? '',
            'impressions' => (int) ($variant->get('impressions')->value ?? 0),
            'engagements' => (int) ($variant->get('engagements')->value ?? 0),
            'clicks' => (int) ($variant->get('clicks')->value ?? 0),
            'shares' => (int) ($variant->get('shares')->value ?? 0),
            'engagement_rate' => $rate,
            'is_winner' => (bool) $variant->get('is_winner')->value,
          ];

          if ($rate > $bestRate) {
            $bestRate = $rate;
            $bestVariantId = (int) $variant->id();
          }
        }
      }

      return [
        'post_id' => $postId,
        'title' => $post->label(),
        'status' => $post->get('status')->value ?? SocialPost::STATUS_DRAFT,
        'variants' => $variants,
        'best_variant' => $bestVariantId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo rendimiento del post @id: @error', [
        '@id' => $postId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene metricas comparativas entre plataformas para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array indexado por plataforma con metricas agregadas:
   *   - posts_count: Numero de posts.
   *   - total_impressions: Impresiones totales.
   *   - total_engagements: Interacciones totales.
   *   - avg_engagement_rate: Tasa de engagement promedio.
   */
  public function getCrossPlatformMetrics(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', SocialPost::STATUS_PUBLISHED);

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $posts = $storage->loadMultiple($ids);
      $platformMetrics = [];

      foreach ($posts as $post) {
        $accounts = $post->get('accounts')->referencedEntities();
        foreach ($accounts as $account) {
          $platform = $account->get('platform')->value ?? 'unknown';
          if (!isset($platformMetrics[$platform])) {
            $platformMetrics[$platform] = [
              'posts_count' => 0,
              'total_impressions' => 0,
              'total_engagements' => 0,
              'avg_engagement_rate' => 0.0,
            ];
          }
          $platformMetrics[$platform]['posts_count']++;
        }
      }

      return $platformMetrics;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo metricas cross-platform para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene los posts con mejor rendimiento de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de posts a retornar.
   *
   * @return array
   *   Array de posts ordenados por engagement rate descendente, cada uno con:
   *   - post_id: ID del post.
   *   - title: Titulo.
   *   - engagement_rate: Mejor tasa de engagement de sus variantes.
   *   - impressions: Total de impresiones.
   *   - published_at: Fecha de publicacion.
   */
  public function getTopPerformingPosts(int $tenantId, int $limit = 10): array {
    try {
      $variantStorage = $this->entityTypeManager->getStorage('social_post_variant');
      $variantIds = $variantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('engagement_rate', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($variantIds)) {
        return [];
      }

      $variants = $variantStorage->loadMultiple($variantIds);
      $postStorage = $this->entityTypeManager->getStorage('social_post');
      $result = [];
      $seenPosts = [];

      foreach ($variants as $variant) {
        $postId = (int) ($variant->get('post_id')->target_id ?? 0);
        if ($postId === 0 || isset($seenPosts[$postId])) {
          continue;
        }

        $post = $postStorage->load($postId);
        if (!$post) {
          continue;
        }

        $seenPosts[$postId] = TRUE;
        $result[] = [
          'post_id' => $postId,
          'title' => $post->label(),
          'engagement_rate' => (float) ($variant->get('engagement_rate')->value ?? 0),
          'impressions' => (int) ($variant->get('impressions')->value ?? 0),
          'published_at' => $post->get('published_at')->value ?? NULL,
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top posts para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
