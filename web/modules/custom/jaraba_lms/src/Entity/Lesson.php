<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Lesson entity.
 *
 * Una lección es una unidad de contenido dentro de un curso.
 * Soporta múltiples tipos de contenido: video, texto, H5P interactivo.
 *
 * @ContentEntityType(
 *   id = "lms_lesson",
 *   label = @Translation("Lesson"),
 *   label_collection = @Translation("Lessons"),
 *   label_singular = @Translation("lesson"),
 *   label_plural = @Translation("lessons"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_lms\LessonListBuilder",
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
 *   },
 *   base_table = "lms_lesson",
 *   admin_permission = "administer lms courses",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/lesson/{lms_lesson}",
 *     "add-form" = "/admin/content/lessons/add",
 *     "edit-form" = "/admin/content/lesson/{lms_lesson}/edit",
 *     "delete-form" = "/admin/content/lesson/{lms_lesson}/delete",
 *     "collection" = "/admin/content/lessons",
 *   },
 *   field_ui_base_route = "entity.lms_lesson.settings",
 * )
 */
class Lesson extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Tipos de contenido de lección.
     */
    const TYPE_VIDEO = 'video';
    const TYPE_TEXT = 'text';
    const TYPE_H5P = 'h5p';
    const TYPE_QUIZ = 'quiz';
    const TYPE_ASSIGNMENT = 'assignment';

    /**
     * Proveedores de video soportados.
     */
    const VIDEO_YOUTUBE = 'youtube';
    const VIDEO_VIMEO = 'vimeo';
    const VIDEO_BUNNY = 'bunny';
    const VIDEO_SELF = 'self_hosted';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Título de la lección
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('The lesson title'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('view', ['label' => 'hidden', 'type' => 'string', 'weight' => -5])
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al curso
        $fields['course_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Course'))
            ->setDescription(t('The course this lesson belongs to'))
            ->setSetting('target_type', 'lms_course')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Peso/orden dentro del curso
        $fields['weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Weight'))
            ->setDescription(t('Order within the course'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 1])
            ->setDisplayConfigurable('form', TRUE);

        // Tipo de contenido
        $fields['lesson_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Lesson Type'))
            ->setDescription(t('Type of lesson content'))
            ->setSettings([
                'allowed_values' => [
                    self::TYPE_VIDEO => 'Video',
                    self::TYPE_TEXT => 'Text/Reading',
                    self::TYPE_H5P => 'H5P Interactive',
                    self::TYPE_QUIZ => 'Quiz',
                    self::TYPE_ASSIGNMENT => 'Assignment',
                ],
            ])
            ->setDefaultValue(self::TYPE_VIDEO)
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 2])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Contenido de texto (para TYPE_TEXT)
        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Content'))
            ->setDescription(t('Text content for reading lessons'))
            ->setDisplayOptions('view', ['type' => 'text_default', 'weight' => 5])
            ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ========== CAMPOS DE VIDEO ==========

        // Proveedor de video
        $fields['video_provider'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Video Provider'))
            ->setDescription(t('Source of the video'))
            ->setSettings([
                'allowed_values' => [
                    self::VIDEO_YOUTUBE => 'YouTube',
                    self::VIDEO_VIMEO => 'Vimeo',
                    self::VIDEO_BUNNY => 'Bunny.net',
                    self::VIDEO_SELF => 'Self-hosted',
                ],
            ])
            ->setDefaultValue(self::VIDEO_YOUTUBE)
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // URL del video
        $fields['video_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Video URL'))
            ->setDescription(t('URL of the video (YouTube, Vimeo, or direct link)'))
            ->setSettings(['max_length' => 512])
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 11])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Duración del video en segundos
        $fields['video_duration'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duration'))
            ->setDescription(t('Video duration in seconds'))
            ->setDisplayOptions('view', ['weight' => 12])
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 12])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ID de contenido H5P (para TYPE_H5P)
        $fields['h5p_content_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('H5P Content ID'))
            ->setDescription(t('ID of H5P interactive content'))
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 15])
            ->setDisplayConfigurable('form', TRUE);

        // ========== CAMPOS DE PROGRESO ==========

        // Duración estimada en minutos
        $fields['estimated_duration'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Estimated Duration'))
            ->setDescription(t('Estimated time to complete in minutes'))
            ->setDefaultValue(10)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 20])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Porcentaje mínimo de video a ver para completar
        $fields['completion_threshold'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Completion Threshold'))
            ->setDescription(t('Minimum percentage of video to watch (for video lessons)'))
            ->setDefaultValue(90)
            ->setSettings(['min' => 0, 'max' => 100])
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 21])
            ->setDisplayConfigurable('form', TRUE);

        // Publicado/Draft
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Published'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 30])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('When the lesson was created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('When the lesson was last updated'));

        // Tenant ID (multi-tenancy)
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant');

        return $fields;
    }

    /**
     * Obtiene el ID de video de YouTube desde la URL.
     */
    public function getYouTubeVideoId(): ?string
    {
        $url = $this->get('video_url')->value;
        if (empty($url)) {
            return NULL;
        }

        // Patrones de URL de YouTube
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return NULL;
    }

    /**
     * Indica si esta lección es de tipo video.
     */
    public function isVideoLesson(): bool
    {
        return $this->get('lesson_type')->value === self::TYPE_VIDEO;
    }

    /**
     * Indica si esta lección usa H5P interactivo.
     */
    public function isH5PLesson(): bool
    {
        return $this->get('lesson_type')->value === self::TYPE_H5P
            || ($this->isVideoLesson() && !empty($this->get('h5p_content_id')->value));
    }

    /**
     * Obtiene la duración formateada (MM:SS).
     */
    public function getFormattedDuration(): string
    {
        $seconds = (int) $this->get('video_duration')->value;
        if ($seconds <= 0) {
            return '--:--';
        }
        return sprintf('%02d:%02d', floor($seconds / 60), $seconds % 60);
    }

}
