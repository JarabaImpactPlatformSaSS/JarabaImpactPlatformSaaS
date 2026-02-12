<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_social\Service\SocialCalendarService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para SocialCalendarService.
 *
 * Verifica la gestion de calendario de publicaciones sociales:
 * vista de calendario por tenant, posts programados por fecha,
 * reprogramacion de posts y horarios optimos por plataforma.
 *
 * @covers \Drupal\jaraba_social\Service\SocialCalendarService
 * @group jaraba_social
 */
class SocialCalendarServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected SocialCalendarService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

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
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->postStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('social_post')
      ->willReturn($this->postStorage);

    $this->service = new SocialCalendarService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que getCalendarForTenant retorna posts organizados por fecha.
   *
   * @covers ::getCalendarForTenant
   */
  public function testGetCalendarForTenantReturnsGroupedByDate(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3]);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    // 3 posts: 2 en la misma fecha, 1 en otra.
    $post1 = $this->createMockPost(1, 'Post 1', '2026-03-10T09:00:00', 'scheduled');
    $post2 = $this->createMockPost(2, 'Post 2', '2026-03-10T14:00:00', 'scheduled');
    $post3 = $this->createMockPost(3, 'Post 3', '2026-03-12T10:00:00', 'draft');

    $this->postStorage
      ->method('loadMultiple')
      ->with([1, 2, 3])
      ->willReturn([$post1, $post2, $post3]);

    $calendar = $this->service->getCalendarForTenant(42, '2026-03-01', '2026-03-31');

    // Debe haber 2 fechas como claves.
    $this->assertCount(2, $calendar);
    $this->assertArrayHasKey('2026-03-10', $calendar);
    $this->assertArrayHasKey('2026-03-12', $calendar);
    // 2 posts en 2026-03-10.
    $this->assertCount(2, $calendar['2026-03-10']);
    // 1 post en 2026-03-12.
    $this->assertCount(1, $calendar['2026-03-12']);
    // Verificar datos del primer post.
    $this->assertSame(1, $calendar['2026-03-10'][0]['id']);
    $this->assertSame('Post 1', $calendar['2026-03-10'][0]['title']);
    $this->assertSame('2026-03-10T09:00:00', $calendar['2026-03-10'][0]['scheduled_at']);
  }

  /**
   * Tests que getCalendarForTenant retorna array vacio sin posts.
   *
   * @covers ::getCalendarForTenant
   */
  public function testGetCalendarForTenantReturnsEmptyWhenNoPosts(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    $calendar = $this->service->getCalendarForTenant(42, '2026-03-01', '2026-03-31');

    $this->assertIsArray($calendar);
    $this->assertEmpty($calendar);
  }

  /**
   * Tests que getScheduledPosts retorna posts programados para una fecha.
   *
   * @covers ::getScheduledPosts
   */
  public function testGetScheduledPostsReturnsPostsForDate(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([5]);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    // Create the mock with content included via createMockPost.
    $post = $this->createMockPost(5, 'Post programado', '2026-03-15T10:00:00', 'scheduled', 'Contenido del post programado');

    $this->postStorage
      ->method('loadMultiple')
      ->with([5])
      ->willReturn([$post]);

    $posts = $this->service->getScheduledPosts(42, '2026-03-15');

    $this->assertCount(1, $posts);
    $this->assertSame(5, $posts[0]['id']);
    $this->assertSame('Post programado', $posts[0]['title']);
    $this->assertSame('Contenido del post programado', $posts[0]['content']);
  }

  /**
   * Tests que reschedulePost reprograma un post exitosamente.
   *
   * @covers ::reschedulePost
   */
  public function testReschedulePostSucceeds(): void {
    $post = $this->createMock(ContentEntityInterface::class);

    $post->expects($this->exactly(2))
      ->method('set');

    $post->expects($this->once())
      ->method('save');

    $this->postStorage
      ->method('load')
      ->with(5)
      ->willReturn($post);

    $this->logger->expects($this->once())
      ->method('info');

    $result = $this->service->reschedulePost(5, '2026-04-01T15:00:00');

    $this->assertTrue($result);
  }

  /**
   * Tests que reschedulePost retorna FALSE cuando el post no existe.
   *
   * @covers ::reschedulePost
   */
  public function testReschedulePostReturnsFalseWhenNotFound(): void {
    $this->postStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->service->reschedulePost(999, '2026-04-01T15:00:00');

    $this->assertFalse($result);
  }

  /**
   * Tests que getOptimalTimes retorna horarios por defecto por plataforma.
   *
   * @covers ::getOptimalTimes
   */
  public function testGetOptimalTimesReturnsDefaultsForPlatform(): void {
    $times = $this->service->getOptimalTimes(42, 'instagram');

    $this->assertCount(3, $times);
    // Verificar estructura del primer horario.
    $this->assertArrayHasKey('day', $times[0]);
    $this->assertArrayHasKey('hour', $times[0]);
    $this->assertArrayHasKey('score', $times[0]);
    // Instagram: primer horario es martes a las 11.
    $this->assertSame(2, $times[0]['day']);
    $this->assertSame(11, $times[0]['hour']);
    $this->assertSame(0.90, $times[0]['score']);
  }

  /**
   * Tests que getOptimalTimes retorna array vacio para plataforma desconocida.
   *
   * @covers ::getOptimalTimes
   */
  public function testGetOptimalTimesReturnsEmptyForUnknownPlatform(): void {
    $times = $this->service->getOptimalTimes(42, 'mastodon');

    $this->assertIsArray($times);
    $this->assertEmpty($times);
  }

  /**
   * Crea un mock de SocialPost con datos basicos.
   *
   * @param int $id
   *   ID del post.
   * @param string $title
   *   Titulo del post.
   * @param string $scheduled_at
   *   Fecha programada ISO.
   * @param string $status
   *   Estado del post.
   * @param string $content
   *   Contenido del post.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad SocialPost.
   */
  protected function createMockPost(int $id, string $title, string $scheduled_at, string $status, string $content = ''): MockObject {
    $post = $this->createMock(ContentEntityInterface::class);
    $post->method('id')->willReturn($id);
    $post->method('label')->willReturn($title);

    $fields = [
      'scheduled_at' => $scheduled_at,
      'status' => $status,
      'content' => $content,
    ];

    $post->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        $field = new \stdClass();
        $field->value = $fields[$field_name] ?? NULL;
        return $field;
      });

    return $post;
  }

}
