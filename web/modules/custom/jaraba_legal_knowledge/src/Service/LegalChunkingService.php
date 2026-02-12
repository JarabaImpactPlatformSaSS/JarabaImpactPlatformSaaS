<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de segmentacion (chunking) de textos legales.
 *
 * Divide normas juridicas en fragmentos optimizados para embedding
 * y busqueda semantica. Respeta la estructura jerarquica del texto
 * legal: capitulos, secciones y articulos.
 *
 * ALGORITMO:
 * 1. Divide por articulos (Articulo \d+).
 * 2. Si un articulo excede ~500 tokens, subdivide por secciones.
 * 3. Como fallback, subdivide por parrafos.
 * 4. Cada chunk incluye metadatos de seccion, articulo y capitulo.
 *
 * Estimacion de tokens: word_count * 1.3 (ajustado para castellano).
 */
class LegalChunkingService {

  /**
   * Tamanio objetivo por chunk en tokens.
   */
  protected const TARGET_CHUNK_TOKENS = 500;

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Divide un texto legal en chunks semanticos.
   *
   * @param string $fullText
   *   Texto completo de la norma.
   * @param string $title
   *   Titulo de la norma (incluido como contexto en cada chunk).
   *
   * @return array
   *   Array de chunks. Cada chunk contiene:
   *   - content: (string) Texto del fragmento.
   *   - section_title: (string) Titulo de la seccion o capitulo.
   *   - article_number: (string|null) Numero de articulo si aplica.
   *   - chapter: (string|null) Capitulo al que pertenece.
   *   - chunk_index: (int) Indice secuencial del chunk.
   *   - token_count: (int) Estimacion de tokens del chunk.
   */
  public function chunkNorm(string $fullText, string $title): array {
    if (empty(trim($fullText))) {
      $this->logger->warning('chunkNorm invocado con texto vacio para norma: @title', [
        '@title' => $title,
      ]);
      return [];
    }

    $chunks = [];
    $chunkIndex = 0;
    $currentChapter = NULL;

    // Detectar capitulos (Capitulo I, II, III, IV, etc.).
    $chapterPattern = '/\b(?:Cap[ií]tulo)\s+([IVXLCDM]+(?:\s+[Bb]is)?)\b[.\-:\s]*(.*)/iu';

    // Dividir por articulos.
    $articlePattern = '/\b(?:Art[ií]culo)\s+(\d+(?:\s+[Bb]is)?)\b[.\-:\s]*/iu';
    $articleSections = preg_split($articlePattern, $fullText, -1, PREG_SPLIT_DELIM_CAPTURE);

    if ($articleSections === FALSE || count($articleSections) <= 1) {
      // No se encontraron articulos; dividir por parrafos.
      $chunks = $this->chunkByParagraphs($fullText, $title, NULL, $currentChapter, $chunkIndex);
      $this->logger->info('Norma "@title" dividida en @count chunks (sin articulos detectados).', [
        '@title' => $title,
        '@count' => count($chunks),
      ]);
      return $chunks;
    }

    // El primer elemento es el texto antes del primer articulo (preambulo).
    $preamble = trim($articleSections[0]);
    if (!empty($preamble)) {
      // Detectar capitulo en el preambulo.
      if (preg_match($chapterPattern, $preamble, $chapterMatch)) {
        $currentChapter = 'Capitulo ' . trim($chapterMatch[1]);
      }

      $tokenCount = $this->estimateTokens($preamble);
      if ($tokenCount > self::TARGET_CHUNK_TOKENS) {
        $subChunks = $this->chunkByParagraphs($preamble, $title, NULL, $currentChapter, $chunkIndex);
        $chunks = array_merge($chunks, $subChunks);
        $chunkIndex += count($subChunks);
      }
      else {
        $chunks[] = [
          'content' => $preamble,
          'section_title' => $title . ' - Preambulo',
          'article_number' => NULL,
          'chapter' => $currentChapter,
          'chunk_index' => $chunkIndex,
          'token_count' => $tokenCount,
        ];
        $chunkIndex++;
      }
    }

    // Procesar pares (numero_articulo, texto_articulo).
    for ($i = 1; $i < count($articleSections); $i += 2) {
      $articleNumber = trim($articleSections[$i]);
      $articleText = isset($articleSections[$i + 1]) ? trim($articleSections[$i + 1]) : '';

      if (empty($articleText)) {
        continue;
      }

      // Detectar cambio de capitulo dentro del texto del articulo.
      if (preg_match($chapterPattern, $articleText, $chapterMatch)) {
        $currentChapter = 'Capitulo ' . trim($chapterMatch[1]);
      }

      $sectionTitle = $title . ' - Art. ' . $articleNumber;
      $tokenCount = $this->estimateTokens($articleText);

      if ($tokenCount > self::TARGET_CHUNK_TOKENS) {
        // Intentar dividir por secciones dentro del articulo.
        $subChunks = $this->chunkBySection($articleText, $title, $articleNumber, $currentChapter, $chunkIndex);
        if (count($subChunks) > 1) {
          $chunks = array_merge($chunks, $subChunks);
          $chunkIndex += count($subChunks);
        }
        else {
          // Dividir por parrafos como fallback.
          $subChunks = $this->chunkByParagraphs($articleText, $title, $articleNumber, $currentChapter, $chunkIndex);
          $chunks = array_merge($chunks, $subChunks);
          $chunkIndex += count($subChunks);
        }
      }
      else {
        $chunks[] = [
          'content' => $articleText,
          'section_title' => $sectionTitle,
          'article_number' => $articleNumber,
          'chapter' => $currentChapter,
          'chunk_index' => $chunkIndex,
          'token_count' => $tokenCount,
        ];
        $chunkIndex++;
      }
    }

    $this->logger->info('Norma "@title" dividida en @count chunks.', [
      '@title' => $title,
      '@count' => count($chunks),
    ]);

    return $chunks;
  }

  /**
   * Estima el numero de tokens de un texto.
   *
   * Aproximacion para castellano: word_count * 1.3 (el castellano
   * tiene mas morfologia que el ingles, generando ~1.3 tokens/palabra).
   *
   * @param string $text
   *   Texto a estimar.
   *
   * @return int
   *   Estimacion del numero de tokens.
   */
  public function estimateTokens(string $text): int {
    $wordCount = str_word_count($text);

    return (int) ceil($wordCount * 1.3);
  }

  /**
   * Divide texto por secciones legales (Seccion 1, Seccion 2, etc.).
   *
   * @param string $text
   *   Texto a dividir.
   * @param string $title
   *   Titulo de la norma.
   * @param string|null $articleNumber
   *   Numero de articulo padre.
   * @param string|null $chapter
   *   Capitulo actual.
   * @param int $startIndex
   *   Indice inicial para los chunks.
   *
   * @return array
   *   Array de chunks.
   */
  protected function chunkBySection(string $text, string $title, ?string $articleNumber, ?string $chapter, int $startIndex): array {
    $sectionPattern = '/\b(?:Secci[oó]n)\s+(\d+[a-z]?(?:\.\d+)?)\b[.\-:\s]*/iu';
    $parts = preg_split($sectionPattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    if ($parts === FALSE || count($parts) <= 1) {
      return [];
    }

    $chunks = [];
    $chunkIndex = $startIndex;

    // Texto antes de la primera seccion.
    $preSectionText = trim($parts[0]);
    if (!empty($preSectionText)) {
      $tokenCount = $this->estimateTokens($preSectionText);
      $sectionTitle = $title;
      if ($articleNumber) {
        $sectionTitle .= ' - Art. ' . $articleNumber;
      }
      $chunks[] = [
        'content' => $preSectionText,
        'section_title' => $sectionTitle,
        'article_number' => $articleNumber,
        'chapter' => $chapter,
        'chunk_index' => $chunkIndex,
        'token_count' => $tokenCount,
      ];
      $chunkIndex++;
    }

    // Procesar pares (numero_seccion, texto_seccion).
    for ($i = 1; $i < count($parts); $i += 2) {
      $sectionNumber = trim($parts[$i]);
      $sectionText = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';

      if (empty($sectionText)) {
        continue;
      }

      $sectionTitle = $title;
      if ($articleNumber) {
        $sectionTitle .= ' - Art. ' . $articleNumber;
      }
      $sectionTitle .= ' - Seccion ' . $sectionNumber;

      $tokenCount = $this->estimateTokens($sectionText);

      if ($tokenCount > self::TARGET_CHUNK_TOKENS) {
        $subChunks = $this->chunkByParagraphs($sectionText, $sectionTitle, $articleNumber, $chapter, $chunkIndex);
        $chunks = array_merge($chunks, $subChunks);
        $chunkIndex += count($subChunks);
      }
      else {
        $chunks[] = [
          'content' => $sectionText,
          'section_title' => $sectionTitle,
          'article_number' => $articleNumber,
          'chapter' => $chapter,
          'chunk_index' => $chunkIndex,
          'token_count' => $tokenCount,
        ];
        $chunkIndex++;
      }
    }

    return $chunks;
  }

  /**
   * Divide texto por parrafos cuando no hay estructura jerarquica.
   *
   * @param string $text
   *   Texto a dividir.
   * @param string $title
   *   Titulo o contexto del chunk.
   * @param string|null $articleNumber
   *   Numero de articulo padre.
   * @param string|null $chapter
   *   Capitulo actual.
   * @param int $startIndex
   *   Indice inicial para los chunks.
   *
   * @return array
   *   Array de chunks.
   */
  protected function chunkByParagraphs(string $text, string $title, ?string $articleNumber, ?string $chapter, int $startIndex): array {
    $paragraphs = preg_split('/\n\s*\n/', $text);
    if ($paragraphs === FALSE) {
      $paragraphs = [$text];
    }
    $paragraphs = array_filter(array_map('trim', $paragraphs));

    $chunks = [];
    $chunkIndex = $startIndex;
    $currentContent = '';
    $currentTokens = 0;

    foreach ($paragraphs as $paragraph) {
      $paragraphTokens = $this->estimateTokens($paragraph);

      if ($currentTokens + $paragraphTokens > self::TARGET_CHUNK_TOKENS && !empty($currentContent)) {
        // Guardar chunk acumulado.
        $chunks[] = [
          'content' => trim($currentContent),
          'section_title' => $title,
          'article_number' => $articleNumber,
          'chapter' => $chapter,
          'chunk_index' => $chunkIndex,
          'token_count' => $currentTokens,
        ];
        $chunkIndex++;
        $currentContent = '';
        $currentTokens = 0;
      }

      $currentContent .= ($currentContent !== '' ? "\n\n" : '') . $paragraph;
      $currentTokens += $paragraphTokens;
    }

    // Chunk final.
    if (!empty(trim($currentContent))) {
      $chunks[] = [
        'content' => trim($currentContent),
        'section_title' => $title,
        'article_number' => $articleNumber,
        'chapter' => $chapter,
        'chunk_index' => $chunkIndex,
        'token_count' => $currentTokens,
      ];
    }

    return $chunks;
  }

}
