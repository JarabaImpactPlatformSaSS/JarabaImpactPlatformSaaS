<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad MatchFeedback para feedback sobre matches.
 *
 * Almacena el resultado real de un match (contratado, rechazado, etc)
 * para entrenar el modelo de matching con datos reales.
 *
 * @ContentEntityType(
 *   id = "match_feedback",
 *   label = @Translation("Match Feedback"),
 *   label_collection = @Translation("Match Feedback"),
 *   label_singular = @Translation("match feedback"),
 *   label_plural = @Translation("match feedbacks"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_matching\MatchFeedbackListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "match_feedback",
 *   admin_permission = "administer matching",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/match-feedback/{match_feedback}",
 *     "collection" = "/admin/content/match-feedbacks",
 *     "add-form" = "/admin/content/match-feedback/add",
 *   },
 *   field_ui_base_route = "entity.match_feedback.settings",
 * )
 */
class MatchFeedback extends ContentEntityBase
{

    /**
     * Outcomes posibles del match.
     */
    const OUTCOME_HIRED = 'hired';
    const OUTCOME_REJECTED_BY_EMPLOYER = 'rejected_employer';
    const OUTCOME_REJECTED_BY_CANDIDATE = 'rejected_candidate';
    const OUTCOME_NO_RESPONSE = 'no_response';
    const OUTCOME_WITHDRAWN = 'withdrawn';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al MatchResult
        $fields['match_result_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Match Result'))
            ->setDescription(t('The match result this feedback is for'))
            ->setSetting('target_type', 'match_result')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al JobApplication (si aplica)
        $fields['application_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Job Application'))
            ->setDescription(t('Related job application if applicable'))
            ->setSetting('target_type', 'job_application')
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        // Outcome del match
        $fields['outcome'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Outcome'))
            ->setDescription(t('Final outcome of the match'))
            ->setSettings([
                'allowed_values' => [
                    self::OUTCOME_HIRED => 'Hired',
                    self::OUTCOME_REJECTED_BY_EMPLOYER => 'Rejected by Employer',
                    self::OUTCOME_REJECTED_BY_CANDIDATE => 'Rejected by Candidate',
                    self::OUTCOME_NO_RESPONSE => 'No Response',
                    self::OUTCOME_WITHDRAWN => 'Withdrawn',
                ],
            ])
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Score original del match (snapshot)
        $fields['original_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Original Score'))
            ->setDescription(t('The score when match was shown'))
            ->setSettings([
                'precision' => 5,
                'scale' => 2,
            ])
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayConfigurable('view', TRUE);

        // Días hasta outcome
        $fields['days_to_outcome'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Days to Outcome'))
            ->setDescription(t('Days from match shown to outcome'))
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayConfigurable('view', TRUE);

        // Feedback cualitativo (opcional)
        $fields['feedback_text'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Feedback Text'))
            ->setDescription(t('Optional qualitative feedback'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setRequired(TRUE);

        // Timestamp de feedback
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('When feedback was recorded'));

        return $fields;
    }

    /**
     * Indica si el match fue exitoso (hired).
     */
    public function isSuccessful(): bool
    {
        return $this->get('outcome')->value === self::OUTCOME_HIRED;
    }

}
