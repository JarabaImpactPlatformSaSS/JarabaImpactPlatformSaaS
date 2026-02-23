<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the CandidateProfile entity.
 *
 * Perfil profesional completo del candidato con datos para matching,
 * CV Builder y JobSeeker Dashboard.
 *
 * @ContentEntityType(
 *   id = "candidate_profile",
 *   label = @Translation("Candidate Profile"),
 *   label_collection = @Translation("Candidate Profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_candidate\CandidateProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_candidate\Form\CandidateProfileForm",
 *       "add" = "Drupal\jaraba_candidate\Form\CandidateProfileForm",
 *       "edit" = "Drupal\jaraba_candidate\Form\CandidateProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_candidate\CandidateProfileAccessControlHandler",
 *   },
 *   base_table = "candidate_profile",
 *   admin_permission = "administer candidate profiles",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/candidate/{candidate_profile}",
 *     "add-form" = "/admin/content/candidates/add",
 *     "edit-form" = "/admin/content/candidate/{candidate_profile}/edit",
 *     "delete-form" = "/admin/content/candidate/{candidate_profile}/delete",
 *     "collection" = "/admin/content/candidates",
 *   },
 *   field_ui_base_route = "entity.candidate_profile.settings",
 * )
 */
class CandidateProfile extends ContentEntityBase implements CandidateProfileInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Availability status constants.
     */
    public const AVAILABILITY_ACTIVE = 'active';
    public const AVAILABILITY_PASSIVE = 'passive';
    public const AVAILABILITY_NOT_LOOKING = 'not_looking';
    public const AVAILABILITY_EMPLOYED = 'employed';

    /**
     * {@inheritdoc}
     */
    public function getFullName(): string
    {
        return trim($this->get('first_name')->value . ' ' . $this->get('last_name')->value);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeadline(): string
    {
        return $this->get('headline')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSummary(): string
    {
        $value = $this->get('summary')->value ?? '';
        $format = $this->get('summary')->format ?? 'basic_html';
        return (string) check_markup($value, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailability(): string
    {
        return $this->get('availability_status')->value ?? self::AVAILABILITY_PASSIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function isActivelyLooking(): bool
    {
        return $this->getAvailability() === self::AVAILABILITY_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletionPercent(): int
    {
        return (int) ($this->get('completion_percent')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function setCompletionPercent(int $percent): CandidateProfileInterface
    {
        $this->set('completion_percent', min(100, max(0, $percent)));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExperienceYears(): int
    {
        return (int) ($this->get('experience_years')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getEducationLevel(): string
    {
        return $this->get('education_level')->value ?? 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function getCity(): string
    {
        return $this->get('city')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getPreferredJobTypes(): array
    {
        $json = $this->get('preferred_job_types')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPreferredRemoteTypes(): array
    {
        $json = $this->get('preferred_remote_types')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSalaryExpectation(): ?float
    {
        $value = $this->get('salary_expectation')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublic(): bool
    {
        return (bool) $this->get('is_public')->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === Identificación ===
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('Associated Drupal user.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->addConstraint('UniqueField');

        // AUDIT-CONS-005: tenant_id como entity_reference al entity type 'tenant'.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant');

        // === Datos Personales ===
        $fields['first_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('First Name'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -10]);

        $fields['last_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Last Name'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -9]);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'email_default', 'weight' => -8]);

        $fields['phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Phone'))
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7]);

        $fields['photo'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Photo'))
            ->setSetting('target_type', 'file')
            ->setDisplayOptions('form', ['type' => 'file_generic', 'weight' => -6]);

        $fields['date_of_birth'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Date of Birth'))
            ->setSetting('datetime_type', 'date');

        $fields['nationality'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nationality'))
            ->setSetting('max_length', 2);

        // === Perfil Profesional ===
        $fields['headline'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Professional Headline'))
            ->setDescription(t('Short tagline (e.g., "Senior Frontend Developer").'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -5]);

        $fields['summary'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Professional Summary'))
            ->setDescription(t('Brief career summary (300-500 characters).'))
            ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => -4]);

        $fields['experience_years'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Years of Experience'))
            ->setSetting('min', 0)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => -3]);

        $fields['experience_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Experience Level'))
            ->setSetting('allowed_values', [
                'entry' => t('Entry level'),
                'junior' => t('Junior'),
                'mid' => t('Mid-level'),
                'senior' => t('Senior'),
                'executive' => t('Executive'),
            ])
            ->setDefaultValue('mid');

        $fields['education_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Highest Education'))
            ->setSetting('allowed_values', [
                'secondary' => t('Secondary'),
                'vocational' => t('Vocational'),
                'bachelor' => t('Bachelor'),
                'master' => t('Master'),
                'phd' => t('PhD'),
            ])
            ->setDefaultValue('bachelor');

        // === Ubicación ===
        $fields['city'] = BaseFieldDefinition::create('string')
            ->setLabel(t('City'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -2]);

        $fields['province'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Province'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -1]);

        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Country'))
            ->setDescription(t('Country code (ISO 3166-1 alpha-2).'))
            ->setSetting('max_length', 2)
            ->setDefaultValue('ES')
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0]);

        $fields['postal_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Postal Code'))
            ->setSetting('max_length', 10)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1]);

        $fields['willing_to_relocate'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Willing to Relocate'))
            ->setDescription(t('Check if you are open to relocating for a job opportunity.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 2]);

        $fields['relocation_countries'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Relocation Countries'))
            ->setDescription(t('JSON array of country codes.'));

        // === Geolocalización ===
        $fields['location'] = BaseFieldDefinition::create('geofield')
            ->setLabel(t('Geographic Location'))
            ->setDescription(t('Geocoded coordinates for map display. Auto-populated from city/country.'))
            ->setDisplayOptions('view', ['type' => 'geofield_latlon', 'weight' => 0]);

        // === Preferencias de Empleo ===
        $fields['availability_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Availability'))
            ->setSetting('allowed_values', [
                self::AVAILABILITY_ACTIVE => t('Actively looking'),
                self::AVAILABILITY_PASSIVE => t('Open to opportunities'),
                self::AVAILABILITY_NOT_LOOKING => t('Not looking'),
                self::AVAILABILITY_EMPLOYED => t('Employed, not interested'),
            ])
            ->setDefaultValue(self::AVAILABILITY_PASSIVE)
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -1]);

        $fields['available_from'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Available From'))
            ->setSetting('datetime_type', 'date');

        $fields['preferred_job_types'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Preferred Job Types'))
            ->setDescription(t('JSON array: full_time, part_time, contract, etc.'));

        $fields['preferred_remote_types'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Preferred Remote Types'))
            ->setDescription(t('JSON array: onsite, hybrid, remote.'));

        $fields['preferred_industries'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Preferred Industries'))
            ->setDescription(t('JSON array of industry codes.'));

        $fields['salary_expectation'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Salary Expectation'))
            ->setDescription(t('Expected annual salary in EUR.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 0])
            ->setDisplayOptions('view', ['type' => 'number_decimal']);


        // === Links Externos ===
        $fields['linkedin_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('LinkedIn URL'))
            ->setDescription(t('Your LinkedIn profile URL'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 0]);

        $fields['github_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('GitHub URL'))
            ->setDescription(t('Your GitHub profile URL'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1]);

        $fields['portfolio_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Portfolio URL'))
            ->setDescription(t('Link to your online portfolio'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 2]);

        $fields['website_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Personal Website'))
            ->setDescription(t('Your personal website or blog'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 3]);

        // === CV y Documentos ===
        $fields['cv_file_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('CV File'))
            ->setSetting('target_type', 'file');

        $fields['cv_last_updated'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('CV Last Updated'));

        $fields['cv_generated_html'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Generated CV HTML'))
            ->setDescription(t('Cached HTML of generated CV.'));

        // === Privacidad y Estado ===
        $fields['is_public'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Public Profile'))
            ->setDescription(t('Make your profile visible to employers and recruiters.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 0]);

        $fields['show_photo'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Show Photo'))
            ->setDescription(t('Display your profile photo publicly.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 1]);

        $fields['show_contact'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Show Contact Info'))
            ->setDescription(t('Allow employers to see your contact information.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 2]);

        $fields['is_verified'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Verified'))
            ->setDefaultValue(FALSE);

        $fields['completion_percent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Completion %'))
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 100);

        // === Diagnóstico Express ===
        $fields['diagnostic_profile'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Diagnostic Profile'))
            ->setDescription(t('Profile from Diagnostic Express.'))
            ->setSetting('max_length', 32);

        $fields['diagnostic_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Diagnostic Score'));

        $fields['diagnostic_gaps'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Diagnostic Gaps'))
            ->setDescription(t('JSON array of identified gaps.'));

        // === Matching ===
        $fields['embedding_vector_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Qdrant Vector ID'))
            ->setDescription(t('Vector ID in Qdrant for semantic matching.'))
            ->setSetting('max_length', 64);

        $fields['embedding_updated'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Embedding Updated'));

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        $fields['last_active'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Last Active'));

        return $fields;
    }

}
