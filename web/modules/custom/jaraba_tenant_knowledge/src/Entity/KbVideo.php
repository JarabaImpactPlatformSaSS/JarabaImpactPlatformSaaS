<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * VÍDEO DE BASE DE CONOCIMIENTO - KbVideo
 *
 * PROPÓSITO:
 * Almacena vídeos tutoriales y explicativos del centro de ayuda.
 * Pueden vincularse a artículos y categorías.
 *
 * ESTRUCTURA:
 * - title: Título del vídeo
 * - video_url: URL del vídeo (YouTube, Vimeo, etc.)
 * - thumbnail_url: URL de la imagen miniatura
 * - description: Descripción del vídeo
 * - duration_seconds: Duración en segundos
 * - category_id: Referencia a KbCategory
 * - article_id: Referencia a KbArticle (opcional)
 * - video_status: draft / published / archived
 * - view_count: contador de reproducciones
 *
 * MULTI-TENANCY:
 * Campo tenant_id obligatorio. Aislamiento completo por tenant.
 *
 * @ContentEntityType(
 *   id = "kb_video",
 *   label = @Translation("Vídeo KB"),
 *   label_collection = @Translation("Vídeos KB"),
 *   label_singular = @Translation("vídeo KB"),
 *   label_plural = @Translation("vídeos KB"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\KbVideoListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\KbVideoForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\KbVideoForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\KbVideoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\Access\KbVideoAccessControlHandler",
 *   },
 *   base_table = "kb_video",
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/kb-videos",
 *     "add-form" = "/admin/content/kb-videos/add",
 *     "edit-form" = "/admin/content/kb-videos/{kb_video}/edit",
 *     "delete-form" = "/admin/content/kb-videos/{kb_video}/delete",
 *   },
 * )
 */
class KbVideo extends ContentEntityBase implements EntityChangedInterface
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
            ->setDescription(t('El tenant propietario de este vídeo.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTENIDO PRINCIPAL ===

        // Título del vídeo.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título del vídeo.'))
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
            ]);

        // URL del vídeo.
        $fields['video_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL del Vídeo'))
            ->setDescription(t('URL del vídeo (YouTube, Vimeo, etc.).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 2048)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
                'settings' => [
                    'placeholder' => 'https://www.youtube.com/watch?v=...',
                ],
            ]);

        // URL de la miniatura.
        $fields['thumbnail_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de Miniatura'))
            ->setDescription(t('URL de la imagen miniatura del vídeo.'))
            ->setSetting('max_length', 2048)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ]);

        // Descripción.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción del vídeo.'))
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 3,
                'settings' => [
                    'rows' => 4,
                ],
            ]);

        // Duración en segundos.
        $fields['duration_seconds'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración (segundos)'))
            ->setDescription(t('Duración del vídeo en segundos.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 4,
            ]);

        // === RELACIONES ===

        // Categoría.
        $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría del vídeo.'))
            ->setSetting('target_type', 'kb_category')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ]);

        // Artículo vinculado (opcional).
        $fields['article_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Artículo Vinculado'))
            ->setDescription(t('Artículo KB asociado a este vídeo (opcional).'))
            ->setSetting('target_type', 'kb_article')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 6,
            ]);

        // === ESTADO ===

        // Estado del vídeo.
        $fields['video_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de publicación del vídeo.'))
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
                'weight' => 7,
            ]);

        // === MÉTRICAS ===

        // Contador de vistas.
        $fields['view_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Vistas'))
            ->setDescription(t('Número de reproducciones del vídeo.'))
            ->setDefaultValue(0);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el título del vídeo.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene la URL del vídeo.
     */
    public function getVideoUrl(): string
    {
        return $this->get('video_url')->value ?? '';
    }

    /**
     * Obtiene la URL de la miniatura.
     */
    public function getThumbnailUrl(): string
    {
        return $this->get('thumbnail_url')->value ?? '';
    }

    /**
     * Obtiene la descripción.
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * Obtiene la duración en segundos.
     */
    public function getDurationSeconds(): int
    {
        return (int) ($this->get('duration_seconds')->value ?? 0);
    }

    /**
     * Obtiene la duración formateada (MM:SS).
     */
    public function getFormattedDuration(): string
    {
        $seconds = $this->getDurationSeconds();
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $remaining);
    }

    /**
     * Obtiene el estado del vídeo.
     */
    public function getVideoStatus(): string
    {
        return $this->get('video_status')->value ?? 'draft';
    }

    /**
     * Verifica si el vídeo está publicado.
     */
    public function isPublished(): bool
    {
        return $this->getVideoStatus() === 'published';
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
     * Obtiene el ID del artículo vinculado.
     */
    public function getArticleId(): ?int
    {
        return $this->get('article_id')->target_id ? (int) $this->get('article_id')->target_id : NULL;
    }

}
