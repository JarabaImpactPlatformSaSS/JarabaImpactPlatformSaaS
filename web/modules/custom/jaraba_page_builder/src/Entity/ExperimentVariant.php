<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ExperimentVariant para variantes de A/B Testing.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Una variante representa una versión de la página dentro del experimento.
 * Incluye:
 * - Content data modificado
 * - Peso de tráfico (distribución)
 * - Métricas: visitantes, conversiones
 *
 * @ContentEntityType(
 *   id = "experiment_variant",
 *   label = @Translation("Variante de Experimento"),
 *   label_collection = @Translation("Variantes de Experimento"),
 *   label_singular = @Translation("variante"),
 *   label_plural = @Translation("variantes"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\ExperimentVariantListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\ExperimentVariantForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\ExperimentVariantForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\ExperimentVariantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\ExperimentVariantAccessControlHandler",
 *   },
 *   base_table = "experiment_variants",
 *   admin_permission = "administer page builder",
 *   field_ui_base_route = "entity.experiment_variant.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/experiments/{page_experiment}/variants/{experiment_variant}",
 *     "add-form" = "/admin/content/experiments/{page_experiment}/variants/add",
 *     "edit-form" = "/admin/content/experiments/{page_experiment}/variants/{experiment_variant}/edit",
 *     "delete-form" = "/admin/content/experiments/{page_experiment}/variants/{experiment_variant}/delete",
 *   },
 * )
 */
class ExperimentVariant extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la variante.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre de la variante (ej: Control, Variante A, Variante B).'))
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

        // Referencia al experimento padre.
        $fields['experiment_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Experimento'))
            ->setDescription(t('El experimento al que pertenece esta variante.'))
            ->setSetting('target_type', 'page_experiment')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Es la variante de control?
        $fields['is_control'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Es control'))
            ->setDescription(t('Si esta es la variante de control (original).'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 0,
                'settings' => [
                    'display_label' => TRUE,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Peso de distribución de tráfico.
        $fields['traffic_weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Peso de tráfico'))
            ->setDescription(t('Peso relativo para distribución de tráfico (ej: 50 = 50%).'))
            ->setDefaultValue(50)
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Datos de contenido modificados (JSON).
        $fields['content_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos del Contenido'))
            ->setDescription(t('Datos JSON de esta variante (diferencias vs control).'))
            ->setDefaultValue('{}')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 10,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de visitantes únicos.
        $fields['visitors'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Visitantes'))
            ->setDescription(t('Número de visitantes únicos asignados a esta variante.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de conversiones.
        $fields['conversions'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Conversiones'))
            ->setDescription(t('Número de conversiones registradas.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación de la variante.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la variante.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Verifica si es la variante de control.
     */
    public function isControl(): bool
    {
        return (bool) $this->get('is_control')->value;
    }

    /**
     * Obtiene el peso de tráfico.
     */
    public function getTrafficWeight(): int
    {
        return (int) ($this->get('traffic_weight')->value ?? 50);
    }

    /**
     * Obtiene los datos de contenido como array.
     */
    public function getContentData(): array
    {
        $data = $this->get('content_data')->value ?? '{}';
        return json_decode($data, TRUE) ?: [];
    }

    /**
     * Establece los datos de contenido.
     */
    public function setContentData(array $data): self
    {
        $this->set('content_data', json_encode($data, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    /**
     * Obtiene el número de visitantes.
     */
    public function getVisitors(): int
    {
        return (int) ($this->get('visitors')->value ?? 0);
    }

    /**
     * Obtiene el número de conversiones.
     */
    public function getConversions(): int
    {
        return (int) ($this->get('conversions')->value ?? 0);
    }

    /**
     * Calcula la tasa de conversión.
     */
    public function getConversionRate(): float
    {
        $visitors = $this->getVisitors();
        if ($visitors === 0) {
            return 0.0;
        }
        return ($this->getConversions() / $visitors) * 100;
    }

    /**
     * Incrementa el contador de visitantes.
     */
    public function incrementVisitors(): self
    {
        $current = $this->getVisitors();
        $this->set('visitors', $current + 1);
        return $this;
    }

    /**
     * Incrementa el contador de conversiones.
     */
    public function incrementConversions(): self
    {
        $current = $this->getConversions();
        $this->set('conversions', $current + 1);
        return $this;
    }

    /**
     * Obtiene el ID del experimento padre.
     */
    public function getExperimentId(): ?int
    {
        return $this->get('experiment_id')->target_id ?? NULL;
    }

}
