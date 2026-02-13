<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AUDIT-PERF-N11: Sends event reminder emails asynchronously.
 *
 * Replaces synchronous N×M email loop in _jaraba_groups_remind_upcoming_events().
 * Each queue item represents one event to process (sends to all members).
 *
 * @QueueWorker(
 *   id = "jaraba_groups_event_reminder",
 *   title = @Translation("Send group event reminder emails"),
 *   cron = {"time" = 30}
 * )
 */
class EventReminderWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
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
      $container->get('plugin.manager.mail'),
      $container->get('logger.channel.jaraba_groups'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $eventId = $data['event_id'] ?? NULL;
    if (!$eventId) {
      return;
    }

    $event = $this->entityTypeManager->getStorage('group_event')->load($eventId);
    if (!$event) {
      return;
    }

    $group = $event->getGroup();
    if (!$group) {
      return;
    }

    $sent = 0;
    $members = $group->getMembers();

    foreach ($members as $membership) {
      $member = $membership->getUser();
      if ($member && $member->getEmail()) {
        $this->mailManager->mail('jaraba_groups', 'event_reminder', $member->getEmail(), $member->getPreferredLangcode(), [
          'user_name' => $member->getDisplayName(),
          'event_title' => $event->label(),
          'start_datetime' => $event->get('start_datetime')->value ?? '',
          'location' => $event->get('location')->value ?? 'Por confirmar',
          'event_url' => $event->toUrl('canonical', ['absolute' => TRUE])->toString(),
        ]);
        $sent++;
      }
    }

    $this->logger->info('QueueWorker: @count reminder emails sent for event @id.', [
      '@count' => $sent,
      '@id' => $eventId,
    ]);
  }

}
