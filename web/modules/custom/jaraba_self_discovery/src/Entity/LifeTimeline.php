<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad LifeTimeline para eventos de la Línea de Vida.
 *
 * Almacena momentos significativos (álgidos y bajos) de la vida
 * del usuario para el ejercicio de autodescubrimiento "Mi Línea de Vida".
 *
 * @ContentEntityType(
 *   id = "life_timeline",
 *   label = @Translation("Evento de Línea de Vida"),
 *   label_collection = @Translation("Eventos de Línea de Vida"),
 *   label_singular = @Translation("evento"),
 *   label_plural = @Translation("eventos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_self_discovery\LifeTimelineListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_self_discovery\LifeTimelineAccessControlHandler",
 *   },
 *   base_table = "life_timeline",
 *   admin_permission = "administer self discovery",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/life-timeline/{life_timeline}",
 *     "add-form" = "/admin/content/life-timeline/add",
 *     "edit-form" = "/admin/content/life-timeline/{life_timeline}/edit",
 *     "delete-form" = "/admin/content/life-timeline/{life_timeline}/delete",
 *     "collection" = "/admin/content/life-timeline",
 *   },
 * )
 */
class LifeTimeline extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Campo owner (usuario propietario).
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('Usuario al que pertenece este evento.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título breve del evento.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['event_date'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Fecha/Período'))
            ->setDescription(t('Fecha o período del evento (ej: "2020" o "Verano 2018").'))
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['event_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de momento'))
            ->setDescription(t('high_moment, low_moment o turning_point.'))
            ->setSettings(['max_length' => 32])
            ->setDefaultValue('high_moment')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['category'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('professional o personal.'))
            ->setSettings(['max_length' => 32])
            ->setDefaultValue('personal')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del evento.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['satisfaction_factors'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Factores de satisfacción'))
            ->setDescription(t('JSON array de factores: achievement, recognition, autonomy, etc.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['skills_used'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Competencias demostradas'))
            ->setDescription(t('JSON array de competencias.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['learnings'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Aprendizajes'))
            ->setDescription(t('¿Qué aprendiste de este momento?'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['values_discovered'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Valores descubiertos'))
            ->setDescription(t('JSON array de valores que se manifestaron.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['patterns'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Patrones'))
            ->setDescription(t('Patrones identificados con otros momentos.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última actualización'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Gets the event type label.
     *
     * @return string
     *   Human-readable label.
     */
    public function getEventTypeLabel(): string
    {
        $labels = [
            'high_moment' => t('Momento Álgido'),
            'low_moment' => t('Momento Bajo'),
            'turning_point' => t('Punto de Inflexión'),
        ];
        $type = $this->get('event_type')->value;
        return (string) ($labels[$type] ?? $type);
    }

    /**
     * Gets satisfaction factors as array.
     *
     * @return array
     *   Decoded factors array.
     */
    public function getSatisfactionFactors(): array
    {
        $raw = $this->get('satisfaction_factors')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

    /**
     * Gets skills as array.
     *
     * @return array
     *   Decoded skills array.
     */
    public function getSkills(): array
    {
        $raw = $this->get('skills_used')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

    /**
     * Gets discovered values as array.
     *
     * @return array
     *   Decoded values array.
     */
    public function getValuesDiscovered(): array
    {
        $raw = $this->get('values_discovered')->value;
        return $raw ? (json_decode($raw, TRUE) ?? []) : [];
    }

}
