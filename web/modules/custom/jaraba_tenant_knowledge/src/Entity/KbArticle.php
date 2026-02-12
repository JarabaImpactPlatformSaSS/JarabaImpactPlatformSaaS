<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * ARTÍCULO DE BASE DE CONOCIMIENTO - KbArticle
 *
 * PROPÓSITO:
 * Almacena artículos del centro de ayuda público del tenant.
 * Cada artículo pertenece a una categoría y puede tener vídeos vinculados.
 *
 * ESTRUCTURA:
 * - title: Título del artículo
 * - slug: URL amigable
 * - body: Contenido completo en HTML
 * - summary: Resumen corto para listados
 * - category_id: Referencia a KbCategory
 * - author_id: Referencia al usuario autor
 * - article_status: draft / published / archived
 * - view_count, helpful_count, not_helpful_count: métricas
 * - tags: JSON con etiquetas
 *
 * MULTI-TENANCY:
 * Campo tenant_id obligatorio. Aislamiento completo por tenant.
 *
 * @ContentEntityType(
 *   id = "kb_article",
 *   label = @Translation("Artículo KB"),
 *   label_collection = @Translation("Artículos KB"),
 *   label_singular = @Translation("artículo KB"),
 *   label_plural = @Translation("artículos KB"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\KbArticleListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\KbArticleForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\KbArticleForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\KbArticleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\Access\KbArticleAccessControlHandler",
 *   },
 *   base_table = "kb_article",
 *   admin_permission = "administer tenant knowledge",
 *   field_ui_base_route = "jaraba_tenant_knowledge.admin.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/kb-articles",
 *     "add-form" = "/admin/content/kb-articles/add",
 *     "edit-form" = "/admin/content/kb-articles/{kb_article}/edit",
 *     "delete-form" = "/admin/content/kb-articles/{kb_article}/delete",
 *   },
 * )
 */
class KbArticle extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario (OBLIGATORIO).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de este artículo.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTENIDO PRINCIPAL ===

        // Título del artículo.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título del artículo de la base de conocimiento.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
                'settings' => [
                    'placeholder' => 'Cómo configurar tu cuenta',
                ],
            ]);

        // Slug URL amigable.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Slug'))
            ->setDescription(t('URL amigable del artículo. Se genera automáticamente si se deja vacío.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ]);

        // Cuerpo del artículo.
        $fields['body'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setDescription(t('Contenido completo del artículo.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
                'settings' => [
                    'rows' => 12,
                ],
            ]);

        // Resumen corto.
        $fields['summary'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Resumen'))
            ->setDescription(t('Resumen corto para listados y resultados de búsqueda.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 3,
                'settings' => [
                    'rows' => 3,
                ],
            ]);

        // === RELACIONES ===

        // Categoría.
        $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría del artículo.'))
            ->setSetting('target_type', 'kb_category')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 4,
            ]);

        // Autor.
        $fields['author_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Autor'))
            ->setDescription(t('Usuario autor del artículo.'))
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ]);

        // === ESTADO ===

        // Estado del artículo.
        $fields['article_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de publicación del artículo.'))
            ->setRequired(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'draft' => 'Borrador',
                    'published' => 'Publicado',
                    'archived' => 'Archivado',
                ],
            ])
            ->setDefaultValue('draft')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 6,
            ]);

        // === MÉTRICAS ===

        // Contador de vistas.
        $fields['view_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Vistas'))
            ->setDescription(t('Número de veces que se ha visto el artículo.'))
            ->setDefaultValue(0);

        // Contador de útil.
        $fields['helpful_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Útil'))
            ->setDescription(t('Número de votos positivos.'))
            ->setDefaultValue(0);

        // Contador de no útil.
        $fields['not_helpful_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('No útil'))
            ->setDescription(t('Número de votos negativos.'))
            ->setDefaultValue(0);

        // === METADATOS ===

        // Tags en formato JSON.
        $fields['tags'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tags'))
            ->setDescription(t('Etiquetas del artículo en formato JSON.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 7,
                'settings' => [
                    'rows' => 2,
                ],
            ]);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el título del artículo.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene el slug.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * Obtiene el cuerpo del artículo.
     */
    public function getBody(): string
    {
        return $this->get('body')->value ?? '';
    }

    /**
     * Obtiene el resumen.
     */
    public function getSummary(): string
    {
        return $this->get('summary')->value ?? '';
    }

    /**
     * Obtiene el estado del artículo.
     */
    public function getArticleStatus(): string
    {
        return $this->get('article_status')->value ?? 'draft';
    }

    /**
     * Verifica si el artículo está publicado.
     */
    public function isPublished(): bool
    {
        return $this->getArticleStatus() === 'published';
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID de la categoría.
     */
    public function getCategoryId(): ?int
    {
        return $this->get('category_id')->target_id ? (int) $this->get('category_id')->target_id : NULL;
    }

    /**
     * Obtiene las tags como array.
     */
    public function getTagsArray(): array
    {
        $tagsJson = $this->get('tags')->value;
        if (empty($tagsJson)) {
            return [];
        }
        $decoded = json_decode($tagsJson, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene el contador de vistas.
     */
    public function getViewCount(): int
    {
        return (int) ($this->get('view_count')->value ?? 0);
    }

    /**
     * Obtiene el contador de votos útiles.
     */
    public function getHelpfulCount(): int
    {
        return (int) ($this->get('helpful_count')->value ?? 0);
    }

    /**
     * Obtiene el contador de votos no útiles.
     */
    public function getNotHelpfulCount(): int
    {
        return (int) ($this->get('not_helpful_count')->value ?? 0);
    }

}
