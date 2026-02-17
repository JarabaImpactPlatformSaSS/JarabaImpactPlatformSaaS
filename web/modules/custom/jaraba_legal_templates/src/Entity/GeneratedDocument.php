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
 * Define la entidad Documento Generado (GeneratedDocument) — FASE C2.
 *
 * ESTRUCTURA:
 * Documento legal generado a partir de una plantilla, con o sin asistencia
 * de IA. Almacena el HTML generado, los datos de merge usados y las
 * citas juridicas incluidas.
 *
 * LOGICA:
 * Ciclo de vida: draft -> reviewing -> approved -> finalized.
 * Puede vincularse a un documento seguro en la boveda (vault_document_id)
 * una vez finalizado.
 *
 * RELACIONES:
 * - GeneratedDocument -> ClientCase (case_id): expediente vinculado.
 * - GeneratedDocument -> LegalTemplate (template_id): plantilla utilizada.
 * - GeneratedDocument -> User (generated_by): quien genero el documento.
 * - GeneratedDocument -> SecureDocument (vault_document_id): doc en boveda.
 * - GeneratedDocument -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 *
 * @ContentEntityType(
 *   id = "generated_document",
 *   label = @Translation("Documento Generado"),
 *   label_collection = @Translation("Documentos Generados"),
 *   label_singular = @Translation("documento generado"),
 *   label_plural = @Translation("documentos generados"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_templates\ListBuilder\GeneratedDocumentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_templates\Form\GeneratedDocumentForm",
 *       "add" = "Drupal\jaraba_legal_templates\Form\GeneratedDocumentForm",
 *       "edit" = "Drupal\jaraba_legal_templates\Form\GeneratedDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_templates\Access\GeneratedDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "generated_document",
 *   admin_permission = "administer legal templates",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/generated-documents",
 *     "add-form" = "/admin/content/generated-documents/add",
 *     "canonical" = "/admin/content/generated-documents/{generated_document}",
 *     "edit-form" = "/admin/content/generated-documents/{generated_document}/edit",
 *     "delete-form" = "/admin/content/generated-documents/{generated_document}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_templates.generated_document.settings",
 * )
 */
class GeneratedDocument extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS PRINCIPALES
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente vinculado al documento.'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Plantilla'))
      ->setDescription(new TranslatableMarkup('Plantilla utilizada para generar el documento.'))
      ->setSetting('target_type', 'legal_template')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['generated_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Generado Por'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vault_document_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Documento en Boveda'))
      ->setDescription(new TranslatableMarkup('Vinculo al documento seguro en la boveda.'))
      ->setSetting('target_type', 'secure_document')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DEL DOCUMENTO
    // =========================================================================

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Contenido HTML'))
      ->setDescription(new TranslatableMarkup('Contenido generado del documento.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: METADATOS DE GENERACION
    // =========================================================================

    $fields['merge_data'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Datos de Merge'))
      ->setDescription(new TranslatableMarkup('JSON con los valores de merge-fields usados.'));

    $fields['citations_used'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Citas Utilizadas'))
      ->setDescription(new TranslatableMarkup('JSON array de IDs de citas juridicas incluidas.'));

    $fields['ai_model_version'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Modelo IA'))
      ->setDescription(new TranslatableMarkup('Version del modelo IA usado para generar.'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('view', TRUE);

    $fields['generation_mode'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Modo de Generacion'))
      ->setSetting('allowed_values', [
        'template_only' => 'Solo plantilla',
        'ai_assisted' => 'Asistido por IA',
        'ai_full' => 'Generacion completa IA',
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('template_only')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: ESTADO
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'reviewing' => 'En Revision',
        'approved' => 'Aprobado',
        'finalized' => 'Finalizado',
      ])
      ->setDefaultValue('draft')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
