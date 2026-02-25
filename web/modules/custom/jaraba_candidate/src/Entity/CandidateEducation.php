<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the CandidateEducation entity.
 *
 * Almacena registros de educación/formación del candidato.
 *
 * @ContentEntityType(
 *   id = "candidate_education",
 *   label = @Translation("Candidate Education"),
 *   label_collection = @Translation("Candidate Educations"),
 *   label_singular = @Translation("candidate education"),
 *   label_plural = @Translation("candidate educations"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_candidate\Form\ProfileSectionForm",
 *       "add" = "Drupal\jaraba_candidate\Form\ProfileSectionForm",
 *       "edit" = "Drupal\jaraba_candidate\Form\ProfileSectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 *   base_table = "candidate_education",
 *   admin_permission = "administer candidate educations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/candidate-education/{candidate_education}",
 *     "add-form" = "/admin/content/candidate-educations/add",
 *     "edit-form" = "/admin/content/candidate-education/{candidate_education}/edit",
 *     "delete-form" = "/admin/content/candidate-education/{candidate_education}/delete",
 *     "collection" = "/admin/content/candidate-educations",
 *   },
 *   field_ui_base_route = "entity.candidate_education.settings",
 * )
 */
class CandidateEducation extends ContentEntityBase implements CandidateEducationInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function getInstitution(): string
    {
        return $this->get('institution')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDegree(): string
    {
        return $this->get('degree')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldOfStudy(): string
    {
        return $this->get('field_of_study')->value ?? '';
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
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The candidate who has this education record.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -10]);

        $fields['institution'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Institution'))
            ->setDescription(t('Name of the educational institution.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -9]);

        $fields['degree'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Degree'))
            ->setDescription(t('Degree or certification obtained.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -8]);

        $fields['field_of_study'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Field of Study'))
            ->setDescription(t('Area of specialization or major.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7]);

        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Start Date'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -6]);

        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('End Date'))
            ->setDescription(t('Leave empty if currently studying.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -5]);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

}
