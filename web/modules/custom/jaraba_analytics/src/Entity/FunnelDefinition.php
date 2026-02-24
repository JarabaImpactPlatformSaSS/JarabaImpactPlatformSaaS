<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad FunnelDefinition.
 *
 * PROPÓSITO:
 * Almacena definiciones de funnels de conversión configurables.
 * Cada funnel tiene una secuencia de pasos (event_type) que los
 * visitantes deben completar dentro de una ventana de conversión.
 *
 * LÓGICA:
 * - steps: JSON array de definiciones de paso [{event_type, label, filters}].
 * - conversion_window_hours: ventana temporal máxima para considerar
 *   que un visitante completó la secuencia.
 * - tenant_id: aislamiento multi-tenant vía referencia a grupo.
 *
 * @ContentEntityType(
 *   id = "funnel_definition",
 *   label = @Translation("Funnel Definition"),
 *   label_collection = @Translation("Funnel Definitions"),
 *   label_singular = @Translation("funnel definition"),
 *   label_plural = @Translation("funnel definitions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count funnel definition",
 *     plural = "@count funnel definitions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\FunnelDefinitionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\FunnelDefinitionForm",
 *       "add" = "Drupal\jaraba_analytics\Form\FunnelDefinitionForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\FunnelDefinitionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\FunnelDefinitionAccessControlHandler",
 *   },
 *   base_table = "funnel_definition",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/jaraba/analytics/funnels",
 *     "canonical" = "/admin/jaraba/analytics/funnels/{funnel_definition}",
 *     "add-form" = "/admin/jaraba/analytics/funnels/add",
 *     "edit-form" = "/admin/jaraba/analytics/funnels/{funnel_definition}/edit",
 *     "delete-form" = "/admin/jaraba/analytics/funnels/{funnel_definition}/delete",
 *   },
 *   field_ui_base_route = "entity.funnel_definition.settings",
 * )
 */
class FunnelDefinition extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The funnel definition name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this funnel belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['steps'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Steps'))
      ->setDescription(t('JSON array of step definitions: [{event_type, label, filters}].'));

    $fields['conversion_window_hours'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversion Window (hours)'))
      ->setDescription(t('Maximum hours for a visitor to complete the funnel.'))
      ->setRequired(TRUE)
      ->setDefaultValue(72)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Obtiene los pasos del funnel decodificados.
   *
   * @return array
   *   Array de definiciones de paso [{event_type, label, filters}].
   */
  public function getSteps(): array {
    $value = $this->get('steps')->getValue();
    if (!empty($value[0]) && is_array($value[0])) {
      return $value[0];
    }
    return [];
  }

  /**
   * Obtiene la ventana de conversión en horas.
   *
   * @return int
   *   Número de horas de la ventana de conversión.
   */
  public function getConversionWindow(): int {
    return (int) ($this->get('conversion_window_hours')->value ?? 72);
  }

}
