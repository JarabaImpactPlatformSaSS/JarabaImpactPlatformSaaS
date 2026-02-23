<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Condition;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if notifications are not muted for a user in a conversation.
 *
 * @EcaCondition(
 *   id = "jaraba_messaging_notification_not_muted",
 *   label = @Translation("Notification not muted"),
 *   description = @Translation("Checks whether the user has not muted notifications for the given conversation."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class NotificationNotMuted extends ConditionBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $event = $this->getEvent();
    if (!$event) {
      return FALSE;
    }

    $conversationId = $event->conversation_id ?? NULL;
    $userId = $this->tokenService->replaceClear($this->configuration['user_id'] ?? '');

    if (!$conversationId || empty($userId)) {
      return FALSE;
    }

    $storage = $this->entityTypeManager->getStorage('conversation_participant');
    $participants = $storage->loadByProperties([
      'conversation_id' => $conversationId,
      'user_id' => (int) $userId,
      'status' => 'active',
    ]);

    if (empty($participants)) {
      return FALSE;
    }

    $participant = reset($participants);
    $isMuted = (bool) $participant->get('is_muted')->value;
    $notificationPref = $participant->get('notification_pref')->value ?? 'all';

    $result = !$isMuted && $notificationPref !== 'none';
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'user_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#description' => $this->t('The user ID to check notification mute status for. Supports tokens.'),
      '#default_value' => $this->configuration['user_id'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['user_id'] = $form_state->getValue('user_id');
  }

}
