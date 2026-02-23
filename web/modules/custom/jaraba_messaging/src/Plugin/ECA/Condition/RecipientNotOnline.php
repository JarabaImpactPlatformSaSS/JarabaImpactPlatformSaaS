<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Drupal\jaraba_messaging\Service\PresenceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if a recipient is not currently online.
 *
 * @EcaCondition(
 *   id = "jaraba_messaging_recipient_not_online",
 *   label = @Translation("Recipient is not online"),
 *   description = @Translation("Checks whether the specified recipient user is not currently online in messaging."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class RecipientNotOnline extends ConditionBase {

  protected PresenceServiceInterface $presenceService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->presenceService = $container->get('jaraba_messaging.presence');
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

    $recipientId = $this->tokenService->replaceClear($this->configuration['recipient_id'] ?? '');
    if (empty($recipientId)) {
      return FALSE;
    }

    $status = $this->presenceService->getStatus((int) $recipientId);
    $result = ($status['status'] ?? 'offline') === 'offline';
    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'recipient_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['recipient_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient user ID'),
      '#description' => $this->t('The user ID to check online status for. Supports tokens.'),
      '#default_value' => $this->configuration['recipient_id'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['recipient_id'] = $form_state->getValue('recipient_id');
  }

}
