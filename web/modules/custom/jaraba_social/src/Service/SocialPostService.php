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
     * Publica un post inmediatamente.
     *
     * @param \Drupal\jaraba_social\Entity\SocialPost $post
     *   El post a publicar.
     *
     * @return array
     *   Resultados por plataforma.
     */
    public function publish(SocialPost $post): array
    {
        $results = [];
        $accounts = $post->get('accounts')->referencedEntities();

        foreach ($accounts as $account) {
            /** @var \Drupal\jaraba_social\Entity\SocialAccount $account */
            if (!$account->isActive()) {
                continue;
            }

            $platform = $account->getPlatform();
            $results[$platform] = $this->publishToPlatform($post, $account);
        }

        // Actualizar estado global.
        $hasSuccess = array_filter($results, fn($r) => $r['success'] ?? FALSE);
        if (!empty($hasSuccess)) {
            $post->markPublished();
        } else {
            $post->markFailed();
        }

        $post->set('external_ids', $results);
        $post->save();

        return $results;
    }

    /**
     * Publica a una plataforma específica.
     */
    protected function publishToPlatform(SocialPost $post, SocialAccount $account): array
    {
        $platform = $account->getPlatform();
        $content = $post->getContent();

        // TODO: Implementar clientes de API para cada plataforma.
        // Por ahora, retornamos un placeholder.
        $this->logger->info('Publishing to @platform: @content', [
            '@platform' => $platform,
            '@content' => substr($content, 0, 100),
        ]);

        return [
            'success' => TRUE,
            'platform' => $platform,
            'external_id' => 'placeholder_' . time(),
            'message' => 'Published successfully (simulated)',
        ];
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
     * Procesa posts programados (para cron).
     *
     * @return int
     *   Número de posts procesados.
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
