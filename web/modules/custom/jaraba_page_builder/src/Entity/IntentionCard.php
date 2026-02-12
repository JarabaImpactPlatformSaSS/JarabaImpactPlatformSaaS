<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad IntentionCard para tarjetas de avatar/intención.
 *
 * PROPÓSITO:
 * Almacena items de intención (verticales/avatares) para homepage.
 * Usado como entity_reference desde HomepageContent.
 *
 * @ContentEntityType(
 *   id = "intention_card",
 *   label = @Translation("Tarjeta de Intención"),
 *   label_collection = @Translation("Tarjetas de Intención"),
 *   label_singular = @Translation("intención"),
 *   label_plural = @Translation("intenciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\IntentionCardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\IntentionCardForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\IntentionCardForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\IntentionCardForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\IntentionCardAccessControlHandler",
 *   },
 *   base_table = "intention_card",
 *   data_table = "intention_card_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer page builder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/intention-cards",
 *     "add-form" = "/admin/content/intention-cards/add",
 *     "canonical" = "/admin/content/intention-cards/{intention_card}",
 *     "edit-form" = "/admin/content/intention-cards/{intention_card}/edit",
 *     "delete-form" = "/admin/content/intention-cards/{intention_card}/delete",
 *   },
 *   field_ui_base_route = "entity.intention_card.settings",
 * )
 */
class IntentionCard extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Título de la tarjeta (traducible).
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título de la intención (ej: "Busco empleo")'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción (traducible).
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la intención'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del icono (jaraba_icon).
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('Nombre del icono para jaraba_icon() - ej: "briefcase", "rocket"'))
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // URL de destino.
        $fields['url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL'))
            ->setDescription(t('URL de destino al hacer clic (ej: "/empleo")'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Clase de color (vertical).
        $fields['color_class'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Color'))
            ->setDescription(t('Clase de color según vertical'))
            ->setSettings([
                'allowed_values' => [
                    'corporate' => 'Corporate (Azul)',
                    'impulse' => 'Impulse (Naranja)',
                    'innovation' => 'Innovation (Verde)',
                    'agro' => 'Agro (Verde oscuro)',
                    'commerce' => 'Commerce (Morado)',
                ],
            ])
            ->setDefaultValue('corporate')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Peso para ordenación.
        $fields['weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Peso'))
            ->setDescription(t('Orden de aparición (menor = primero)'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación'));

        return $fields;
    }

    /**
     * Obtiene el título.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene la descripción.
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * Obtiene el nombre del icono.
     */
    public function getIcon(): string
    {
        return $this->get('icon')->value ?? '';
    }

    /**
     * Obtiene la URL de destino.
     */
    public function getUrl(): string
    {
        return $this->get('url')->value ?? '';
    }

    /**
     * Obtiene la clase de color.
     */
    public function getColorClass(): string
    {
        return $this->get('color_class')->value ?? 'corporate';
    }

    /**
     * Obtiene el peso para ordenación.
     */
    public function getWeight(): int
    {
        return (int) $this->get('weight')->value;
    }

}
