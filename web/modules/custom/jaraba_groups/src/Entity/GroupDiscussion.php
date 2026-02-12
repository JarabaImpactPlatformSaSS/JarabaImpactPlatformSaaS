<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Group Discussion.
 *
 * Hilo de discusión en el foro del grupo.
 *
 * SPEC: 34_Emprendimiento_Collaboration_Groups_v1
 *
 * @ContentEntityType(
 *   id = "group_discussion",
 *   label = @Translation("Discusión de Grupo"),
 *   label_collection = @Translation("Discusiones de Grupo"),
 *   label_singular = @Translation("discusión"),
 *   label_plural = @Translation("discusiones"),
 *   label_count = @PluralTranslation(
 *     singular = "@count discusión",
 *     plural = "@count discusiones",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_groups\GroupDiscussionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_groups\Form\GroupDiscussionForm",
 *       "add" = "Drupal\jaraba_groups\Form\GroupDiscussionForm",
 *       "edit" = "Drupal\jaraba_groups\Form\GroupDiscussionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_groups\Access\GroupDiscussionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "group_discussion",
 *   admin_permission = "administer collaboration groups",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "author_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/group-discussions",
 *     "add-form" = "/admin/content/group-discussions/add",
 *     "canonical" = "/admin/content/group-discussions/{group_discussion}",
 *     "edit-form" = "/admin/content/group-discussions/{group_discussion}/edit",
 *     "delete-form" = "/admin/content/group-discussions/{group_discussion}/delete",
 *   },
 *   field_ui_base_route = "entity.group_discussion.settings",
 * )
 */
class GroupDiscussion extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Discussion categories.
     */
    public const CATEGORY_QUESTION = 'question';
    public const CATEGORY_DISCUSSION = 'discussion';
    public const CATEGORY_ANNOUNCEMENT = 'announcement';
    public const CATEGORY_RESOURCE = 'resource';
    public const CATEGORY_FEEDBACK = 'feedback';

    /**
     * Gets the group ID.
     */
    public function getGroupId(): int
    {
        return (int) $this->get('group_id')->target_id;
    }

    /**
     * Gets the title.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Gets the body content.
     */
    public function getBody(): string
    {
        return $this->get('body')->value ?? '';
    }

    /**
     * Gets the category.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? self::CATEGORY_DISCUSSION;
    }

    /**
     * Checks if discussion is pinned.
     */
    public function isPinned(): bool
    {
        return (bool) $this->get('is_pinned')->value;
    }

    /**
     * Checks if discussion is locked.
     */
    public function isLocked(): bool
    {
        return (bool) $this->get('is_locked')->value;
    }

    /**
     * Gets the reply count.
     */
    public function getReplyCount(): int
    {
        return (int) $this->get('reply_count')->value;
    }

    /**
     * Increments reply count.
     */
    public function incrementReplyCount(): self
    {
        $this->set('reply_count', $this->getReplyCount() + 1);
        return $this;
    }

    /**
     * Gets the view count.
     */
    public function getViewCount(): int
    {
        return (int) $this->get('view_count')->value;
    }

    /**
     * Increments view count.
     */
    public function incrementViewCount(): self
    {
        $this->set('view_count', $this->getViewCount() + 1);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Grupo'))
            ->setDescription(t('Grupo donde se publica la discusión.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'collaboration_group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['author_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Autor'))
            ->setDescription(t('Autor del hilo.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDefaultValueCallback(static::class . '::getDefaultAuthorId')
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título del tema.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['body'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setDescription(t('Contenido inicial de la discusión.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Tipo de discusión.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::CATEGORY_QUESTION => t('Pregunta'),
                self::CATEGORY_DISCUSSION => t('Discusión'),
                self::CATEGORY_ANNOUNCEMENT => t('Anuncio'),
                self::CATEGORY_RESOURCE => t('Recurso'),
                self::CATEGORY_FEEDBACK => t('Feedback'),
            ])
            ->setDefaultValue(self::CATEGORY_DISCUSSION)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_pinned'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Fijado'))
            ->setDescription(t('Mostrar arriba del listado.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_locked'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Cerrado'))
            ->setDescription(t('No permite nuevas respuestas.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['reply_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Respuestas'))
            ->setDescription(t('Número de respuestas.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['view_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Visualizaciones'))
            ->setDescription(t('Número de veces visto.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['last_reply_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Última Respuesta'))
            ->setDescription(t('Fecha de la última respuesta.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['last_reply_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Última Respuesta Por'))
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'active' => t('Activo'),
                'hidden' => t('Oculto'),
                'archived' => t('Archivado'),
            ])
            ->setDefaultValue('active')
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Default value callback for author_id.
     */
    public static function getDefaultAuthorId(): array
    {
        return [\Drupal::currentUser()->id()];
    }

}
