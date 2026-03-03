<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Drupal\jaraba_social\Entity\SocialPost;
use Drupal\jaraba_social\Entity\SocialAccount;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar publicaciones en redes sociales.
 *
 * FUNCIONALIDADES:
 * - Generación de contenido con IA
 * - Scheduling de publicaciones
 * - Publicación directa a plataformas
 * - Métricas de engagement
 */
class SocialPostService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AgentOrchestrator $orchestrator,
        protected QueueFactory $queueFactory,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera contenido para un post usando IA.
     *
     * @param string $prompt
     *   Descripción de lo que se quiere publicar.
     * @param string $platform
     *   Plataforma destino (afecta longitud y estilo).
     * @param array $context
     *   Contexto adicional (tenant_id, brand_voice, etc.).
     *
     * @return array
     *   Contenido generado.
     */
    public function generateContent(string $prompt, string $platform, array $context = []): array
    {
        $platformLimits = [
            SocialAccount::PLATFORM_TWITTER => 280,
            SocialAccount::PLATFORM_LINKEDIN => 3000,
            SocialAccount::PLATFORM_FACEBOOK => 63206,
            SocialAccount::PLATFORM_INSTAGRAM => 2200,
            SocialAccount::PLATFORM_TIKTOK => 2200,
        ];

        $limit = $platformLimits[$platform] ?? 2000;

        $agentContext = array_merge($context, [
            'action' => 'generate_social_post',
            'platform' => $platform,
            'character_limit' => $limit,
            'prompt' => $prompt,
        ]);

        try {
            $result = $this->orchestrator->execute('marketing', 'generate_social_post', $agentContext);

            if ($result['success']) {
                return [
                    'success' => TRUE,
                    'content' => $result['data']['content'] ?? '',
                    'hashtags' => $result['data']['hashtags'] ?? [],
                    'platform' => $platform,
                ];
            }

            return [
                'success' => FALSE,
                'error' => $result['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error generating social content: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Programa un post para publicación futura.
     *
     * @param \Drupal\jaraba_social\Entity\SocialPost $post
     *   El post a programar.
     * @param \DateTimeInterface $scheduledAt
     *   Fecha y hora de publicación.
     *
     * @return bool
     *   TRUE si se programó correctamente.
     */
    public function schedule(SocialPost $post, \DateTimeInterface $scheduledAt): bool
    {
        $post->set('scheduled_at', $scheduledAt->format('Y-m-d\TH:i:s'));
        $post->set('status', SocialPost::STATUS_SCHEDULED);
        $post->save();

        $this->logger->info('Post @id scheduled for @date', [
            '@id' => $post->id(),
            '@date' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);

        return TRUE;
    }

    /**
     * Encola un post para publicación asíncrona.
     *
     * AUDIT-PERF-003: Las publicaciones se procesan vía QueueWorker, una
     * plataforma por item. Esto evita bloquear la petición HTTP del usuario
     * mientras se llaman APIs externas (Instagram requiere 2 llamadas, etc.).
     *
     * @param \Drupal\jaraba_social\Entity\SocialPost $post
     *   El post a publicar.
     *
     * @return array
     *   Plataformas encoladas.
     */
    public function publish(SocialPost $post): array
    {
        $queued = [];
        $accounts = $post->get('accounts')->referencedEntities();
        $queue = $this->queueFactory->get('social_publish');

        foreach ($accounts as $account) {
            /** @var \Drupal\jaraba_social\Entity\SocialAccount $account */
            if (!$account->isActive()) {
                continue;
            }

            $queue->createItem([
                'post_id' => (int) $post->id(),
                'account_id' => (int) $account->id(),
                'platform' => $account->getPlatform(),
            ]);

            $queued[] = $account->getPlatform();
        }

        // Marcar como "en cola" (scheduled → queued).
        $post->set('status', SocialPost::STATUS_SCHEDULED);
        $post->save();

        $this->logger->info('Post @id enqueued for @count platforms: @platforms', [
            '@id' => $post->id(),
            '@count' => count($queued),
            '@platforms' => implode(', ', $queued),
        ]);

        return $queued;
    }

    /**
     * Publica a una plataforma específica.
     *
     * Public para permitir invocación desde SocialPublishQueueWorker.
     */
    public function publishToPlatform(SocialPost $post, SocialAccount $account): array
    {
        $platform = $account->getPlatform();
        $content = $post->getContent();
        $accessToken = $account->getAccessToken();

        try {
            $httpClient = \Drupal::httpClient();

            // Extraer URLs de media asociados al post.
            $mediaUrls = [];
            $mediaEntities = $post->get('media')->referencedEntities();
            foreach ($mediaEntities as $mediaEntity) {
                /** @var \Drupal\media\MediaInterface $mediaEntity */
                $sourceField = $mediaEntity->getSource()->getConfiguration()['source_field'] ?? NULL;
                if ($sourceField && $mediaEntity->hasField($sourceField)) {
                    $fileEntity = $mediaEntity->get($sourceField)->entity;
                    if ($fileEntity) {
                        $mediaUrls[] = \Drupal::service('file_url_generator')->generateAbsoluteString($fileEntity->getFileUri());
                    }
                }
            }

            switch ($platform) {
                case 'facebook':
                    $response = $httpClient->post('https://graph.facebook.com/v19.0/me/feed', [
                        'form_params' => [
                            'message' => $content,
                            'access_token' => $accessToken,
                        ],
                    ]);
                    $result = json_decode($response->getBody()->getContents(), TRUE);
                    return ['success' => TRUE, 'external_post_id' => $result['id'] ?? NULL, 'platform' => $platform];

                case 'instagram':
                    // Instagram requires media. Create container then publish.
                    if (empty($mediaUrls)) {
                        return ['success' => FALSE, 'error' => 'Instagram requires at least one image', 'platform' => $platform];
                    }
                    $containerResponse = $httpClient->post('https://graph.facebook.com/v19.0/me/media', [
                        'form_params' => [
                            'image_url' => $mediaUrls[0],
                            'caption' => $content,
                            'access_token' => $accessToken,
                        ],
                    ]);
                    $container = json_decode($containerResponse->getBody()->getContents(), TRUE);
                    $publishResponse = $httpClient->post('https://graph.facebook.com/v19.0/me/media_publish', [
                        'form_params' => [
                            'creation_id' => $container['id'],
                            'access_token' => $accessToken,
                        ],
                    ]);
                    $result = json_decode($publishResponse->getBody()->getContents(), TRUE);
                    return ['success' => TRUE, 'external_post_id' => $result['id'] ?? NULL, 'platform' => $platform];

                case 'twitter':
                case 'x':
                    $response = $httpClient->post('https://api.twitter.com/2/tweets', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => ['text' => $content],
                    ]);
                    $result = json_decode($response->getBody()->getContents(), TRUE);
                    return ['success' => TRUE, 'external_post_id' => $result['data']['id'] ?? NULL, 'platform' => $platform];

                case 'linkedin':
                    $externalAccountId = $account->get('account_id')->value ?? '';
                    $response = $httpClient->post('https://api.linkedin.com/v2/ugcPosts', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json',
                            'X-Restli-Protocol-Version' => '2.0.0',
                        ],
                        'json' => [
                            'author' => 'urn:li:person:' . $externalAccountId,
                            'lifecycleState' => 'PUBLISHED',
                            'specificContent' => [
                                'com.linkedin.ugc.ShareContent' => [
                                    'shareCommentary' => ['text' => $content],
                                    'shareMediaCategory' => 'NONE',
                                ],
                            ],
                            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
                        ],
                    ]);
                    $result = json_decode($response->getBody()->getContents(), TRUE);
                    return ['success' => TRUE, 'external_post_id' => $result['id'] ?? NULL, 'platform' => $platform];

                default:
                    return ['success' => FALSE, 'error' => "Unsupported platform: {$platform}", 'platform' => $platform];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish to @platform: @error', [
                '@platform' => $platform,
                '@error' => $e->getMessage(),
            ]);
            return ['success' => FALSE, 'error' => $e->getMessage(), 'platform' => $platform];
        }
    }

    /**
     * Obtiene posts programados para publicar ahora.
     *
     * @return array
     *   Array de SocialPost.
     */
    public function getScheduledForNow(): array
    {
        $storage = $this->entityTypeManager->getStorage('social_post');
        $now = new \DateTime();

        $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', SocialPost::STATUS_SCHEDULED)
            ->condition('scheduled_at', $now->format('Y-m-d\TH:i:s'), '<=')
            ->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Encola posts programados cuyo momento de publicación ha llegado.
     *
     * AUDIT-PERF-003: publish() ahora encola; processScheduled sigue
     * siendo el entry point para cron.
     *
     * @return int
     *   Número de posts encolados.
     */
    public function processScheduled(): int
    {
        $posts = $this->getScheduledForNow();
        $count = 0;

        foreach ($posts as $post) {
            $this->publish($post);
            $count++;
        }

        return $count;
    }

    /**
     * Obtiene posts programados para un tenant.
     */
    public function getScheduledPosts(?int $tenantId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('social_post');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', SocialPost::STATUS_SCHEDULED)
            ->sort('scheduled_at', 'ASC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->range(0, 100)->execute();
        $posts = $ids ? $storage->loadMultiple($ids) : [];
        $result = [];

        foreach ($posts as $post) {
            $result[] = [
                'id' => (int) $post->id(),
                'content' => $post->getContent(),
                'scheduled_at' => $post->get('scheduled_at')->value ?? NULL,
                'status' => $post->get('status')->value,
            ];
        }

        return $result;
    }

    /**
     * Reprograma un post.
     */
    public function reschedulePost(int $postId, string $newDate): array
    {
        $post = $this->entityTypeManager->getStorage('social_post')->load($postId);
        if (!$post) {
            return ['success' => FALSE, 'error' => 'Post not found'];
        }

        $post->set('scheduled_at', $newDate);
        $post->save();

        return ['success' => TRUE, 'post_id' => $postId, 'scheduled_at' => $newDate];
    }

    /**
     * Metricas de analytics de redes sociales.
     */
    public function getAnalyticsMetrics(?int $tenantId = NULL, int $days = 30): array
    {
        $stats = $this->getStats($tenantId);
        return array_merge($stats, [
            'days' => $days,
            'engagement_rate' => 0.0,
            'reach' => 0,
        ]);
    }

    /**
     * Rendimiento de un post individual.
     */
    public function getPostPerformance(int $postId): array
    {
        $post = $this->entityTypeManager->getStorage('social_post')->load($postId);
        if (!$post) {
            return ['error' => 'Post not found'];
        }

        return [
            'post_id' => $postId,
            'impressions' => (int) ($post->get('impressions')->value ?? 0),
            'clicks' => (int) ($post->get('clicks')->value ?? 0),
            'likes' => (int) ($post->get('likes')->value ?? 0),
            'shares' => (int) ($post->get('shares')->value ?? 0),
        ];
    }

    /**
     * Posts con mejor rendimiento.
     */
    public function getTopPosts(?int $tenantId = NULL, int $limit = 10): array
    {
        $storage = $this->entityTypeManager->getStorage('social_post');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', SocialPost::STATUS_PUBLISHED)
            ->sort('impressions', 'DESC')
            ->range(0, $limit);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        $posts = $ids ? $storage->loadMultiple($ids) : [];
        $result = [];

        foreach ($posts as $post) {
            $result[] = [
                'id' => (int) $post->id(),
                'content' => mb_substr($post->getContent(), 0, 100),
                'impressions' => (int) ($post->get('impressions')->value ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Publica un post via Make.com webhook.
     */
    public function publishViaMakecom(int $postId): array
    {
        $post = $this->entityTypeManager->getStorage('social_post')->load($postId);
        if (!$post) {
            return ['success' => FALSE, 'error' => 'Post not found'];
        }

        $this->publish($post);

        return ['success' => TRUE, 'post_id' => $postId, 'status' => 'queued'];
    }

    /**
     * Procesa webhook entrante de Make.com.
     */
    public function processMakecomWebhook(array $data): void
    {
        $postId = $data['post_id'] ?? NULL;
        if (!$postId) {
            return;
        }

        $post = $this->entityTypeManager->getStorage('social_post')->load($postId);
        if (!$post) {
            return;
        }

        if (!empty($data['external_post_id'])) {
            $post->set('external_id', $data['external_post_id']);
        }
        if (!empty($data['status'])) {
            $post->set('status', $data['status']);
        }
        $post->save();
    }

    /**
     * Obtiene estadísticas de posts.
     *
     * @param int|null $tenantId
     *   Filtrar por tenant.
     *
     * @return array
     *   Estadísticas.
     */
    public function getStats(?int $tenantId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('social_post');
        $query = $storage->getQuery()->accessCheck(FALSE);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $total = (clone $query)->count()->execute();

        $published = (clone $query)
            ->condition('status', SocialPost::STATUS_PUBLISHED)
            ->count()->execute();

        $scheduled = (clone $query)
            ->condition('status', SocialPost::STATUS_SCHEDULED)
            ->count()->execute();

        $drafts = (clone $query)
            ->condition('status', SocialPost::STATUS_DRAFT)
            ->count()->execute();

        return [
            'total' => (int) $total,
            'published' => (int) $published,
            'scheduled' => (int) $scheduled,
            'drafts' => (int) $drafts,
        ];
    }

}
