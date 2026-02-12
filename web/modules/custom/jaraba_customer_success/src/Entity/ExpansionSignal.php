<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Señal de oportunidad de expansión detectada.
 *
 * PROPÓSITO:
 * Registra oportunidades de upsell/cross-sell detectadas
 * automáticamente por el sistema (uso > 80%, feature requests,
 * crecimiento de usuarios) para que el CSM pueda actuar.
 *
 * LÓGICA:
 * - signal_type: tipo de señal (usage_limit, feature_request, user_growth).
 * - status: flujo new → contacted → won/lost/deferred.
 * - potential_arr: estimación de ingresos incrementales.
 * - signal_details: JSON con datos específicos de la señal.
 *
 * @ContentEntityType(
 *   id = "expansion_signal",
 *   label = @Translation("Expansion Signal"),
 *   label_collection = @Translation("Expansion Signals"),
 *   label_singular = @Translation("expansion signal"),
 *   label_plural = @Translation("expansion signals"),
 *   label_count = @PluralTranslation(
 *     singular = "@count expansion signal",
 *     plural = "@count expansion signals",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\ExpansionSignalListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\ExpansionSignalAccessControlHandler",
 *   },
 *   base_table = "expansion_signal",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/expansion-signals",
 *     "canonical" = "/admin/content/expansion-signals/{expansion_signal}",
 *     "delete-form" = "/admin/content/expansion-signals/{expansion_signal}/delete",
 *   },
 * )
 */
class ExpansionSignal extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de tipo de señal.
   */
  public const TYPE_USAGE_LIMIT = 'usage_limit';
  public const TYPE_FEATURE_REQUEST = 'feature_request';
  public const TYPE_USER_GROWTH = 'user_growth';

  /**
   * Constantes de estado.
   */
  public const STATUS_NEW = 'new';
  public const STATUS_CONTACTED = 'contacted';
  public const STATUS_WON = 'won';
  public const STATUS_LOST = 'lost';
  public const STATUS_DEFERRED = 'deferred';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant with expansion opportunity.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['signal_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Signal Type'))
      ->setDescription(t('Type of expansion signal detected.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::TYPE_USAGE_LIMIT => t('Usage Limit'),
        self::TYPE_FEATURE_REQUEST => t('Feature Request'),
        self::TYPE_USER_GROWTH => t('User Growth'),
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_plan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Current Plan'))
      ->setDescription(t('Tenant current subscription plan.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50);

    $fields['recommended_plan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recommended Plan'))
      ->setDescription(t('Suggested upgrade plan.'))
      ->setSetting('max_length', 50);

    $fields['potential_arr'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Potential ARR'))
      ->setDescription(t('Estimated incremental annual recurring revenue.'))
      ->setDefaultValue(0);

    $fields['signal_details'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Signal Details'))
      ->setDescription(t('JSON with specific signal data.'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Signal processing status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_NEW => t('New'),
        self::STATUS_CONTACTED => t('Contacted'),
        self::STATUS_WON => t('Won'),
        self::STATUS_LOST => t('Lost'),
        self::STATUS_DEFERRED => t('Deferred'),
      ])
      ->setDefaultValue(self::STATUS_NEW)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['detected_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Detected At'))
      ->setDescription(t('Timestamp when signal was detected.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Obtiene el tipo de señal.
   */
  public function getSignalType(): string {
    return $this->get('signal_type')->value ?? self::TYPE_USAGE_LIMIT;
  }

  /**
   * Obtiene el ARR potencial.
   */
  public function getPotentialArr(): float {
    return (float) $this->get('potential_arr')->value;
  }

  /**
   * Obtiene el estado de la señal.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? self::STATUS_NEW;
  }

}
