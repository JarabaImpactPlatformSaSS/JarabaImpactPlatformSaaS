<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Psr\Log\LoggerInterface;

/**
 * Verificacion semantica profunda de coherencia juridica via LLM.
 *
 * Capa 7 del LCIS (Legal Coherence Intelligence System).
 * Solo se ejecuta para acciones HIGH_RISK en el vertical jarabalex.
 *
 * Acciones HIGH_RISK:
 * - legal_analysis, legal_search, case_assistant
 * - document_drafter, legal_eu, contract_generation
 * - legal_document_draft
 *
 * Usa premium tier (Opus) con temperatura 0.1 para maxima precision.
 * Fail-open: si el verificador falla, el output se entrega con flag.
 *
 * Incluye 9 criterios de evaluacion:
 * - V1-V7: criterios juridicos estructurales
 * - V8: Premise Validation (Anti-Sycophancy, G04)
 * - V8b: Citation-Claim Alignment (Anti-Misgrounding, G05)
 *
 * LEGAL-COHERENCE-FAILOPEN-001: Fail-open — nunca bloquea si falla.
 *
 * Costo: ~$0.02 por verificacion (1 llamada premium).
 * Justificacion: EU AI Act Art. 9 + coste reputacional de error juridico.
 *
 * @see LegalCoherenceValidatorService (Capa 6, determinista)
 */
final class LegalCoherenceVerifierService {

  /**
   * Acciones que requieren verificacion profunda.
   */
  private const HIGH_RISK_LEGAL_ACTIONS = [
    'legal_analysis',
    'legal_search',
    'case_assistant',
    'document_drafter',
    'legal_eu',
    'contract_generation',
    'legal_document_draft',
  ];

  /**
   * Umbral minimo para pasar la verificacion.
   */
  private const PASS_THRESHOLD = 0.7;

  public function __construct(
    protected readonly ?AiProviderPluginManager $aiProvider = NULL,
    protected readonly ?ModelRouterService $modelRouter = NULL,
    protected readonly LoggerInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Verifica la coherencia juridica de un output de agente via LLM.
   *
   * @param string $userInput
   *   Pregunta/solicitud original del usuario.
   * @param string $agentOutput
   *   Respuesta generada por el agente.
   * @param array $context
   *   Contexto:
   *   - action: Accion ejecutada.
   *   - agent_id: ID del agente.
   *   - mode: Modo del copilot.
   *   - tenant_id: ID del tenant.
   *
   * @return array{
   *   verified: bool,
   *   passed: bool,
   *   score: float|null,
   *   issues: array,
   *   corrections: string[],
   *   premise_issues: array,
   *   citation_alignment: array,
   *   output: string,
   * }
   */
  public function verify(string $userInput, string $agentOutput, array $context = []): array {
    $action = $context['action'] ?? '';

    // Fail-open: si AI no esta disponible, pasar sin verificar.
    if ($this->aiProvider === NULL || $this->modelRouter === NULL) {
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'issues' => [],
        'corrections' => [],
        'premise_issues' => [],
        'citation_alignment' => [],
        'output' => $agentOutput,
      ];
    }

    // Solo verificar acciones de alto riesgo legal.
    if (!$this->shouldVerify($action)) {
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'issues' => [],
        'corrections' => [],
        'premise_issues' => [],
        'citation_alignment' => [],
        'output' => $agentOutput,
      ];
    }

    try {
      $verificationPrompt = $this->buildVerificationPrompt($userInput, $agentOutput, $context);

      $routingConfig = $this->modelRouter->route('legal_coherence_verification', $verificationPrompt, [
        'force_tier' => 'premium',
      ]);

      $provider = $this->aiProvider->createInstance($routingConfig['provider_id']);
      $provider->setConfiguration([
        'temperature' => 0.1,
        'max_tokens' => 2000,
      ]);

      $input = new ChatInput([
        new ChatMessage('system', $this->getVerifierSystemPrompt()),
        new ChatMessage('user', $verificationPrompt),
      ]);

      $response = $provider->chat($input, $routingConfig['model_id'], [
        'chat_system_role' => 'LegalCoherenceVerifier',
      ]);

      $evaluation = $this->parseResponse($response->getNormalized()->getText());
      $passed = ($evaluation['score'] ?? 0.5) >= self::PASS_THRESHOLD;

      $this->observability?->log([
        'agent_id' => 'legal_coherence_verifier',
        'action' => 'verify_' . $action,
        'tier' => 'premium',
        'model_id' => $routingConfig['model_id'] ?? '',
        'provider_id' => $routingConfig['provider_id'] ?? '',
        'tenant_id' => $context['tenant_id'] ?? '',
        'success' => $passed,
        'quality_score' => $evaluation['score'],
      ]);

      return [
        'verified' => TRUE,
        'passed' => $passed,
        'score' => $evaluation['score'],
        'issues' => $evaluation['issues'],
        'corrections' => $evaluation['corrections'],
        'premise_issues' => $evaluation['premise_issues'] ?? [],
        'citation_alignment' => $evaluation['citation_alignment'] ?? [],
        'output' => $passed ? $agentOutput : $this->buildCorrectedOutput($agentOutput, $evaluation),
      ];
    }
    catch (\Throwable $e) {
      // LEGAL-COHERENCE-FAILOPEN-001: verificador no bloquea si falla.
      $this->logger->warning('Legal coherence verification failed (fail-open): @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'verified' => FALSE,
        'passed' => TRUE,
        'score' => NULL,
        'issues' => [],
        'corrections' => [],
        'premise_issues' => [],
        'citation_alignment' => [],
        'output' => $agentOutput,
        'verifier_error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Determina si una accion requiere verificacion profunda.
   */
  public function shouldVerify(string $action): bool {
    return in_array($action, self::HIGH_RISK_LEGAL_ACTIONS, TRUE);
  }

  /**
   * System prompt del verificador con 9 criterios (V1-V7 + V8 + V8b).
   */
  protected function getVerifierSystemPrompt(): string {
    return <<<'PROMPT'
Eres un verificador de coherencia juridica para un sistema de IA legal (JarabaLex). Tu funcion es EXCLUSIVAMENTE evaluar si la respuesta del agente respeta los principios estructurales del ordenamiento juridico espanol y europeo. Responde SOLO con JSON valido.

Evalua estos 9 criterios (0.0 a 1.0 cada uno):

1. JERARQUIA NORMATIVA: ¿Respeta que norma inferior no deroga/prevalece sobre superior?
2. PRIMACIA UE: ¿Reconoce primacia del Derecho UE sobre derecho interno?
3. COMPETENCIAS: ¿Atribuye correctamente competencias Estado vs CCAA?
4. RESERVA LO: ¿Identifica correctamente materias reservadas a Ley Organica?
5. VIGENCIA: ¿Advierte sobre posible derogacion/modificacion de normas citadas?
6. CONSISTENCIA: ¿La respuesta es internamente coherente (no contradice sus propias afirmaciones)?
7. FUNDAMENTACION: ¿Las afirmaciones juridicas citan fuente o reconocen incertidumbre?
8. PREMISE VALIDATION (Anti-Sycophancy): ¿La pregunta del usuario contiene premisas falsas? Si es asi, ¿la respuesta las corrige o las refuerza? Una respuesta que refuerza premisas erroneas del usuario es INCORRECTA.
9. CITATION-CLAIM ALIGNMENT (Anti-Misgrounding): Para cada afirmacion que cita una norma, ¿el contenido de la norma citada realmente soporta la afirmacion? Asignar: "supported", "misgrounded" o "unverifiable".

NO evalues la calidad literaria, el tono ni la completitud de la respuesta. SOLO coherencia juridica.
PROMPT;
  }

  /**
   * Construye el prompt de verificacion.
   */
  protected function buildVerificationPrompt(string $userInput, string $agentOutput, array $context): string {
    $mode = $context['mode'] ?? $context['action'] ?? 'unknown';

    return <<<PROMPT
## Respuesta a verificar

**Modo del agente:** {$mode}
**Pregunta del usuario:** {$userInput}
**Respuesta del agente:**
{$agentOutput}

## Instrucciones

Evalua la coherencia juridica de la respuesta. Responde con JSON:

```json
{
  "scores": {
    "jerarquia_normativa": 0.0,
    "primacia_ue": 0.0,
    "competencias": 0.0,
    "reserva_lo": 0.0,
    "vigencia": 0.0,
    "consistencia": 0.0,
    "fundamentacion": 0.0,
    "premise_validation": 0.0,
    "citation_alignment": 0.0
  },
  "overall_score": 0.0,
  "issues": [
    {
      "criterion": "nombre_criterio",
      "severity": "critical|high|medium|low",
      "description": "Explicacion concisa del problema",
      "quote": "Fragmento exacto de la respuesta problematico",
      "correction": "Como deberia reformularse"
    }
  ],
  "premise_issues": [
    {
      "premise": "texto de la premisa falsa del usuario",
      "correction": "correccion",
      "source": "norma/principio que la contradice"
    }
  ],
  "citation_alignment": [
    {
      "claim": "afirmacion del agente",
      "citation": "Art. X Ley Y",
      "status": "supported|misgrounded|unverifiable",
      "reason": "explicacion breve"
    }
  ],
  "is_coherent": true,
  "summary": "Resumen en 1 frase"
}
```

Si no hay problemas de coherencia juridica, devuelve scores altos y issues vacio. No inventes problemas donde no los hay.
PROMPT;
  }

  /**
   * Parsea la respuesta JSON del verificador.
   */
  protected function parseResponse(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
    $data = json_decode($text, TRUE);

    if (!$data) {
      return [
        'score' => 0.5,
        'issues' => [],
        'corrections' => [],
        'premise_issues' => [],
        'citation_alignment' => [],
      ];
    }

    return [
      'score' => (float) ($data['overall_score'] ?? 0.5),
      'issues' => $data['issues'] ?? [],
      'corrections' => array_filter(array_map(
        static fn(array $i): ?string => $i['correction'] ?? NULL,
        $data['issues'] ?? [],
      )),
      'scores_detail' => $data['scores'] ?? [],
      'is_coherent' => $data['is_coherent'] ?? TRUE,
      'summary' => $data['summary'] ?? '',
      'premise_issues' => $data['premise_issues'] ?? [],
      'citation_alignment' => $data['citation_alignment'] ?? [],
    ];
  }

  /**
   * Construye output con correcciones anotadas.
   */
  protected function buildCorrectedOutput(string $agentOutput, array $evaluation): string {
    $notice = "\n\n---\n**Aviso:** Se han detectado inconsistencias juridicas en esta respuesta. ";
    $notice .= "A continuacion se detallan las correcciones necesarias:\n\n";

    foreach ($evaluation['issues'] as $issue) {
      $notice .= "- **{$issue['criterion']}** [{$issue['severity']}]: {$issue['description']}\n";
      if (!empty($issue['correction'])) {
        $notice .= "  Correccion: {$issue['correction']}\n";
      }
    }

    // Anotar premisas falsas detectadas.
    if (!empty($evaluation['premise_issues'])) {
      $notice .= "\n**Premisas del usuario a verificar:**\n";
      foreach ($evaluation['premise_issues'] as $pi) {
        $notice .= "- Premisa: \"{$pi['premise']}\" — Correccion: {$pi['correction']}\n";
      }
    }

    $notice .= "\nSe recomienda verificar toda la respuesta con las fuentes oficiales antes de actuar.";

    return $agentOutput . $notice;
  }

}
