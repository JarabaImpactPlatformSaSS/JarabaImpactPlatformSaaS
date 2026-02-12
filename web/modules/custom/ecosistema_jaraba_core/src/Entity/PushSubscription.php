<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad PushSubscription.
 *
 * Almacena las suscripciones de notificaciones push (Web Push API)
 * de los usuarios de la plataforma. Cada registro representa un
 * endpoint de navegador suscrito para recibir notificaciones.
 *
 * Las suscripciones se gestionan exclusivamente via API REST
 * (PushApiController), no mediante formularios de entidad.
 *
 * PHASE 5 - G109-3: Push Notifications
 *
 * @ContentEntityType(
 *   id = "push_subscription",
 *   label = @Translation("Suscripción Push"),
 *   label_collection = @Translation("Suscripciones Push"),
 *   label_singular = @Translation("suscripción push"),
 *   label_plural = @Translation("suscripciones push"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\PushSubscriptionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "push_subscription",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/push-subscriptions",
 *     "canonical" = "/admin/config/push-subscriptions/{push_subscription}",
 *     "delete-form" = "/admin/config/push-subscriptions/{push_subscription}/delete",
 *   },
 * )
 */
class PushSubscription extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Usuario propietario de la suscripción.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario propietario de esta suscripción push.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    // Tenant al que pertenece la suscripción.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) asociado a la suscripción.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ]);

    // URL del endpoint push del navegador (proporcionado por Push API).
    $fields['endpoint'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Endpoint'))
      ->setDescription(t('URL del endpoint push del navegador.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 2,
      ]);

    // Clave de autenticación para el protocolo Web Push (RFC 8030).
    $fields['auth_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Auth Key'))
      ->setDescription(t('Clave de autenticación del suscriptor (auth).'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ]);

    // Clave pública P-256 del suscriptor.
    $fields['p256dh_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('P256DH Key'))
      ->setDescription(t('Clave pública P-256 Diffie-Hellman del suscriptor.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 4,
      ]);

    // User Agent del navegador suscrito.
    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setDescription(t('User Agent del navegador suscrito.'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ]);

    // Indica si la suscripción está activa.
    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la suscripción está activa.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 6,
      ]);

    // Timestamp de creación.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Momento en que se creó la suscripción.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 7,
      ]);

    return $fields;
  }

  /**
   * Obtiene el ID del usuario propietario.
   *
   * @return int|null
   *   El ID del usuario, o NULL si no está definido.
   */
  public function getUserId(): ?int {
    $value = $this->get('user_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Obtiene el endpoint push del navegador.
   *
   * @return string
   *   La URL del endpoint push.
   */
  public function getEndpoint(): string {
    return $this->get('endpoint')->value ?? '';
  }

  /**
   * Obtiene la clave de autenticación.
   *
   * @return string
   *   La clave auth del suscriptor.
   */
  public function getAuthKey(): string {
    return $this->get('auth_key')->value ?? '';
  }

  /**
   * Obtiene la clave P256DH.
   *
   * @return string
   *   La clave pública P-256 DH.
   */
  public function getP256dhKey(): string {
    return $this->get('p256dh_key')->value ?? '';
  }

  /**
   * Indica si la suscripción está activa.
   *
   * @return bool
   *   TRUE si la suscripción está activa.
   */
  public function isActive(): bool {
    return (bool) $this->get('active')->value;
  }

  /**
   * Desactiva la suscripción.
   *
   * @return $this
   */
  public function deactivate(): static {
    $this->set('active', FALSE);
    return $this;
  }

}
