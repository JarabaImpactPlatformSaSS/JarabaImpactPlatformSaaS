<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de evaluación de calidad de respuestas IA.
 *
 * PROPÓSITO:
 * Implementa el patrón LLM-as-Judge para evaluación automatizada
 * de la calidad de las respuestas generadas por los agentes IA.
 *
 * CRITERIOS DE EVALUACIÓN (por defecto):
 * - relevance (25%): Qué tan bien responde al prompt
 * - accuracy (25%): Corrección factual y lógica
 * - clarity (20%): Claridad y estructura
 * - brand_alignment (15%): Alineación con Brand Voice
 * - actionability (15%): Utilidad práctica
 *
 * FLUJO:
 * 1. Recibe prompt original y respuesta generada
 * 2. Construye prompt de evaluación con criterios
 * 3. Llama a un LLM evaluador (temperatura baja: 0.3)
 * 4. Parsea resultado JSON con scores y feedback
 * 5. Opcionalmente actualiza el log con quality_score
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class QualityEvaluatorService
{

    /**
     * El gestor de proveedores IA.
     *
     * @var \Drupal\ai\AiProviderPluginManager
     */
    protected AiProviderPluginManager $aiProvider;

    /**
     * La factoría de configuración.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El servicio de observabilidad.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService
     */
    protected AIObservabilityService $observability;

    /**
     * El logger para registrar errores.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un QualityEvaluatorService.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     * @param \Drupal\jaraba_ai_agents\Service\AIObservabilityService $observability
     *   El servicio de observabilidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        AIObservabilityService $observability,
        LoggerInterface $logger,
    ) {
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->observability = $observability;
        $this->logger = $logger;
    }

    /**
     * Evalúa la calidad de una respuesta IA.
     *
     * @param string $prompt
     *   El prompt original enviado al agente.
     * @param string $response
     *   La respuesta IA a evaluar.
     * @param array $criteria
     *   Criterios de evaluación (opcional, usa defaults).
     * @param array $context
     *   Contexto adicional para la evaluación:
     *   - agent_id: ID del agente.
     *   - action: Acción ejecutada.
     *   - brand_voice: Brand Voice usado.
     *
     * @return array
     *   Resultado de evaluación:
     *   - success: bool
     *   - data: array con scores, strengths, improvements
     */
    public function evaluate(
        string $prompt,
        string $response,
        array $criteria = [],
        array $context = [],
    ): array {
        $criteria = $criteria ?: $this->getDefaultCriteria();

        $evaluationPrompt = $this->buildEvaluationPrompt($prompt, $response, $criteria, $context);

        try {
            $result = $this->callEvaluator($evaluationPrompt);

            if ($result['success']) {
                $evaluation = $this->parseEvaluation($result['data']['text']);
                $evaluation['raw_evaluation'] = $result['data']['text'];

                return [
                    'success' => TRUE,
                    'data' => $evaluation,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error en evaluación de calidad: @msg', ['@msg' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Evalúa y registra la puntuación de calidad en el log.
     *
     * Útil para evaluación automática post-ejecución donde
     * se quiere persistir el score en el ai_usage_log.
     *
     * @param int $logId
     *   El ID del log de uso IA a actualizar.
     * @param string $prompt
     *   El prompt original.
     * @param string $response
     *   La respuesta a evaluar.
     *
     * @return array
     *   Resultado de la evaluación.
     */
    public function evaluateAndLog(int $logId, string $prompt, string $response): array
    {
        $evaluation = $this->evaluate($prompt, $response);

        if ($evaluation['success'] && isset($evaluation['data']['overall_score'])) {
            // Actualizar el log con la puntuación de calidad.
            $this->updateLogQualityScore($logId, $evaluation['data']['overall_score']);
        }

        return $evaluation;
    }

    /**
     * Construye el prompt de evaluación.
     *
     * @param string $prompt
     *   El prompt original.
     * @param string $response
     *   La respuesta a evaluar.
     * @param array $criteria
     *   Los criterios de evaluación.
     * @param array $context
     *   Contexto adicional.
     *
     * @return string
     *   El prompt formateado para el evaluador.
     */
    protected function buildEvaluationPrompt(
        string $prompt,
        string $response,
        array $criteria,
        array $context,
    ): string {
        $criteriaList = '';
        foreach ($criteria as $name => $definition) {
            $criteriaList .= "- **{$name}**: {$definition['description']} (peso: {$definition['weight']})\n";
        }

        $contextInfo = '';
        if (!empty($context['agent_id'])) {
            $contextInfo .= "Agente: {$context['agent_id']}\n";
        }
        if (!empty($context['action'])) {
            $contextInfo .= "Acción: {$context['action']}\n";
        }
        if (!empty($context['brand_voice'])) {
            $contextInfo .= "Brand Voice: {$context['brand_voice']}\n";
        }

        return <<<EOT
Eres un evaluador experto de respuestas IA. Evalúa la siguiente respuesta según los criterios proporcionados.

## PROMPT ORIGINAL
{$prompt}

## RESPUESTA IA A EVALUAR
{$response}

## CONTEXTO
{$contextInfo}

## CRITERIOS DE EVALUACIÓN
{$criteriaList}

## INSTRUCCIONES
1. Puntúa cada criterio de 0.0 a 1.0
2. Proporciona una justificación breve para cada puntuación
3. Calcula la puntuación global ponderada
4. Identifica mejoras específicas si la puntuación es < 0.8

## FORMATO DE SALIDA REQUERIDO (JSON)
{
  "criteria_scores": {
    "nombre_criterio": {"score": 0.0-1.0, "justification": "..."},
    ...
  },
  "overall_score": 0.0-1.0,
  "strengths": ["...", "..."],
  "improvements": ["...", "..."],
  "summary": "Evaluación general breve"
}
EOT;
    }

    /**
     * Obtiene los criterios de evaluación por defecto.
     *
     * @return array
     *   Array de criterios con descripción y peso.
     */
    protected function getDefaultCriteria(): array
    {
        return [
            'relevance' => [
                'description' => 'Qué tan bien responde al prompt original',
                'weight' => 0.25,
            ],
            'accuracy' => [
                'description' => 'Corrección factual y consistencia lógica',
                'weight' => 0.25,
            ],
            'clarity' => [
                'description' => 'Claridad, buena estructura, fácil de entender',
                'weight' => 0.20,
            ],
            'brand_alignment' => [
                'description' => 'Coincide con el tono y Brand Voice esperado',
                'weight' => 0.15,
            ],
            'actionability' => [
                'description' => 'Salida práctica y usable para su propósito',
                'weight' => 0.15,
            ],
        ];
    }

    /**
     * Llama al LLM evaluador.
     *
     * @param string $prompt
     *   El prompt de evaluación.
     *
     * @return array
     *   Respuesta del evaluador.
     */
    protected function callEvaluator(string $prompt): array
    {
        $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');

        if (empty($defaults)) {
            return ['success' => FALSE, 'error' => 'No hay proveedor IA configurado.'];
        }

        $provider = $this->aiProvider->createInstance($defaults['provider_id']);

        $chatInput = new ChatInput([
            new ChatMessage('system', 'Eres un evaluador experto. Siempre responde con JSON válido únicamente.'),
            new ChatMessage('user', $prompt),
        ]);

        // Temperatura baja para evaluación consistente.
        $configuration = ['temperature' => 0.3];

        $response = $provider->chat($chatInput, $defaults['model_id'], $configuration);
        $text = $response->getNormalized()->getText();

        return [
            'success' => TRUE,
            'data' => ['text' => $text],
        ];
    }

    /**
     * Parsea la respuesta de evaluación.
     *
     * @param string $text
     *   El texto de respuesta del evaluador.
     *
     * @return array
     *   Evaluación parseada.
     */
    protected function parseEvaluation(string $text): array
    {
        // Extraer JSON de la respuesta.
        $json = $text;

        if (preg_match('/```json?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $json = $matches[0];
        }

        $decoded = json_decode($json, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'overall_score' => NULL,
                'parse_error' => 'No se pudo parsear la respuesta de evaluación',
            ];
        }

        return [
            'criteria_scores' => $decoded['criteria_scores'] ?? [],
            'overall_score' => (float) ($decoded['overall_score'] ?? 0),
            'strengths' => $decoded['strengths'] ?? [],
            'improvements' => $decoded['improvements'] ?? [],
            'summary' => $decoded['summary'] ?? '',
        ];
    }

    /**
     * Actualiza una entrada de log con la puntuación de calidad.
     *
     * @param int $logId
     *   El ID del log.
     * @param float $score
     *   La puntuación de calidad (0-1).
     */
    protected function updateLogQualityScore(int $logId, float $score): void
    {
        try {
            $storage = \Drupal::entityTypeManager()->getStorage('ai_usage_log');
            $log = $storage->load($logId);

            if ($log) {
                $log->set('quality_score', $score);
                $log->save();
            }
        } catch (\Exception $e) {
            $this->logger->error('Error al actualizar puntuación de calidad: @msg', ['@msg' => $e->getMessage()]);
        }
    }

    /**
     * Obtiene estadísticas de calidad para un período.
     *
     * @param string $period
     *   El período: day, week, month, year.
     *
     * @return array
     *   Estadísticas de calidad.
     */
    public function getQualityStats(string $period = 'month'): array
    {
        $stats = $this->observability->getStats($period);

        return [
            'avg_quality_score' => $stats['avg_quality_score'],
            'total_evaluated' => $stats['total_executions'],
            'high_quality' => 0, // Requeriría query a BD para score >= 0.8
            'needs_improvement' => 0, // Requeriría query a BD para score < 0.6
        ];
    }

}
