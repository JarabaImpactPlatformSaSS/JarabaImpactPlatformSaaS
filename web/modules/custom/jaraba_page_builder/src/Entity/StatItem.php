<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad StatItem para métricas/estadísticas.
 *
 * PROPÓSITO:
 * Almacena items de estadísticas para secciones de homepage.
 * Usado como entity_reference desde HomepageContent.
 *
 * @ContentEntityType(
 *   id = "stat_item",
 *   label = @Translation("Estadística"),
 *   label_collection = @Translation("Estadísticas"),
 *   label_singular = @Translation("estadística"),
 *   label_plural = @Translation("estadísticas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\StatItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\StatItemForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\StatItemForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\StatItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\StatItemAccessControlHandler",
 *   },
 *   base_table = "stat_item",
 *   data_table = "stat_item_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer page builder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/stat-items",
 *     "add-form" = "/admin/content/stat-items/add",
 *     "canonical" = "/admin/content/stat-items/{stat_item}",
 *     "edit-form" = "/admin/content/stat-items/{stat_item}/edit",
 *     "delete-form" = "/admin/content/stat-items/{stat_item}/delete",
 *   },
 *   field_ui_base_route = "entity.stat_item.settings",
 * )
 */
class StatItem extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Valor numérico de la estadística.
        $fields['value'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Valor'))
            ->setDescription(t('Valor numérico de la estadística (ej: 1500)'))
            ->setRequired(TRUE)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Sufijo (%, +, etc).
        $fields['suffix'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sufijo'))
            ->setDescription(t('Sufijo del valor (ej: "%", "+", "K")'))
            ->setSettings([
                'max_length' => 10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Etiqueta descriptiva (traducible).
        $fields['label'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Etiqueta'))
            ->setDescription(t('Descripción de la estadística (ej: "Candidatos activos")'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
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
     * Obtiene el valor numérico.
     */
    public function getValue(): int
    {
        return (int) $this->get('value')->value;
    }

    /**
     * Obtiene el sufijo.
     */
    public function getSuffix(): string
    {
        return $this->get('suffix')->value ?? '';
    }

    /**
     * Obtiene la etiqueta.
     */
    public function getLabel(): string
    {
        return $this->get('label')->value ?? '';
    }

    /**
     * Obtiene el peso para ordenación.
     */
    public function getWeight(): int
    {
        return (int) $this->get('weight')->value;
    }

}
