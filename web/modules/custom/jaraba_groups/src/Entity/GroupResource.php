<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Group Resource.
 *
 * Recurso compartido en la biblioteca del grupo.
 *
 * @ContentEntityType(
 *   id = "group_resource",
 *   label = @Translation("Recurso de Grupo"),
 *   label_collection = @Translation("Recursos de Grupo"),
 *   label_singular = @Translation("recurso"),
 *   label_plural = @Translation("recursos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count recurso",
 *     plural = "@count recursos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_groups\GroupResourceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_groups\Form\GroupResourceForm",
 *       "add" = "Drupal\jaraba_groups\Form\GroupResourceForm",
 *       "edit" = "Drupal\jaraba_groups\Form\GroupResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_groups\Access\GroupResourceAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "group_resource",
 *   admin_permission = "administer collaboration groups",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uploader_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/group-resources",
 *     "add-form" = "/admin/content/group-resources/add",
 *     "canonical" = "/admin/content/group-resources/{group_resource}",
 *     "edit-form" = "/admin/content/group-resources/{group_resource}/edit",
 *     "delete-form" = "/admin/content/group-resources/{group_resource}/delete",
 *   },
 *   field_ui_base_route = "entity.group_resource.settings",
 * )
 */
class GroupResource extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Resource types.
     */
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_PRESENTATION = 'presentation';
    public const TYPE_SPREADSHEET = 'spreadsheet';
    public const TYPE_VIDEO = 'video';
    public const TYPE_LINK = 'link';

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
     * Gets the resource type.
     */
    public function getResourceType(): string
    {
        return $this->get('resource_type')->value ?? self::TYPE_DOCUMENT;
    }

    /**
     * Gets the download count.
     */
    public function getDownloadCount(): int
    {
        return (int) $this->get('download_count')->value;
    }

    /**
     * Increments download count.
     */
    public function incrementDownloadCount(): self
    {
        $this->set('download_count', $this->getDownloadCount() + 1);
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
            ->setDescription(t('Grupo donde se comparte el recurso.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'collaboration_group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['uploader_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Subido por'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDefaultValueCallback(static::class . '::getDefaultUploaderId')
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['resource_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Recurso'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_DOCUMENT => t('Documento'),
                self::TYPE_TEMPLATE => t('Plantilla'),
                self::TYPE_PRESENTATION => t('Presentación'),
                self::TYPE_SPREADSHEET => t('Hoja de cálculo'),
                self::TYPE_VIDEO => t('Vídeo'),
                self::TYPE_LINK => t('Enlace externo'),
            ])
            ->setDefaultValue(self::TYPE_DOCUMENT)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['file'] = BaseFieldDefinition::create('file')
            ->setLabel(t('Archivo'))
            ->setDescription(t('Archivo a compartir.'))
            ->setSetting('file_extensions', 'pdf doc docx xls xlsx ppt pptx zip txt csv jpg png gif mp4 webm')
            ->setSetting('max_filesize', '50 MB')
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['external_url'] = BaseFieldDefinition::create('link')
            ->setLabel(t('URL Externa'))
            ->setDescription(t('Para recursos tipo enlace.'))
            ->setDisplayOptions('form', [
                'type' => 'link_default',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['tags'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Etiquetas'))
            ->setDescription(t('Etiquetas separadas por comas.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['download_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Descargas'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_pinned'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Fijado'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'active' => t('Activo'),
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
     * Default value callback for uploader_id.
     */
    public static function getDefaultUploaderId(): array
    {
        return [\Drupal::currentUser()->id()];
    }

}
