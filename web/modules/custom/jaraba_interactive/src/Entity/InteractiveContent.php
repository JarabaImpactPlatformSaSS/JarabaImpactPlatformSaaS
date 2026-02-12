<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad InteractiveContent.
 *
 * Almacena contenido interactivo AI-Powered: videos, quizzes, escenarios.
 * Cada registro contiene su estructura JSON y metadatos.
 *
 * @ContentEntityType(
 *   id = "interactive_content",
 *   label = @Translation("Contenido Interactivo"),
 *   label_collection = @Translation("Contenido Interactivo"),
 *   label_singular = @Translation("contenido interactivo"),
 *   label_plural = @Translation("contenidos interactivos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count contenido interactivo",
 *     plural = "@count contenidos interactivos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_interactive\InteractiveContentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jaraba_interactive\Form\InteractiveContentForm",
 *       "edit" = "Drupal\jaraba_interactive\Form\InteractiveContentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_interactive\InteractiveContentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "interactive_content",
 *   data_table = "interactive_content_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer interactive content",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/interactive-content/{interactive_content}",
 *     "add-form" = "/admin/content/interactive-content/add",
 *     "edit-form" = "/admin/content/interactive-content/{interactive_content}/edit",
 *     "delete-form" = "/admin/content/interactive-content/{interactive_content}/delete",
 *     "collection" = "/admin/content/interactive-content",
 *   },
 *   field_ui_base_route = "entity.interactive_content.settings",
 * )
 */
class InteractiveContent extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage): void
    {
        parent::preSave($storage);

        // Establecer el usuario actual como propietario si no hay uno.
        if (!$this->getOwner()) {
            $this->setOwnerId(\Drupal::currentUser()->id());
        }
    }

    /**
     * Obtiene el tipo de contenido (plugin ID).
     *
     * @return string
     *   El ID del plugin InteractiveType.
     */
    public function getContentType(): string
    {
        return $this->get('content_type')->value ?? 'question_set';
    }

    /**
     * Obtiene los datos del contenido como array.
     *
     * @return array
     *   Los datos JSON decodificados.
     */
    public function getContentData(): array
    {
        $value = $this->get('content_data')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Establece los datos del contenido.
     *
     * @param array $data
     *   Los datos a almacenar.
     *
     * @return $this
     */
    public function setContentData(array $data): self
    {
        $this->set('content_data', json_encode($data, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    /**
     * Obtiene los settings del contenido.
     *
     * @return array
     *   Los settings JSON decodificados.
     */
    public function getSettings(): array
    {
        $value = $this->get('settings')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Añadir trait fields (uid).
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('El título del contenido interactivo.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['content_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Contenido'))
            ->setDescription(t('El plugin que renderiza este contenido.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDefaultValue('question_set')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['content_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Datos del Contenido'))
            ->setDescription(t('Estructura JSON del contenido interactivo.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 0,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['settings'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración'))
            ->setDescription(t('Settings JSON del contenido (scoring, reintentos, etc).'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Organización'))
            ->setDescription(t('La organización (grupo) propietaria de este contenido.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('El estado de publicación del contenido.'))
            ->setDefaultValue('draft')
            ->setSetting('allowed_values', [
                'draft' => t('Borrador'),
                'published' => t('Publicado'),
                'archived' => t('Archivado'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['difficulty'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Dificultad'))
            ->setDescription(t('Nivel de dificultad del contenido.'))
            ->setDefaultValue('beginner')
            ->setSetting('allowed_values', [
                'beginner' => t('Principiante'),
                'intermediate' => t('Intermedio'),
                'advanced' => t('Avanzado'),
                'expert' => t('Experto'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 16,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['duration_minutes'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración (minutos)'))
            ->setDescription(t('Duración estimada para completar el contenido.'))
            ->setDefaultValue(10)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 17,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('La marca temporal de creación de la entidad.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('La marca temporal de la última modificación.'));

        // Configurar el campo owner.
        $fields['uid']
            ->setLabel(t('Autor'))
            ->setDescription(t('El usuario que creó este contenido.'))
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 20,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

}
