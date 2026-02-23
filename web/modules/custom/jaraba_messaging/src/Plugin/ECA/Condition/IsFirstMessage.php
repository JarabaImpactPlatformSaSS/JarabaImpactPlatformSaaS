<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Condition;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if the current message is the first in a conversation.
 *
 * @EcaCondition(
 *   id = "jaraba_messaging_is_first_message",
 *   label = @Translation("Is first message in conversation"),
 *   description = @Translation("Checks whether the triggering message is the first one in its conversation."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
class IsFirstMessage extends ConditionBase {

  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
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
    if (!$conversationId) {
      return FALSE;
    }

    $count = (int) $this->database->select('secure_message', 'm')
      ->condition('m.conversation_id', $conversationId)
      ->condition('m.is_deleted', 0)
      ->countQuery()
      ->execute()
      ->fetchField();

    $result = $count <= 1;
    return $this->negationCheck($result);
  }

}
