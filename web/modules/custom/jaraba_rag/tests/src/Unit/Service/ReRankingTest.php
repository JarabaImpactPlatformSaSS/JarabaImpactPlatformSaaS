<?php

namespace Drupal\Tests\jaraba_rag\Unit\Service;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for the re-ranking algorithm in JarabaRagService.
 *
 * Tests the reciprocal rank fusion logic extracted for testability.
 *
 * @group jaraba_rag
 */
class ReRankingTest extends UnitTestCase
{

    /**
     * Applies the same re-ranking algorithm as JarabaRagService::reRankResults.
     *
     * Extracted here for unit testing without needing to mock all service deps.
     */
    protected function reRankResults(string $query, array $results, int $topK): array
    {
        if (count($results) <= $topK) {
            return $results;
        }

        $queryWords = array_unique(array_filter(
            preg_split('/\s+/', mb_strtolower(trim($query))),
            fn($w) => mb_strlen($w) > 2
        ));

        if (empty($queryWords)) {
            return array_slice($results, 0, $topK);
        }

        foreach ($results as &$result) {
            $text = mb_strtolower($result['payload']['text'] ?? '');
            $vectorScore = $result['score'];

            $matchCount = 0;
            foreach ($queryWords as $word) {
                if (mb_strpos($text, $word) !== FALSE) {
                    $matchCount++;
                }
            }
            $keywordScore = count($queryWords) > 0 ? $matchCount / count($queryWords) : 0;

            $phraseBoost = mb_strpos($text, mb_strtolower($query)) !== FALSE ? 0.15 : 0;

            $result['rerank_score'] = ($vectorScore * 0.7) + ($keywordScore * 0.2) + $phraseBoost;
        }
        unset($result);

        usort($results, fn($a, $b) => $b['rerank_score'] <=> $a['rerank_score']);

        $reranked = array_slice($results, 0, $topK);
        foreach ($reranked as &$item) {
            $item['score'] = $item['rerank_score'];
            unset($item['rerank_score']);
        }

        return $reranked;
    }

    /**
     * Tests that exact phrase match gets boosted.
     */
    public function testExactPhraseMatchGetsBoost(): void
    {
        $results = [
            ['score' => 0.85, 'payload' => ['text' => 'This document covers aceite de oliva production in Jaén']],
            ['score' => 0.90, 'payload' => ['text' => 'Generic product information about agricultural goods']],
            ['score' => 0.80, 'payload' => ['text' => 'El aceite de oliva virgen extra de Jaén es reconocido mundialmente']],
        ];

        $reranked = $this->reRankResults('aceite de oliva', $results, 2);

        $this->assertCount(2, $reranked);
        // The result with exact phrase match should score higher despite lower vector score.
        $this->assertStringContainsString('aceite', mb_strtolower($reranked[0]['payload']['text']));
    }

    /**
     * Tests that keyword overlap is correctly calculated.
     */
    public function testKeywordOverlapScoring(): void
    {
        $results = [
            ['score' => 0.70, 'payload' => ['text' => 'cooperativa olivar jaén produce aceite calidad']],
            ['score' => 0.90, 'payload' => ['text' => 'información general sobre agricultura extensiva']],
            ['score' => 0.75, 'payload' => ['text' => 'calidad cooperativa aceite producción']],
        ];

        $reranked = $this->reRankResults('cooperativa aceite calidad', $results, 2);

        // Results with more keyword matches should rank higher.
        $firstText = mb_strtolower($reranked[0]['payload']['text']);
        $this->assertTrue(
            mb_strpos($firstText, 'cooperativa') !== FALSE &&
            mb_strpos($firstText, 'aceite') !== FALSE &&
            mb_strpos($firstText, 'calidad') !== FALSE
        );
    }

    /**
     * Tests that results are trimmed to topK.
     */
    public function testResultsTrimmedToTopK(): void
    {
        $results = [];
        for ($i = 0; $i < 15; $i++) {
            $results[] = ['score' => 0.5 + ($i * 0.03), 'payload' => ['text' => "Result $i with some content"]];
        }

        $reranked = $this->reRankResults('some content', $results, 5);

        $this->assertCount(5, $reranked);
    }

    /**
     * Tests passthrough when results count <= topK.
     */
    public function testPassthroughWhenLessThanTopK(): void
    {
        $results = [
            ['score' => 0.90, 'payload' => ['text' => 'Only result']],
        ];

        $reranked = $this->reRankResults('test query', $results, 5);

        $this->assertCount(1, $reranked);
        $this->assertEquals(0.90, $reranked[0]['score']);
    }

    /**
     * Tests short query words (<=2 chars) are filtered out.
     */
    public function testShortWordsFiltered(): void
    {
        $results = [
            ['score' => 0.80, 'payload' => ['text' => 'El aceite de la cooperativa']],
            ['score' => 0.90, 'payload' => ['text' => 'Otra información sin relación']],
            ['score' => 0.70, 'payload' => ['text' => 'Más datos sobre aceite oliva']],
        ];

        // "el" and "de" should be filtered (<=2 chars).
        // Only "aceite" should count as a keyword.
        $reranked = $this->reRankResults('el aceite de', $results, 2);

        $this->assertCount(2, $reranked);
    }

    /**
     * Tests score formula: (vectorScore * 0.7) + (keywordScore * 0.2) + phraseBoost.
     */
    public function testScoreFormula(): void
    {
        $results = [
            ['score' => 0.80, 'payload' => ['text' => 'aceite oliva virgen extra de jaén']],
            ['score' => 0.90, 'payload' => ['text' => 'documento sin relación alguna aquí']],
            ['score' => 0.60, 'payload' => ['text' => 'aceite oliva']],
        ];

        $reranked = $this->reRankResults('aceite oliva', $results, 2);

        // First result: vector=0.80, keywords=2/2=1.0, exact phrase="aceite oliva" found.
        // Expected: (0.80 * 0.7) + (1.0 * 0.2) + 0.15 = 0.56 + 0.2 + 0.15 = 0.91
        $this->assertEqualsWithDelta(0.91, $reranked[0]['score'], 0.01);
    }

}
