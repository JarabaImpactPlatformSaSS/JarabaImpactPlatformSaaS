<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the CandidateExperience entity.
 *
 * Almacena experiencias laborales del candidato.
 *
 * @ContentEntityType(
 *   id = "candidate_experience",
 *   label = @Translation("Candidate Experience"),
 *   label_collection = @Translation("Candidate Experiences"),
 *   label_singular = @Translation("candidate experience"),
 *   label_plural = @Translation("candidate experiences"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "candidate_experience",
 *   admin_permission = "administer candidate experiences",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 * )
 */
class CandidateExperience extends ContentEntityBase implements CandidateExperienceInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

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
    public function getJobTitle(): string
    {
        return $this->get('job_title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getStartDate(): ?int
    {
        $value = $this->get('start_date')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndDate(): ?int
    {
        $value = $this->get('end_date')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(): bool
    {
        return (bool) $this->get('is_current')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLocation(): string
    {
        return $this->get('location')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The candidate who has this experience.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user');

        $fields['profile_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Profile ID'))
            ->setDescription(t('The candidate profile ID.'));

        $fields['company_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Company Name'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255);

        $fields['job_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Job Title'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Description'))
            ->setDescription(t('Description of responsibilities and achievements.'));

        $fields['location'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Location'))
            ->setSetting('max_length', 255);

        $fields['start_date'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Start Date'))
            ->setRequired(TRUE);

        $fields['end_date'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('End Date'))
            ->setDescription(t('NULL if current position.'));

        $fields['is_current'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Is Current'))
            ->setDefaultValue(FALSE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

}
