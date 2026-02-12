<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Digital Kit.
 *
 * Kit de herramientas digitales para emprendedores con plantillas,
 * guías y recursos descargables.
 *
 * @ContentEntityType(
 *   id = "digital_kit",
 *   label = @Translation("Kit Digital"),
 *   label_collection = @Translation("Kits Digitales"),
 *   label_singular = @Translation("kit digital"),
 *   label_plural = @Translation("kits digitales"),
 *   label_count = @PluralTranslation(
 *     singular = "@count kit",
 *     plural = "@count kits",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_resources\DigitalKitListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_resources\Form\DigitalKitForm",
 *       "add" = "Drupal\jaraba_resources\Form\DigitalKitForm",
 *       "edit" = "Drupal\jaraba_resources\Form\DigitalKitForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_resources\Access\DigitalKitAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "digital_kit",
 *   admin_permission = "administer digital kits",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/digital-kits",
 *     "add-form" = "/admin/content/digital-kits/add",
 *     "canonical" = "/admin/content/digital-kits/{digital_kit}",
 *     "edit-form" = "/admin/content/digital-kits/{digital_kit}/edit",
 *     "delete-form" = "/admin/content/digital-kits/{digital_kit}/delete",
 *   },
 *   field_ui_base_route = "entity.digital_kit.settings",
 * )
 */
class DigitalKit extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Kit categories.
     */
    public const CATEGORY_BUSINESS_PLAN = 'business_plan';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_FINANCE = 'finance';
    public const CATEGORY_LEGAL = 'legal';
    public const CATEGORY_OPERATIONS = 'operations';
    public const CATEGORY_DIGITAL = 'digital';
    public const CATEGORY_HR = 'hr';

    /**
     * Access levels.
     */
    public const ACCESS_FREE = 'free';
    public const ACCESS_STARTER = 'starter';
    public const ACCESS_PROFESSIONAL = 'professional';
    public const ACCESS_ENTERPRISE = 'enterprise';

    /**
     * Gets the kit name.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Gets the category.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? self::CATEGORY_DIGITAL;
    }

    /**
     * Gets the access level required.
     */
    public function getAccessLevel(): string
    {
        return $this->get('access_level')->value ?? self::ACCESS_FREE;
    }

    /**
     * Checks if a user can access this kit.
     */
    public function canAccess(?int $userId = NULL): bool
    {
        $requiredLevel = $this->getAccessLevel();

        // Free kits are always accessible.
        if ($requiredLevel === self::ACCESS_FREE) {
            return TRUE;
        }

        // Check user's subscription level.
        // This would integrate with the subscription service.
        return FALSE;
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
     * Gets the rating.
     */
    public function getRating(): float
    {
        return (float) $this->get('rating')->value;
    }

    /**
     * Gets applicable sectors.
     */
    public function getSectors(): array
    {
        $value = $this->get('sectors')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Kit'))
            ->setDescription(t('Nombre descriptivo del kit digital.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del contenido del kit.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Área temática del kit.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::CATEGORY_BUSINESS_PLAN => t('Plan de Negocio'),
                self::CATEGORY_MARKETING => t('Marketing y Ventas'),
                self::CATEGORY_FINANCE => t('Finanzas'),
                self::CATEGORY_LEGAL => t('Legal y Administrativo'),
                self::CATEGORY_OPERATIONS => t('Operaciones'),
                self::CATEGORY_DIGITAL => t('Transformación Digital'),
                self::CATEGORY_HR => t('Recursos Humanos'),
            ])
            ->setDefaultValue(self::CATEGORY_DIGITAL)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen de Portada'))
            ->setDescription(t('Imagen representativa del kit.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp')
            ->setSetting('alt_field', TRUE)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['files'] = BaseFieldDefinition::create('file')
            ->setLabel(t('Archivos del Kit'))
            ->setDescription(t('Archivos descargables incluidos en el kit.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('file_extensions', 'pdf doc docx xls xlsx ppt pptx zip')
            ->setSetting('max_filesize', '50 MB')
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['access_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de Acceso'))
            ->setDescription(t('Plan mínimo requerido para acceder.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::ACCESS_FREE => t('Gratuito'),
                self::ACCESS_STARTER => t('Plan Starter'),
                self::ACCESS_PROFESSIONAL => t('Plan Professional'),
                self::ACCESS_ENTERPRISE => t('Plan Enterprise'),
            ])
            ->setDefaultValue(self::ACCESS_FREE)
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sectors'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Sectores Aplicables'))
            ->setDescription(t('JSON con sectores donde aplica el kit.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['tags'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Etiquetas'))
            ->setDescription(t('Etiquetas para búsqueda.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['download_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Descargas'))
            ->setDescription(t('Número total de descargas.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['rating'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Valoración'))
            ->setDescription(t('Valoración media de usuarios (1-5).'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['rating_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Valoraciones'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Mostrar en sección destacada.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_new'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Nuevo'))
            ->setDescription(t('Marcar como nuevo (primeros 30 días).'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'draft' => t('Borrador'),
                'published' => t('Publicado'),
                'archived' => t('Archivado'),
            ])
            ->setDefaultValue('draft')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
