<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Template de Onboarding.
 *
 * Almacena la configuracion de pasos y flujos de onboarding
 * por vertical, permitiendo personalizar la experiencia de
 * bienvenida para cada tipo de tenant.
 *
 * @ContentEntityType(
 *   id = "onboarding_template",
 *   label = @Translation("Template de Onboarding"),
 *   label_collection = @Translation("Templates de Onboarding"),
 *   label_singular = @Translation("template de onboarding"),
 *   label_plural = @Translation("templates de onboarding"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_onboarding\OnboardingTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_onboarding\Form\OnboardingTemplateForm",
 *       "add" = "Drupal\jaraba_onboarding\Form\OnboardingTemplateForm",
 *       "edit" = "Drupal\jaraba_onboarding\Form\OnboardingTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_onboarding\Access\OnboardingTemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "onboarding_template",
 *   admin_permission = "administer onboarding",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/onboarding-templates/{onboarding_template}",
 *     "add-form" = "/admin/content/onboarding-templates/add",
 *     "edit-form" = "/admin/content/onboarding-templates/{onboarding_template}/edit",
 *     "delete-form" = "/admin/content/onboarding-templates/{onboarding_template}/delete",
 *     "collection" = "/admin/content/onboarding-templates",
 *   },
 *   field_ui_base_route = "jaraba_onboarding.onboarding_template.settings",
 * )
 */
class OnboardingTemplate extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Valores permitidos para el campo vertical.
   */
  public const VERTICAL_AGROCONECTA = 'agroconecta';
  public const VERTICAL_SERVICIOS = 'servicios';
  public const VERTICAL_EMPRENDIMIENTO = 'emprendimiento';
  public const VERTICAL_EMPLEABILIDAD = 'empleabilidad';
  public const VERTICAL_COMERCIO = 'comercio';

  /**
   * Devuelve los valores permitidos para verticals.
   *
   * @return array
   *   Array asociativo de valores permitidos.
   */
  public static function getVerticalOptions(): array {
    return [
      self::VERTICAL_AGROCONECTA => t('AgroConecta'),
      self::VERTICAL_SERVICIOS => t('Servicios'),
      self::VERTICAL_EMPRENDIMIENTO => t('Emprendimiento'),
      self::VERTICAL_EMPLEABILIDAD => t('Empleabilidad'),
      self::VERTICAL_COMERCIO => t('Comercio'),
    ];
  }

  /**
   * Obtiene la configuracion de pasos como array.
   *
   * @return array
   *   Los pasos del template decodificados de JSON.
   */
  public function getStepsConfig(): array {
    $value = $this->get('steps_config')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Comprueba si el template esta activo.
   */
  public function isActive(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion'))
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::VERTICAL_AGROCONECTA => 'AgroConecta',
        self::VERTICAL_SERVICIOS => 'Servicios',
        self::VERTICAL_EMPRENDIMIENTO => 'Emprendimiento',
        self::VERTICAL_EMPLEABILIDAD => 'Empleabilidad',
        self::VERTICAL_COMERCIO => 'Comercio',
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['steps_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion de Pasos'))
      ->setDescription(t('JSON con la definicion de pasos del onboarding.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
