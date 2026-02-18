<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_push_subscription",
 *   label = @Translation("Suscripcion Push"),
 *   label_collection = @Translation("Suscripciones Push"),
 *   label_singular = @Translation("suscripcion push"),
 *   label_plural = @Translation("suscripciones push"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\PushSubscriptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_push_subscription",
 *   admin_permission = "manage comercio notifications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-push-subscription/{comercio_push_subscription}",
 *     "collection" = "/admin/content/comercio-push-subscriptions",
 *   },
 * )
 */
class PushSubscription extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['endpoint'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Endpoint'))
      ->setRequired(TRUE)
      ->setDescription(t('URL del endpoint Web Push API'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['p256dh_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave P256DH'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['auth_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave Auth'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscribed_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Suscrito'));

    $fields['last_used_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultimo uso'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
