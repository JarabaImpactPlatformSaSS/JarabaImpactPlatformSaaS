<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SepeAccionFormativa entity.
 *
 * Acción formativa comunicada al SEPE, vinculada a un curso del LMS.
 *
 * @ContentEntityType(
 *   id = "sepe_accion_formativa",
 *   label = @Translation("Acción Formativa SEPE"),
 *   label_collection = @Translation("Acciones Formativas SEPE"),
 *   label_singular = @Translation("acción formativa"),
 *   label_plural = @Translation("acciones formativas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "sepe_accion_formativa",
 *   admin_permission = "administer sepe teleformacion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "denominacion",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sepe-acciones",
 *     "add-form" = "/admin/content/sepe-acciones/add",
 *     "canonical" = "/admin/content/sepe-acciones/{sepe_accion_formativa}",
 *     "edit-form" = "/admin/content/sepe-acciones/{sepe_accion_formativa}/edit",
 *     "delete-form" = "/admin/content/sepe-acciones/{sepe_accion_formativa}/delete",
 *   },
 * )
 */
class SepeAccionFormativa extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['id_accion_sepe'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID Acción SEPE'))
            ->setDescription(t('Identificador único asignado por el SEPE.'))
            ->setRequired(TRUE)
            ->addConstraint('UniqueField')
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['centro_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Centro de Formación'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'sepe_centro')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nota: Almacenamos el ID de curso como integer ya que la entidad
        // course puede no existir en todas las instalaciones.
        $fields['course_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID Curso LMS Vinculado'))
            ->setDescription(t('ID del curso del LMS vinculado a esta acción formativa.'))
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['codigo_especialidad'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código Especialidad'))
            ->setDescription(t('Código del Catálogo de Especialidades Formativas.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 15)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['denominacion'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Denominación'))
            ->setDescription(t('Nombre oficial de la acción formativa.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 200)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['modalidad'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Modalidad'))
            ->setSetting('allowed_values', [
                'T' => t('Teleformación'),
                'M' => t('Mixta'),
            ])
            ->setDefaultValue('T')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['numero_horas'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Horas'))
            ->setRequired(TRUE)
            ->setSetting('min', 1)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['fecha_inicio'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Inicio'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['fecha_fin'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Fin'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['num_participantes_max'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Plazas Máximas'))
            ->setDefaultValue(80)
            ->setDisplayConfigurable('form', TRUE);

        $fields['estado'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'pendiente' => t('Pendiente'),
                'autorizada' => t('Autorizada'),
                'en_curso' => t('En Curso'),
                'finalizada' => t('Finalizada'),
                'cancelada' => t('Cancelada'),
            ])
            ->setDefaultValue('pendiente')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['es_certificado'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Es Certificado de Profesionalidad'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['nivel_cp'] = BaseFieldDefinition::create('list_integer')
            ->setLabel(t('Nivel CP'))
            ->setDescription(t('Nivel del Certificado de Profesionalidad (1, 2 o 3).'))
            ->setSetting('allowed_values', [
                1 => t('Nivel 1'),
                2 => t('Nivel 2'),
                3 => t('Nivel 3'),
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['numero_expediente'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Número de Expediente'))
            ->setSetting('max_length', 30)
            ->setDisplayConfigurable('form', TRUE);

        $fields['fecha_comunicacion'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Comunicación Inicio'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
