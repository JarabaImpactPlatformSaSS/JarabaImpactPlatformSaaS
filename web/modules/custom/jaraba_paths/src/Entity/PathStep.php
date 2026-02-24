<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad PathStep.
 *
 * Representa un paso de acción individual dentro de un módulo.
 * Puede ser una tarea, un recurso a leer, o un quick win.
 *
 * SPEC: 29_Emprendimiento_Action_Plans_v1
 *
 * @ContentEntityType(
 *   id = "path_step",
 *   label = @Translation("Paso del Itinerario"),
 *   label_collection = @Translation("Pasos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "path_step",
 *   admin_permission = "administer digitalization paths",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/path-steps",
 *     "add-form" = "/admin/structure/path-steps/add",
 *     "edit-form" = "/admin/structure/path-step/{path_step}/edit",
 *     "delete-form" = "/admin/structure/path-step/{path_step}/delete",
 *   },
 *   field_ui_base_route = "entity.path_step.settings",
 * )
 */
class PathStep extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['module_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Módulo'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'path_module')
            ->setDisplayOptions('form', ['weight' => 0])
            ->setDisplayConfigurable('form', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', ['weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Instrucciones'))
            ->setDisplayOptions('form', ['weight' => 2])
            ->setDisplayConfigurable('form', TRUE);

        $fields['step_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Paso'))
            ->setSetting('allowed_values', [
                'task' => 'Tarea',
                'resource' => 'Recurso/Lectura',
                'quick_win' => 'Quick Win',
                'milestone' => 'Hito/Milestone',
                'assessment' => 'Autoevaluación',
            ])
            ->setDefaultValue('task')
            ->setDisplayOptions('form', ['weight' => 3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['weight' => 4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['estimated_minutes'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Minutos Estimados'))
            ->setDefaultValue(30)
            ->setDisplayOptions('form', ['weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_required'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Obligatorio'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['weight' => 6])
            ->setDisplayConfigurable('form', TRUE);

        $fields['xp_reward'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('XP de Recompensa'))
            ->setDefaultValue(10)
            ->setDisplayOptions('form', ['weight' => 7])
            ->setDisplayConfigurable('form', TRUE);

        $fields['resource_url'] = BaseFieldDefinition::create('link')
            ->setLabel(t('Recurso Externo'))
            ->setDisplayOptions('form', ['weight' => 8])
            ->setDisplayConfigurable('form', TRUE);

        $fields['tool_suggestion'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Herramienta Sugerida'))
            ->setDescription(t('Nombre de herramienta recomendada para completar el paso.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['weight' => 9])
            ->setDisplayConfigurable('form', TRUE);

        return $fields;
    }

}
