<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Unit\Service;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\jaraba_page_builder\Service\CanvasCacheTagsInvalidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CanvasCacheTagsInvalidator.
 *
 * Verifica la invalidacion de cache tags al actualizar paginas del Canvas
 * Editor: propagacion de tags de coleccion, sitemap, preview, deduplicacion
 * y logging de operaciones de invalidacion.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\Service\CanvasCacheTagsInvalidator
 * @group jaraba_page_builder
 */
class CanvasCacheTagsInvalidatorTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private CanvasCacheTagsInvalidator $service;

  /**
   * Mock del invalidador interno de cache tags.
   */
  private CacheTagsInvalidatorInterface&MockObject $innerInvalidator;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->innerInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new CanvasCacheTagsInvalidator(
      $this->innerInvalidator,
      $this->logger,
    );
  }

  // =========================================================================
  // TESTS: invalidateTags() — tag individual page_content
  // =========================================================================

  /**
   * Verifica que un tag page_content:42 propaga coleccion, sitemap y preview.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsWithPageContentTag(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        return in_array('page_content_list', $tags, TRUE)
          && in_array('jaraba_sitemap', $tags, TRUE)
          && in_array('canvas_preview', $tags, TRUE)
          && in_array('canvas_preview:42', $tags, TRUE);
      }));

    $this->service->invalidateTags(['page_content:42']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — multiples tags page_content
  // =========================================================================

  /**
   * Verifica que multiples tags page_content deduplicados correctamente,
   * pero mantiene los preview individuales para cada pagina.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsWithMultiplePageContentTags(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        // Debe incluir preview:1 y preview:2 individuales.
        $hasPreview1 = in_array('canvas_preview:1', $tags, TRUE);
        $hasPreview2 = in_array('canvas_preview:2', $tags, TRUE);
        // Pero coleccion, sitemap y preview genericos deben estar solo una vez.
        $collectionCount = count(array_filter($tags, fn($t) => $t === 'page_content_list'));
        $sitemapCount = count(array_filter($tags, fn($t) => $t === 'jaraba_sitemap'));
        $previewCount = count(array_filter($tags, fn($t) => $t === 'canvas_preview'));

        return $hasPreview1
          && $hasPreview2
          && $collectionCount === 1
          && $sitemapCount === 1
          && $previewCount === 1;
      }));

    $this->service->invalidateTags(['page_content:1', 'page_content:2']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — tags no-page_content
  // =========================================================================

  /**
   * Verifica que un tag que no es page_content no dispara invalidacion adicional.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsWithNonPageContentTag(): void {
    $this->innerInvalidator->expects($this->never())
      ->method('invalidateTags');

    $this->service->invalidateTags(['node:5']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — array vacio
  // =========================================================================

  /**
   * Verifica que un array vacio no dispara invalidacion.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsWithEmptyArray(): void {
    $this->innerInvalidator->expects($this->never())
      ->method('invalidateTags');

    $this->service->invalidateTags([]);
  }

  // =========================================================================
  // TESTS: invalidateTags() — mezcla de tags
  // =========================================================================

  /**
   * Verifica que una mezcla de tags page_content y otros solo procesa los page_content.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsWithMixedTags(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        // Solo debe incluir tags derivados de page_content:7.
        return in_array('page_content_list', $tags, TRUE)
          && in_array('jaraba_sitemap', $tags, TRUE)
          && in_array('canvas_preview', $tags, TRUE)
          && in_array('canvas_preview:7', $tags, TRUE)
          // No debe incluir los tags originales no-page_content.
          && !in_array('node:3', $tags, TRUE)
          && !in_array('taxonomy_term:12', $tags, TRUE);
      }));

    $this->service->invalidateTags(['node:3', 'page_content:7', 'taxonomy_term:12']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — llamada al innerInvalidator
  // =========================================================================

  /**
   * Verifica que innerInvalidator->invalidateTags se llama exactamente una vez
   * con los tags correctos.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsCallsInnerInvalidator(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        $expected = [
          'page_content_list',
          'jaraba_sitemap',
          'canvas_preview',
          'canvas_preview:99',
        ];
        // Verificar que todos los tags esperados estan presentes.
        foreach ($expected as $tag) {
          if (!in_array($tag, $tags, TRUE)) {
            return FALSE;
          }
        }
        return count($tags) === 4;
      }));

    $this->service->invalidateTags(['page_content:99']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — deduplicacion
  // =========================================================================

  /**
   * Verifica que dos tags page_content deduplicados los tags de coleccion,
   * sitemap y preview generico, pero conserva ambos preview individuales.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsDeduplicatesTags(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        // Con page_content:10 y page_content:20, los tags unicos deben ser:
        // page_content_list, jaraba_sitemap, canvas_preview,
        // canvas_preview:10, canvas_preview:20
        return count($tags) === 5
          && in_array('page_content_list', $tags, TRUE)
          && in_array('jaraba_sitemap', $tags, TRUE)
          && in_array('canvas_preview', $tags, TRUE)
          && in_array('canvas_preview:10', $tags, TRUE)
          && in_array('canvas_preview:20', $tags, TRUE);
      }));

    $this->service->invalidateTags(['page_content:10', 'page_content:20']);
  }

  // =========================================================================
  // TESTS: invalidateTags() — logging debug
  // =========================================================================

  /**
   * Verifica que logger->debug se llama por cada tag page_content procesado.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsLogsDebug(): void {
    $this->logger->expects($this->exactly(2))
      ->method('debug');

    $this->service->invalidateTags(['page_content:1', 'page_content:2']);
  }

  /**
   * Verifica que logger->debug NO se llama para tags que no son page_content.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsDoesNotLogForOtherTags(): void {
    $this->logger->expects($this->never())
      ->method('debug');

    $this->service->invalidateTags(['node:1', 'block:2', 'config:system.site']);
  }

  // =========================================================================
  // TESTS: constantes (via reflection)
  // =========================================================================

  /**
   * Verifica los valores de las constantes protegidas del servicio.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testConstantValues(): void {
    $reflection = new \ReflectionClass(CanvasCacheTagsInvalidator::class);

    $prefix = $reflection->getConstant('PAGE_CONTENT_TAG_PREFIX');
    $this->assertSame('page_content:', $prefix, 'PAGE_CONTENT_TAG_PREFIX debe ser "page_content:"');

    $collection = $reflection->getConstant('COLLECTION_TAG');
    $this->assertSame('page_content_list', $collection, 'COLLECTION_TAG debe ser "page_content_list"');

    $sitemap = $reflection->getConstant('SITEMAP_TAG');
    $this->assertSame('jaraba_sitemap', $sitemap, 'SITEMAP_TAG debe ser "jaraba_sitemap"');

    $preview = $reflection->getConstant('CANVAS_PREVIEW_TAG');
    $this->assertSame('canvas_preview', $preview, 'CANVAS_PREVIEW_TAG debe ser "canvas_preview"');
  }

  // =========================================================================
  // TESTS: invalidateTags() — formato de preview tag con pageId
  // =========================================================================

  /**
   * Verifica que el tag de preview incluye el pageId con formato canvas_preview:N.
   *
   * @covers ::invalidateTags
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testInvalidateTagsPreviewTagIncludesPageId(): void {
    $this->innerInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with($this->callback(function (array $tags): bool {
        // Verificar formato exacto canvas_preview:42.
        foreach ($tags as $tag) {
          if (preg_match('/^canvas_preview:\d+$/', $tag)) {
            return $tag === 'canvas_preview:42';
          }
        }
        return FALSE;
      }));

    $this->service->invalidateTags(['page_content:42']);
  }

}
