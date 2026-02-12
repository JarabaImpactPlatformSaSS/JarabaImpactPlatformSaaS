<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the JobPosting entity.
 *
 * Representa una oferta de empleo publicada por un empleador.
 * Incluye campos para búsqueda facetada y SEO/GEO.
 *
 * @ContentEntityType(
 *   id = "job_posting",
 *   label = @Translation("Job Posting"),
 *   label_collection = @Translation("Job Postings"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_job_board\JobPostingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_job_board\Form\JobPostingForm",
 *       "add" = "Drupal\jaraba_job_board\Form\JobPostingForm",
 *       "edit" = "Drupal\jaraba_job_board\Form\JobPostingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_job_board\JobPostingAccessControlHandler",
 *   },
 *   base_table = "job_posting",
 *   admin_permission = "administer job postings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "employer_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/job/{job_posting}",
 *     "add-form" = "/admin/content/jobs/add",
 *     "edit-form" = "/admin/content/job/{job_posting}/edit",
 *     "delete-form" = "/admin/content/job/{job_posting}/delete",
 *     "collection" = "/admin/content/jobs",
 *   },
 *   field_ui_base_route = "entity.job_posting.settings",
 * )
 */
class JobPosting extends ContentEntityBase implements JobPostingInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Status constants.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_FILLED = 'filled';

    /**
     * Job type constants.
     */
    public const TYPE_FULL_TIME = 'full_time';
    public const TYPE_PART_TIME = 'part_time';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INTERNSHIP = 'internship';
    public const TYPE_FREELANCE = 'freelance';

    /**
     * Remote type constants.
     */
    public const REMOTE_ONSITE = 'onsite';
    public const REMOTE_HYBRID = 'hybrid';
    public const REMOTE_FULL = 'remote';
    public const REMOTE_FLEXIBLE = 'flexible';

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceCode(): string
    {
        return $this->get('reference_code')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_DRAFT;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus(string $status): JobPostingInterface
    {
        $this->set('status', $status);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublished(): bool
    {
        return $this->getStatus() === self::STATUS_PUBLISHED;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(): JobPostingInterface
    {
        $this->set('status', self::STATUS_PUBLISHED);
        $this->set('published_at', \Drupal::time()->getRequestTime());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): JobPostingInterface
    {
        $this->set('status', self::STATUS_CLOSED);
        $this->set('closed_at', \Drupal::time()->getRequestTime());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmployerId(): int
    {
        return (int) $this->get('employer_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocationCity(): string
    {
        return $this->get('location_city')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getJobType(): string
    {
        return $this->get('job_type')->value ?? self::TYPE_FULL_TIME;
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoteType(): string
    {
        return $this->get('remote_type')->value ?? self::REMOTE_ONSITE;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalaryRange(): array
    {
        return [
            'min' => $this->get('salary_min')->value,
            'max' => $this->get('salary_max')->value,
            'currency' => $this->get('salary_currency')->value ?? 'EUR',
            'period' => $this->get('salary_period')->value ?? 'yearly',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSkillsRequired(): array
    {
        $json = $this->get('skills_required')->value;
        return $json ? json_decode($json, TRUE) : [];
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
    public function getApplicationsCount(): int
    {
        return (int) ($this->get('applications_count')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementApplicationsCount(): JobPostingInterface
    {
        $current = $this->getApplicationsCount();
        $this->set('applications_count', $current + 1);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === Campos de Identificación ===
        $fields['reference_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Reference Code'))
            ->setDescription(t('Visible job reference code.'))
            ->setSetting('max_length', 32)
            ->addConstraint('UniqueField');

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('Job title.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Slug'))
            ->setDescription(t('URL-friendly identifier.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->addConstraint('UniqueField');

        // === Campos de Empresa ===
        $fields['employer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Employer'))
            ->setDescription(t('Employer posting this job.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ]);

        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('Associated tenant.'));

        // === Descripción del Puesto ===
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Description'))
            ->setDescription(t('Full job description.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
            ]);

        $fields['requirements'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Requirements'))
            ->setDescription(t('Job requirements.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -7,
            ]);

        $fields['responsibilities'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Responsibilities'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -6,
            ]);

        $fields['benefits'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Benefits'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -5,
            ]);

        // === Tipo de Trabajo ===
        $fields['job_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Job Type'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_FULL_TIME => t('Full-time'),
                self::TYPE_PART_TIME => t('Part-time'),
                self::TYPE_CONTRACT => t('Contract'),
                self::TYPE_INTERNSHIP => t('Internship'),
                self::TYPE_FREELANCE => t('Freelance'),
            ])
            ->setDefaultValue(self::TYPE_FULL_TIME)
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -4]);

        $fields['experience_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Experience Level'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'entry' => t('Entry level'),
                'junior' => t('Junior (1-2 years)'),
                'mid' => t('Mid-level (3-5 years)'),
                'senior' => t('Senior (5+ years)'),
                'executive' => t('Executive'),
            ])
            ->setDefaultValue('mid')
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -3]);

        $fields['education_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Education Level'))
            ->setSetting('allowed_values', [
                'none' => t('Not required'),
                'secondary' => t('Secondary education'),
                'vocational' => t('Vocational training'),
                'bachelor' => t("Bachelor's degree"),
                'master' => t("Master's degree"),
                'phd' => t('PhD'),
            ])
            ->setDefaultValue('none');

        $fields['remote_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Remote Type'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::REMOTE_ONSITE => t('On-site'),
                self::REMOTE_HYBRID => t('Hybrid'),
                self::REMOTE_FULL => t('Full remote'),
                self::REMOTE_FLEXIBLE => t('Flexible'),
            ])
            ->setDefaultValue(self::REMOTE_ONSITE)
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -2]);

        // === Ubicación ===
        $fields['location_city'] = BaseFieldDefinition::create('string')
            ->setLabel(t('City'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -1]);

        $fields['location_province'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Province'))
            ->setSetting('max_length', 64);

        $fields['location_country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Country'))
            ->setSetting('max_length', 2)
            ->setDefaultValue('ES');

        $fields['location_lat'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Latitude'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 8);

        $fields['location_lng'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Longitude'))
            ->setSetting('precision', 11)
            ->setSetting('scale', 8);

        // === Salario ===
        $fields['salary_min'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Minimum Salary'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 0]);

        $fields['salary_max'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Maximum Salary'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => 1]);

        $fields['salary_currency'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Currency'))
            ->setSetting('max_length', 3)
            ->setDefaultValue('EUR');

        $fields['salary_period'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Salary Period'))
            ->setSetting('allowed_values', [
                'hourly' => t('Per hour'),
                'monthly' => t('Per month'),
                'yearly' => t('Per year'),
            ])
            ->setDefaultValue('yearly');

        $fields['salary_visible'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Show Salary'))
            ->setDefaultValue(TRUE);

        // === Skills y Categorías ===
        $fields['skills_required'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Required Skills'))
            ->setDescription(t('JSON array of skill taxonomy IDs.'));

        $fields['skills_preferred'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Preferred Skills'))
            ->setDescription(t('JSON array of preferred skill IDs.'));

        $fields['languages'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Languages'))
            ->setDescription(t('JSON array of required languages.'));

        $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Category'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['job_category']]);

        // === Estado y Visibilidad ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Status'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_DRAFT => t('Draft'),
                self::STATUS_PENDING => t('Pending review'),
                self::STATUS_PUBLISHED => t('Published'),
                self::STATUS_PAUSED => t('Paused'),
                self::STATUS_CLOSED => t('Closed'),
                self::STATUS_FILLED => t('Filled'),
            ])
            ->setDefaultValue(self::STATUS_DRAFT);

        $fields['visibility'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Visibility'))
            ->setSetting('allowed_values', [
                'public' => t('Public'),
                'ecosystem' => t('Ecosystem only'),
                'private' => t('Private/Invite'),
            ])
            ->setDefaultValue('public');

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Featured'))
            ->setDefaultValue(FALSE);

        $fields['featured_until'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Featured Until'));

        // === Método de Aplicación ===
        $fields['application_method'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Application Method'))
            ->setSetting('allowed_values', [
                'internal' => t('Internal (via platform)'),
                'external' => t('External URL'),
                'email' => t('Email'),
            ])
            ->setDefaultValue('internal');

        $fields['external_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('External URL'))
            ->setSetting('max_length', 512);

        $fields['application_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Application Email'));

        // === Métricas ===
        $fields['vacancies'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Vacancies'))
            ->setDefaultValue(1)
            ->setSetting('min', 1);

        $fields['applications_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Applications Count'))
            ->setDefaultValue(0);

        $fields['views_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Views Count'))
            ->setDefaultValue(0);

        // === Fechas ===
        $fields['published_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Published At'));

        $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Expires At'));

        $fields['closed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Closed At'));

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function preSave($storage): void
    {
        parent::preSave($storage);

        // Generate reference code if new
        if ($this->isNew() && empty($this->get('reference_code')->value)) {
            $year = date('Y');
            $seq = \Drupal::database()->query('SELECT MAX(id) FROM {job_posting}')->fetchField() ?? 0;
            $this->set('reference_code', sprintf('JOB-%s-%05d', $year, $seq + 1));
        }

        // Generate slug from title if empty
        if (empty($this->get('slug')->value)) {
            $slug = \Drupal::transliteration()->transliterate($this->getTitle());
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($slug));
            $slug = trim($slug, '-');
            $this->set('slug', $slug . '-' . $this->id());
        }
    }

}
