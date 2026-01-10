<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad de contenido Vertical.
 *
 * Una Vertical representa un segmento de negocio especializado dentro
 * del ecosistema Jaraba Impact Platform (AgroConecta, FormaTech, etc.)
 *
 * @ContentEntityType(
 *   id = "vertical",
 *   label = @Translation("Vertical"),
 *   label_collection = @Translation("Verticales"),
 *   label_singular = @Translation("vertical"),
 *   label_plural = @Translation("verticales"),
 *   label_count = @PluralTranslation(
 *     singular = "@count vertical",
 *     plural = "@count verticales",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\VerticalListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\VerticalForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\VerticalForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\VerticalForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\VerticalAccessControlHandler",
 *   },
 *   base_table = "vertical",
 *   data_table = "vertical_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer verticals",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/vertical",
 *     "add-form" = "/admin/structure/vertical/add",
 *     "canonical" = "/admin/structure/vertical/{vertical}",
 *     "edit-form" = "/admin/structure/vertical/{vertical}/edit",
 *     "delete-form" = "/admin/structure/vertical/{vertical}/delete",
 *   },
 *   field_ui_base_route = "entity.vertical.collection",
 * )
 */
class Vertical extends ContentEntityBase implements VerticalInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): VerticalInterface
    {
        $this->set('name', $name);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMachineName(): string
    {
        return $this->get('machine_name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): ?string
    {
        return $this->get('description')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnabledFeatures(): array
    {
        $features = [];
        foreach ($this->get('enabled_features') as $item) {
            if ($item->target_id) {
                $features[] = $item->target_id;
            }
        }
        return $features;
    }

    /**
     * {@inheritdoc}
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getEnabledFeatures(), TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeSettings(): array
    {
        $settings = $this->get('theme_settings')->value;
        if (is_string($settings)) {
            return json_decode($settings, TRUE) ?? [];
        }
        return $settings ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la vertical.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('El nombre de la vertical (ej: AgroConecta).'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 100)
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

        // Machine name (identificador único).
        $fields['machine_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Machine Name'))
            ->setDescription(t('Identificador único para código y URLs (ej: agroconecta).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 50)
            ->addConstraint('UniqueField')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Descripción.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la vertical para landing pages y marketing.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Features habilitadas (referencia a Feature config entity).
        $fields['enabled_features'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Features Habilitadas'))
            ->setDescription(t('Funcionalidades activas para esta vertical.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('target_type', 'feature')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'options_buttons',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Configuración de tema (JSON).
        $fields['theme_settings'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración de Tema'))
            ->setDescription(t('Configuración JSON con colores, tipografía y logo por defecto.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 10,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Agentes IA habilitados (referencia a AIAgent config entity).
        $fields['ai_agents'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Agentes IA'))
            ->setDescription(t('Agentes de IA disponibles para esta vertical.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('target_type', 'ai_agent')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'options_buttons',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Estado activo/inactivo.
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Indica si la vertical está activa y disponible.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'))
            ->setDescription(t('Fecha en que se creó la vertical.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de modificación'))
            ->setDescription(t('Fecha de la última modificación.'));

        return $fields;
    }

}
