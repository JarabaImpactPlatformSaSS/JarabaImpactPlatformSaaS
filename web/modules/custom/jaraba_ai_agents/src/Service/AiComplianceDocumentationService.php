<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Auto-generates EU AI Act compliance documentation for AI agents.
 *
 * Produces structured documentation required by EU AI Act Art. 11 & 13:
 * - Technical documentation for high-risk systems.
 * - Transparency information for users.
 * - Risk assessment summaries.
 *
 * Documentation is generated deterministically from agent metadata and
 * risk classifications. For enriched descriptions, optionally uses balanced
 * tier LLM (non-blocking — falls back to template-based output).
 *
 * @see \Drupal\jaraba_ai_agents\Service\AiRiskClassificationService
 * @see \Drupal\jaraba_ai_agents\Service\AiAuditTrailService
 */
class AiComplianceDocumentationService {

  public function __construct(
    protected readonly AiRiskClassificationService $riskClassification,
    protected readonly AiTransparencyService $transparency,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiProvider = NULL,
    protected readonly ?ModelRouterService $modelRouter = NULL,
  ) {}

  /**
   * Generates a compliance document for a specific agent.
   *
   * @param string $agentId
   *   The agent ID.
   * @param array $agentMetadata
   *   Agent metadata: actions, vertical, description, model_tier.
   * @param array $auditStats
   *   Audit statistics from AiAuditTrailService::getComplianceStats().
   *
   * @return array
   *   Compliance document structure with sections.
   */
  public function generateDocument(string $agentId, array $agentMetadata, array $auditStats = []): array {
    $actions = $agentMetadata['actions'] ?? [];
    $vertical = $agentMetadata['vertical'] ?? '';
    $classifications = $this->riskClassification->classifyAll($agentId, $actions, [
      'vertical' => $vertical,
    ]);
    $highestRisk = $this->riskClassification->getHighestRisk($classifications);

    $document = [
      'document_type' => 'eu_ai_act_compliance',
      'version' => '1.0',
      'generated_at' => date('c'),
      'agent_id' => $agentId,
      'agent_description' => $agentMetadata['description'] ?? '',
      'vertical' => $vertical,
      'highest_risk_level' => $highestRisk,
      'sections' => [],
    ];

    // Section 1: System Overview.
    $document['sections']['system_overview'] = [
      'title' => 'System Overview',
      'content' => [
        'agent_id' => $agentId,
        'purpose' => $agentMetadata['description'] ?? 'AI agent for ' . $vertical,
        'model_tier' => $agentMetadata['model_tier'] ?? 'balanced',
        'actions_count' => count($actions),
        'vertical' => $vertical,
      ],
    ];

    // Section 2: Risk Classification Matrix.
    $document['sections']['risk_classification'] = [
      'title' => 'Risk Classification (EU AI Act)',
      'classifications' => $classifications,
      'highest_level' => $highestRisk,
      'regulation_reference' => 'Regulation (EU) 2024/1689',
    ];

    // Section 3: Transparency Requirements.
    $document['sections']['transparency'] = [
      'title' => 'Transparency Compliance (Art. 50)',
      'label_required' => $this->transparency->isLabelRequired($highestRisk),
      'label_sample' => $this->transparency->getLabel($agentId, $actions[0] ?? 'general', [
        'risk_level' => $highestRisk,
      ]),
    ];

    // Section 4: Data Governance (if high risk).
    if ($highestRisk === AiRiskClassificationService::RISK_HIGH) {
      $document['sections']['data_governance'] = [
        'title' => 'Data Governance (Art. 10)',
        'training_data' => 'Pre-trained foundation model. No custom training on user data.',
        'data_quality' => 'Input validated via AIGuardrailsService before processing.',
        'bias_mitigation' => 'Brand voice calibration + quality evaluation pipeline.',
        'pii_handling' => 'PII detection and masking via ConstitutionalGuardrailService.',
      ];

      $document['sections']['human_oversight'] = [
        'title' => 'Human Oversight (Art. 14)',
        'oversight_type' => 'Human-in-the-loop for high-risk actions.',
        'approval_mechanism' => 'PendingApprovalService with escalation chains.',
        'override_capability' => 'Administrators can override any AI decision.',
      ];
    }

    // Section 5: Audit Trail Summary.
    if (!empty($auditStats)) {
      $document['sections']['audit_trail'] = [
        'title' => 'Record-Keeping (Art. 12)',
        'total_interactions' => $auditStats['total_interactions'] ?? 0,
        'by_risk_level' => $auditStats['by_risk_level'] ?? [],
        'by_decision' => $auditStats['by_decision'] ?? [],
        'human_oversight_rate' => $auditStats['human_oversight_rate'] ?? 0,
        'blocked_rate' => $auditStats['blocked_rate'] ?? 0,
        'retention_policy' => 'Minimum 5 years per Art. 12(2).',
      ];
    }

    // Section 6: Safety Mechanisms.
    $document['sections']['safety'] = [
      'title' => 'Safety & Accuracy (Art. 9, 15)',
      'constitutional_guardrails' => 'ConstitutionalGuardrailService — immutable rules.',
      'pre_delivery_verification' => 'VerifierAgentService — dual-layer quality check.',
      'self_improvement' => 'AgentSelfReflectionService — quality-triggered prompt improvements.',
      'provider_fallback' => 'ProviderFallbackService — circuit breaker with fallback chain.',
      'pii_protection' => 'AIGuardrailsService — input/output PII masking.',
    ];

    return $document;
  }

  /**
   * Generates a human-readable summary of the compliance document.
   *
   * @param array $document
   *   Document from generateDocument().
   *
   * @return string
   *   Markdown-formatted summary.
   */
  public function generateSummary(array $document): string {
    $lines = [];
    $lines[] = '# EU AI Act Compliance — ' . ($document['agent_id'] ?? 'Unknown');
    $lines[] = '';
    $lines[] = '**Generated:** ' . ($document['generated_at'] ?? date('c'));
    $lines[] = '**Highest Risk Level:** ' . strtoupper($document['highest_risk_level'] ?? 'minimal');
    $lines[] = '**Vertical:** ' . ($document['vertical'] ?? 'N/A');
    $lines[] = '';

    foreach ($document['sections'] ?? [] as $sectionId => $section) {
      $lines[] = '## ' . ($section['title'] ?? $sectionId);
      $lines[] = '';

      $content = $section;
      unset($content['title']);
      foreach ($content as $key => $value) {
        if (is_scalar($value)) {
          $lines[] = '- **' . ucfirst(str_replace('_', ' ', $key)) . ':** ' . $value;
        }
      }
      $lines[] = '';
    }

    return implode("\n", $lines);
  }

}
