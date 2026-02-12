<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio RAG (Retrieval-Augmented Generation) para consultas legales.
 *
 * Orquesta el pipeline completo de RAG legal:
 * 1. Genera embedding de la pregunta del usuario.
 * 2. Busca chunks similares en Qdrant (filtrados por scope/areas).
 * 3. Construye contexto a partir de los chunks mas relevantes.
 * 4. Invoca al LLM con system prompt + contexto + pregunta.
 * 5. Formatea citas con LegalCitationService.
 * 6. Anade disclaimer con LegalDisclaimerService.
 *
 * ARQUITECTURA:
 * - Embeddings via LegalEmbeddingService -> Qdrant.
 * - LLM via Drupal AI module (chat operation type).
 * - Citas y disclaimers gestionados por servicios dedicados.
 * - Configuracion: rag_max_chunks, rag_similarity_threshold.
 */
class LegalRagService {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalEmbeddingService $embeddingService
   *   Servicio de embeddings para busqueda semantica.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalCitationService $citationService
   *   Servicio de formateo de citas legales.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalDisclaimerService $disclaimerService
   *   Servicio de disclaimers legales.
   * @param \Drupal\ai\AiProviderPluginManager $aiProvider
   *   Gestor de proveedores de IA.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LegalEmbeddingService $embeddingService,
    protected LegalCitationService $citationService,
    protected LegalDisclaimerService $disclaimerService,
    protected AiProviderPluginManager $aiProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta el pipeline RAG completo para una consulta legal.
   *
   * @param string $question
   *   Pregunta del usuario en lenguaje natural.
   * @param array $filters
   *   Filtros opcionales:
   *   - scope: (string) Ambito normativo (estatal, autonomico).
   *   - subject_areas: (array) Areas tematicas para filtrar.
   *
   * @return array
   *   Respuesta completa con:
   *   - answer: (string) Respuesta generada por el LLM.
   *   - citations: (array) Citas formateadas de las fuentes usadas.
   *   - disclaimer: (string) Texto de descargo de responsabilidad.
   *   - confidence: (float) Confianza estimada (0-1).
   *   - chunks_used: (int) Numero de chunks utilizados.
   *   - model_used: (string) Modelo de LLM utilizado.
   *   - tokens_input: (int) Tokens de entrada estimados.
   *   - tokens_output: (int) Tokens de salida estimados.
   */
  public function query(string $question, array $filters = []): array {
    $config = $this->configFactory->get('jaraba_legal_knowledge.settings');
    $maxChunks = (int) ($config->get('rag_max_chunks') ?: 10);
    $threshold = (float) ($config->get('rag_similarity_threshold') ?: 0.75);

    try {
      // 1. Buscar chunks similares en Qdrant.
      $searchResults = $this->embeddingService->searchSimilar(
        $question,
        $maxChunks,
        $threshold
      );

      if (empty($searchResults)) {
        $this->logger->info('RAG legal: sin resultados para la consulta: "@question".', [
          '@question' => mb_substr($question, 0, 100),
        ]);

        return [
          'answer' => 'No se encontraron normas relevantes para su consulta. Intente reformular la pregunta o ampliar los criterios de busqueda.',
          'citations' => [],
          'disclaimer' => $this->disclaimerService->getDisclaimer(),
          'confidence' => 0.0,
          'chunks_used' => 0,
          'model_used' => '',
          'tokens_input' => 0,
          'tokens_output' => 0,
        ];
      }

      // 2. Construir contexto a partir de los chunks.
      $context = $this->buildContext($searchResults);
      $chunkIds = array_map(fn($r) => $r['payload']['chunk_id'] ?? NULL, $searchResults);
      $chunkIds = array_filter($chunkIds);

      // 3. Llamar al LLM con system prompt + contexto + pregunta.
      $systemPrompt = $this->buildSystemPrompt();
      $userPrompt = "CONTEXTO NORMATIVO:\n" . $context . "\n\nPREGUNTA: " . $question;

      $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
      if (!$defaults) {
        throw new \RuntimeException('No hay proveedor de chat configurado.');
      }

      /** @var \Drupal\ai\OperationType\Chat\ChatInterface $provider */
      $provider = $this->aiProvider->createInstance($defaults['provider_id']);
      $provider->setConfiguration([
        'temperature' => 0.2,
        'max_tokens' => 1000,
      ]);

      $chatInput = new ChatInput([
        new ChatMessage('system', $systemPrompt),
        new ChatMessage('user', $userPrompt),
      ]);

      $modelId = $defaults['model_id'] ?? 'gpt-4o-mini';
      $result = $provider->chat($chatInput, $modelId);
      $message = $result->getNormalized();
      $answer = $message->getText();

      // 4. Formatear citas.
      $citations = $this->citationService->formatCitations($chunkIds);

      // 5. Calcular confianza basada en scores de similaridad.
      $scores = array_map(fn($r) => $r['score'], $searchResults);
      $avgScore = !empty($scores) ? array_sum($scores) / count($scores) : 0.0;
      $confidence = min(1.0, $avgScore);

      // 6. Estimacion de tokens.
      $tokensInput = (int) ceil(str_word_count($systemPrompt . $userPrompt) * 1.3);
      $tokensOutput = (int) ceil(str_word_count($answer) * 1.3);

      $this->logger->info('RAG legal: consulta procesada con @chunks chunks, confianza @confidence.', [
        '@chunks' => count($searchResults),
        '@confidence' => round($confidence, 2),
      ]);

      return [
        'answer' => $answer,
        'citations' => $citations,
        'disclaimer' => $this->disclaimerService->getDisclaimer(),
        'confidence' => round($confidence, 4),
        'chunks_used' => count($searchResults),
        'model_used' => $modelId,
        'tokens_input' => $tokensInput,
        'tokens_output' => $tokensOutput,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en RAG legal: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'answer' => 'Se produjo un error al procesar su consulta. Intente de nuevo mas tarde.',
        'citations' => [],
        'disclaimer' => $this->disclaimerService->getDisclaimer(),
        'confidence' => 0.0,
        'chunks_used' => 0,
        'model_used' => '',
        'tokens_input' => 0,
        'tokens_output' => 0,
      ];
    }
  }

  /**
   * Construye el system prompt para el asistente juridico.
   *
   * @return string
   *   System prompt en castellano con instrucciones para el LLM.
   */
  protected function buildSystemPrompt(): string {
    return <<<PROMPT
Eres un asistente juridico especializado en derecho espanol. Tu funcion es responder consultas legales basandote EXCLUSIVAMENTE en el contexto normativo proporcionado.

## REGLAS INQUEBRANTABLES

1. SOLO CONTEXTO: Responde UNICAMENTE con informacion del CONTEXTO NORMATIVO proporcionado. NUNCA inventes ni extrapoles.

2. CITAS OBLIGATORIAS: Cada afirmacion debe citar la fuente. Formato: [Art. X, Titulo de la Norma].

3. HONESTIDAD: Si el contexto no contiene informacion suficiente para responder, indica claramente:
   "No dispongo de informacion normativa suficiente para responder a esta consulta con precision."

4. PRECISION: Usa terminologia juridica precisa. No simplifiques en exceso conceptos legales complejos.

5. ESTRUCTURA: Organiza la respuesta de forma clara:
   - Respuesta directa a la pregunta.
   - Fundamento normativo con citas.
   - Matices o excepciones relevantes si los hay.

6. ACTUALIZACION: Si el contexto incluye fechas de vigencia, mencionarlas. Advierte si una norma podria estar derogada o modificada.

7. AMBITO: Solo hablas de derecho espanol. No menciones legislacion de otros paises salvo referencia explicita en el contexto.

8. IDIOMA: Responde siempre en castellano.
PROMPT;
  }

  /**
   * Construye el contexto formateado a partir de los chunks recuperados.
   *
   * @param array $chunks
   *   Resultados de la busqueda semantica con payload.
   *
   * @return string
   *   Texto formateado con el contenido de cada chunk y sus metadatos.
   */
  protected function buildContext(array $chunks): string {
    $lines = [];

    foreach ($chunks as $index => $chunk) {
      $payload = $chunk['payload'] ?? [];
      $num = $index + 1;
      $normTitle = $payload['norm_title'] ?? 'Norma sin titulo';
      $articleNumber = $payload['article_number'] ?? NULL;
      $text = $payload['text'] ?? '';
      $score = round($chunk['score'] ?? 0, 3);

      $header = "FUENTE {$num}: {$normTitle}";
      if ($articleNumber) {
        $header .= " - Art. {$articleNumber}";
      }
      $header .= " (relevancia: {$score})";

      $lines[] = $header;
      $lines[] = $text;
      $lines[] = '';
    }

    return implode("\n", $lines);
  }

}
