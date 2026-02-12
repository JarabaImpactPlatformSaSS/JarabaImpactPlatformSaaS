<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad FeatureCard para tarjetas de características.
 *
 * PROPÓSITO:
 * Almacena items de características para secciones de homepage y landing pages.
 * Usado como entity_reference desde HomepageContent.
 *
 * DIRECTRIZ:
 * Es una Content Entity para Field UI y Views integration.
 *
 * @ContentEntityType(
 *   id = "feature_card",
 *   label = @Translation("Tarjeta de Característica"),
 *   label_collection = @Translation("Tarjetas de Características"),
 *   label_singular = @Translation("tarjeta"),
 *   label_plural = @Translation("tarjetas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\FeatureCardListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\FeatureCardForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\FeatureCardForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\FeatureCardForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\FeatureCardAccessControlHandler",
 *   },
 *   base_table = "feature_card",
 *   data_table = "feature_card_field_data",
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
 *     "collection" = "/admin/content/feature-cards",
 *     "add-form" = "/admin/content/feature-cards/add",
 *     "canonical" = "/admin/content/feature-cards/{feature_card}",
 *     "edit-form" = "/admin/content/feature-cards/{feature_card}/edit",
 *     "delete-form" = "/admin/content/feature-cards/{feature_card}/delete",
 *   },
 *   field_ui_base_route = "entity.feature_card.settings",
 * )
 */
class FeatureCard extends ContentEntityBase
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
            ->setDescription(t('Título de la característica'))
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
            ->setDescription(t('Descripción detallada de la característica'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Badge (etiqueta destacada).
        $fields['badge'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Badge'))
            ->setDescription(t('Etiqueta destacada (ej: "Nuevo", "Popular")'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del icono (jaraba_icon).
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('Nombre del icono para jaraba_icon() - ej: "rocket", "users"'))
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
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
     * Obtiene el título de la tarjeta.
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
     * Obtiene el badge.
     */
    public function getBadge(): string
    {
        return $this->get('badge')->value ?? '';
    }

    /**
     * Obtiene el nombre del icono.
     */
    public function getIcon(): string
    {
        return $this->get('icon')->value ?? '';
    }

    /**
     * Obtiene el peso para ordenación.
     */
    public function getWeight(): int
    {
        return (int) $this->get('weight')->value;
    }

}
