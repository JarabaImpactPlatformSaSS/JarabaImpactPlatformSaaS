<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the EmployerProfile entity.
 *
 * Perfil de empresa/empleador para el Job Board. Almacena datos
 * de la empresa que publica ofertas de empleo.
 *
 * @ContentEntityType(
 *   id = "employer_profile",
 *   label = @Translation("Employer Profile"),
 *   label_collection = @Translation("Employer Profiles"),
 *   label_singular = @Translation("employer profile"),
 *   label_plural = @Translation("employer profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
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
 *   base_table = "employer_profile",
 *   admin_permission = "administer job board",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/employer/{employer_profile}",
 *     "add-form" = "/admin/content/employers/add",
 *     "edit-form" = "/admin/content/employer/{employer_profile}/edit",
 *     "delete-form" = "/admin/content/employer/{employer_profile}/delete",
 *     "collection" = "/admin/content/employers",
 *   },
 *   field_ui_base_route = "entity.employer_profile.settings",
 * )
 */
class EmployerProfile extends ContentEntityBase implements EmployerProfileInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Company size constants.
     */
    public const SIZE_STARTUP = 'startup';
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';
    public const SIZE_ENTERPRISE = 'enterprise';

    /**
     * {@inheritdoc}
     */
    public function getCompanyName(): string
    {
        return $this->get('company_name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?int
    {
        $value = $this->get('tenant_id')->target_id;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerified(): bool
    {
        return (bool) $this->get('is_verified')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatured(): bool
    {
        return (bool) $this->get('is_featured')->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // User reference (employer account).
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user account of the employer.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -15,
            ]);

        // Tenant reference.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('The tenant this employer belongs to.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -14,
            ]);

        // Company name.
        $fields['company_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Company Name'))
            ->setDescription(t('The commercial name of the company.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -13,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -13,
            ]);

        // Legal name.
        $fields['legal_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Legal Name'))
            ->setDescription(t('The registered legal name of the company.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -12,
            ]);

        // Tax ID.
        $fields['tax_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tax ID'))
            ->setDescription(t('CIF/NIF/VAT number.'))
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -11,
            ]);

        // Description.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Description'))
            ->setDescription(t('Company description and about.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -10,
            ])
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => -10,
            ]);

        // Website URL.
        $fields['website_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('Website'))
            ->setDescription(t('Company website URL.'))
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => -9,
            ]);

        // Logo (file reference).
        $fields['logo'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Logo'))
            ->setDescription(t('Company logo image.'))
            ->setSetting('target_type', 'file')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -8,
            ]);

        // Industry (taxonomy reference).
        $fields['industry'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Industry'))
            ->setDescription(t('The industry sector of the company.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler', 'default:taxonomy_term')
            ->setSetting('handler_settings', [
                'target_bundles' => ['industries' => 'industries'],
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -7,
            ]);

        // Company size.
        $fields['company_size'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Company Size'))
            ->setDescription(t('The size category of the company.'))
            ->setSetting('allowed_values', [
                self::SIZE_STARTUP => t('Startup (1-10)'),
                self::SIZE_SMALL => t('Small (11-50)'),
                self::SIZE_MEDIUM => t('Medium (51-250)'),
                self::SIZE_LARGE => t('Large (251-1000)'),
                self::SIZE_ENTERPRISE => t('Enterprise (1000+)'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -6,
            ]);

        // City.
        $fields['city'] = BaseFieldDefinition::create('string')
            ->setLabel(t('City'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ]);

        // Country (ISO 3166-1 alpha-2).
        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Country'))
            ->setDescription(t('ISO 3166-1 alpha-2 country code.'))
            ->setSetting('max_length', 2)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -4,
            ]);

        // Contact email.
        $fields['contact_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Contact Email'))
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -3,
            ]);

        // Contact phone.
        $fields['contact_phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Contact Phone'))
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -2,
            ]);

        // LinkedIn URL.
        $fields['linkedin_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('LinkedIn'))
            ->setDescription(t('Company LinkedIn page URL.'))
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => -1,
            ]);

        // Is verified.
        $fields['is_verified'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Verified'))
            ->setDescription(t('Whether this employer profile has been verified.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 0,
            ]);

        // Is featured.
        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Featured'))
            ->setDescription(t('Whether this employer is featured/highlighted.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 1,
            ]);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

}
