<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SepeParticipante entity.
 *
 * Participante en una acción formativa SEPE con datos de seguimiento.
 *
 * @ContentEntityType(
 *   id = "sepe_participante",
 *   label = @Translation("Participante SEPE"),
 *   label_collection = @Translation("Participantes SEPE"),
 *   label_singular = @Translation("participante SEPE"),
 *   label_plural = @Translation("participantes SEPE"),
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
 *   base_table = "sepe_participante",
 *   admin_permission = "administer sepe teleformacion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "dni",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sepe-participantes",
 *     "canonical" = "/admin/content/sepe-participantes/{sepe_participante}",
 *     "edit-form" = "/admin/content/sepe-participantes/{sepe_participante}/edit",
 *     "delete-form" = "/admin/content/sepe-participantes/{sepe_participante}/delete",
 *   },
 * )
 */
class SepeParticipante extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['accion_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Acción Formativa'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'sepe_accion_formativa')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nota: Almacenamos el ID de matrícula como integer ya que la entidad
        // enrollment puede no existir en todas las instalaciones.
        $fields['enrollment_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID Matrícula LMS'))
            ->setDescription(t('ID de la matrícula del LMS vinculada (si aplica).'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['dni'] = BaseFieldDefinition::create('string')
            ->setLabel(t('DNI/NIE'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 9)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['nombre'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['apellidos'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Apellidos'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['fecha_alta'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Alta'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayConfigurable('form', TRUE);

        $fields['fecha_baja'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Baja'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayConfigurable('form', TRUE);

        $fields['motivo_baja'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Motivo de Baja'))
            ->setSetting('max_length', 100)
            ->setDisplayConfigurable('form', TRUE);

        // === DATOS DE SEGUIMIENTO ===

        $fields['horas_conectado'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Conectado'))
            ->setDescription(t('Total horas de conexión a la plataforma.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('view', TRUE);

        $fields['porcentaje_progreso'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('% Progreso'))
            ->setDescription(t('Porcentaje de contenido completado.'))
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayConfigurable('view', TRUE);

        $fields['num_actividades'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Actividades Realizadas'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['nota_media'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Nota Media'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('view', TRUE);

        $fields['estado'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'activo' => t('Activo'),
                'baja' => t('Baja'),
                'finalizado' => t('Finalizado'),
                'certificado' => t('Certificado'),
            ])
            ->setDefaultValue('activo')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['ultima_conexion'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Última Conexión'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['fecha_finalizacion'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Finalización'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['apto'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Apto'))
            ->setDescription(t('Resultado final: APTO/NO APTO.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
