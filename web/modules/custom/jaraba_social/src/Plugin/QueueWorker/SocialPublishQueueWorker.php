<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\jaraba_social\Service\SocialPostService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes social media publications asynchronously.
 *
 * AUDIT-PERF-003: Each queue item represents ONE platform publication,
 * preventing synchronous blocking when publishing to multiple platforms.
 * Instagram (2 API calls), LinkedIn, Twitter, etc. are processed independently.
 *
 * @QueueWorker(
 *   id = "social_publish",
 *   title = @Translation("Social Publish Worker"),
 *   cron = {"time" = 60}
 * )
 */
class SocialPublishQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SocialPostService $postService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('jaraba_social.post_service'),
      $container->get('logger.channel.jaraba_social'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $postId = $data['post_id'] ?? NULL;
    $accountId = $data['account_id'] ?? NULL;

    if (!$postId || !$accountId) {
      $this->logger->error('SocialPublishQueueWorker: missing post_id or account_id.');
      return;
    }

    $post = $this->entityTypeManager->getStorage('social_post')->load($postId);
    if (!$post) {
      $this->logger->warning('SocialPublishQueueWorker: post @id not found.', ['@id' => $postId]);
      return;
    }

    $account = $this->entityTypeManager->getStorage('social_account')->load($accountId);
    if (!$account || !$account->isActive()) {
      $this->logger->warning('SocialPublishQueueWorker: account @id not found or inactive.', ['@id' => $accountId]);
      return;
    }

    try {
      $result = $this->postService->publishToPlatform($post, $account);

      // Update external_ids on the post with the result.
      $externalIds = $post->get('external_ids')->value;
      $externalIds = $externalIds ? json_decode($externalIds, TRUE) : [];
      $externalIds[$account->getPlatform()] = $result;
      $post->set('external_ids', json_encode($externalIds));

      if ($result['success'] ?? FALSE) {
        $post->markPublished();
        $this->logger->info('Published post @post to @platform.', [
          '@post' => $postId,
          '@platform' => $account->getPlatform(),
        ]);
      }

      $post->save();
    }
    catch (\Exception $e) {
      $this->logger->error('SocialPublishQueueWorker failed for post @post on @platform: @error', [
        '@post' => $postId,
        '@platform' => $account->getPlatform(),
        '@error' => $e->getMessage(),
      ]);
      throw new RequeueException($e->getMessage());
    }
  }

}
