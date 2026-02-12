<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad PathPhase.
 *
 * Representa una fase del Método Jaraba dentro de un itinerario.
 * Las 3 fases principales: Diagnóstico, Acción, Optimización.
 *
 * SPEC: 28_Emprendimiento_Digitalization_Paths_v1
 *
 * @ContentEntityType(
 *   id = "path_phase",
 *   label = @Translation("Fase del Itinerario"),
 *   label_collection = @Translation("Fases"),
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
 *   base_table = "path_phase",
 *   admin_permission = "administer digitalization paths",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/path-phases",
 *     "add-form" = "/admin/structure/path-phases/add",
 *     "edit-form" = "/admin/structure/path-phase/{path_phase}/edit",
 *     "delete-form" = "/admin/structure/path-phase/{path_phase}/delete",
 *   },
 * )
 */
class PathPhase extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al path padre
        $fields['path_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Itinerario'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'digitalization_path')
            ->setDisplayOptions('form', ['weight' => 0])
            ->setDisplayConfigurable('form', TRUE);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la Fase'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', ['weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['phase_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Fase'))
            ->setDescription(t('Fase del Método Jaraba.'))
            ->setSetting('allowed_values', [
                'diagnostico' => 'Fase 1: Diagnóstico',
                'accion' => 'Fase 2: Acción',
                'optimizacion' => 'Fase 3: Optimización',
            ])
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['weight' => 2])
            ->setDisplayConfigurable('form', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', ['weight' => 3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['weight' => 4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        $fields['estimated_days'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración Estimada (días)'))
            ->setDefaultValue(14)
            ->setDisplayOptions('form', ['weight' => 6])
            ->setDisplayConfigurable('form', TRUE);

        return $fields;
    }

}
