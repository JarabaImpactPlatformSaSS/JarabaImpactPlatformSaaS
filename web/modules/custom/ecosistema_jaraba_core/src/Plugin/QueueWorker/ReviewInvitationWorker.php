<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\Service\ReviewInvitationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa invitaciones de resenas encoladas.
 *
 * Los items son encolados por ReviewInvitationService::scheduleInvitation().
 *
 * @QueueWorker(
 *   id = "review_invitation_queue",
 *   title = @Translation("Invitaciones de Resenas"),
 *   cron = {"time" = 30}
 * )
 */
class ReviewInvitationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly ReviewInvitationService $invitationService,
    protected readonly LoggerInterface $logger,
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
      $container->get('ecosistema_jaraba_core.review_invitation'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!is_array($data) || empty($data['vertical'])) {
      $this->logger->warning('Invalid review invitation queue item — missing vertical.');
      return;
    }

    $this->invitationService->processInvitation($data);
  }

}
