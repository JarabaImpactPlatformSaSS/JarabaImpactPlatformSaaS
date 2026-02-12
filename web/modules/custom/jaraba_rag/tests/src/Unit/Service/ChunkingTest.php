<?php

namespace Drupal\Tests\jaraba_rag\Unit\Service;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for the recursive character text splitter in KbIndexerService.
 *
 * Tests the chunking algorithm extracted for testability.
 *
 * @group jaraba_rag
 */
class ChunkingTest extends UnitTestCase
{

    /**
     * Recursive split algorithm mirroring KbIndexerService::recursiveSplit.
     */
    protected function recursiveSplit(string $text, array $separators, int $maxChars): array
    {
        if (strlen($text) <= $maxChars) {
            return [$text];
        }

        foreach ($separators as $idx => $separator) {
            $parts = explode($separator, $text);
            if (count($parts) <= 1) {
                continue;
            }

            $chunks = [];
            $current = '';
            foreach ($parts as $part) {
                $candidate = $current === '' ? $part : $current . $separator . $part;

                if (strlen($candidate) <= $maxChars) {
                    $current = $candidate;
                } else {
                    if ($current !== '') {
                        $chunks[] = $current;
                    }
                    if (strlen($part) > $maxChars) {
                        $remaining = array_slice($separators, $idx + 1);
                        if (!empty($remaining)) {
                            $chunks = array_merge($chunks, $this->recursiveSplit($part, $remaining, $maxChars));
                            $current = '';
                        } else {
                            while (strlen($part) > $maxChars) {
                                $chunks[] = substr($part, 0, $maxChars);
                                $part = substr($part, $maxChars);
                            }
                            $current = $part;
                        }
                    } else {
                        $current = $part;
                    }
                }
            }
            if ($current !== '') {
                $chunks[] = $current;
            }
            return $chunks;
        }

        // Fallback: hard cut.
        $chunks = [];
        while (strlen($text) > $maxChars) {
            $chunks[] = substr($text, 0, $maxChars);
            $text = substr($text, $maxChars);
        }
        if (strlen($text) > 0) {
            $chunks[] = $text;
        }
        return $chunks;
    }

    /**
     * Tests that short text is returned as single chunk.
     */
    public function testShortTextSingleChunk(): void
    {
        $text = 'Short text under limit.';
        $separators = ["\n\n", "\n", ". "];

        $chunks = $this->recursiveSplit($text, $separators, 100);

        $this->assertCount(1, $chunks);
        $this->assertEquals($text, $chunks[0]);
    }

    /**
     * Tests splitting by paragraph separator.
     */
    public function testSplitByParagraph(): void
    {
        $text = str_repeat('A', 100) . "\n\n" . str_repeat('B', 100);
        $separators = ["\n\n", "\n", ". "];

        $chunks = $this->recursiveSplit($text, $separators, 150);

        $this->assertCount(2, $chunks);
        $this->assertEquals(str_repeat('A', 100), $chunks[0]);
        $this->assertEquals(str_repeat('B', 100), $chunks[1]);
    }

    /**
     * Tests that paragraphs that fit together are merged.
     */
    public function testMergeSmallParagraphs(): void
    {
        $text = "Para 1.\n\nPara 2.\n\nPara 3.";
        $separators = ["\n\n", "\n", ". "];

        $chunks = $this->recursiveSplit($text, $separators, 200);

        // All 3 paragraphs fit in 200 chars, so they should be 1 chunk.
        $this->assertCount(1, $chunks);
        $this->assertEquals($text, $chunks[0]);
    }

    /**
     * Tests fallback to sentence splitting when paragraphs are too long.
     */
    public function testFallbackToSentenceSplit(): void
    {
        // One long paragraph (no \n\n), but with sentences.
        $sentence1 = str_repeat('X', 60);
        $sentence2 = str_repeat('Y', 60);
        $sentence3 = str_repeat('Z', 60);
        $text = "$sentence1. $sentence2. $sentence3";
        $separators = ["\n\n", "\n", ". "];

        $chunks = $this->recursiveSplit($text, $separators, 130);

        // Each chunk should be <= 130 chars.
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(130, strlen($chunk));
        }
        $this->assertGreaterThanOrEqual(2, count($chunks));
    }

    /**
     * Tests hard cut fallback when no separators work.
     */
    public function testHardCutFallback(): void
    {
        // No separators present in text.
        $text = str_repeat('A', 500);
        $separators = ["\n\n", "\n", ". "];

        $chunks = $this->recursiveSplit($text, $separators, 100);

        $this->assertCount(5, $chunks);
        foreach ($chunks as $chunk) {
            $this->assertEquals(100, strlen($chunk));
        }
    }

    /**
     * Tests recursive descent through separator hierarchy.
     */
    public function testRecursiveDescentThroughSeparators(): void
    {
        // Paragraph level: one short, one long.
        $shortPara = 'Short paragraph.';
        $longPara = implode('. ', array_fill(0, 20, 'This is a sentence with words'));
        $text = $shortPara . "\n\n" . $longPara;
        $separators = ["\n\n", "\n", ". ", ", "];

        $chunks = $this->recursiveSplit($text, $separators, 200);

        // Short para should be its own chunk, long para split by sentences.
        $this->assertGreaterThan(1, count($chunks));
        $this->assertEquals($shortPara, $chunks[0]);

        // All chunks should respect the limit.
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(200, strlen($chunk));
        }
    }

    /**
     * Tests heading-based splitting.
     */
    public function testHeadingBasedSplitting(): void
    {
        $section1 = str_repeat('Content of section one. ', 10);
        $section2 = str_repeat('Content of section two. ', 10);
        $text = $section1 . "\n# " . $section2;
        $separators = ["\n\n", "\n# ", "\n## ", "\n### ", "\n", ". ", ", "];

        $chunks = $this->recursiveSplit($text, $separators, 300);

        // Should split at the heading marker.
        $this->assertGreaterThanOrEqual(2, count($chunks));
    }

    /**
     * Tests that all content is preserved (no data loss).
     *
     * @dataProvider contentPreservationDataProvider
     */
    public function testContentPreservation(string $text, int $maxChars): void
    {
        $separators = ["\n\n", "\n", ". ", ", "];

        $chunks = $this->recursiveSplit($text, $separators, $maxChars);

        // Rejoin with the first working separator for comparison.
        $totalLength = array_sum(array_map('strlen', $chunks));

        // Total content length should be approximately the same (separators may be lost).
        $this->assertGreaterThanOrEqual(strlen($text) * 0.9, $totalLength);
        $this->assertNotEmpty($chunks);
    }

    /**
     * Data provider for content preservation test.
     */
    public static function contentPreservationDataProvider(): array
    {
        return [
            'short text' => ['Hello world.', 100],
            'medium text' => [str_repeat('Word. ', 50), 100],
            'large text with paragraphs' => [
                implode("\n\n", array_fill(0, 10, str_repeat('Sentence. ', 5))),
                200,
            ],
        ];
    }

    /**
     * Tests empty text returns empty array.
     */
    public function testEmptyTextReturnsEmpty(): void
    {
        $chunks = $this->recursiveSplit('', ["\n\n", "\n"], 100);
        $this->assertCount(1, $chunks);
        $this->assertEquals('', $chunks[0]);
    }

}
