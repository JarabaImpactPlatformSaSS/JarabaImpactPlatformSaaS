<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Plantilla Juridica (LegalTemplate).
 *
 * ESTRUCTURA:
 * Entidad que almacena plantillas reutilizables de documentos juridicos.
 * Contiene el cuerpo del template con merge-fields ({{ case.title }}),
 * instrucciones para IA y metadatos de clasificacion.
 *
 * LOGICA:
 * Las plantillas pueden ser del sistema (is_system=TRUE) o creadas por
 * usuarios. El campo template_body admite merge-fields con sintaxis
 * {{ campo.subcampo }} que se resuelven en DocumentGeneratorService.
 * El usage_count se incrementa automaticamente al generar documentos.
 *
 * RELACIONES:
 * - LegalTemplate -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - LegalTemplate -> TaxonomyTerm (practice_area_tid): area de practica.
 * - LegalTemplate -> TaxonomyTerm (jurisdiction_tid): jurisdiccion.
 * - LegalTemplate -> User (uid): creador/propietario del template.
 * - LegalTemplate <- GeneratedDocument (template_id): documentos generados.
 *
 * @ContentEntityType(
 *   id = "legal_template",
 *   label = @Translation("Plantilla Juridica"),
 *   label_collection = @Translation("Plantillas Juridicas"),
 *   label_singular = @Translation("plantilla juridica"),
 *   label_plural = @Translation("plantillas juridicas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_templates\ListBuilder\LegalTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_templates\Form\LegalTemplateForm",
 *       "add" = "Drupal\jaraba_legal_templates\Form\LegalTemplateForm",
 *       "edit" = "Drupal\jaraba_legal_templates\Form\LegalTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_templates\Access\LegalTemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_template",
 *   admin_permission = "administer legal templates",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-templates",
 *     "add-form" = "/admin/content/legal-templates/add",
 *     "canonical" = "/admin/content/legal-templates/{legal_template}",
 *     "edit-form" = "/admin/content/legal-templates/{legal_template}/edit",
 *     "delete-form" = "/admin/content/legal-templates/{legal_template}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_templates.legal_template.settings",
 * )
 */
class LegalTemplate extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece la plantilla.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: IDENTIFICACION DE LA PLANTILLA
    // =========================================================================

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre'))
      ->setDescription(new TranslatableMarkup('Nombre descriptivo de la plantilla.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['template_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Plantilla'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'demanda' => new TranslatableMarkup('Demanda'),
        'contestacion' => new TranslatableMarkup('Contestacion'),
        'recurso' => new TranslatableMarkup('Recurso'),
        'escrito' => new TranslatableMarkup('Escrito'),
        'contrato' => new TranslatableMarkup('Contrato'),
        'dictamen' => new TranslatableMarkup('Dictamen'),
        'informe' => new TranslatableMarkup('Informe'),
        'consulta' => new TranslatableMarkup('Consulta'),
        'recurso_tributario' => new TranslatableMarkup('Recurso Tributario'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: CLASIFICACION TAXONOMICA
    // =========================================================================

    $fields['practice_area_tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Area de Practica'))
      ->setDescription(new TranslatableMarkup('Area juridica de la plantilla.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['jurisdiction_tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Jurisdiccion'))
      ->setDescription(new TranslatableMarkup('Jurisdiccion aplicable a la plantilla.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: CONTENIDO DEL TEMPLATE
    // =========================================================================

    $fields['template_body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Cuerpo de la Plantilla'))
      ->setDescription(new TranslatableMarkup('Contenido con merge-fields: {{ case.title }}, {{ client.name }}, etc.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merge_fields'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Campos de Fusion'))
      ->setDescription(new TranslatableMarkup('Definicion JSON de los merge-fields disponibles en la plantilla.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_instructions'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Instrucciones IA'))
      ->setDescription(new TranslatableMarkup('Instrucciones para el modelo de IA al generar documentos con esta plantilla.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: FLAGS Y CONTADORES
    // =========================================================================

    $fields['is_system'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Plantilla del Sistema'))
      ->setDescription(new TranslatableMarkup('Las plantillas del sistema no pueden ser eliminadas por usuarios.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activa'))
      ->setDescription(new TranslatableMarkup('Solo las plantillas activas estan disponibles para generacion.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['usage_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Veces Utilizada'))
      ->setDescription(new TranslatableMarkup('Contador de documentos generados con esta plantilla.'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', ['weight' => 30])
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
