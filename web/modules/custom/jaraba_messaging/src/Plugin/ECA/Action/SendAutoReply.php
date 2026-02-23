<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Action\ActionBase;
use Drupal\jaraba_messaging\Service\MessagingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends an auto-reply message in a conversation.
 *
 * @EcaAction(
 *   id = "jaraba_messaging_send_auto_reply",
 *   label = @Translation("Send auto-reply message"),
 *   description = @Translation("Sends an automated reply message in the conversation that triggered the event."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class SendAutoReply extends ActionBase {

  protected MessagingServiceInterface $messagingService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->messagingService = $container->get('jaraba_messaging.messaging');
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

    $conversationUuid = $event->conversationUuid ?? NULL;
    if (!$conversationUuid) {
      return;
    }

    $body = $this->tokenService->replaceClear($this->configuration['message_body'] ?? '');
    if (empty($body)) {
      return;
    }

    try {
      $this->messagingService->sendMessage(
        $conversationUuid,
        $body,
        'system',
        NULL,
        [],
      );
    }
    catch (\Throwable $e) {
      // Log but don't break the ECA workflow.
      \Drupal::logger('jaraba_messaging')->warning('Auto-reply failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message_body' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['message_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message body'),
      '#description' => $this->t('The auto-reply message content. Supports tokens.'),
      '#default_value' => $this->configuration['message_body'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['message_body'] = $form_state->getValue('message_body');
  }

}
