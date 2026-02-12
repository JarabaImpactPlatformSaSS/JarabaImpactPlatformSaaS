<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the PushSubscription entity.
 *
 * Stores Web Push API subscriptions (RFC 8030) for platform users.
 * Each record represents a browser endpoint subscribed to receive
 * push notifications with VAPID authentication (RFC 8292).
 *
 * Migrated from ecosistema_jaraba_core to jaraba_pwa module.
 *
 * @ContentEntityType(
 *   id = "push_subscription",
 *   label = @Translation("Push Subscription"),
 *   label_collection = @Translation("Push Subscriptions"),
 *   label_singular = @Translation("push subscription"),
 *   label_plural = @Translation("push subscriptions"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_pwa\PushSubscriptionListBuilder",
 *     "access" = "Drupal\jaraba_pwa\Access\PushSubscriptionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "push_subscription",
 *   admin_permission = "administer pwa",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/push-subscriptions",
 *     "canonical" = "/admin/content/push-subscriptions/{push_subscription}",
 *     "delete-form" = "/admin/content/push-subscriptions/{push_subscription}/delete",
 *   },
 * )
 */
class PushSubscription extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Push endpoint URL provided by the browser's Push API.
    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Endpoint'))
      ->setDescription(t('Push service endpoint URL from the browser.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 2048,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ]);

    // P-256 Diffie-Hellman public key for payload encryption (RFC 8291).
    $fields['p256dh_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('P256DH Key'))
      ->setDescription(t('P-256 Diffie-Hellman public key for encryption.'))
      ->setSettings([
        'max_length' => 512,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ]);

    // Authentication key for Web Push protocol.
    $fields['auth_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Auth Key'))
      ->setDescription(t('Authentication key for the subscriber.'))
      ->setSettings([
        'max_length' => 512,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ]);

    // Owner user reference.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who owns this push subscription.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 3,
      ]);

    // Tenant reference (group entity).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) associated with this subscription.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 4,
      ]);

    // Topics the subscription is interested in (JSON array).
    $fields['topics'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Topics'))
      ->setDescription(t('JSON array of notification topics this subscription follows.'))
      ->setDefaultValue('[]')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 5,
      ]);

    // Subscription status with allowed values.
    $fields['subscription_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Current subscription status.'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSettings([
        'allowed_values' => [
          'active' => 'Active',
          'paused' => 'Paused',
          'expired' => 'Expired',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 6,
      ]);

    // User agent string of the subscribed browser.
    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setDescription(t('User agent string of the subscribed browser.'))
      ->setSettings([
        'max_length' => 512,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 7,
      ]);

    // Creation timestamp.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the subscription was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 8,
      ]);

    // Changed timestamp (EntityChangedTrait).
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the subscription was last updated.'));

    return $fields;
  }

  /**
   * Gets the owner user ID.
   */
  public function getUserId(): ?int {
    $value = $this->get('user_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Gets the push endpoint URL.
   */
  public function getEndpoint(): string {
    return $this->get('endpoint')->value ?? '';
  }

  /**
   * Gets the authentication key.
   */
  public function getAuthKey(): string {
    return $this->get('auth_key')->value ?? '';
  }

  /**
   * Gets the P256DH public key.
   */
  public function getP256dhKey(): string {
    return $this->get('p256dh_key')->value ?? '';
  }

  /**
   * Gets the subscription status.
   */
  public function getSubscriptionStatus(): string {
    return $this->get('subscription_status')->value ?? 'active';
  }

  /**
   * Gets the topics as an array.
   *
   * @return array
   *   Array of topic strings.
   */
  public function getTopics(): array {
    $raw = $this->get('topics')->value ?? '[]';
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Checks if the subscription is active.
   */
  public function isActive(): bool {
    return $this->getSubscriptionStatus() === 'active';
  }

  /**
   * Marks the subscription as expired.
   *
   * @return $this
   */
  public function expire(): static {
    $this->set('subscription_status', 'expired');
    return $this;
  }

  /**
   * Pauses the subscription.
   *
   * @return $this
   */
  public function pause(): static {
    $this->set('subscription_status', 'paused');
    return $this;
  }

  /**
   * Activates the subscription.
   *
   * @return $this
   */
  public function activate(): static {
    $this->set('subscription_status', 'active');
    return $this;
  }

}
