<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the CandidateLanguage entity.
 *
 * Almacena los idiomas de un candidato con niveles CEFR por competencia.
 *
 * @ContentEntityType(
 *   id = "candidate_language",
 *   label = @Translation("Candidate Language"),
 *   label_collection = @Translation("Candidate Languages"),
 *   label_singular = @Translation("candidate language"),
 *   label_plural = @Translation("candidate languages"),
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
 *     "access" = "Drupal\jaraba_candidate\CandidateSkillAccessControlHandler",
 *   },
 *   base_table = "candidate_language",
 *   admin_permission = "administer candidate skills",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/candidate-language/{candidate_language}",
 *     "add-form" = "/admin/content/candidate-languages/add",
 *     "edit-form" = "/admin/content/candidate-language/{candidate_language}/edit",
 *     "delete-form" = "/admin/content/candidate-language/{candidate_language}/delete",
 *     "collection" = "/admin/content/candidate-languages",
 *   },
 *   field_ui_base_route = "entity.candidate_language.settings",
 * )
 */
class CandidateLanguage extends ContentEntityBase implements CandidateLanguageInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * CEFR proficiency levels.
     */
    public const LEVEL_A1 = 'A1';
    public const LEVEL_A2 = 'A2';
    public const LEVEL_B1 = 'B1';
    public const LEVEL_B2 = 'B2';
    public const LEVEL_C1 = 'C1';
    public const LEVEL_C2 = 'C2';

    /**
     * {@inheritdoc}
     */
    public function getLanguageCode(): string
    {
        return $this->get('language_code')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageName(): string
    {
        return $this->get('language_name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getProficiencyLevel(): string
    {
        return $this->get('proficiency_level')->value ?? self::LEVEL_A1;
    }

    /**
     * {@inheritdoc}
     */
    public function isNative(): bool
    {
        return (bool) $this->get('is_native')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCertification(): ?string
    {
        $value = $this->get('certification')->value;
        return $value ?: NULL;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $cefrLevels = [
            self::LEVEL_A1 => t('A1 - Beginner'),
            self::LEVEL_A2 => t('A2 - Elementary'),
            self::LEVEL_B1 => t('B1 - Intermediate'),
            self::LEVEL_B2 => t('B2 - Upper Intermediate'),
            self::LEVEL_C1 => t('C1 - Advanced'),
            self::LEVEL_C2 => t('C2 - Mastery'),
        ];

        // User/Candidate reference.
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The candidate who speaks this language.'))
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

        // Language code (ISO 639-1).
        $fields['language_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Language Code'))
            ->setDescription(t('ISO 639-1 language code (e.g., es, en, fr).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 5)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => -9,
            ]);

        // Language name.
        $fields['language_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Language Name'))
            ->setDescription(t('Human-readable language name.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => -8,
            ]);

        // Overall proficiency level (CEFR).
        $fields['proficiency_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Proficiency Level'))
            ->setDescription(t('Overall CEFR proficiency level.'))
            ->setSetting('allowed_values', $cefrLevels)
            ->setDefaultValue(self::LEVEL_A1)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => -7,
            ]);

        // Reading level.
        $fields['reading_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Reading Level'))
            ->setSetting('allowed_values', $cefrLevels)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -6,
            ]);

        // Writing level.
        $fields['writing_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Writing Level'))
            ->setSetting('allowed_values', $cefrLevels)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -5,
            ]);

        // Speaking level.
        $fields['speaking_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Speaking Level'))
            ->setSetting('allowed_values', $cefrLevels)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -4,
            ]);

        // Listening level.
        $fields['listening_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Listening Level'))
            ->setSetting('allowed_values', $cefrLevels)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -3,
            ]);

        // Is native.
        $fields['is_native'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Native Language'))
            ->setDescription(t('Whether this is a native/mother tongue.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -2,
            ]);

        // Certification.
        $fields['certification'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Certification'))
            ->setDescription(t('Language certification (e.g., DELE, TOEFL, DELF, Goethe).'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -1,
            ]);

        // Source.
        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Source'))
            ->setDescription(t('How this language entry was added.'))
            ->setSetting('allowed_values', [
                'manual' => t('Manually added'),
                'linkedin' => t('LinkedIn'),
                'cv_parser' => t('CV Parser'),
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
