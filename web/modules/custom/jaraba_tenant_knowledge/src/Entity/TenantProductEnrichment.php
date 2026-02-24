<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * ENRIQUECIMIENTO DE PRODUCTOS - TenantProductEnrichment
 *
 * PROPÓSITO:
 * Almacena información adicional sobre productos/servicios del tenant
 * que el copiloto puede usar para dar respuestas más precisas.
 *
 * CASOS DE USO:
 * - Ficha extendida de productos
 * - Especificaciones técnicas
 * - FAQs específicas del producto
 * - Comparativas y diferenciadores
 *
 * MULTILINGÜE (G114-3):
 * Campos de contenido traducibles (product_name, description, specifications,
 * benefits, use_cases, price_info, product_faqs).
 * Campos de organización (product_sku, category, is_published, timestamps) no traducibles.
 * Integración con content_translation para UI de traducción estándar.
 *
 * @ContentEntityType(
 *   id = "tenant_product_enrichment",
 *   label = @Translation("Enriquecimiento de Producto"),
 *   label_collection = @Translation("Enriquecimientos de Productos"),
 *   label_singular = @Translation("enriquecimiento de producto"),
 *   label_plural = @Translation("enriquecimientos de productos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\TenantProductEnrichmentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantProductEnrichmentForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\TenantProductEnrichmentForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantProductEnrichmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_product_enrichment",
 *   data_table = "tenant_product_enrichment_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "product_name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tenant-product-enrichments",
 *     "add-form" = "/knowledge/products/add",
 *     "edit-form" = "/knowledge/products/{tenant_product_enrichment}/edit",
 *     "delete-form" = "/knowledge/products/{tenant_product_enrichment}/delete",
 *   },
 *   field_ui_base_route = "entity.tenant_product_enrichment.settings",
 * )
 */
class TenantProductEnrichment extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === IDENTIFICACIÓN ===

        // ID o SKU del producto (opcional, para enlazar con Commerce).
        $fields['product_sku'] = BaseFieldDefinition::create('string')
            ->setLabel(t('SKU/Referencia'))
            ->setDescription(t('Código único del producto en el sistema.'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Nombre del producto.
        $fields['product_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Producto'))
            ->setDescription(t('Nombre comercial del producto o servicio.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ]);

        // Categoría.
        $fields['category'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría del producto.'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ]);

        // === DESCRIPCIÓN EXTENDIDA ===

        // Descripción larga para el copiloto.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción Completa'))
            ->setDescription(t('Descripción detallada para que el copiloto pueda explicar el producto.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 3,
                'settings' => ['rows' => 8],
            ]);

        // Especificaciones técnicas.
        $fields['specifications'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Especificaciones Técnicas'))
            ->setDescription(t('Características técnicas, medidas, materiales, etc.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 4,
            ]);

        // Beneficios/Diferenciadores.
        $fields['benefits'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Beneficios y Diferenciadores'))
            ->setDescription(t('¿Por qué elegir este producto? Ventajas competitivas.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 5,
            ]);

        // Casos de uso.
        $fields['use_cases'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Casos de Uso'))
            ->setDescription(t('¿Para quién es ideal? Escenarios de uso.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 6,
            ]);

        // === PRICING ===

        // Rango de precios (texto libre para flexibilidad).
        $fields['price_info'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Información de Precios'))
            ->setDescription(t('Precio o rango de precios, promociones, etc.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 7,
            ]);

        // === FAQs DEL PRODUCTO ===

        // Preguntas frecuentes específicas del producto.
        $fields['product_faqs'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('FAQs del Producto'))
            ->setDescription(t('Preguntas y respuestas frecuentes específicas.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 8,
                'settings' => ['rows' => 10],
            ]);

        // === ESTADO ===

        // Publicado.
        $fields['is_published'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDescription(t('El copiloto puede usar esta información.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 9,
            ]);

        // === RAG ===

        // Hash para detectar cambios.
        $fields['content_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del Contenido'))
            ->setSetting('max_length', 32);

        // Point ID en Qdrant.
        $fields['qdrant_point_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Qdrant Point ID'))
            ->setSetting('max_length', 64);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el nombre del producto.
     */
    public function getProductName(): string
    {
        return $this->get('product_name')->value ?? '';
    }

    /**
     * Obtiene la categoría.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? '';
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Verifica si está publicado.
     */
    public function isPublished(): bool
    {
        return (bool) $this->get('is_published')->value;
    }

    /**
     * Genera texto para embedding.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        $parts[] = "Producto: " . $this->getProductName();

        $category = $this->getCategory();
        if (!empty($category)) {
            $parts[] = "Categoría: " . $category;
        }

        $description = $this->get('description')->value;
        if (!empty($description)) {
            $parts[] = "Descripción: " . $description;
        }

        $specs = $this->get('specifications')->value;
        if (!empty($specs)) {
            $parts[] = "Especificaciones: " . $specs;
        }

        $benefits = $this->get('benefits')->value;
        if (!empty($benefits)) {
            $parts[] = "Beneficios: " . $benefits;
        }

        $useCases = $this->get('use_cases')->value;
        if (!empty($useCases)) {
            $parts[] = "Casos de uso: " . $useCases;
        }

        $faqs = $this->get('product_faqs')->value;
        if (!empty($faqs)) {
            $parts[] = "FAQs: " . $faqs;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Genera hash del contenido.
     */
    public function generateContentHash(): string
    {
        return md5($this->getEmbeddingText());
    }

    /**
     * Verifica si necesita regenerar embedding.
     */
    public function needsRegeneration(): bool
    {
        $storedHash = $this->get('content_hash')->value;
        return $storedHash !== $this->generateContentHash();
    }

    /**
     * Actualiza el hash del contenido.
     */
    public function updateContentHash(): void
    {
        $this->set('content_hash', $this->generateContentHash());
    }

}
