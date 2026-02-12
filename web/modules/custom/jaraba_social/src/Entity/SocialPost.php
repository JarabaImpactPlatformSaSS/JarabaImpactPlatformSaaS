<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad SocialPost para publicaciones en redes sociales.
 *
 * PROPÓSITO:
 * Almacena publicaciones programadas o publicadas en redes sociales.
 * Soporta generación de contenido via IA y scheduling.
 *
 * ESTADOS:
 * - draft: Borrador
 * - scheduled: Programado para publicar
 * - published: Ya publicado
 * - failed: Error al publicar
 *
 * @ContentEntityType(
 *   id = "social_post",
 *   label = @Translation("Publicación Social"),
 *   label_collection = @Translation("Publicaciones Sociales"),
 *   label_singular = @Translation("publicación social"),
 *   label_plural = @Translation("publicaciones sociales"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "social_post",
 *   admin_permission = "administer social posts",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/social-posts/{social_post}",
 *     "add-form" = "/admin/content/social-posts/add",
 *     "edit-form" = "/admin/content/social-posts/{social_post}/edit",
 *     "delete-form" = "/admin/content/social-posts/{social_post}/delete",
 *     "collection" = "/admin/content/social-posts",
 *   },
 * )
 */
class SocialPost extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Estados del post.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título interno del post.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setDescription(t('Texto del post.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['accounts'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cuentas'))
            ->setDescription(t('Cuentas donde publicar.'))
            ->setSetting('target_type', 'social_account')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['media'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Multimedia'))
            ->setDescription(t('Imágenes o videos a incluir.'))
            ->setSetting('target_type', 'media')
            ->setCardinality(10)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado del post.'))
            ->setDefaultValue(self::STATUS_DRAFT)
            ->setSettings([
                'allowed_values' => [
                    self::STATUS_DRAFT => 'Borrador',
                    self::STATUS_SCHEDULED => 'Programado',
                    self::STATUS_PUBLISHED => 'Publicado',
                    self::STATUS_FAILED => 'Error',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Programada'))
            ->setDescription(t('Cuándo publicar el post.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['published_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha Publicación'))
            ->setDescription(t('Cuándo se publicó realmente.'));

        $fields['external_ids'] = BaseFieldDefinition::create('map')
            ->setLabel(t('IDs Externos'))
            ->setDescription(t('IDs del post en cada plataforma.'));

        $fields['metrics'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Métricas'))
            ->setDescription(t('Likes, shares, comments por plataforma.'));

        $fields['ai_generated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Generado por IA'))
            ->setDescription(t('Indica si el contenido fue generado por IA.'))
            ->setDefaultValue(FALSE);

        $fields['ai_prompt'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('AI Prompt'))
            ->setDescription(t('Prompt usado para generar el contenido.'));

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant propietario.'))
            ->setSetting('target_type', 'group');

        $fields['author'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Autor'))
            ->setSetting('target_type', 'user')
            ->setDefaultValueCallback(static::class . '::getCurrentUserId');

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Default value callback para author.
     */
    public static function getCurrentUserId(): array
    {
        return [\Drupal::currentUser()->id()];
    }

    /**
     * Getters.
     */
    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }

    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->getStatus() === self::STATUS_SCHEDULED;
    }

    public function isPublished(): bool
    {
        return $this->getStatus() === self::STATUS_PUBLISHED;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->get('scheduled_at')->date;
    }

    /**
     * Marcar como publicado.
     */
    public function markPublished(): self
    {
        $this->set('status', self::STATUS_PUBLISHED);
        $this->set('published_at', time());
        return $this;
    }

    /**
     * Marcar como fallido.
     */
    public function markFailed(): self
    {
        $this->set('status', self::STATUS_FAILED);
        return $this;
    }

}
