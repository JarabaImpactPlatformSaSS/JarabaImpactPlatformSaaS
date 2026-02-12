<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad PageExperiment para A/B Testing.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Un experimento contiene múltiples variantes de una página
 * para medir cuál tiene mejor conversión.
 *
 * Estados del experimento:
 * - draft: En preparación
 * - running: Activo, distribuyendo tráfico
 * - paused: Pausado temporalmente
 * - completed: Finalizado con ganador declarado
 *
 * @ContentEntityType(
 *   id = "page_experiment",
 *   label = @Translation("Experimento A/B"),
 *   label_collection = @Translation("Experimentos A/B"),
 *   label_singular = @Translation("experimento"),
 *   label_plural = @Translation("experimentos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\PageExperimentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\PageExperimentForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\PageExperimentForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\PageExperimentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\PageExperimentAccessControlHandler",
 *   },
 *   base_table = "page_experiments",
 *   admin_permission = "administer page builder",
 *   field_ui_base_route = "entity.page_experiment.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/experiments",
 *     "add-form" = "/admin/content/experiments/add",
 *     "canonical" = "/admin/content/experiments/{page_experiment}",
 *     "edit-form" = "/admin/content/experiments/{page_experiment}/edit",
 *     "delete-form" = "/admin/content/experiments/{page_experiment}/delete",
 *   },
 * )
 */
class PageExperiment extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Estados posibles del experimento.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    /**
     * Tipos de objetivo.
     */
    const GOAL_CONVERSION = 'conversion';
    const GOAL_CLICK = 'click';
    const GOAL_FORM_SUBMIT = 'form_submit';
    const GOAL_SCROLL_DEPTH = 'scroll_depth';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre del experimento.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre descriptivo del experimento.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al tenant.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de este experimento.'))
            ->setSetting('target_type', 'group')
            ->setRequired(FALSE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia a la página original.
        $fields['page_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página'))
            ->setDescription(t('La página sobre la que se realiza el experimento.'))
            ->setSetting('target_type', 'page_content')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -5,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del experimento.
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado actual del experimento.'))
            ->setSetting('allowed_values', [
                self::STATUS_DRAFT => 'Borrador',
                self::STATUS_RUNNING => 'En ejecución',
                self::STATUS_PAUSED => 'Pausado',
                self::STATUS_COMPLETED => 'Completado',
            ])
            ->setDefaultValue(self::STATUS_DRAFT)
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de objetivo.
        $fields['goal_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de objetivo'))
            ->setDescription(t('Qué métrica se usa para determinar el ganador.'))
            ->setSetting('allowed_values', [
                self::GOAL_CONVERSION => 'Conversión (compra, registro)',
                self::GOAL_CLICK => 'Click en elemento',
                self::GOAL_FORM_SUBMIT => 'Envío de formulario',
                self::GOAL_SCROLL_DEPTH => 'Profundidad de scroll',
            ])
            ->setDefaultValue(self::GOAL_CONVERSION)
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Selector CSS del objetivo (para clicks, forms).
        // NOTA UX: Valor por defecto para facilitar uso a usuarios no técnicos.
        $fields['goal_target'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Selector del objetivo'))
            ->setDescription(t('Identificador del elemento a medir. Usa el valor por defecto si no lo conoces.'))
            ->setSetting('max_length', 255)
            ->setDefaultValue('.cta-button, .btn-primary, form')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Porcentaje de tráfico asignado al experimento.
        $fields['traffic_allocation'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Asignación de tráfico'))
            ->setDescription(t('Porcentaje del tráfico total que participa en el experimento (0-100).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(100.00)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Umbral de confianza para declarar ganador.
        $fields['confidence_threshold'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Umbral de confianza'))
            ->setDescription(t('Porcentaje de confianza estadística requerido (típicamente 95).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(95.00)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Variante ganadora (si se ha declarado).
        $fields['winner_variant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Variante ganadora'))
            ->setDescription(t('La variante declarada ganadora del experimento.'))
            ->setSetting('target_type', 'experiment_variant')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de inicio.
        $fields['started_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Iniciado'))
            ->setDescription(t('Fecha en que se inició el experimento.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de finalización.
        $fields['ended_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Finalizado'))
            ->setDescription(t('Fecha en que se finalizó el experimento.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación del experimento.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        // Propietario.
        $fields['uid']
            ->setLabel(t('Creador'))
            ->setDescription(t('El usuario que creó el experimento.'));

        return $fields;
    }

    /**
     * Obtiene el nombre del experimento.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el estado actual.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_DRAFT;
    }

    /**
     * Verifica si el experimento está activo.
     */
    public function isRunning(): bool
    {
        return $this->getStatus() === self::STATUS_RUNNING;
    }

    /**
     * Verifica si el experimento está completado.
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * Inicia el experimento.
     */
    public function start(): self
    {
        $this->set('status', self::STATUS_RUNNING);
        $this->set('started_at', \Drupal::time()->getRequestTime());
        return $this;
    }

    /**
     * Pausa el experimento.
     */
    public function pause(): self
    {
        $this->set('status', self::STATUS_PAUSED);
        return $this;
    }

    /**
     * Reanuda el experimento.
     */
    public function resume(): self
    {
        $this->set('status', self::STATUS_RUNNING);
        return $this;
    }

    /**
     * Completa el experimento con un ganador.
     */
    public function complete(?int $winnerVariantId = NULL): self
    {
        $this->set('status', self::STATUS_COMPLETED);
        $this->set('ended_at', \Drupal::time()->getRequestTime());
        if ($winnerVariantId) {
            $this->set('winner_variant_id', $winnerVariantId);
        }
        return $this;
    }

    /**
     * Obtiene el ID de la página del experimento.
     */
    public function getPageId(): ?int
    {
        return $this->get('page_id')->target_id ?? NULL;
    }

    /**
     * Obtiene el umbral de confianza.
     */
    public function getConfidenceThreshold(): float
    {
        return (float) ($this->get('confidence_threshold')->value ?? 95.00);
    }

}
