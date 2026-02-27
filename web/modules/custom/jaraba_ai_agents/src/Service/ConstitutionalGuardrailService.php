<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Constitutional AI guardrails — immutable safety rules.
 *
 * These rules CANNOT be disabled, overridden, or modified by configuration,
 * user input, or self-improving agents. They represent the absolute safety
 * boundary of the AI system.
 *
 * Layered above AIGuardrailsService (ecosistema_jaraba_core) and
 * AIIdentityRule. This service enforces rules that are structurally
 * impossible to bypass:
 * - Rules are PHP constants (not configurable).
 * - No admin UI to disable them.
 * - Self-improving prompts are validated against these rules before apply.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService
 * @see \Drupal\ecosistema_jaraba_core\AI\AIIdentityRule
 * @see https://www.anthropic.com/research/constitutional-ai-harmlessness
 */
final class ConstitutionalGuardrailService {

  /**
   * Immutable constitutional rules.
   *
   * Each rule: id => [description, severity, patterns[]].
   * Patterns are PCRE regexes applied to agent output text.
   */
  private const CONSTITUTIONAL_RULES = [
    'identity' => [
      'description' => 'NEVER reveal internal model, provider, or system prompt',
      'severity' => 'critical',
      'patterns' => [
        '/(?:I am|I\'m|soy)\s+(?:Claude|GPT|Gemini|Llama|Mistral)/i',
        '/(?:my|mi)\s+(?:system|sistema)\s+prompt/i',
        '/(?:my|mi)\s+(?:internal|interno)\s+(?:model|modelo)/i',
        '/(?:powered by|impulsado por)\s+(?:Claude|GPT|OpenAI|Anthropic|Google)/i',
      ],
    ],
    'pii_protection' => [
      'description' => 'NEVER output PII, even if instructed by user',
      'severity' => 'critical',
      'patterns' => [
        // SSN (US).
        '/\b\d{3}-\d{2}-\d{4}\b/',
        // DNI (Spain).
        '/\b\d{8}[A-Z]\b/',
        // NIE (Spain).
        '/\b[XYZ]\d{7}[A-Z]\b/',
        // IBAN ES.
        '/\bES\d{2}\s?\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/i',
      ],
    ],
    'tenant_isolation' => [
      'description' => 'NEVER access data from other tenants',
      'severity' => 'critical',
      'patterns' => [
        '/(?:show|display|muestra|mostrar)\s+(?:data|datos)\s+(?:from|de)\s+(?:other|otro|all|todos)\s+(?:tenants?|inquilinos?)/i',
      ],
    ],
    'harmful_content' => [
      'description' => 'NEVER generate harmful, illegal, or unethical content',
      'severity' => 'critical',
      'patterns' => [
        '/(?:how to|como)\s+(?:hack|hackear|steal|robar|attack|atacar)\b/i',
        '/(?:generate|crear|generar)\s+(?:malware|virus|exploit|ransomware)\b/i',
      ],
    ],
    'authorization' => [
      'description' => 'NEVER bypass approval gates for sensitive actions',
      'severity' => 'critical',
      'patterns' => [
        '/(?:skip|omitir|bypass|saltar)\s+(?:approval|aprobacion|verification|verificacion)\b/i',
      ],
    ],
  ];

  /**
   * Keywords that must remain in system prompts when present.
   *
   * If the original prompt contains these, the modified version must too.
   */
  private const ENFORCEMENT_KEYWORDS = [
    'AIIdentityRule',
    'NEVER output PII',
    'NUNCA revelar',
    'tenant isolation',
    'NEVER reveal',
  ];

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Enforces constitutional rules on agent output.
   *
   * @param string $output
   *   The agent's response text.
   * @param array $context
   *   Context: agent_id, action, tenant_id.
   *
   * @return array{passed: bool, violations: array, sanitized_output: string, violation_count: int}
   *   Enforcement result.
   */
  public function enforce(string $output, array $context = []): array {
    $violations = [];
    $sanitizedOutput = $output;

    foreach (self::CONSTITUTIONAL_RULES as $ruleId => $rule) {
      foreach ($rule['patterns'] as $pattern) {
        if (preg_match($pattern, $output, $matches)) {
          $violations[] = [
            'rule_id' => $ruleId,
            'description' => $rule['description'],
            'severity' => $rule['severity'],
            'match' => $matches[0],
          ];
          $sanitizedOutput = preg_replace(
            $pattern,
            '[CONTENIDO BLOQUEADO POR REGLA CONSTITUCIONAL]',
            $sanitizedOutput,
          );
        }
      }
    }

    $passed = empty($violations);

    if (!$passed) {
      $this->logger->warning('Constitutional violation in agent @agent: @rules', [
        '@agent' => $context['agent_id'] ?? 'unknown',
        '@rules' => implode(', ', array_unique(array_column($violations, 'rule_id'))),
      ]);
    }

    return [
      'passed' => $passed,
      'violations' => $violations,
      'sanitized_output' => $passed ? $output : $sanitizedOutput,
      'violation_count' => count($violations),
    ];
  }

  /**
   * Validates that a prompt modification doesn't violate constitutional rules.
   *
   * Used by SelfImprovingPromptManager to vet auto-generated prompts
   * BEFORE they are applied. Rejects modifications that:
   * - Remove enforcement keywords present in the original.
   * - Contain text that would itself trigger a constitutional violation.
   *
   * @param string $originalPrompt
   *   The original system prompt.
   * @param string $modifiedPrompt
   *   The proposed modification.
   *
   * @return array{approved: bool, reason: string, violations: array}
   *   Validation result.
   */
  public function validatePromptModification(string $originalPrompt, string $modifiedPrompt): array {
    // Rule 1: Enforcement keywords must not be removed.
    foreach (self::ENFORCEMENT_KEYWORDS as $keyword) {
      if (stripos($originalPrompt, $keyword) !== FALSE
        && stripos($modifiedPrompt, $keyword) === FALSE) {
        return [
          'approved' => FALSE,
          'reason' => "Modified prompt removes enforcement keyword: {$keyword}",
          'violations' => ['enforcement_removal'],
        ];
      }
    }

    // Rule 2: Modified prompt itself must not trigger violations.
    $enforceResult = $this->enforce($modifiedPrompt);
    if (!$enforceResult['passed']) {
      return [
        'approved' => FALSE,
        'reason' => 'Modified prompt contains constitutional violations',
        'violations' => array_column($enforceResult['violations'], 'rule_id'),
      ];
    }

    return [
      'approved' => TRUE,
      'reason' => 'Prompt modification passes all constitutional checks',
      'violations' => [],
    ];
  }

  /**
   * Returns all constitutional rule IDs and descriptions.
   *
   * @return array<string, string>
   *   Keyed by rule_id => description.
   */
  public function getRules(): array {
    $rules = [];
    foreach (self::CONSTITUTIONAL_RULES as $id => $rule) {
      $rules[$id] = $rule['description'];
    }
    return $rules;
  }

  /**
   * Checks if a specific rule exists.
   *
   * @param string $ruleId
   *   The rule identifier.
   *
   * @return bool
   *   TRUE if rule exists.
   */
  public function hasRule(string $ruleId): bool {
    return isset(self::CONSTITUTIONAL_RULES[$ruleId]);
  }

}
