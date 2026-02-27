<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\Service\DemoJourneyProgressionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemoJourneyProgressionService.
 *
 * Verifica la lógica de progressive disclosure, evaluación de nudges
 * y dismissal para sesiones demo PLG.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\DemoJourneyProgressionService
 * @group ecosistema_jaraba_core
 */
class DemoJourneyProgressionServiceTest extends TestCase {

  protected DemoJourneyProgressionService $service;
  protected Connection|MockObject $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->createMock(Connection::class);
    $this->service = new DemoJourneyProgressionService($this->database);

    // Inyectar StringTranslation mock para que $this->t() funcione.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')->willReturnCallback(
      fn($string) => (string) $string->getUntranslatedString(),
    );
    $this->service->setStringTranslation($translation);

    // Mock del container para Url::fromRoute() (getNudgeUrl usa Url::fromRoute).
    $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $urlGenerator->method('generateFromRoute')->willReturn('/es/registro/emprendimiento');

    $container = new ContainerBuilder();
    $container->set('url_generator', $urlGenerator);
    \Drupal::setContainer($container);
  }

  /**
   * Simula una query SELECT que retorna session_data JSON.
   */
  protected function mockSelectQuery(?string $jsonData): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($jsonData ?: FALSE);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);
  }

  /**
   * @covers ::getDisclosureLevel
   */
  public function testGetDisclosureLevelReturnsLevel1ForNewSession(): void {
    $this->mockSelectQuery(NULL);

    $result = $this->service->getDisclosureLevel('new-session');

    $this->assertSame(1, $result['level']);
    $this->assertContains('dashboard', $result['unlocked']);
    $this->assertContains('metrics', $result['unlocked']);
    $this->assertSame(1, $result['next_actions_needed']);
  }

  /**
   * @covers ::getDisclosureLevel
   */
  public function testGetDisclosureLevelReturnsLevel2After1Action(): void {
    $sessionData = json_encode([
      'actions' => [
        ['action' => 'browse_marketplace', 'timestamp' => time()],
      ],
    ]);
    $this->mockSelectQuery($sessionData);

    $result = $this->service->getDisclosureLevel('session-1action');

    $this->assertSame(2, $result['level']);
    $this->assertContains('products', $result['unlocked']);
    $this->assertContains('storytelling', $result['unlocked']);
    $this->assertSame(2, $result['next_actions_needed']);
  }

  /**
   * @covers ::getDisclosureLevel
   */
  public function testGetDisclosureLevelReturnsLevel3After3Actions(): void {
    $actions = array_map(fn($i) => [
      'action' => 'view_products',
      'timestamp' => time() - $i,
    ], range(1, 3));

    $this->mockSelectQuery(json_encode(['actions' => $actions]));

    $result = $this->service->getDisclosureLevel('session-3actions');

    $this->assertSame(3, $result['level']);
    $this->assertContains('ai_playground', $result['unlocked']);
    $this->assertSame(2, $result['next_actions_needed']);
  }

  /**
   * @covers ::getDisclosureLevel
   */
  public function testGetDisclosureLevelReturnsLevel4WithTtfv(): void {
    $this->mockSelectQuery(json_encode([
      'actions' => [['action' => 'browse_marketplace', 'timestamp' => time()]],
      'ttfv_seconds' => 45,
    ]));

    $result = $this->service->getDisclosureLevel('session-ttfv');

    $this->assertSame(4, $result['level']);
    $this->assertContains('full_features', $result['unlocked']);
    $this->assertContains('conversion_prompt', $result['unlocked']);
    $this->assertSame(0, $result['next_actions_needed']);
  }

  /**
   * @covers ::evaluateNudges
   */
  public function testEvaluateNudgesReturnsEmptyForNewSession(): void {
    $this->mockSelectQuery(NULL);

    $nudges = $this->service->evaluateNudges('new-session');

    $this->assertSame([], $nudges);
  }

  /**
   * @covers ::evaluateNudges
   */
  public function testEvaluateNudgesReturnsNudgesForStoryGenerated(): void {
    $this->mockSelectQuery(json_encode([
      'actions' => [
        ['action' => 'generate_story', 'timestamp' => time()],
      ],
    ]));

    $nudges = $this->service->evaluateNudges('session-story');

    $this->assertNotEmpty($nudges);
    $storyNudge = array_filter($nudges, fn($n) => $n['id'] === 'ai_story_generated');
    $this->assertNotEmpty($storyNudge);
    $nudge = reset($storyNudge);
    $this->assertSame('engagement', $nudge['state']);
    $this->assertSame('fab_dot', $nudge['channel']);
  }

  /**
   * @covers ::evaluateNudges
   */
  public function testEvaluateNudgesSkipsDismissedNudges(): void {
    $this->mockSelectQuery(json_encode([
      'actions' => [
        ['action' => 'generate_story', 'timestamp' => time()],
      ],
      'dismissed_nudges' => ['ai_story_generated'],
    ]));

    $nudges = $this->service->evaluateNudges('session-dismissed');
    $storyNudge = array_filter($nudges, fn($n) => $n['id'] === 'ai_story_generated');

    $this->assertEmpty($storyNudge);
  }

  /**
   * @covers ::evaluateNudges
   */
  public function testEvaluateNudgesOrderedByPriority(): void {
    // Sesión con TTFV + 5 acciones + historia generada — activa múltiples nudges.
    $actions = [];
    for ($i = 0; $i < 5; $i++) {
      $actions[] = ['action' => 'view_products', 'timestamp' => time() - $i];
    }
    $actions[] = ['action' => 'generate_story', 'timestamp' => time()];

    $this->mockSelectQuery(json_encode([
      'actions' => $actions,
      'ttfv_seconds' => 30,
    ]));

    $nudges = $this->service->evaluateNudges('session-multi');

    // Al menos 2 nudges.
    $this->assertGreaterThanOrEqual(2, count($nudges));
    // Verificar orden ascendente de prioridad.
    for ($i = 1; $i < count($nudges); $i++) {
      $this->assertGreaterThanOrEqual($nudges[$i - 1]['priority'], $nudges[$i]['priority']);
    }
  }

  /**
   * @covers ::dismissNudge
   */
  public function testDismissNudgeMarksNudgeAsDismissed(): void {
    $sessionData = json_encode([
      'actions' => [],
      'dismissed_nudges' => [],
    ]);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($sessionData);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);

    $update = $this->createMock(Update::class);
    $update->method('condition')->willReturnSelf();
    $update->method('execute')->willReturn(1);
    $update->expects($this->once())
      ->method('fields')
      ->with($this->callback(function (array $fields) {
        $data = json_decode($fields['session_data'], TRUE);
        return in_array('first_value_reached', $data['dismissed_nudges'] ?? [], TRUE);
      }))
      ->willReturnSelf();

    $this->database->method('update')->willReturn($update);

    $this->service->dismissNudge('session-dismiss', 'first_value_reached');
  }

  /**
   * @covers ::dismissNudge
   */
  public function testDismissNudgeIgnoresUnknownNudgeId(): void {
    // No debería hacer ninguna query para nudges desconocidos.
    $this->database->expects($this->never())->method('select');
    $this->database->expects($this->never())->method('update');

    $this->service->dismissNudge('session-unknown', 'totally_fake_nudge');
  }

}
