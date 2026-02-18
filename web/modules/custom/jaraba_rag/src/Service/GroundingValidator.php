<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Validador de Grounding para prevenir alucinaciones del LLM.
 *
 * Este servicio implementa la Capa 4 de defensa (Output Guardrails):
 * - Verifica que cada claim en la respuesta esté soportado por el contexto
 * - Utiliza NLI (Natural Language Inference) para verificación
 * - Calcula score de confianza
 * - Identifica y marca claims no verificables
 *
 * @see docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md (Sección 7)
 */
class GroundingValidator
{

    /**
     * Tipos de verificación de claims.
     */
    public const ENTAILED = 'entailed';
    public const NEUTRAL = 'neutral';
    public const CONTRADICTED = 'contradicted';

    /**
     * Constructs a GroundingValidator object.
     */
    public function __construct(
        protected AiProviderPluginManager $aiProvider,
        protected LoggerChannelFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Valida que la respuesta esté grounded en el contexto.
     *
     * @param string $response
     *   Respuesta generada por el LLM.
     * @param array $context
     *   Contexto utilizado para generar la respuesta.
     *
     * @return array
     *   Resultado de validación:
     *   - 'is_valid': bool - Si la respuesta es válida.
     *   - 'confidence': float 0-1 - Score de confianza.
     *   - 'claims': array - Claims extraídos y su verificación.
     *   - 'hallucination_count': int - Número de alucinaciones detectadas.
     */
    public function validate(string $response, array $context): array
    {
        // Extraer claims (afirmaciones verificables) de la respuesta
        $claims = $this->extractClaims($response);

        if (empty($claims)) {
            // Sin claims verificables = asumimos válido
            return [
                'is_valid' => TRUE,
                'confidence' => 0.8,
                'claims' => [],
                'hallucination_count' => 0,
            ];
        }

        // Concatenar todo el contexto para verificación
        $contextText = $this->flattenContext($context);

        // Verificar cada claim
        $validatedClaims = [];
        $hallucinationCount = 0;

        foreach ($claims as $claim) {
            $verification = $this->verifyClaim($claim, $contextText);

            $validatedClaims[] = [
                'claim' => $claim,
                'verdict' => $verification['verdict'],
                'confidence' => $verification['confidence'],
                'source' => $verification['source'] ?? NULL,
            ];

            if (
                $verification['verdict'] === self::CONTRADICTED ||
                ($verification['verdict'] === self::NEUTRAL && $verification['confidence'] < 0.5)
            ) {
                $hallucinationCount++;
            }
        }

        // Calcular confianza global
        $avgConfidence = array_sum(array_column($validatedClaims, 'confidence')) / count($validatedClaims);

        // Es válido si no hay alucinaciones
        $isValid = $hallucinationCount === 0;

        return [
            'is_valid' => $isValid,
            'confidence' => $avgConfidence,
            'claims' => $validatedClaims,
            'hallucination_count' => $hallucinationCount,
        ];
    }

    /**
     * Extrae claims verificables de una respuesta.
     *
     * Un claim es una afirmación factual que puede ser verificada:
     * - Precios ("€12.50")
     * - Características ("acidez 0.2%")
     * - Afirmaciones sobre productos ("elaborado con aceitunas Picual")
     *
     * @param string $response
     *   Respuesta del LLM.
     *
     * @return array
     *   Array de claims (strings).
     */
    protected function extractClaims(string $response): array
    {
        $claims = [];

        // Dividir en oraciones
        $sentences = preg_split('/(?<=[.!?])\s+/', $response, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($sentences as $sentence) {
            // Filtrar saludos y preguntas (no son claims)
            if ($this->isClaim($sentence)) {
                $claims[] = trim($sentence);
            }
        }

        return $claims;
    }

    /**
     * Determina si una oración es un claim verificable.
     */
    protected function isClaim(string $sentence): bool
    {
        // Ignorar preguntas
        if (str_ends_with(trim($sentence), '?')) {
            return FALSE;
        }

        // Ignorar saludos y expresiones comunes
        $nonClaims = [
            '¡hola',
            'puedo ayudarte',
            'no tengo esa información',
            'gracias',
            'de nada',
        ];

        $lowerSentence = mb_strtolower($sentence);
        foreach ($nonClaims as $pattern) {
            if (str_contains($lowerSentence, $pattern)) {
                return FALSE;
            }
        }

        // Es un claim si contiene información factual típica
        $claimIndicators = [
            '€',           // Precios
            'precio',
            '%',           // Porcentajes
            'ml',          // Unidades
            'kg',
            'gramos',
            'elaborado',   // Proceso
            'producido',
            'contiene',    // Ingredientes
            'incluye',
        ];

        foreach ($claimIndicators as $indicator) {
            if (str_contains($lowerSentence, $indicator)) {
                return TRUE;
            }
        }

        // Por defecto, oraciones largas probablemente son claims
        return strlen($sentence) > 50;
    }

    /**
     * Verifica si un claim está soportado por el contexto.
     *
     * @param string $claim
     *   El claim a verificar.
     * @param string $contextText
     *   Texto del contexto concatenado.
     *
     * @return array
     *   - 'verdict': entailed|neutral|contradicted
     *   - 'confidence': float 0-1
     *   - 'source': fragmento de contexto que soporta el claim (si existe)
     */
    protected function verifyClaim(string $claim, string $contextText): array
    {
        // Enfoque 1: Verificación basada en overlap de texto
        // (Simplificado - en producción usaríamos NLI con LLM)

        $claimLower = mb_strtolower($claim);
        $contextLower = mb_strtolower($contextText);

        // Extraer términos clave del claim
        $keyTerms = $this->extractKeyTerms($claim);

        // Contar cuántos términos clave están en el contexto
        $matchedTerms = 0;
        $matchedSource = '';

        foreach ($keyTerms as $term) {
            if (str_contains($contextLower, mb_strtolower($term))) {
                $matchedTerms++;
                // Encontrar fragmento del contexto que contiene el término
                $pos = mb_stripos($contextText, $term);
                if ($pos !== FALSE) {
                    $start = max(0, $pos - 50);
                    $length = strlen($term) + 100;
                    $matchedSource = mb_substr($contextText, $start, $length);
                }
            }
        }

        // Calcular confianza basada en términos coincidentes
        $confidence = count($keyTerms) > 0 ? $matchedTerms / count($keyTerms) : 0;

        // Determinar veredicto
        if ($confidence >= 0.7) {
            return [
                'verdict' => self::ENTAILED,
                'confidence' => $confidence,
                'source' => $matchedSource,
            ];
        } elseif ($confidence >= 0.3) {
            return [
                'verdict' => self::NEUTRAL,
                'confidence' => $confidence,
                'source' => $matchedSource,
            ];
        } else {
            return [
                'verdict' => self::CONTRADICTED,
                'confidence' => $confidence,
                'source' => NULL,
            ];
        }
    }

    /**
     * Extrae términos clave de un claim.
     *
     * @param string $claim
     *   El claim del cual extraer términos.
     *
     * @return array
     *   Array de términos clave.
     */
    protected function extractKeyTerms(string $claim): array
    {
        $terms = [];

        // Extraer números con unidades (precios, porcentajes, etc.)
        preg_match_all('/€?\d+[.,]?\d*\s*(?:%|ml|kg|g|gramos)?/', $claim, $matches);
        $terms = array_merge($terms, $matches[0]);

        // Extraer palabras capitalizadas (nombres de productos, lugares)
        preg_match_all('/\b[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*/', $claim, $matches);
        $terms = array_merge($terms, $matches[0]);

        // Extraer términos entre comillas
        preg_match_all('/"([^"]+)"/', $claim, $matches);
        $terms = array_merge($terms, $matches[1]);

        // Filtrar términos muy cortos o comunes
        $stopWords = ['el', 'la', 'los', 'las', 'un', 'una', 'de', 'del', 'en', 'con', 'para'];
        $terms = array_filter($terms, function ($term) use ($stopWords) {
            $termLower = mb_strtolower(trim($term));
            return strlen($term) > 2 && !in_array($termLower, $stopWords);
        });

        return array_unique($terms);
    }

    /**
     * Aplana el contexto a texto plano.
     */
    protected function flattenContext(array $context): string
    {
        $texts = [];

        foreach ($context as $item) {
            if (isset($item['chunk_text'])) {
                $texts[] = $item['chunk_text'];
            }
            if (isset($item['title'])) {
                $texts[] = $item['title'];
            }
        }

        return implode("\n\n", $texts);
    }

    /**
     * Valida con NLI usando LLM (versión avanzada).
     *
     * AI-07: Sanitiza inputs antes de interpolar en prompts NLI.
     *
     * @todo Implementar llamada LLM cuando se necesite mayor precisión.
     */
    protected function validateWithNli(string $claim, string $context): array
    {
        // AI-07: Sanitizar y limitar inputs antes de interpolar en prompt.
        $sanitizedContext = $this->sanitizeNliInput($context, 8000);
        $sanitizedClaim = $this->sanitizeNliInput($claim, 1000);

        // Prompt para NLI con LLM
        $prompt = <<<PROMPT
Analiza si la siguiente afirmación está SOPORTADA por el contexto.

CONTEXTO:
{$sanitizedContext}

AFIRMACIÓN:
{$sanitizedClaim}

Responde SOLO con:
- ENTAILED: La afirmación se puede inferir del contexto
- NEUTRAL: No hay suficiente información para confirmar o negar
- CONTRADICTED: La afirmación contradice el contexto

Respuesta:
PROMPT;

        // AUDIT-TODO-RESOLVED: LLM-based NLI validation via Drupal AI module.
        try {
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');

            if (!$defaults) {
                $this->loggerFactory->get('jaraba_rag')->warning(
                    'No chat AI provider configured for NLI grounding validation.'
                );
                return [
                    'verdict' => self::NEUTRAL,
                    'confidence' => 0.5,
                    'source' => NULL,
                ];
            }

            /** @var \Drupal\ai\OperationType\Chat\ChatInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            $provider->setConfiguration([
                'temperature' => 0.0,
                'max_tokens' => 50,
            ]);

            $chatInput = new ChatInput([
                new ChatMessage('system', 'You are a Natural Language Inference classifier. Respond with ONLY one word: ENTAILED, NEUTRAL, or CONTRADICTED.'),
                new ChatMessage('user', $prompt),
            ]);

            $modelId = $defaults['model_id'] ?? 'gpt-4o-mini';
            $result = $provider->chat($chatInput, $modelId);
            $responseText = trim($result->getNormalized()->getText());

            // Parse the NLI verdict from the LLM response.
            $responseUpper = strtoupper($responseText);
            $verdict = self::NEUTRAL;
            $confidence = 0.5;

            if (str_contains($responseUpper, 'ENTAILED')) {
                $verdict = self::ENTAILED;
                $confidence = 0.9;
            }
            elseif (str_contains($responseUpper, 'CONTRADICTED')) {
                $verdict = self::CONTRADICTED;
                $confidence = 0.9;
            }
            elseif (str_contains($responseUpper, 'NEUTRAL')) {
                $verdict = self::NEUTRAL;
                $confidence = 0.6;
            }

            return [
                'verdict' => $verdict,
                'confidence' => $confidence,
                'source' => NULL,
            ];
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('jaraba_rag')->error(
                'NLI grounding validation LLM call failed: @msg',
                ['@msg' => $e->getMessage()]
            );

            // Fallback to NEUTRAL on error rather than blocking the response.
            return [
                'verdict' => self::NEUTRAL,
                'confidence' => 0.5,
                'source' => NULL,
            ];
        }
    }

    /**
     * Sanitiza un input antes de interpolarlo en un prompt NLI.
     *
     * AI-07: Previene inyección de instrucciones eliminando patrones
     * que podrían alterar el comportamiento del LLM, y limita longitud.
     *
     * @param string $input
     *   Texto a sanitizar.
     * @param int $maxLength
     *   Longitud máxima en caracteres.
     *
     * @return string
     *   Texto sanitizado.
     */
    protected function sanitizeNliInput(string $input, int $maxLength): string
    {
        // Limitar longitud.
        $input = mb_substr($input, 0, $maxLength);

        // Eliminar caracteres de control (excepto newlines y tabs).
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Eliminar patrones de inyección de prompts.
        $dangerousPatterns = [
            '/ignore\s+(all\s+)?(previous|above|prior)\s+(instructions?|rules?|prompts?)/i',
            '/you\s+are\s+now/i',
            '/new\s+instructions?:/i',
            '/system\s*:/i',
            '/\bignora\b.*\b(instrucciones|reglas|anteriores)\b/i',
            '/\bahora\s+eres\b/i',
            '/\bnuevas?\s+instrucciones?\b/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '[FILTERED]', $input);
        }

        return trim($input);
    }

}
