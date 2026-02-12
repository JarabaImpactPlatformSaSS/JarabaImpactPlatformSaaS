<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Drupal\jaraba_social\Entity\SocialPost;
use Drupal\jaraba_social\Service\SocialPostService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para SocialPostService.
 *
 * Verifica la generacion de contenido con IA, programacion de posts,
 * publicacion, procesamiento de posts programados y estadisticas.
 *
 * @covers \Drupal\jaraba_social\Service\SocialPostService
 * @group jaraba_social
 */
class SocialPostServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected SocialPostService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del orquestador de agentes IA.
   */
  protected AgentOrchestrator&MockObject $orchestrator;

  /**
   * Mock del queue factory.
   */
  protected QueueFactory&MockObject $queueFactory;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de social_post.
   */
  protected EntityStorageInterface&MockObject $postStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->orchestrator = $this->createMock(AgentOrchestrator::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->postStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('social_post')
      ->willReturn($this->postStorage);

    $this->service = new SocialPostService(
      $this->entityTypeManager,
      $this->orchestrator,
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * Tests que generateContent retorna contenido exitoso desde el orquestador.
   *
   * @covers ::generateContent
   */
  public function testGenerateContentReturnsSuccess(): void {
    $this->orchestrator
      ->method('execute')
      ->with('marketing', 'generate_social_post', $this->callback(function (array $ctx): bool {
        return $ctx['platform'] === 'linkedin'
          && $ctx['action'] === 'generate_social_post'
          && $ctx['character_limit'] === 3000;
      }))
      ->willReturn([
        'success' => TRUE,
        'data' => [
          'content' => 'Post generado por IA sobre marketing digital.',
          'hashtags' => ['#marketing', '#IA'],
        ],
      ]);

    $result = $this->service->generateContent(
      'Escribe un post sobre marketing digital',
      'linkedin',
      ['tenant_id' => 42]
    );

    $this->assertTrue($result['success']);
    $this->assertSame('Post generado por IA sobre marketing digital.', $result['content']);
    $this->assertSame(['#marketing', '#IA'], $result['hashtags']);
    $this->assertSame('linkedin', $result['platform']);
  }

  /**
   * Tests que generateContent maneja errores del orquestador.
   *
   * @covers ::generateContent
   */
  public function testGenerateContentReturnsErrorOnFailure(): void {
    $this->orchestrator
      ->method('execute')
      ->willReturn([
        'success' => FALSE,
        'error' => 'Rate limit exceeded',
      ]);

    $result = $this->service->generateContent('Genera un post', 'twitter');

    $this->assertFalse($result['success']);
    $this->assertSame('Rate limit exceeded', $result['error']);
  }

  /**
   * Tests que generateContent maneja excepciones del orquestador.
   *
   * @covers ::generateContent
   */
  public function testGenerateContentHandlesException(): void {
    $this->orchestrator
      ->method('execute')
      ->willThrowException(new \RuntimeException('Service unavailable'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->generateContent('Genera un post', 'facebook');

    $this->assertFalse($result['success']);
    $this->assertSame('Service unavailable', $result['error']);
  }

  /**
   * Tests que schedule programa un post correctamente.
   *
   * @covers ::schedule
   */
  public function testScheduleSetsStatusAndDate(): void {
    $post = $this->createMock(SocialPost::class);
    $scheduledAt = new \DateTime('2026-04-15T14:00:00');

    $post->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function (string $field, $value) {
        // Verificar que se setean los campos correctos.
        $this->assertTrue(
          in_array($field, ['scheduled_at', 'status'], TRUE),
          "Campo inesperado: $field"
        );
      });

    $post->expects($this->once())
      ->method('save');

    $post->method('id')->willReturn(5);

    $this->logger->expects($this->once())
      ->method('info');

    $result = $this->service->schedule($post, $scheduledAt);

    $this->assertTrue($result);
  }

  /**
   * Tests que getStats retorna estadisticas correctas.
   *
   * @covers ::getStats
   */
  public function testGetStatsReturnsCorrectCounts(): void {
    // Mock de query que soporta clone (mediante multiples llamadas).
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();

    // Secuencia de resultados: total=10, published=5, scheduled=3, drafts=2.
    $query->method('execute')
      ->willReturnOnConsecutiveCalls(10, 5, 3, 2);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    $stats = $this->service->getStats(42);

    $this->assertSame(10, $stats['total']);
    $this->assertSame(5, $stats['published']);
    $this->assertSame(3, $stats['scheduled']);
    $this->assertSame(2, $stats['drafts']);
  }

  /**
   * Tests que getScheduledForNow retorna posts programados para publicar.
   *
   * @covers ::getScheduledForNow
   */
  public function testGetScheduledForNowReturnsPosts(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    $post1 = $this->createMock(ContentEntityInterface::class);
    $post2 = $this->createMock(ContentEntityInterface::class);

    $this->postStorage
      ->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $post1, 2 => $post2]);

    $posts = $this->service->getScheduledForNow();

    $this->assertCount(2, $posts);
  }

}
