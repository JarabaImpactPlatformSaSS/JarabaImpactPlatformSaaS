<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the JobApplication entity.
 *
 * Registra una candidatura de un usuario a una oferta de empleo.
 * Implementa un mini-ATS con estados de pipeline.
 *
 * @ContentEntityType(
 *   id = "job_application",
 *   label = @Translation("Job Application"),
 *   label_collection = @Translation("Job Applications"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_job_board\JobApplicationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_job_board\Form\JobApplicationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_job_board\JobApplicationAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "job_application",
 *   admin_permission = "administer job applications",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/application/{job_application}",
 *     "collection" = "/admin/content/applications",
 *   },
 *   field_ui_base_route = "entity.job_application.settings",
 * )
 */
class JobApplication extends ContentEntityBase implements JobApplicationInterface
{

    use EntityChangedTrait;

    /**
     * Status constants (ATS pipeline).
     */
    public const STATUS_APPLIED = 'applied';
    public const STATUS_SCREENING = 'screening';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_INTERVIEWED = 'interviewed';
    public const STATUS_OFFERED = 'offered';
    public const STATUS_HIRED = 'hired';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * {@inheritdoc}
     */
    public function getJobId(): int
    {
        return (int) $this->get('job_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getJob(): ?JobPostingInterface
    {
        return $this->get('job_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getCandidateId(): int
    {
        return (int) $this->get('candidate_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_APPLIED;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus(string $status): JobApplicationInterface
    {
        $this->set('status', $status);
        $this->set('last_status_change', \Drupal::time()->getRequestTime());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return !in_array($this->getStatus(), [
            self::STATUS_HIRED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ], TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function isHired(): bool
    {
        return $this->getStatus() === self::STATUS_HIRED;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchScore(): ?float
    {
        $value = $this->get('match_score')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function setMatchScore(float $score): JobApplicationInterface
    {
        $this->set('match_score', $score);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoverLetter(): ?string
    {
        return $this->get('cover_letter')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getAppliedAt(): int
    {
        return (int) $this->get('applied_at')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsViewed(): JobApplicationInterface
    {
        if (!$this->get('viewed_by_employer')->value) {
            $this->set('viewed_by_employer', TRUE);
            $this->set('viewed_at', \Drupal::time()->getRequestTime());
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hire(?float $salary = NULL): JobApplicationInterface
    {
        $this->setStatus(self::STATUS_HIRED);
        $this->set('hired_at', \Drupal::time()->getRequestTime());
        if ($salary !== NULL) {
            $this->set('offered_salary', $salary);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reject(?string $reason = NULL, ?string $feedback = NULL): JobApplicationInterface
    {
        $this->setStatus(self::STATUS_REJECTED);
        if ($reason !== NULL) {
            $this->set('rejection_reason', $reason);
        }
        if ($feedback !== NULL) {
            $this->set('rejection_feedback', $feedback);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Referencias ===
        $fields['job_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Job'))
            ->setDescription(t('The job posting.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'job_posting');

        $fields['candidate_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Candidate'))
            ->setDescription(t('The candidate user.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user');

        $fields['candidate_profile_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Candidate Profile ID'))
            ->setDescription(t('Reference to candidate profile.'));

        // === Estado del Pipeline ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Status'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_APPLIED => t('Applied'),
                self::STATUS_SCREENING => t('Screening'),
                self::STATUS_SHORTLISTED => t('Shortlisted'),
                self::STATUS_INTERVIEWED => t('Interviewed'),
                self::STATUS_OFFERED => t('Offer extended'),
                self::STATUS_HIRED => t('Hired'),
                self::STATUS_REJECTED => t('Rejected'),
                self::STATUS_WITHDRAWN => t('Withdrawn'),
            ])
            ->setDefaultValue(self::STATUS_APPLIED);

        // === Documentos de Aplicación ===
        $fields['cover_letter'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Cover Letter'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ]);

        $fields['cv_file_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('CV File'))
            ->setSetting('target_type', 'file');

        $fields['cv_version'] = BaseFieldDefinition::create('string')
            ->setLabel(t('CV Version'))
            ->setDescription(t('Snapshot of profile at application time.'))
            ->setSetting('max_length', 32);

        $fields['portfolio_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Portfolio URL'))
            ->setSetting('max_length', 512);

        $fields['answers'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Screening Answers'))
            ->setDescription(t('JSON answers to screening questions.'));

        // === Matching ===
        $fields['match_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Match Score'))
            ->setDescription(t('Calculated matching score 0-100.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2);

        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Source'))
            ->setSetting('allowed_values', [
                'organic' => t('Organic search'),
                'recommended' => t('Recommended'),
                'alert' => t('Job alert'),
                'import' => t('Import'),
                'api' => t('API'),
            ])
            ->setDefaultValue('organic');

        $fields['referral_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Referral Code'))
            ->setSetting('max_length', 64);

        // === Feedback del Empleador ===
        $fields['employer_notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Employer Notes'))
            ->setDescription(t('Private internal notes.'));

        $fields['employer_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Employer Rating'))
            ->setDescription(t('Rating 1-5.'))
            ->setSetting('min', 1)
            ->setSetting('max', 5);

        $fields['rejection_reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Rejection Reason'))
            ->setSetting('max_length', 64);

        $fields['rejection_feedback'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Rejection Feedback'))
            ->setDescription(t('Feedback sent to candidate.'));

        // === Entrevista y Oferta ===
        $fields['interview_scheduled_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Interview Scheduled'));

        $fields['offered_salary'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Offered Salary'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2);

        $fields['offer_expires_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Offer Expires'));

        $fields['hired_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Hired At'));

        // === Tracking ===
        $fields['applied_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Applied At'))
            ->setRequired(TRUE)
            ->setDefaultValueCallback(static::class . '::getCurrentTime');

        $fields['last_status_change'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Last Status Change'))
            ->setDefaultValueCallback(static::class . '::getCurrentTime');

        $fields['viewed_by_employer'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Viewed by Employer'))
            ->setDefaultValue(FALSE);

        $fields['viewed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Viewed At'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

    /**
     * Default value callback for timestamps.
     */
    public static function getCurrentTime(): array
    {
        return [\Drupal::time()->getRequestTime()];
    }

}
