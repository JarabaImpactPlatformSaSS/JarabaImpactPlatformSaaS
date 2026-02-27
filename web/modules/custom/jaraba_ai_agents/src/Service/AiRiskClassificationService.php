<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Classifies AI agent actions into EU AI Act risk levels.
 *
 * Implements the four-tier risk classification from EU Regulation 2024/1689:
 * - Unacceptable: Prohibited (blocked by ConstitutionalGuardrailService).
 * - High: Requires documentation, human oversight, audit trail.
 * - Limited: Requires transparency label (AI-generated disclosure).
 * - Minimal: No additional requirements.
 *
 * Classification is deterministic (rule-based, no LLM), zero-cost, and runs
 * at service initialization time per agent+action pair.
 *
 * @see https://eur-lex.europa.eu/eli/reg/2024/1689/oj
 * @see \Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
 */
final class AiRiskClassificationService {

  /**
   * Risk levels in order of severity.
   */
  public const RISK_MINIMAL = 'minimal';
  public const RISK_LIMITED = 'limited';
  public const RISK_HIGH = 'high';
  public const RISK_UNACCEPTABLE = 'unacceptable';

  /**
   * Actions classified as High Risk per EU AI Act Annex III.
   *
   * These require: documentation, human oversight, logging, transparency.
   */
  private const HIGH_RISK_ACTIONS = [
    // Employment & recruitment (Annex III, 4).
    'recruitment_assessment',
    'candidate_scoring',
    'candidate_matching',
    'cv_screening',
    // Legal & justice (Annex III, 6, 8).
    'legal_analysis',
    'legal_search',
    'case_assistant',
    'contract_generation',
    'legal_document_draft',
    // Financial (Annex III, 5b).
    'financial_advice',
    'credit_assessment',
    'pricing_suggestion',
    'invoice_generation',
  ];

  /**
   * Actions classified as Limited Risk.
   *
   * Require transparency label — user must know they interact with AI.
   */
  private const LIMITED_RISK_ACTIONS = [
    'chatbot',
    'copilot_chat',
    'content_generation',
    'generate_copy',
    'storytelling',
    'email_draft',
    'social_media_post',
    'product_description',
    'blog_article',
    'seo_optimization',
    'translate',
    'summarize',
    'customer_support',
  ];

  /**
   * Verticals that elevate all actions to at least Limited Risk.
   */
  private const ELEVATED_VERTICALS = [
    'jarabalex',
    'empleabilidad',
  ];

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Classifies a specific agent+action into a risk level.
   *
   * @param string $agentId
   *   The agent identifier.
   * @param string $action
   *   The action being performed.
   * @param array $context
   *   Optional context: vertical, tenant_id.
   *
   * @return array{risk_level: string, requirements: array, eu_annex: string, documentation_required: bool, human_oversight_required: bool, transparency_required: bool}
   *   Risk classification result with applicable requirements.
   */
  public function classify(string $agentId, string $action, array $context = []): array {
    $vertical = $context['vertical'] ?? '';

    // Check high risk first.
    if (in_array($action, self::HIGH_RISK_ACTIONS, TRUE)) {
      return $this->buildResult(self::RISK_HIGH, $action, $vertical);
    }

    // Check limited risk.
    if (in_array($action, self::LIMITED_RISK_ACTIONS, TRUE)) {
      return $this->buildResult(self::RISK_LIMITED, $action, $vertical);
    }

    // Elevated verticals push minimal to limited.
    if (in_array($vertical, self::ELEVATED_VERTICALS, TRUE)) {
      return $this->buildResult(self::RISK_LIMITED, $action, $vertical);
    }

    return $this->buildResult(self::RISK_MINIMAL, $action, $vertical);
  }

  /**
   * Classifies all actions for a given agent.
   *
   * @param string $agentId
   *   The agent identifier.
   * @param array $actions
   *   List of action names.
   * @param array $context
   *   Context (vertical, tenant_id).
   *
   * @return array<string, array>
   *   Keyed by action => classification result.
   */
  public function classifyAll(string $agentId, array $actions, array $context = []): array {
    $results = [];
    foreach ($actions as $action) {
      $results[$action] = $this->classify($agentId, $action, $context);
    }
    return $results;
  }

  /**
   * Gets the highest risk level from a list of classifications.
   *
   * @param array $classifications
   *   Array of classification results from classifyAll().
   *
   * @return string
   *   The highest risk level found.
   */
  public function getHighestRisk(array $classifications): string {
    $order = [
      self::RISK_MINIMAL => 0,
      self::RISK_LIMITED => 1,
      self::RISK_HIGH => 2,
      self::RISK_UNACCEPTABLE => 3,
    ];

    $maxLevel = self::RISK_MINIMAL;
    foreach ($classifications as $classification) {
      $level = $classification['risk_level'] ?? self::RISK_MINIMAL;
      if (($order[$level] ?? 0) > ($order[$maxLevel] ?? 0)) {
        $maxLevel = $level;
      }
    }

    return $maxLevel;
  }

  /**
   * Returns all high-risk action identifiers.
   *
   * @return array
   *   List of high-risk action names.
   */
  public function getHighRiskActions(): array {
    return self::HIGH_RISK_ACTIONS;
  }

  /**
   * Returns all limited-risk action identifiers.
   *
   * @return array
   *   List of limited-risk action names.
   */
  public function getLimitedRiskActions(): array {
    return self::LIMITED_RISK_ACTIONS;
  }

  /**
   * Builds a classification result with requirements.
   */
  protected function buildResult(string $riskLevel, string $action, string $vertical): array {
    $requirements = match ($riskLevel) {
      self::RISK_HIGH => [
        'documentation',
        'human_oversight',
        'audit_trail',
        'transparency_label',
        'risk_assessment',
        'data_governance',
      ],
      self::RISK_LIMITED => [
        'transparency_label',
        'audit_trail',
      ],
      self::RISK_MINIMAL => [],
      default => [],
    };

    $euAnnex = match ($riskLevel) {
      self::RISK_HIGH => $this->resolveAnnex($action),
      self::RISK_LIMITED => 'Art. 50 (Transparency)',
      self::RISK_MINIMAL => 'N/A',
      default => 'N/A',
    };

    return [
      'risk_level' => $riskLevel,
      'requirements' => $requirements,
      'eu_annex' => $euAnnex,
      'documentation_required' => $riskLevel === self::RISK_HIGH,
      'human_oversight_required' => $riskLevel === self::RISK_HIGH,
      'transparency_required' => in_array($riskLevel, [self::RISK_HIGH, self::RISK_LIMITED], TRUE),
    ];
  }

  /**
   * Resolves the EU AI Act Annex reference for a high-risk action.
   */
  protected function resolveAnnex(string $action): string {
    return match (TRUE) {
      str_starts_with($action, 'recruitment') || str_starts_with($action, 'candidate') || $action === 'cv_screening' => 'Annex III, 4 (Employment)',
      str_starts_with($action, 'legal') || str_starts_with($action, 'case') || str_starts_with($action, 'contract') => 'Annex III, 8 (Administration of Justice)',
      str_starts_with($action, 'financial') || str_starts_with($action, 'credit') || $action === 'pricing_suggestion' || $action === 'invoice_generation' => 'Annex III, 5b (Credit & Insurance)',
      default => 'Annex III (General)',
    };
  }

}
