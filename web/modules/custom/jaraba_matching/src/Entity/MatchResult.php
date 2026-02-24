<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad MatchResult para almacenar resultados de matching.
 *
 * Almacena el score calculado entre un job_posting y un candidate_profile,
 * junto con el desglose de scores por factor (skills, experience, location, etc).
 *
 * @ContentEntityType(
 *   id = "match_result",
 *   label = @Translation("Match Result"),
 *   label_collection = @Translation("Match Results"),
 *   label_singular = @Translation("match result"),
 *   label_plural = @Translation("match results"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_matching\MatchResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_matching\MatchResultAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "match_result",
 *   admin_permission = "administer matching",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/match-result/{match_result}",
 *     "collection" = "/admin/content/match-results",
 *   },
 *   field_ui_base_route = "entity.match_result.settings",
 * )
 */
class MatchResult extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al Job Posting
        $fields['job_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Job Posting'))
            ->setDescription(t('The job posting being matched'))
            ->setSetting('target_type', 'job_posting')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al Candidate Profile
        $fields['candidate_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Candidate Profile'))
            ->setDescription(t('The candidate profile being matched'))
            ->setSetting('target_type', 'candidate_profile')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        // Score final (0-100)
        $fields['final_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Final Score'))
            ->setDescription(t('Combined matching score (0-100)'))
            ->setSettings([
                'precision' => 5,
                'scale' => 2,
            ])
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayConfigurable('view', TRUE);

        // Score por reglas (0-100)
        $fields['rule_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Rule-Based Score'))
            ->setDescription(t('Score from structured rules'))
            ->setSettings([
                'precision' => 5,
                'scale' => 2,
            ])
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayConfigurable('view', TRUE);

        // Score semántico (0-100)
        $fields['semantic_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Semantic Score'))
            ->setDescription(t('Score from vector similarity'))
            ->setSettings([
                'precision' => 5,
                'scale' => 2,
            ])
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayConfigurable('view', TRUE);

        // Desglose de scores (JSON)
        $fields['scores_breakdown'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Scores Breakdown'))
            ->setDescription(t('JSON breakdown of individual scores'))
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID para multi-tenancy
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setRequired(TRUE);

        // Timestamp de cálculo
        $fields['calculated'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Calculated'))
            ->setDescription(t('When the match was calculated'));

        // Rank en búsqueda (posición)
        $fields['rank'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Rank'))
            ->setDescription(t('Position in search results'))
            ->setDefaultValue(0);

        return $fields;
    }

    /**
     * Obtiene el desglose de scores como array.
     */
    public function getScoresBreakdown(): array
    {
        $json = $this->get('scores_breakdown')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    /**
     * Establece el desglose de scores desde array.
     */
    public function setScoresBreakdown(array $breakdown): self
    {
        $this->set('scores_breakdown', json_encode($breakdown));
        return $this;
    }

}
