<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Evento de Uso.
 *
 * Registra cada evento individual de consumo de recursos por tenant.
 * Es el dato crudo antes de la agregación temporal.
 *
 * @ContentEntityType(
 *   id = "usage_event",
 *   label = @Translation("Evento de Uso"),
 *   label_collection = @Translation("Eventos de Uso"),
 *   label_singular = @Translation("evento de uso"),
 *   label_plural = @Translation("eventos de uso"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_usage_billing\UsageEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_usage_billing\Access\UsageEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "usage_event",
 *   admin_permission = "administer usage billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "event_type",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/usage-events/{usage_event}",
 *     "collection" = "/admin/content/usage-events",
 *   },
 *   field_ui_base_route = "entity.usage_event.settings",
 * )
 */
class UsageEvent extends ContentEntityBase {

  /**
   * Constantes para tipos de evento comunes.
   */
  public const EVENT_TYPE_API_CALL = 'api_call';
  public const EVENT_TYPE_STORAGE = 'storage';
  public const EVENT_TYPE_COMPUTE = 'compute';
  public const EVENT_TYPE_EMAIL = 'email_sent';
  public const EVENT_TYPE_AI_TOKEN = 'ai_token';
  public const EVENT_TYPE_BANDWIDTH = 'bandwidth';

  /**
   * Obtiene los metadatos decodificados.
   *
   * @return array
   *   Array de metadatos o vacío si no hay.
   */
  public function getDecodedMetadata(): array {
    $raw = $this->get('metadata')->value;
    if (!$raw) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Evento'))
      ->setDescription(t('Tipo de evento de uso (api_call, storage, compute, etc.).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metric_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Métrica'))
      ->setDescription(t('Identificador de la métrica medida.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Cantidad'))
      ->setDescription(t('Cantidad consumida del recurso.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unidad'))
      ->setDescription(t('Unidad de medida (requests, GB, tokens, etc.).'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (group) al que pertenece este evento.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario que generó el evento.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON con datos adicionales del evento.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recorded_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Registrado en'))
      ->setDescription(t('Timestamp del momento en que se produjo el evento.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    return $fields;
  }

}
