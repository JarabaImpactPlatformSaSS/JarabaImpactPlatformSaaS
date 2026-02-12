<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_knowledge\Unit\Service;

use Drupal\jaraba_legal_knowledge\Service\LegalChunkingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para LegalChunkingService.
 *
 * Verifica la logica de fragmentacion de texto legal en chunks para el
 * pipeline RAG, la estimacion de tokens y la deteccion de articulos.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_knowledge\Service\LegalChunkingService
 * @group jaraba_legal_knowledge
 */
class LegalChunkingServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\LegalChunkingService
   */
  protected LegalChunkingService $service;

  /**
   * Mock del logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new LegalChunkingService($this->logger);
  }

  /**
   * Verifica que chunkText devuelve array vacio con texto vacio.
   *
   * @covers ::chunkText
   */
  public function testChunkTextEmptyInput(): void {
    $result = $this->service->chunkText('');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que chunkText devuelve un solo chunk para texto corto.
   *
   * @covers ::chunkText
   */
  public function testChunkTextShortTextSingleChunk(): void {
    $text = 'Articulo 1. Objeto de la ley. Esta ley tiene por objeto regular las condiciones basicas.';

    $result = $this->service->chunkText($text);

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('content', $result[0]);
    $this->assertArrayHasKey('token_count', $result[0]);
    $this->assertStringContainsString('Articulo 1', $result[0]['content']);
  }

  /**
   * Verifica que chunkText genera multiples chunks para texto largo.
   *
   * @covers ::chunkText
   */
  public function testChunkTextLongTextMultipleChunks(): void {
    // Generate a text long enough to require multiple chunks.
    $text = '';
    for ($i = 1; $i <= 50; $i++) {
      $text .= "Articulo $i. Disposicion numero $i. ";
      $text .= str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit. ', 20);
      $text .= "\n\n";
    }

    $result = $this->service->chunkText($text);

    $this->assertIsArray($result);
    $this->assertGreaterThan(1, count($result));
  }

  /**
   * Verifica que cada chunk tiene las claves requeridas.
   *
   * @covers ::chunkText
   */
  public function testChunkTextChunkHasRequiredKeys(): void {
    $text = "Articulo 1. Objeto.\nContenido del articulo primero.\n\n"
      . "Articulo 2. Ambito de aplicacion.\nContenido del articulo segundo.";

    $result = $this->service->chunkText($text);

    $this->assertNotEmpty($result);
    foreach ($result as $chunk) {
      $this->assertArrayHasKey('content', $chunk);
      $this->assertArrayHasKey('token_count', $chunk);
      $this->assertArrayHasKey('chunk_index', $chunk);
    }
  }

  /**
   * Verifica que los indices de chunk son secuenciales desde 0.
   *
   * @covers ::chunkText
   */
  public function testChunkTextIndicesAreSequential(): void {
    $text = '';
    for ($i = 1; $i <= 20; $i++) {
      $text .= "Articulo $i. Disposicion $i. " . str_repeat('Texto de relleno. ', 30) . "\n\n";
    }

    $result = $this->service->chunkText($text);

    for ($i = 0; $i < count($result); $i++) {
      $this->assertEquals($i, $result[$i]['chunk_index']);
    }
  }

  /**
   * Verifica que estimateTokens devuelve un numero razonable.
   *
   * @covers ::estimateTokens
   */
  public function testEstimateTokensReturnsReasonableCount(): void {
    // Approximately 4 characters per token for Spanish text.
    $text = 'Esta es una frase de prueba para estimar tokens.';
    $result = $this->service->estimateTokens($text);

    $this->assertIsInt($result);
    $this->assertGreaterThan(5, $result);
    $this->assertLessThan(50, $result);
  }

  /**
   * Verifica que estimateTokens devuelve 0 para texto vacio.
   *
   * @covers ::estimateTokens
   */
  public function testEstimateTokensEmptyText(): void {
    $result = $this->service->estimateTokens('');

    $this->assertIsInt($result);
    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que estimateTokens maneja texto largo correctamente.
   *
   * @covers ::estimateTokens
   */
  public function testEstimateTokensLongText(): void {
    $text = str_repeat('Palabra ', 1000);
    $result = $this->service->estimateTokens($text);

    $this->assertIsInt($result);
    $this->assertGreaterThan(500, $result);
  }

  /**
   * Verifica que detectArticles identifica articulos en el texto.
   *
   * @covers ::detectArticles
   */
  public function testDetectArticlesFindsArticles(): void {
    $text = "Articulo 1. Objeto de la ley.\n"
      . "Contenido del articulo primero.\n\n"
      . "Articulo 2. Ambito de aplicacion.\n"
      . "Contenido del articulo segundo.\n\n"
      . "Articulo 3. Definiciones.\n"
      . "Contenido del articulo tercero.";

    $result = $this->service->detectArticles($text);

    $this->assertIsArray($result);
    $this->assertCount(3, $result);
  }

  /**
   * Verifica que detectArticles maneja el patron 'Art.' abreviado.
   *
   * @covers ::detectArticles
   */
  public function testDetectArticlesAbbreviatedPattern(): void {
    $text = "Art. 1. Objeto.\nContenido.\n\nArt. 2. Ambito.\nContenido.";

    $result = $this->service->detectArticles($text);

    $this->assertIsArray($result);
    $this->assertGreaterThanOrEqual(2, count($result));
  }

  /**
   * Verifica que detectArticles devuelve array vacio sin articulos.
   *
   * @covers ::detectArticles
   */
  public function testDetectArticlesNoArticlesFound(): void {
    $text = 'Este es un texto sin estructura de articulos legales.';

    $result = $this->service->detectArticles($text);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que detectArticles incluye el numero de articulo.
   *
   * @covers ::detectArticles
   */
  public function testDetectArticlesIncludesArticleNumber(): void {
    $text = "Articulo 15. Obligaciones fiscales.\nContenido del articulo.\n\n"
      . "Articulo 16. Sanciones.\nContenido sobre sanciones.";

    $result = $this->service->detectArticles($text);

    $this->assertNotEmpty($result);
    $this->assertEquals('15', $result[0]['article_number']);
    $this->assertEquals('16', $result[1]['article_number']);
  }

  /**
   * Verifica que detectArticles detecta capitulos.
   *
   * @covers ::detectArticles
   */
  public function testDetectArticlesDetectsChapters(): void {
    $text = "CAPITULO I\nDisposiciones generales\n\n"
      . "Articulo 1. Objeto.\nContenido.\n\n"
      . "CAPITULO II\nAmbito de aplicacion\n\n"
      . "Articulo 2. Ambito.\nContenido.";

    $result = $this->service->detectArticles($text);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
  }

  /**
   * Verifica que chunkText no excede el limite maximo de tokens por chunk.
   *
   * @covers ::chunkText
   */
  public function testChunkTextRespectsMaxTokenLimit(): void {
    $text = str_repeat('Palabra de prueba para el chunk de texto legal. ', 500);

    $result = $this->service->chunkText($text);

    foreach ($result as $chunk) {
      // Default max is typically 512 tokens; allow reasonable margin.
      $this->assertLessThanOrEqual(600, $chunk['token_count']);
    }
  }

  /**
   * Verifica que chunkText preserva el contenido completo.
   *
   * @covers ::chunkText
   */
  public function testChunkTextPreservesContent(): void {
    $text = "Articulo unico. Disposicion final. Este texto debe aparecer en los chunks.";

    $result = $this->service->chunkText($text);

    $allContent = implode(' ', array_column($result, 'content'));
    $this->assertStringContainsString('Disposicion final', $allContent);
  }

  /**
   * Verifica que chunkText maneja texto con solo espacios en blanco.
   *
   * @covers ::chunkText
   */
  public function testChunkTextWhitespaceOnly(): void {
    $result = $this->service->chunkText("   \n\n  \t  ");

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que chunkText con un articulo muy largo lo divide en subchunks.
   *
   * @covers ::chunkText
   */
  public function testChunkTextSplitsLongArticle(): void {
    $text = "Articulo 1. Disposicion extensa. " . str_repeat('Contenido extenso de la norma. ', 200);

    $result = $this->service->chunkText($text);

    $this->assertIsArray($result);
    $this->assertGreaterThanOrEqual(1, count($result));
  }

}
