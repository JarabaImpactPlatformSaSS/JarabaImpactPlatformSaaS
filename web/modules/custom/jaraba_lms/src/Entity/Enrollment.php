<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Enrollment entity.
 *
 * Registro de inscripción de un usuario a un curso.
 * Gestiona acceso, fechas, estado de la matrícula y progreso.
 *
 * @ContentEntityType(
 *   id = "lms_enrollment",
 *   label = @Translation("Enrollment"),
 *   label_collection = @Translation("Enrollments"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_lms\EnrollmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_lms\Form\EnrollmentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_lms\EnrollmentAccessControlHandler",
 *   },
 *   base_table = "lms_enrollment",
 *   admin_permission = "administer lms enrollments",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/enrollment/{lms_enrollment}",
 *     "add-form" = "/admin/content/enrollments/add",
 *     "edit-form" = "/admin/content/enrollment/{lms_enrollment}/edit",
 *     "delete-form" = "/admin/content/enrollment/{lms_enrollment}/delete",
 *     "collection" = "/admin/content/enrollments",
 *   },
 *   field_ui_base_route = "entity.lms_enrollment.settings",
 * )
 */
class Enrollment extends ContentEntityBase implements EnrollmentInterface
{

    use EntityChangedTrait;

    /**
     * Status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Enrollment type constants.
     */
    public const TYPE_FREE = 'free';
    public const TYPE_PAID = 'paid';
    public const TYPE_GRANT = 'grant';
    public const TYPE_SCHOLARSHIP = 'scholarship';
    public const TYPE_BULK = 'bulk';

    /**
     * {@inheritdoc}
     */
    public function getUserId(): int
    {
        return (int) $this->get('user_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getCourseId(): int
    {
        return (int) $this->get('course_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getCourse(): ?CourseInterface
    {
        return $this->get('course_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus(string $status): EnrollmentInterface
    {
        $this->set('status', $status);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompleted(): bool
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgressPercent(): float
    {
        return (float) ($this->get('progress_percent')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function setProgressPercent(float $percent): EnrollmentInterface
    {
        $this->set('progress_percent', min(100, max(0, $percent)));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnrollmentType(): string
    {
        return $this->get('enrollment_type')->value ?? self::TYPE_FREE;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnrolledAt(): int
    {
        return (int) $this->get('enrolled_at')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletedAt(): ?int
    {
        $value = $this->get('completed_at')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function markCompleted(): EnrollmentInterface
    {
        $this->set('status', self::STATUS_COMPLETED);
        $this->set('completed_at', \Drupal::time()->getRequestTime());
        $this->set('progress_percent', 100);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isCertificateIssued(): bool
    {
        return (bool) $this->get('certificate_issued')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setCertificateIssued(bool $issued, ?int $certificate_id = NULL): EnrollmentInterface
    {
        $this->set('certificate_issued', $issued);
        if ($certificate_id !== NULL) {
            $this->set('certificate_id', $certificate_id);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('Enrolled user.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ]);

        $fields['course_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Course'))
            ->setDescription(t('Enrolled course.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'lms_course')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ]);

        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('Tenant of the enrollment.'));

        $fields['enrollment_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Enrollment type'))
            ->setDescription(t('Type of enrollment.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_FREE => t('Free'),
                self::TYPE_PAID => t('Paid'),
                self::TYPE_GRANT => t('Grant'),
                self::TYPE_SCHOLARSHIP => t('Scholarship'),
                self::TYPE_BULK => t('Bulk import'),
            ])
            ->setDefaultValue(self::TYPE_FREE);

        $fields['payment_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Payment ID'))
            ->setDescription(t('Stripe payment intent ID.'))
            ->setSetting('max_length', 64);

        $fields['grant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Grant ID'))
            ->setDescription(t('Associated grant/subsidy.'));

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Status'))
            ->setDescription(t('Enrollment status.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_ACTIVE => t('Active'),
                self::STATUS_COMPLETED => t('Completed'),
                self::STATUS_EXPIRED => t('Expired'),
                self::STATUS_CANCELLED => t('Cancelled'),
                self::STATUS_SUSPENDED => t('Suspended'),
            ])
            ->setDefaultValue(self::STATUS_ACTIVE);

        $fields['enrolled_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Enrolled at'))
            ->setDescription(t('Enrollment timestamp.'))
            ->setRequired(TRUE)
            ->setDefaultValueCallback(static::class . '::getCurrentTime');

        $fields['started_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Started at'))
            ->setDescription(t('First access timestamp.'));

        $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Completed at'))
            ->setDescription(t('Completion timestamp.'));

        $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Expires at'))
            ->setDescription(t('Access expiration timestamp.'));

        $fields['progress_percent'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Progress'))
            ->setDescription(t('Calculated progress percentage.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['last_activity_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Last activity'))
            ->setDescription(t('Last activity timestamp.'));

        $fields['certificate_issued'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Certificate issued'))
            ->setDefaultValue(FALSE);

        $fields['certificate_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Certificate ID'))
            ->setDescription(t('ID of issued certificate/credential.'));

        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Source'))
            ->setDescription(t('Enrollment source.'))
            ->setSetting('allowed_values', [
                'organic' => t('Organic'),
                'diagnostic' => t('Diagnostic Express'),
                'campaign' => t('Campaign'),
                'import' => t('Import'),
                'api' => t('API'),
            ])
            ->setDefaultValue('organic');

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadata'))
            ->setDescription(t('Additional JSON metadata.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

    /**
     * Default value callback for enrolled_at.
     */
    public static function getCurrentTime(): array
    {
        return [\Drupal::time()->getRequestTime()];
    }

}
