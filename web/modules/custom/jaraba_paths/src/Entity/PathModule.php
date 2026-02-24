<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad PathModule.
 *
 * Representa un módulo temático dentro de una fase.
 * Ejemplo: "Presencia Web Básica", "Primeras Ventas Online".
 *
 * SPEC: 28_Emprendimiento_Digitalization_Paths_v1
 *
 * @ContentEntityType(
 *   id = "path_module",
 *   label = @Translation("Módulo del Itinerario"),
 *   label_collection = @Translation("Módulos"),
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
 *   base_table = "path_module",
 *   admin_permission = "administer digitalization paths",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/path-modules",
 *     "add-form" = "/admin/structure/path-modules/add",
 *     "edit-form" = "/admin/structure/path-module/{path_module}/edit",
 *     "delete-form" = "/admin/structure/path-module/{path_module}/delete",
 *   },
 *   field_ui_base_route = "entity.path_module.settings",
 * )
 */
class PathModule extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['phase_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Fase'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'path_phase')
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
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', ['weight' => 2])
            ->setDisplayConfigurable('form', TRUE);

        $fields['order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['weight' => 3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['estimated_hours'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Horas Estimadas'))
            ->setDefaultValue(4)
            ->setDisplayOptions('form', ['weight' => 4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_optional'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Opcional'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        return $fields;
    }

}
