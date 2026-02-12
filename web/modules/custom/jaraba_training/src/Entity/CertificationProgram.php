<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CertificationProgram.
 *
 * Programa de certificación Método Jaraba™ para consultores y entidades.
 *
 * @ContentEntityType(
 *   id = "certification_program",
 *   label = @Translation("Programa de Certificación"),
 *   label_collection = @Translation("Programas de Certificación"),
 *   label_singular = @Translation("programa de certificación"),
 *   label_plural = @Translation("programas de certificación"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_training\CertificationProgramListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_training\CertificationProgramAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "certification_program",
 *   admin_permission = "administer certification programs",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/certification-programs/{certification_program}",
 *     "add-form" = "/admin/content/certification-programs/add",
 *     "edit-form" = "/admin/content/certification-programs/{certification_program}/edit",
 *     "delete-form" = "/admin/content/certification-programs/{certification_program}/delete",
 *     "collection" = "/admin/content/certification-programs",
 *   },
 *   field_ui_base_route = "entity.certification_program.settings",
 * )
 */
class CertificationProgram extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Programa'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Directriz Nuclear #20: allowed_values configurables desde YAML.
        $fields['certification_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Certificación'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values_function', 'jaraba_training_allowed_certification_types')
            ->setDefaultValue('consultant')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === FEES Y ROYALTIES ===

        $fields['entry_fee'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Fee de Activación'))
            ->setDescription(t('Cuota inicial de certificación.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['annual_fee'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Cuota Anual'))
            ->setDescription(t('Cuota de renovación anual.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['royalty_percent'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('% Royalty'))
            ->setDescription(t('Porcentaje de royalty sobre ventas.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === REQUISITOS ===
        $fields['required_courses'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cursos Obligatorios'))
            ->setDescription(t('Cursos LMS que deben completarse.'))
            ->setSetting('target_type', 'lms_course')
            ->setSetting('handler_settings', ['target_bundles' => []])
            ->setCardinality(-1)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['exam_required'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Requiere Examen'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al contenido interactivo para examen (TRN-002).
        $fields['exam_content_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Examen Interactivo'))
            ->setDescription(t('Contenido interactivo usado como examen de certificación.'))
            ->setSetting('target_type', 'interactive_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['min_mentoring_hours'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Horas Mínimas de Mentoring'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === BENEFICIOS ===

        $fields['benefits'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Beneficios'))
            ->setDescription(t('Lista de beneficios para el certificado.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['territory_exclusive'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Territorio Exclusivo'))
            ->setDescription(t('Incluye exclusividad de territorio.'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CAMPOS DE SISTEMA ===

        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
