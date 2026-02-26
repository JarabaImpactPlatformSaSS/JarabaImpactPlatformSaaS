<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Psr\Log\LoggerInterface;

/**
 * LLM Re-Ranker Service (FIX-037).
 *
 * Uses tier `fast` (Haiku) to re-rank RAG candidates by relevance
 * to the user query. Dramatically improves relevance over basic
 * keyword-overlap re-ranking.
 */
class LlmReRankerService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected object $aiProvider,
        protected ModelRouterService $modelRouter,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Re-ranks candidates using LLM scoring.
     *
     * @param string $query
     *   The user query.
     * @param array $candidates
     *   Array of candidate documents, each with 'text', 'score', and metadata.
     * @param int $topK
     *   Number of top results to return.
     *
     * @return array
     *   Re-ranked candidates (top K).
     */
    public function reRank(string $query, array $candidates, int $topK = 5): array
    {
        if (empty($candidates) || count($candidates) <= 1) {
            return array_slice($candidates, 0, $topK);
        }

        // Limit candidates to avoid exceeding context window.
        $candidates = array_slice($candidates, 0, 20);

        try {
            // Build re-ranking prompt.
            $prompt = $this->buildReRankPrompt($query, $candidates);

            // Route to fast tier for cost efficiency.
            $routing = $this->modelRouter->route('reranking', $prompt, ['force_tier' => 'fast']);
            $provider = $this->aiProvider->createInstance($routing['provider_id']);

            $chatInput = new ChatInput([
                new ChatMessage('system', 'You are a relevance scoring system. Score each document for relevance to the query. Respond only in JSON.'),
                new ChatMessage('user', $prompt),
            ]);

            $response = $provider->chat($chatInput, $routing['model_id'], ['temperature' => 0.1]);
            $text = $response->getNormalized()->getText();

            // Parse scores.
            $scores = $this->parseScores($text, count($candidates));

            // Apply LLM scores to candidates.
            foreach ($candidates as $i => &$candidate) {
                $candidate['llm_relevance_score'] = $scores[$i] ?? 0.5;
                // Hybrid score: 60% LLM + 40% vector similarity.
                $vectorScore = $candidate['score'] ?? 0;
                $candidate['hybrid_score'] = (0.6 * ($scores[$i] ?? 0.5)) + (0.4 * $vectorScore);
            }
            unset($candidate);

            // Sort by hybrid score descending.
            usort($candidates, fn($a, $b) => ($b['hybrid_score'] ?? 0) <=> ($a['hybrid_score'] ?? 0));

            $this->logger->info('LLM re-ranking completed: @count candidates, returning top @k', [
                '@count' => count($candidates),
                '@k' => $topK,
            ]);

            return array_slice($candidates, 0, $topK);

        } catch (\Exception $e) {
            $this->logger->warning('LLM re-ranking failed, returning original order: @msg', ['@msg' => $e->getMessage()]);
            // Fallback: return original order.
            return array_slice($candidates, 0, $topK);
        }
    }

    /**
     * Builds the re-ranking prompt.
     */
    protected function buildReRankPrompt(string $query, array $candidates): string
    {
        $prompt = "Query: \"{$query}\"\n\n";
        $prompt .= "Score each document for relevance (0.0 to 1.0):\n\n";

        foreach ($candidates as $i => $candidate) {
            $text = mb_substr($candidate['text'] ?? '', 0, 500);
            $prompt .= "Document {$i}: {$text}\n\n";
        }

        $prompt .= "Respond in JSON: {\"scores\": [0.8, 0.3, ...]} (one score per document in order)";

        return $prompt;
    }

    /**
     * Parses relevance scores from LLM response.
     */
    protected function parseScores(string $text, int $expectedCount): array
    {
        // Clean markdown.
        $cleaned = preg_replace('/```(?:json)?\s*/is', '', $text);
        $cleaned = preg_replace('/\s*```/is', '', $cleaned);

        if (preg_match('/(\{[\s\S]*\})/m', $cleaned, $matches)) {
            $decoded = json_decode($matches[1], TRUE);
            if (isset($decoded['scores']) && is_array($decoded['scores'])) {
                $scores = array_map('floatval', $decoded['scores']);
                // Pad with 0.5 if not enough scores.
                while (count($scores) < $expectedCount) {
                    $scores[] = 0.5;
                }
                return $scores;
            }
        }

        // Fallback: equal scores.
        return array_fill(0, $expectedCount, 0.5);
    }

}
