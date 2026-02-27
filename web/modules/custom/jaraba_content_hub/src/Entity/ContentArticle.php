<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad ContentArticle (Artículo de Contenido).
 *
 * PROPÓSITO:
 * Entidad core del Content Hub que representa artículos de blog.
 * Incluye campos para SEO (Answer Capsule, meta tags), tracking
 * de engagement y soporte multi-idioma.
 *
 * ESTADOS DE PUBLICACIÓN:
 * - 'draft': Borrador en edición
 * - 'review': Pendiente de revisión
 * - 'scheduled': Programado para publicación
 * - 'published': Publicado y visible
 * - 'archived': Archivado/oculto
 *
 * CAMPOS SEO/GEO:
 * - answer_capsule: Respuesta directa para buscadores IA (max 200 chars)
 * - seo_title: Meta título (max 70 chars)
 * - seo_description: Meta descripción (max 160 chars)
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 *
 * @ContentEntityType(
 *   id = "content_article",
 *   label = @Translation("Article"),
 *   label_collection = @Translation("Articles"),
 *   label_singular = @Translation("article"),
 *   label_plural = @Translation("articles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count article",
 *     plural = "@count articles",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_content_hub\ContentArticleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_content_hub\ContentArticleAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentArticleForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentArticleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "content_article",
 *   data_table = "content_article_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer content hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "owner" = "author",
 *   },
 *   links = {
 *     "canonical" = "/blog/{content_article}",
 *     "add-form" = "/admin/content/articles/add",
 *     "edit-form" = "/admin/content/articles/{content_article}/edit",
 *     "delete-form" = "/admin/content/articles/{content_article}/delete",
 *     "collection" = "/admin/content/articles",
 *   },
 *   field_ui_base_route = "entity.content_article.settings",
 * )
 */
class ContentArticle extends ContentEntityBase implements ContentArticleInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Obtiene el título del artículo.
     *
     * @return string
     *   El título del artículo.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene el slug URL-friendly del artículo.
     *
     * @return string
     *   El slug para la URL.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setSlug(string $slug): static
    {
        $this->set('slug', $slug);
        return $this;
    }

    /**
     * Obtiene el extracto/resumen del artículo.
     *
     * @return string
     *   El texto del extracto.
     */
    public function getExcerpt(): string
    {
        return $this->get('excerpt')->value ?? '';
    }

    /**
     * Obtiene el Answer Capsule para GEO.
     *
     * El Answer Capsule es una respuesta concisa diseñada
     * para ser citada por buscadores IA y featured snippets.
     *
     * @return string
     *   El Answer Capsule (max 200 caracteres).
     */
    public function getAnswerCapsule(): string
    {
        return $this->get('answer_capsule')->value ?? '';
    }

    /**
     * Obtiene el estado de publicación.
     *
     * @return string
     *   Uno de: draft, review, scheduled, published, archived.
     */
    public function getPublicationStatus(): string
    {
        return $this->get('status')->value ?? 'draft';
    }

    /**
     * Verifica si el artículo está publicado.
     *
     * @return bool
     *   TRUE si el estado es 'published'.
     */
    public function isPublished(): bool
    {
        return $this->getPublicationStatus() === 'published';
    }

    /**
     * Obtiene el tiempo estimado de lectura.
     *
     * @return int
     *   Minutos de lectura estimados.
     */
    public function getReadingTime(): int
    {
        return (int) ($this->get('reading_time')->value ?? 0);
    }

    /**
     * Verifica si el artículo fue generado por IA.
     *
     * @return bool
     *   TRUE si fue generado usando el Writing Assistant.
     */
    public function isAiGenerated(): bool
    {
        return (bool) ($this->get('ai_generated')->value ?? FALSE);
    }

    /**
     * Obtiene el modo de layout del artículo.
     *
     * @return string
     *   'legacy' para textarea clásico, 'canvas' para editor visual.
     */
    public function getLayoutMode(): string
    {
        return $this->get('layout_mode')->value ?? 'legacy';
    }

    /**
     * Verifica si el artículo usa el Canvas Editor.
     *
     * @return bool
     *   TRUE si layout_mode es 'canvas'.
     */
    public function isCanvasMode(): bool
    {
        return $this->getLayoutMode() === 'canvas';
    }

    /**
     * Obtiene los datos JSON del Canvas GrapesJS.
     *
     * @return string
     *   JSON del estado completo del editor (components, styles, html, css).
     */
    public function getCanvasData(): string
    {
        return $this->get('canvas_data')->value ?? '{}';
    }

    /**
     * Obtiene el HTML renderizado del Canvas.
     *
     * @return string
     *   HTML sanitizado para la vista pública.
     */
    public function getRenderedHtml(): string
    {
        return $this->get('rendered_html')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatured(): bool
    {
        return (bool) ($this->get('is_featured')->value ?? FALSE);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewsCount(): int
    {
        return (int) ($this->get('views_count')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): string
    {
        return $this->get('tags')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaType(): string
    {
        return $this->get('schema_type')->value ?? 'BlogPosting';
    }

    /**
     * {@inheritdoc}
     */
    public function getVertical(): string
    {
        return $this->get('vertical')->value ?? '';
    }

    /**
     * Obtiene la entidad ContentAuthor referenciada.
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentAuthorInterface|null
     *   La entidad ContentAuthor o NULL si no tiene una asignada.
     */
    public function getContentAuthorEntity(): ?ContentAuthorInterface
    {
        if ($this->get('content_author')->isEmpty()) {
            return NULL;
        }
        return $this->get('content_author')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?int
    {
        $target_id = $this->get('tenant_id')->target_id;
        return $target_id !== NULL ? (int) $target_id : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantId(?int $tenantId): static
    {
        $this->set('tenant_id', $tenantId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Define los campos base de la entidad ContentArticle.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Título del artículo - requerido y traducible.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('El título del artículo.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
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

        // Slug para URLs amigables.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Slug'))
            ->setDescription(t('El slug amigable para la URL.'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Extracto para listados y previews.
        $fields['excerpt'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Extracto'))
            ->setDescription(t('Un resumen corto para listados.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Cuerpo principal del artículo.
        $fields['body'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setDescription(t('El contenido principal del artículo.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -5,
                'settings' => [
                    'rows' => 20,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Answer Capsule para GEO/AI search.
        $fields['answer_capsule'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Answer Capsule'))
            ->setDescription(t('Respuesta concisa para optimización GEO (max 200 caracteres).'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 200)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Imagen destacada.
        $fields['featured_image'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Imagen Destacada'))
            ->setDescription(t('La imagen principal del artículo.'))
            ->setSetting('target_type', 'file')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'image',
                'weight' => -5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Categoría del artículo.
        $fields['category'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría'))
            ->setDescription(t('La categoría del artículo.'))
            ->setSetting('target_type', 'content_category')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado de publicación.
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('El estado de publicación.'))
            ->setRequired(TRUE)
            ->setDefaultValue('draft')
            ->setSetting('allowed_values', [
                'draft' => t('Borrador'),
                'review' => t('En Revisión'),
                'scheduled' => t('Programado'),
                'published' => t('Publicado'),
                'archived' => t('Archivado'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Fecha de publicación programada.
        $fields['publish_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Publicación'))
            ->setDescription(t('La fecha para publicar el artículo.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tiempo estimado de lectura.
        $fields['reading_time'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tiempo de Lectura'))
            ->setDescription(t('Tiempo estimado de lectura en minutos.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Título SEO para meta tag.
        $fields['seo_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título SEO'))
            ->setDescription(t('El meta título (max 70 caracteres).'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 70)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Meta description para SEO.
        $fields['seo_description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Descripción SEO'))
            ->setDescription(t('La meta descripción (max 160 caracteres).'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 160)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Flag de contenido generado por IA.
        $fields['ai_generated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Generado por IA'))
            ->setDescription(t('Indica si este artículo fue generado por IA.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 25,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Análisis de Sentimiento (F194).
        $fields['sentiment_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Sentiment Score'))
            ->setDescription(t('Puntuación de sentimiento de -1.0 a 1.0.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 4)
            ->setDefaultValue(0);

        $fields['sentiment_label'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sentiment Label'))
            ->setDescription(t('Etiqueta de sentimiento: positive, neutral, negative.'))
            ->setSetting('max_length', 16)
            ->setDefaultValue('neutral');

        // Puntuación de engagement.
        $fields['engagement_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Puntuación de Engagement'))
            ->setDescription(t('Puntuación de engagement de 0.0 a 1.0.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 4)
            ->setDefaultValue(0);

        // =====================================================================
        // CONSOLIDACIÓN: Campos backporteados de jaraba_blog + clase mundial.
        // =====================================================================

        // Tags (comma-separated) para categorización flexible.
        $fields['tags'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tags'))
            ->setDescription(t('Tags separados por comas para categorización flexible.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -1,
                'settings' => [
                    'rows' => 2,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Texto alternativo para la imagen destacada (accesibilidad/SEO).
        $fields['featured_image_alt'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Alt Imagen Destacada'))
            ->setDescription(t('Texto alternativo para la imagen destacada (accesibilidad y SEO).'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Flag de contenido destacado.
        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Indica si este artículo es contenido destacado.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Contador de visualizaciones.
        $fields['views_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Visualizaciones'))
            ->setDescription(t('Número de visualizaciones del artículo.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 16,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de Schema.org para datos estructurados.
        $fields['schema_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Schema Type'))
            ->setDescription(t('El tipo de Schema.org para datos estructurados SEO.'))
            ->setDefaultValue('BlogPosting')
            ->setSetting('allowed_values', [
                'BlogPosting' => 'Blog Posting',
                'Article' => 'Article',
                'NewsArticle' => 'News Article',
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 22,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Imagen Open Graph (diferente de featured_image).
        $fields['og_image'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Imagen Open Graph'))
            ->setDescription(t('Imagen optimizada para redes sociales (1200x630).'))
            ->setSetting('target_type', 'file')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 23,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Autor editorial (ContentAuthor entity).
        $fields['content_author'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Autor Editorial'))
            ->setDescription(t('El autor editorial del artículo (perfil público).'))
            ->setSetting('target_type', 'content_author')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 13,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Vertical canónica (VERTICAL-CANONICAL-001).
        $fields['vertical'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('La vertical canónica del ecosistema.'))
            ->setSetting('allowed_values', [
                'empleabilidad' => 'Empleabilidad',
                'emprendimiento' => 'Emprendimiento',
                'comercioconecta' => 'Comercio Conecta',
                'agroconecta' => 'Agro Conecta',
                'jarabalex' => 'JarabaLex',
                'serviciosconecta' => 'Servicios Conecta',
                'andalucia_ei' => 'Andalucía E.I.',
                'jaraba_content_hub' => 'Content Hub',
                'formacion' => 'Formación',
                'demo' => 'Demo',
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Fecha programada de publicación automática.
        $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Publicación Programada'))
            ->setDescription(t('Fecha/hora para auto-publicar via cron.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 14,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('La fecha de creación del artículo.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('La fecha de última modificación del artículo.'));

        // =====================================================================
        // CANVAS EDITOR GrapesJS — Editor visual para artículos premium.
        // Permite composición drag-and-drop reutilizando el engine del
        // Page Builder. El campo body se mantiene para artículos legacy.
        // =====================================================================

        // Modo de layout: legacy (textarea clásico) o canvas (editor visual).
        $fields['layout_mode'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Modo de Layout'))
            ->setDescription(t('Tipo de composición del artículo: textarea clásico o editor visual canvas.'))
            ->setTranslatable(FALSE)
            ->setSetting('allowed_values', [
                'legacy' => 'Clásico (textarea)',
                'canvas' => 'Canvas Editor (visual)',
            ])
            ->setDefaultValue('legacy')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Datos del Canvas GrapesJS (components + styles + html + css).
        $fields['canvas_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos del Canvas'))
            ->setDescription(t('JSON con el estado completo del Canvas Editor (components, styles, html, css).'))
            ->setTranslatable(TRUE)
            ->setDefaultValue('{}')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 4,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // HTML renderizado del canvas para el frontend público.
        $fields['rendered_html'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('HTML Renderizado'))
            ->setDescription(t('HTML final exportado del Canvas Editor para la vista pública.'))
            ->setTranslatable(TRUE)
            ->setDefaultValue('')
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', FALSE);

        // TENANT-BRIDGE-001: Tenant como entity_reference a group.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('The group/tenant that owns this article.'))
            ->setSetting('target_type', 'group')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 90,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        return $fields;
    }

}
