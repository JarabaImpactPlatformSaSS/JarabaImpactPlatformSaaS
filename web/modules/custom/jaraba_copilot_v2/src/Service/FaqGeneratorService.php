<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para generar FAQs automáticas desde preguntas frecuentes.
 *
 * Usa el CopilotQueryLoggerService para obtener preguntas frecuentes
 * y el framework ai.provider de Drupal para generar respuestas via LLM.
 *
 * Las FAQs generadas se guardan como nodos de tipo 'faq' o en un
 * campo de configuración según la vertical.
 *
 * FIX-012: Migrado de ClaudeApiService (HTTP directo a Anthropic) al
 * framework ai.provider para beneficiarse de: failover multi-proveedor,
 * circuit breaker, cost tracking centralizado y gestión unificada de API keys.
 */
class FaqGeneratorService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService $queryLogger
     *   Servicio de logging de queries del copiloto.
     * @param object|null $aiProvider
     *   Gestor de proveedores IA (ai.provider). Opcional para entornos sin módulo AI.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger del canal jaraba_copilot_v2.
     */
    public function __construct(
        protected CopilotQueryLoggerService $queryLogger,
        protected ?object $aiProvider,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera FAQs a partir de las preguntas más frecuentes.
     *
     * @param int $days
     *   Días a considerar para preguntas frecuentes.
     * @param int $limit
     *   Número máximo de FAQs a generar.
     * @param string $source
     *   Fuente del copiloto (all, public, emprendimiento, empleabilidad).
     *
     * @return array
     *   Array de FAQs generadas con 'question', 'answer', 'category'.
     */
    public function generateFaqs(int $days = 30, int $limit = 10, string $source = 'all'): array
    {
        // Obtener preguntas frecuentes filtradas por source
        $frequentQuestions = $this->queryLogger->getFrequentQuestions($days, $limit * 2, $source);

        if (empty($frequentQuestions)) {
            return [
                'success' => FALSE,
                'message' => 'No hay suficientes preguntas frecuentes para generar FAQs.',
                'faqs' => [],
            ];
        }

        // Preparar contexto para el LLM
        $questionsText = $this->formatQuestionsForPrompt($frequentQuestions);

        // Generar FAQs vía LLM
        $faqs = $this->callLlmForFaqs($questionsText, $limit);

        return [
            'success' => TRUE,
            'message' => sprintf('Se generaron %d FAQs a partir de %d preguntas frecuentes.', count($faqs), count($frequentQuestions)),
            'faqs' => $faqs,
            'source_questions' => count($frequentQuestions),
        ];
    }

    /**
     * Formatea las preguntas para el prompt del LLM.
     */
    protected function formatQuestionsForPrompt(array $questions): string
    {
        $lines = [];
        foreach ($questions as $q) {
            $count = $q->count ?? 1;
            $query = $q->query_prefix ?? $q->query ?? '';
            $lines[] = "- ({$count}x) \"{$query}\"";
        }
        return implode("\n", $lines);
    }

    /**
     * Llama al LLM para generar FAQs estructuradas.
     *
     * FIX-012: Usa ai.provider (ChatInput/ChatMessage) en lugar de
     * ClaudeApiService HTTP directo. Incluye AIIdentityRule.
     */
    protected function callLlmForFaqs(string $questionsText, int $limit): array
    {
        if (!$this->aiProvider) {
            $this->logger->error('FaqGenerator: ai.provider no disponible. Módulo AI no instalado.');
            return [];
        }

        $systemPrompt = AIIdentityRule::apply(<<<PROMPT
Eres un experto en comunicación y UX. Tu tarea es generar FAQs claras y útiles para una plataforma SaaS llamada "Jaraba Impact Platform".

La plataforma tiene las siguientes verticales:
- Empleabilidad (JobSeekers, Recruiters)
- Emprendimiento (Entrepreneurs, Mentors)
- Comercio (Producers, Buyers)

A partir de las preguntas frecuentes de los usuarios, genera FAQs bien estructuradas.

REGLAS:
1. Agrupa preguntas similares en una sola FAQ
2. Escribe respuestas claras, concisas y útiles (max 3 párrafos)
3. Usa lenguaje amigable pero profesional
4. Incluye CTAs cuando sea apropiado (ej: "Visita tu dashboard en...")
5. Categoriza cada FAQ (general, empleabilidad, emprendimiento, comercio, cuenta)

FORMATO DE RESPUESTA (JSON array):
[
  {
    "question": "¿Cómo puedo...?",
    "answer": "Para hacer X, sigue estos pasos...",
    "category": "general"
  }
]

Solo responde con el JSON, sin explicaciones adicionales.
PROMPT);

        $userMessage = <<<MSG
Genera un máximo de {$limit} FAQs a partir de estas preguntas frecuentes de usuarios:

{$questionsText}

Recuerda: responde SOLO con el JSON array de FAQs.
MSG;

        try {
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
            if (empty($defaults)) {
                $this->logger->error('FaqGenerator: No hay proveedor IA configurado para operación chat.');
                return [];
            }

            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            $chatInput = new ChatInput([
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $userMessage),
            ]);

            $response = $provider->chat($chatInput, $defaults['model_id'], [
                'temperature' => 0.5,
            ]);

            $text = $response->getNormalized()->getText();

            // Intentar parsear JSON.
            $faqs = $this->parseJsonResponse($text);

            if (!empty($faqs)) {
                $this->logger->info('FaqGenerator: Generated @count FAQs successfully via ai.provider', [
                    '@count' => count($faqs),
                ]);
                return $faqs;
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('FaqGenerator: Error calling LLM via ai.provider: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }


    /**
     * Parsea la respuesta JSON del LLM.
     */
    protected function parseJsonResponse(string $text): array
    {
        // Limpiar posibles prefijos/sufijos markdown
        $text = preg_replace('/^```json\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);

        $decoded = json_decode($text, TRUE);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Validar estructura
            $valid = [];
            foreach ($decoded as $faq) {
                if (isset($faq['question'], $faq['answer'])) {
                    $valid[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'category' => $faq['category'] ?? 'general',
                    ];
                }
            }
            return $valid;
        }

        return [];
    }

    /**
     * Guarda las FAQs generadas como nodos (si existe el tipo).
     *
     * @param array $faqs
     *   Array de FAQs con question, answer, category.
     *
     * @return array
     *   Resultado con nodos creados.
     */
    public function saveFaqsAsNodes(array $faqs): array
    {
        $created = [];
        $nodeStorage = $this->entityTypeManager->getStorage('node');

        // Verificar si existe el tipo de contenido 'faq'
        $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
        $hasFaqType = isset($types['faq']);

        if (!$hasFaqType) {
            return [
                'success' => FALSE,
                'message' => 'El tipo de contenido "faq" no existe. FAQs no guardadas.',
                'created' => [],
            ];
        }

        foreach ($faqs as $faq) {
            try {
                $node = $nodeStorage->create([
                    'type' => 'faq',
                    'title' => $faq['question'],
                    'body' => [
                        'value' => $faq['answer'],
                        'format' => 'basic_html',
                    ],
                    'status' => NodeInterface::NOT_PUBLISHED, // Borrador para revisión
                    'uid' => 1,
                ]);

                // Si existe campo de categoría
                if ($node->hasField('field_faq_category')) {
                    $node->set('field_faq_category', $faq['category']);
                }

                $node->save();
                $created[] = [
                    'nid' => $node->id(),
                    'title' => $faq['question'],
                ];
            } catch (\Exception $e) {
                $this->logger->error('FaqGenerator: Error creating FAQ node: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => count($created) > 0,
            'message' => sprintf('Se crearon %d FAQs como borradores.', count($created)),
            'created' => $created,
        ];
    }

    /**
     * Preview de FAQs sin guardar (para revisión admin).
     */
    public function previewFaqs(int $limit = 5): array
    {
        return $this->generateFaqs(30, $limit);
    }

}
