<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DiagnosticSection.
 *
 * Representa una sección de evaluación del diagnóstico empresarial.
 * Cada sección agrupa preguntas relacionadas con un área específica
 * de madurez digital (presencia online, operaciones, ventas, etc.)
 *
 * SPEC: 25_Emprendimiento_Business_Diagnostic_Core_v1
 *
 * @ContentEntityType(
 *   id = "diagnostic_section",
 *   label = @Translation("Sección de Diagnóstico"),
 *   label_collection = @Translation("Secciones de Diagnóstico"),
 *   label_singular = @Translation("sección"),
 *   label_plural = @Translation("secciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_diagnostic\DiagnosticSectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_diagnostic\BusinessDiagnosticAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "diagnostic_section",
 *   admin_permission = "manage diagnostic sections",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/diagnostic-sections",
 *     "add-form" = "/admin/structure/diagnostic-sections/add",
 *     "canonical" = "/admin/structure/diagnostic-section/{diagnostic_section}",
 *     "edit-form" = "/admin/structure/diagnostic-section/{diagnostic_section}/edit",
 *     "delete-form" = "/admin/structure/diagnostic-section/{diagnostic_section}/delete",
 *   },
 *   field_ui_base_route = "entity.diagnostic_section.settings",
 * )
 */
class DiagnosticSection extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de la Sección'))
      ->setDescription(t('Nombre legible de la sección.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', ['weight' => 0])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setDescription(t('Identificador interno (ej: online_presence, digital_sales).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción de la sección para el usuario.'))
      ->setDisplayOptions('view', ['weight' => 2])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['icon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Icono'))
      ->setDescription(t('Clase CSS del icono (ej: fas fa-globe).'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Peso en el Score'))
      ->setDescription(t('Peso de esta sección en el cálculo del score global (0.0 - 1.0).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDefaultValue(0.20)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    $fields['order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Orden de visualización en el wizard.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE);

    $fields['is_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Obligatoria'))
      ->setDescription(t('Si la sección es obligatoria para completar el diagnóstico.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['min_questions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mínimo de Preguntas'))
      ->setDescription(t('Número mínimo de preguntas a responder.'))
      ->setDefaultValue(3)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el machine name de la sección.
   */
  public function getMachineName(): string {
    return $this->get('machine_name')->value ?? '';
  }

  /**
   * Obtiene el peso de la sección.
   */
  public function getWeight(): float {
    return (float) ($this->get('weight')->value ?? 0.2);
  }

}
