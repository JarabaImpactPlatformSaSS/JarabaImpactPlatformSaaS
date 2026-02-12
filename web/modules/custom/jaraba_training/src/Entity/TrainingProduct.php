<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad TrainingProduct.
 *
 * Producto formativo en la Escalera de Valor (Value Ladder).
 * Peldaños 0-5: Lead Magnet → Microcurso → Membresía → Mentoring →
 * Certificación Consultores → Certificación Entidades.
 *
 * @ContentEntityType(
 *   id = "training_product",
 *   label = @Translation("Producto Training"),
 *   label_collection = @Translation("Productos Training"),
 *   label_singular = @Translation("producto training"),
 *   label_plural = @Translation("productos training"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_training\TrainingProductListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_training\TrainingProductAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "training_product",
 *   admin_permission = "administer training products",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/training-products/{training_product}",
 *     "add-form" = "/admin/content/training-products/add",
 *     "edit-form" = "/admin/content/training-products/{training_product}/edit",
 *     "delete-form" = "/admin/content/training-products/{training_product}/delete",
 *     "collection" = "/admin/content/training-products",
 *   },
 *   field_ui_base_route = "entity.training_product.settings",
 * )
 */
class TrainingProduct extends ContentEntityBase implements TrainingProductInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getProductType(): string
    {
        return $this->get('product_type')->value ?? 'microcourse';
    }

    /**
     * {@inheritdoc}
     */
    public function getLadderLevel(): int
    {
        return (int) ($this->get('ladder_level')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(): float
    {
        return (float) ($this->get('price')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingType(): string
    {
        return $this->get('billing_type')->value ?? 'one_time';
    }

    /**
     * {@inheritdoc}
     */
    public function getNextProduct(): ?TrainingProductInterface
    {
        $nextId = $this->get('next_product_id')->target_id ?? NULL;
        if ($nextId) {
            return \Drupal::entityTypeManager()
                ->getStorage('training_product')
                ->load($nextId);
        }
        return NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isFree(): bool
    {
        return $this->getBillingType() === 'free' || $this->getPrice() <= 0;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === INFORMACIÓN BÁSICA ===

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre del producto formativo.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción del producto.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === ESCALERA DE VALOR ===
        // Directriz Nuclear #20: allowed_values configurables desde YAML.
        $fields['product_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Producto'))
            ->setDescription(t('Categoría del producto en la escalera.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values_function', 'jaraba_training_allowed_product_types')
            ->setDefaultValue('microcourse')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['ladder_level'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Peldaño Escalera'))
            ->setDescription(t('Nivel en la escalera de valor (0-5).'))
            ->setRequired(TRUE)
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 5)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === PRECIOS Y FACTURACIÓN ===

        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio'))
            ->setDescription(t('Precio base del producto.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['billing_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Facturación'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values_function', 'jaraba_training_allowed_billing_types')
            ->setDefaultValue('one_time')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CONTENIDO FORMATIVO ===
        $fields['course_ids'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cursos LMS Incluidos'))
            ->setDescription(t('Cursos del LMS que incluye este producto.'))
            ->setSetting('target_type', 'lms_course')
            ->setSetting('handler_settings', ['target_bundles' => []])
            ->setCardinality(-1)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['mentoring_packages'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Paquetes de Mentoring'))
            ->setDescription(t('Paquetes de mentoring incluidos.'))
            ->setSetting('target_type', 'node')
            ->setSetting('handler_settings', ['target_bundles' => []])
            ->setCardinality(-1)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === ESCALERA: SIGUIENTE PRODUCTO ===

        $fields['next_product_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Siguiente en Escalera'))
            ->setDescription(t('Producto recomendado como siguiente paso.'))
            ->setSetting('target_type', 'training_product')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === MARKETING ===

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Mostrar como producto destacado.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['upsell_message'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Mensaje de Upsell'))
            ->setDescription(t('Mensaje para promocionar el siguiente producto.'))
            ->setSetting('max_length', 500)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CAMPOS DE SISTEMA ===

        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
