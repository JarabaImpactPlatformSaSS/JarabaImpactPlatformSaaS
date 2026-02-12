<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the CandidateSkill entity.
 *
 * Relación many-to-many entre candidatos y skills (términos de taxonomía).
 * Incluye nivel de competencia y años de experiencia.
 *
 * @ContentEntityType(
 *   id = "candidate_skill",
 *   label = @Translation("Candidate Skill"),
 *   label_collection = @Translation("Candidate Skills"),
 *   label_singular = @Translation("candidate skill"),
 *   label_plural = @Translation("candidate skills"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_candidate\CandidateSkillListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_candidate\Form\CandidateSkillForm",
 *       "add" = "Drupal\jaraba_candidate\Form\CandidateSkillForm",
 *       "edit" = "Drupal\jaraba_candidate\Form\CandidateSkillForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_candidate\CandidateSkillAccessControlHandler",
 *   },
 *   base_table = "candidate_skill",
 *   admin_permission = "administer candidate skills",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/candidate-skill/{candidate_skill}",
 *     "add-form" = "/admin/content/candidate-skills/add",
 *     "edit-form" = "/admin/content/candidate-skill/{candidate_skill}/edit",
 *     "delete-form" = "/admin/content/candidate-skill/{candidate_skill}/delete",
 *     "collection" = "/admin/content/candidate-skills",
 *   },
 *   field_ui_base_route = "entity.candidate_skill.settings",
 * )
 */
class CandidateSkill extends ContentEntityBase implements CandidateSkillInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Skill level constants.
     */
    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';
    public const LEVEL_EXPERT = 'expert';

    /**
     * {@inheritdoc}
     */
    public function getSkillId(): ?int
    {
        $value = $this->get('skill_id')->target_id;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getLevel(): string
    {
        return $this->get('level')->value ?? self::LEVEL_INTERMEDIATE;
    }

    /**
     * {@inheritdoc}
     */
    public function getYearsExperience(): int
    {
        return (int) ($this->get('years_experience')->value ?? 0);
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
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // User/Candidate reference.
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user (candidate) who has this skill.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -10,
            ]);

        // Skill taxonomy term reference.
        $fields['skill_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Skill'))
            ->setDescription(t('The skill from the Skills taxonomy.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler', 'default:taxonomy_term')
            ->setSetting('handler_settings', [
                'target_bundles' => ['skills' => 'skills'],
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -9,
            ]);

        // Skill level.
        $fields['level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Skill Level'))
            ->setDescription(t('Your proficiency level for this skill.'))
            ->setSetting('allowed_values', [
                self::LEVEL_BEGINNER => t('Beginner'),
                self::LEVEL_INTERMEDIATE => t('Intermediate'),
                self::LEVEL_ADVANCED => t('Advanced'),
                self::LEVEL_EXPERT => t('Expert'),
            ])
            ->setDefaultValue(self::LEVEL_INTERMEDIATE)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => -8,
            ]);

        // Years of experience.
        $fields['years_experience'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Years of Experience'))
            ->setDescription(t('How many years have you used this skill?'))
            ->setSetting('min', 0)
            ->setSetting('max', 50)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => -7,
            ]);

        // Verified flag.
        $fields['is_verified'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Verified'))
            ->setDescription(t('Whether this skill has been verified (e.g., via assessment).'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -6,
            ]);

        // Verification date.
        $fields['verified_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Verified At'))
            ->setDescription(t('When the skill was verified.'));

        // Source (how skill was added).
        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Source'))
            ->setDescription(t('How this skill was added to the profile.'))
            ->setSetting('allowed_values', [
                'manual' => t('Manually added'),
                'cv_import' => t('CV Import'),
                'linkedin' => t('LinkedIn'),
                'assessment' => t('Assessment'),
                'copilot' => t('AI Copilot'),
            ])
            ->setDefaultValue('manual');

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

}
