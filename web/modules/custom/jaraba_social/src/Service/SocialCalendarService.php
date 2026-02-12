<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_social\Entity\SocialPost;
use Psr\Log\LoggerInterface;

/**
 * Servicio de calendario para publicaciones sociales.
 *
 * PROPOSITO:
 * Gestiona la vista de calendario de publicaciones, permitiendo
 * visualizar, reprogramar y obtener horarios optimos de publicacion
 * por plataforma y tenant.
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Gestión de entidades SocialPost.
 * - logger: Registro de eventos de calendario.
 */
class SocialCalendarService {

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
   * Obtiene el calendario de publicaciones de un tenant para un rango de fechas.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $startDate
   *   Fecha de inicio en formato 'Y-m-d'.
   * @param string $endDate
   *   Fecha de fin en formato 'Y-m-d'.
   *
   * @return array
   *   Array de posts organizados por fecha, cada uno con:
   *   - id: ID del post.
   *   - title: Titulo del post.
   *   - scheduled_at: Fecha y hora programada.
   *   - status: Estado del post.
   *   - platforms: Plataformas destino.
   */
  public function getCalendarForTenant(int $tenantId, string $startDate, string $endDate): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('scheduled_at', $startDate . 'T00:00:00', '>=')
        ->condition('scheduled_at', $endDate . 'T23:59:59', '<=')
        ->sort('scheduled_at', 'ASC');

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $posts = $storage->loadMultiple($ids);
      $calendar = [];

      foreach ($posts as $post) {
        /** @var \Drupal\jaraba_social\Entity\SocialPost $post */
        $scheduledAt = $post->get('scheduled_at')->value ?? '';
        $date = substr($scheduledAt, 0, 10);

        $calendar[$date][] = [
          'id' => (int) $post->id(),
          'title' => $post->label(),
          'scheduled_at' => $scheduledAt,
          'status' => $post->get('status')->value ?? SocialPost::STATUS_DRAFT,
        ];
      }

      return $calendar;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo calendario para tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene los posts programados para una fecha especifica.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $date
   *   Fecha en formato 'Y-m-d'.
   *
   * @return array
   *   Array de posts programados para esa fecha.
   */
  public function getScheduledPosts(int $tenantId, string $date): array {
    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', SocialPost::STATUS_SCHEDULED)
        ->condition('scheduled_at', $date . 'T00:00:00', '>=')
        ->condition('scheduled_at', $date . 'T23:59:59', '<=')
        ->sort('scheduled_at', 'ASC');

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $posts = $storage->loadMultiple($ids);
      $result = [];

      foreach ($posts as $post) {
        $result[] = [
          'id' => (int) $post->id(),
          'title' => $post->label(),
          'scheduled_at' => $post->get('scheduled_at')->value ?? '',
          'content' => $post->get('content')->value ?? '',
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo posts programados para tenant @tid, fecha @date: @error', [
        '@tid' => $tenantId,
        '@date' => $date,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Reprograma un post para una nueva fecha.
   *
   * @param int $postId
   *   ID del post a reprogramar.
   * @param string $newDate
   *   Nueva fecha y hora en formato 'Y-m-d\TH:i:s'.
   *
   * @return bool
   *   TRUE si el post fue reprogramado correctamente.
   */
  public function reschedulePost(int $postId, string $newDate): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('social_post');
      $post = $storage->load($postId);

      if (!$post) {
        $this->logger->warning('Post social @id no encontrado para reprogramar.', [
          '@id' => $postId,
        ]);
        return FALSE;
      }

      $post->set('scheduled_at', $newDate);
      $post->set('status', SocialPost::STATUS_SCHEDULED);
      $post->save();

      $this->logger->info('Post social @id reprogramado para @date.', [
        '@id' => $postId,
        '@date' => $newDate,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error reprogramando post @id: @error', [
        '@id' => $postId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene los horarios optimos de publicacion para una plataforma.
   *
   * Analiza el historico de engagement del tenant para determinar
   * los mejores horarios de publicacion por plataforma.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $platform
   *   Plataforma social (facebook, instagram, linkedin, twitter, tiktok).
   *
   * @return array
   *   Array de horarios optimos con formato:
   *   - day: Dia de la semana (1-7).
   *   - hour: Hora del dia (0-23).
   *   - score: Puntuacion de engagement esperado.
   */
  public function getOptimalTimes(int $tenantId, string $platform): array {
    // Generic defaults as fallback when insufficient data is available.
    $defaults = [
      'facebook' => [
        ['day' => 3, 'hour' => 10, 'score' => 0.85],
        ['day' => 4, 'hour' => 14, 'score' => 0.82],
        ['day' => 5, 'hour' => 11, 'score' => 0.80],
      ],
      'instagram' => [
        ['day' => 2, 'hour' => 11, 'score' => 0.90],
        ['day' => 4, 'hour' => 13, 'score' => 0.87],
        ['day' => 6, 'hour' => 10, 'score' => 0.84],
      ],
      'linkedin' => [
        ['day' => 2, 'hour' => 9, 'score' => 0.88],
        ['day' => 3, 'hour' => 10, 'score' => 0.86],
        ['day' => 4, 'hour' => 9, 'score' => 0.83],
      ],
      'twitter' => [
        ['day' => 1, 'hour' => 8, 'score' => 0.82],
        ['day' => 3, 'hour' => 12, 'score' => 0.80],
        ['day' => 5, 'hour' => 17, 'score' => 0.78],
      ],
      'tiktok' => [
        ['day' => 2, 'hour' => 19, 'score' => 0.92],
        ['day' => 4, 'hour' => 20, 'score' => 0.89],
        ['day' => 6, 'hour' => 18, 'score' => 0.87],
      ],
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('social_post');

      // Query published posts from the last 90 days for the given tenant.
      $ninetyDaysAgo = new \DateTime('-90 days');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', SocialPost::STATUS_PUBLISHED)
        ->condition('published_at', $ninetyDaysAgo->getTimestamp(), '>=');

      $ids = $query->execute();

      // Fall back to generic defaults if fewer than 10 published posts.
      if (count($ids) < 10) {
        return $defaults[$platform] ?? [];
      }

      $posts = $storage->loadMultiple($ids);

      // Group by day_of_week and hour, calculate average engagement.
      $timeSlots = [];
      $postCountForPlatform = 0;

      foreach ($posts as $post) {
        /** @var \Drupal\jaraba_social\Entity\SocialPost $post */
        $publishedAt = $post->get('published_at')->value;
        if (empty($publishedAt)) {
          continue;
        }

        // Check if this post was published to the requested platform via its accounts.
        $accounts = $post->get('accounts')->referencedEntities();
        $platformMatch = FALSE;
        foreach ($accounts as $account) {
          if ($account->get('platform')->value === $platform) {
            $platformMatch = TRUE;
            break;
          }
        }
        if (!$platformMatch) {
          continue;
        }

        $postCountForPlatform++;

        $dateTime = new \DateTime('@' . $publishedAt);
        // Day of week: 1 (Monday) through 7 (Sunday) using ISO-8601.
        $dayOfWeek = (int) $dateTime->format('N');
        $hour = (int) $dateTime->format('G');

        // Extract engagement rate from metrics map field.
        $metrics = $post->get('metrics')->getValue();
        $metricsData = is_array($metrics) ? $metrics : [];
        // Try platform-specific engagement, then global engagement_rate.
        $engagementRate = 0.0;
        if (isset($metricsData[$platform]['engagement_rate'])) {
          $engagementRate = (float) $metricsData[$platform]['engagement_rate'];
        }
        elseif (isset($metricsData['engagement_rate'])) {
          $engagementRate = (float) $metricsData['engagement_rate'];
        }
        else {
          // Derive engagement from likes + shares + comments if available.
          $likes = (int) ($metricsData[$platform]['likes'] ?? $metricsData['likes'] ?? 0);
          $shares = (int) ($metricsData[$platform]['shares'] ?? $metricsData['shares'] ?? 0);
          $comments = (int) ($metricsData[$platform]['comments'] ?? $metricsData['comments'] ?? 0);
          $engagementRate = ($likes + $shares + $comments) > 0 ? (float) ($likes + $shares + $comments) : 0.0;
        }

        $key = $dayOfWeek . '-' . $hour;
        if (!isset($timeSlots[$key])) {
          $timeSlots[$key] = [
            'day' => $dayOfWeek,
            'hour' => $hour,
            'total_engagement' => 0.0,
            'count' => 0,
          ];
        }
        $timeSlots[$key]['total_engagement'] += $engagementRate;
        $timeSlots[$key]['count']++;
      }

      // Fall back to generic defaults if fewer than 10 posts matched the platform.
      if ($postCountForPlatform < 10) {
        return $defaults[$platform] ?? [];
      }

      // Calculate average engagement per time slot and normalize scores.
      $slots = [];
      foreach ($timeSlots as $slot) {
        $avgEngagement = $slot['count'] > 0 ? $slot['total_engagement'] / $slot['count'] : 0.0;
        $slots[] = [
          'day' => $slot['day'],
          'hour' => $slot['hour'],
          'score' => round($avgEngagement, 4),
        ];
      }

      // Sort by score descending.
      usort($slots, fn(array $a, array $b) => $b['score'] <=> $a['score']);

      // Normalize scores to 0-1 range if max score > 0.
      if (!empty($slots) && $slots[0]['score'] > 0) {
        $maxScore = $slots[0]['score'];
        foreach ($slots as &$slot) {
          $slot['score'] = round($slot['score'] / $maxScore, 2);
        }
        unset($slot);
      }

      // Return top 5 time slots.
      return array_slice($slots, 0, 5);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo horarios optimos para tenant @tid, plataforma @platform: @error', [
        '@tid' => $tenantId,
        '@platform' => $platform,
        '@error' => $e->getMessage(),
      ]);
      // Graceful fallback to generic defaults on error.
      return $defaults[$platform] ?? [];
    }
  }

}
