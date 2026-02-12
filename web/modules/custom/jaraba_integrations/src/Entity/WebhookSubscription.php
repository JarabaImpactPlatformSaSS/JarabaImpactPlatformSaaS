<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad WebhookSubscription para suscripciones a eventos.
 *
 * PROPÓSITO:
 * Permite a tenants y apps externas registrar URLs que recibirán
 * notificaciones HTTP POST cuando ocurran eventos en la plataforma.
 *
 * SEGURIDAD:
 * - Cada webhook tiene un secret para firma HMAC-SHA256.
 * - Los payloads se firman con X-Jaraba-Signature header.
 * - Retry automático con backoff exponencial (3 intentos).
 *
 * FLUJO:
 * 1. Tenant crea suscripción a eventos (ej: order.created)
 * 2. Cuando ocurre el evento, WebhookDispatcherService dispara
 * 3. HTTP POST al URL con payload JSON + firma HMAC
 * 4. Retry en caso de fallo (3x con backoff)
 *
 * @ContentEntityType(
 *   id = "webhook_subscription",
 *   label = @Translation("Webhook Subscription"),
 *   label_collection = @Translation("Webhook Subscriptions"),
 *   label_singular = @Translation("webhook subscription"),
 *   label_plural = @Translation("webhook subscriptions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_integrations\WebhookSubscriptionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_integrations\Form\WebhookSubscriptionForm",
 *       "add" = "Drupal\jaraba_integrations\Form\WebhookSubscriptionForm",
 *       "edit" = "Drupal\jaraba_integrations\Form\WebhookSubscriptionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_integrations\Access\WebhookSubscriptionAccessControlHandler",
 *   },
 *   base_table = "webhook_subscription",
 *   admin_permission = "administer integrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/integrations/webhooks",
 *     "add-form" = "/admin/structure/integrations/webhooks/add",
 *     "canonical" = "/admin/structure/integrations/webhooks/{webhook_subscription}",
 *     "edit-form" = "/admin/structure/integrations/webhooks/{webhook_subscription}/edit",
 *     "delete-form" = "/admin/structure/integrations/webhooks/{webhook_subscription}/delete",
 *   },
 * )
 */
class WebhookSubscription extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Estados de la suscripción.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_INACTIVE = 'inactive';
  public const STATUS_FAILING = 'failing';

  /**
   * Número máximo de reintentos.
   */
  public const MAX_RETRIES = 3;

  /**
   * Obtiene el label.
   */
  public function getLabel(): string {
    return $this->get('label')->value ?? '';
  }

  /**
   * Obtiene la URL de destino.
   */
  public function getTargetUrl(): string {
    return $this->get('target_url')->value ?? '';
  }

  /**
   * Obtiene el secreto para firma HMAC.
   */
  public function getSecret(): string {
    return $this->get('secret')->value ?? '';
  }

  /**
   * Obtiene los eventos suscritos.
   */
  public function getEvents(): array {
    $events = $this->get('events')->value;
    if (empty($events)) {
      return [];
    }
    if (is_string($events)) {
      return json_decode($events, TRUE) ?? [];
    }
    return $events;
  }

  /**
   * Verifica si está suscrito a un evento específico.
   */
  public function isSubscribedTo(string $event): bool {
    $events = $this->getEvents();
    return in_array($event, $events, TRUE) || in_array('*', $events, TRUE);
  }

  /**
   * Obtiene el estado de la suscripción.
   */
  public function getSubscriptionStatus(): string {
    return $this->get('status')->value ?? self::STATUS_ACTIVE;
  }

  /**
   * Verifica si la suscripción está activa.
   */
  public function isActive(): bool {
    return $this->getSubscriptionStatus() === self::STATUS_ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Label descriptivo.
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la suscripción (ej: Notificar pedidos a ERP).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 200)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // URL de destino del webhook.
    $fields['target_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL de Destino'))
      ->setDescription(t('URL HTTPS que recibirá los eventos (POST).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Eventos suscritos (JSON array).
    $fields['events'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Eventos'))
      ->setDescription(t('JSON array de eventos suscritos (ej: ["order.created","payment.received"]). Usa "*" para todos.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 1,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Secret para firma HMAC-SHA256.
    $fields['secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secret'))
      ->setDescription(t('Clave secreta para firma HMAC-SHA256 del payload.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 256)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Tenant propietario.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta suscripción.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group');

    // Estado de la suscripción.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la suscripción.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => 'Activa',
        self::STATUS_INACTIVE => 'Inactiva',
        self::STATUS_FAILING => 'Fallando',
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contador de fallos consecutivos.
    $fields['consecutive_failures'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fallos Consecutivos'))
      ->setDescription(t('Número de fallos de entrega consecutivos.'))
      ->setDefaultValue(0);

    // Fecha del último disparo.
    $fields['last_triggered'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Último Disparo'))
      ->setDescription(t('Fecha del último intento de entrega.'))
      ->setSetting('datetime_type', 'datetime');

    // Código de respuesta del último intento.
    $fields['last_response_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Último Código de Respuesta'))
      ->setDescription(t('Código HTTP de la última entrega.'));

    // Total de entregas exitosas.
    $fields['total_deliveries'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Entregas'))
      ->setDescription(t('Número total de entregas exitosas.'))
      ->setDefaultValue(0);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificación'));

    return $fields;
  }

}
