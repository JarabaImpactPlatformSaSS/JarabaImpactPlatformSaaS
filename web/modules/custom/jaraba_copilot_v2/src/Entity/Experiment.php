<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Experiment entity.
 *
 * Combina Test Card (planificación) y Learning Card (resultados).
 * Basado en metodología Testing Business Ideas de Strategyzer.
 *
 * @ContentEntityType(
 *   id = "experiment",
 *   label = @Translation("Experimento"),
 *   label_collection = @Translation("Experimentos"),
 *   label_singular = @Translation("experimento"),
 *   label_plural = @Translation("experimentos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count experimento",
 *     plural = "@count experimentos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_copilot_v2\ListBuilder\ExperimentListBuilder",
 *     "access" = "Drupal\jaraba_copilot_v2\Access\ExperimentAccessControlHandler",
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
 *   base_table = "experiment",
 *   admin_permission = "administer experiments",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/experiments",
 *     "add-form" = "/admin/content/experiments/add",
 *     "canonical" = "/admin/content/experiments/{experiment}",
 *     "edit-form" = "/admin/content/experiments/{experiment}/edit",
 *     "delete-form" = "/admin/content/experiments/{experiment}/delete",
 *   },
 *   field_ui_base_route = "entity.experiment.settings",
 * )
 */
class Experiment extends ContentEntityBase implements ExperimentInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function label()
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getExperimentType(): string
    {
        return $this->get('experiment_type')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHypothesisId(): ?int
    {
        $value = $this->get('hypothesis')->target_id;
        return $value ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'PLANNED';
    }

    /**
     * {@inheritdoc}
     */
    public function getDecision(): ?string
    {
        return $this->get('decision')->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['entrepreneur_profile'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Perfil de Emprendedor'))
            ->setDescription(t('El emprendedor que ejecuta este experimento.'))
            ->setSetting('target_type', 'entrepreneur_profile')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['hypothesis'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Hipótesis'))
            ->setDescription(t('La hipótesis que valida este experimento.'))
            ->setSetting('target_type', 'hypothesis')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre descriptivo del experimento.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['experiment_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Experimento'))
            ->setDescription(t('ID del experimento del catálogo de 44.'))
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === TEST CARD (Planificación) ===
        $fields['plan'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Plan'))
            ->setDescription(t('Para verificar esto voy a...'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['metrics'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Métricas'))
            ->setDescription(t('Y mediré...'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['success_criteria'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Criterio de Éxito'))
            ->setDescription(t('Tengo razón si...'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['failure_criteria'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Criterio de Fracaso'))
            ->setDescription(t('Estoy equivocado si...'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Inicio'))
            ->setDescription(t('Cuándo comienza el experimento.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Fin'))
            ->setDescription(t('Cuándo termina el experimento.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === LEARNING CARD (Resultados) ===
        $fields['observations'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Observaciones'))
            ->setDescription(t('Lo que observé fue... (hechos objetivos)'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['metrics_results'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Resultados de Métricas'))
            ->setDescription(t('Datos numéricos reales obtenidos.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['customer_learning'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Aprendizaje sobre Cliente'))
            ->setDescription(t('Lo nuevo que descubrí sobre el cliente.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['problem_learning'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Aprendizaje sobre Problema'))
            ->setDescription(t('Lo nuevo que descubrí sobre el problema.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['solution_learning'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Aprendizaje sobre Solución'))
            ->setDescription(t('Lo nuevo que descubrí sobre la solución.'))
            ->setDisplayConfigurable('form', TRUE);

        // === Estado y Decisión ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado actual del experimento.'))
            ->setSetting('allowed_values', [
                'PLANNED' => t('Planificado'),
                'IN_PROGRESS' => t('En Progreso'),
                'COMPLETED' => t('Completado'),
                'CANCELLED' => t('Cancelado'),
            ])
            ->setDefaultValue('PLANNED')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['result'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Resultado'))
            ->setDescription(t('Resultado de la validación.'))
            ->setSetting('allowed_values', [
                'VALIDATED' => t('Validada'),
                'INVALIDATED' => t('Invalidada'),
                'INCONCLUSIVE' => t('Inconcluso'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['decision'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Decisión'))
            ->setDescription(t('Decisión tomada tras el experimento.'))
            ->setSetting('allowed_values', [
                'PERSEVERE' => t('Perseverar (+100 Pi)'),
                'PIVOT' => t('Pivotar (+75 Pi)'),
                'ZOOM_IN' => t('Zoom In (+75 Pi)'),
                'ZOOM_OUT' => t('Zoom Out (+75 Pi)'),
                'KILL' => t('Abandonar (+50 Pi)'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 22,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['next_steps'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Próximos Pasos'))
            ->setDescription(t('Por lo tanto, voy a...'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['points_awarded'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntos Otorgados'))
            ->setDescription(t('Puntos de impacto otorgados por este experimento.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación del experimento.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

}
