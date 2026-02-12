<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para generar FAQs automáticas desde preguntas frecuentes.
 *
 * Usa el CopilotQueryLoggerService para obtener preguntas frecuentes
 * y ClaudeApiService para generar respuestas estructuradas.
 *
 * Las FAQs generadas se guardan como nodos de tipo 'faq' o en un
 * campo de configuración según la vertical.
 */
class FaqGeneratorService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected CopilotQueryLoggerService $queryLogger,
        protected ClaudeApiService $claudeApi,
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
     */
    protected function callLlmForFaqs(string $questionsText, int $limit): array
    {
        $systemPrompt = <<<PROMPT
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
PROMPT;

        $userMessage = <<<MSG
Genera un máximo de {$limit} FAQs a partir de estas preguntas frecuentes de usuarios:

{$questionsText}

Recuerda: responde SOLO con el JSON array de FAQs.
MSG;

        try {
            // Usar chat() que es el método público de ClaudeApiService
            // El prompt completo va como mensaje, modo 'general'
            $fullPrompt = $systemPrompt . "\n\n" . $userMessage;
            $response = $this->claudeApi->chat($fullPrompt, [], 'general');

            // chat() devuelve ['text' => ..., 'mode' => ..., 'suggestions' => ...]
            $text = $response['text'] ?? '';

            // Intentar parsear JSON
            $faqs = $this->parseJsonResponse($text);

            if (!empty($faqs)) {
                $this->logger->info('FaqGenerator: Generated @count FAQs successfully', [
                    '@count' => count($faqs),
                ]);
                return $faqs;
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('FaqGenerator: Error calling LLM: @error', [
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
