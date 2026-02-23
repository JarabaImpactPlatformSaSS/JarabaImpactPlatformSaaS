<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Action\ActionBase;
use Drupal\jaraba_messaging\Service\NotificationBridgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends a notification about a messaging event.
 *
 * @EcaAction(
 *   id = "jaraba_messaging_send_notification",
 *   label = @Translation("Send messaging notification"),
 *   description = @Translation("Sends a notification to specified users about a messaging event."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class SendNotification extends ActionBase {

  protected NotificationBridgeService $notificationBridge;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->notificationBridge = $container->get('jaraba_messaging.notification_bridge');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $event = $this->getEvent();
    if (!$event) {
      return;
    }

    $conversationId = $event->conversationId ?? NULL;
    $senderId = $event->senderId ?? NULL;

    if (!$conversationId) {
      return;
    }

    $messagePreview = $this->tokenService->replaceClear($this->configuration['message_preview'] ?? '');

    try {
      $this->notificationBridge->notifyNewMessage(
        (int) $conversationId,
        (int) $senderId,
        $messagePreview,
      );
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_messaging')->warning('ECA notification failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message_preview' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['message_preview'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message preview'),
      '#description' => $this->t('Optional preview text for the notification. Supports tokens.'),
      '#default_value' => $this->configuration['message_preview'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['message_preview'] = $form_state->getValue('message_preview');
  }

}
