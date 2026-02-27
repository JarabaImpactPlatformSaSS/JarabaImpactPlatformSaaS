<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the AI Risk Assessment configuration entity.
 *
 * Stores EU AI Act risk assessment records per agent. Each agent can have
 * one active risk assessment that documents its classification, mitigations,
 * and compliance status.
 *
 * ConfigEntity (not Content) because risk assessments are structural:
 * they define system behavior (which agents need human oversight, which
 * need transparency labels) and should be deployable via config:import.
 *
 * @ConfigEntityType(
 *   id = "ai_risk_assessment",
 *   label = @Translation("AI Risk Assessment"),
 *   label_collection = @Translation("AI Risk Assessments"),
 *   label_singular = @Translation("AI risk assessment"),
 *   label_plural = @Translation("AI risk assessments"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   config_prefix = "risk_assessment",
 *   admin_permission = "configure ai safety",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "agent_id",
 *     "risk_level",
 *     "eu_annex_references",
 *     "purpose_description",
 *     "data_governance_notes",
 *     "human_oversight_plan",
 *     "mitigation_measures",
 *     "transparency_measures",
 *     "assessment_date",
 *     "assessor",
 *     "next_review_date",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/ai/risk-assessments",
 *   },
 * )
 */
class AiRiskAssessment extends ConfigEntityBase {

  /**
   * The risk assessment ID (machine name).
   */
  protected string $id = '';

  /**
   * The risk assessment label.
   */
  protected string $label = '';

  /**
   * The agent ID this assessment covers.
   */
  protected string $agent_id = '';

  /**
   * The assessed risk level: minimal, limited, high.
   */
  protected string $risk_level = 'minimal';

  /**
   * Comma-separated EU AI Act Annex references.
   */
  protected string $eu_annex_references = '';

  /**
   * Description of the AI system's purpose.
   */
  protected string $purpose_description = '';

  /**
   * Data governance notes (training data, quality, bias).
   */
  protected string $data_governance_notes = '';

  /**
   * Human oversight implementation plan.
   */
  protected string $human_oversight_plan = '';

  /**
   * Risk mitigation measures applied.
   */
  protected string $mitigation_measures = '';

  /**
   * Transparency measures (labels, disclosures).
   */
  protected string $transparency_measures = '';

  /**
   * Date of the assessment (Y-m-d).
   */
  protected string $assessment_date = '';

  /**
   * Name/ID of the assessor.
   */
  protected string $assessor = '';

  /**
   * Next review date (Y-m-d).
   */
  protected string $next_review_date = '';

  /**
   * Assessment status: draft, active, archived.
   *
   * No type hint: ConfigEntityBase::$status no tiene tipo declarado.
   */
  protected $status = 'draft';

  /**
   * Gets the agent ID.
   */
  public function getAgentId(): string {
    return $this->agent_id;
  }

  /**
   * Gets the risk level.
   */
  public function getRiskLevel(): string {
    return $this->risk_level;
  }

  /**
   * Gets the assessment status.
   */
  public function getAssessmentStatus(): string {
    return $this->status;
  }

  /**
   * Whether this is a high-risk assessment.
   */
  public function isHighRisk(): bool {
    return $this->risk_level === 'high';
  }

  /**
   * Whether human oversight is documented.
   */
  public function hasHumanOversightPlan(): bool {
    return !empty($this->human_oversight_plan);
  }

}
