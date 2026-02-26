<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\jaraba_ai_agents\Service\TraceContextService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TraceContextService span stack hierarchy and endSpan.
 *
 * Tests nested span creation, parent restoration on endSpan, duration
 * calculation, and edge cases for the span stack.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\TraceContextService
 * @group jaraba_ai_agents
 */
class TraceContextSpanStackTest extends TestCase {

  /**
   * The service under test.
   */
  protected TraceContextService $service;

  /**
   * Mock UUID generator.
   */
  protected UuidInterface|MockObject $uuid;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Counter for sequential UUID generation.
   */
  protected int $uuidCounter = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->uuidCounter = 0;

    $this->uuid = $this->createMock(UuidInterface::class);
    $this->uuid->method('generate')
      ->willReturnCallback(function (): string {
        $this->uuidCounter++;
        return sprintf('uuid-%04d', $this->uuidCounter);
      });

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TraceContextService($this->uuid, $this->logger);
  }

  /**
   * Tests nested span hierarchy: span1 -> span2 -> endSpan2 -> span3.
   *
   * After ending span2, creating span3 should have span1 as parent
   * (not span2, which is closed).
   *
   * @covers ::startSpan
   * @covers ::endSpan
   * @covers ::getParentSpanId
   * @covers ::getActiveSpanId
   */
  public function testNestedSpans(): void {
    $this->service->startTrace();

    // span1 is root.
    $span1 = $this->service->startSpan('copilot.chat');
    $this->assertNull($this->service->getParentSpanId($span1));
    $this->assertSame($span1, $this->service->getActiveSpanId());

    // span2 is child of span1.
    $span2 = $this->service->startSpan('agent.execute');
    $this->assertSame($span1, $this->service->getParentSpanId($span2));
    $this->assertSame($span2, $this->service->getActiveSpanId());

    // End span2 — active should revert to span1.
    $this->service->endSpan($span2);
    $this->assertSame($span1, $this->service->getActiveSpanId());

    // span3 is child of span1 (not span2).
    $span3 = $this->service->startSpan('rag.query');
    $this->assertSame($span1, $this->service->getParentSpanId($span3));
    $this->assertSame($span3, $this->service->getActiveSpanId());
  }

  /**
   * Tests that endSpan() restores the parent as the active span.
   *
   * @covers ::endSpan
   * @covers ::getActiveSpanId
   */
  public function testEndSpanRestoresParent(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('level1');
    $span2 = $this->service->startSpan('level2');
    $span3 = $this->service->startSpan('level3');

    // Active is span3.
    $this->assertSame($span3, $this->service->getActiveSpanId());

    // End span3 — active should be span2.
    $this->service->endSpan($span3);
    $this->assertSame($span2, $this->service->getActiveSpanId());

    // End span2 — active should be span1.
    $this->service->endSpan($span2);
    $this->assertSame($span1, $this->service->getActiveSpanId());

    // End span1 — active should be NULL (no parent).
    $this->service->endSpan($span1);
    $this->assertNull($this->service->getActiveSpanId());
  }

  /**
   * Tests that endSpan() with unknown span ID returns 0.
   *
   * @covers ::endSpan
   */
  public function testEndSpanNonExistent(): void {
    $this->service->startTrace();

    $duration = $this->service->endSpan('nonexistent-span-id');

    $this->assertSame(0, $duration);
  }

  /**
   * Tests that endSpan() returns non-negative milliseconds duration.
   *
   * @covers ::endSpan
   */
  public function testEndSpanDuration(): void {
    $this->service->startTrace();

    $spanId = $this->service->startSpan('timed.operation');

    // Small delay to ensure non-zero duration is possible.
    // The start time is captured at startSpan() with microtime(TRUE).
    $duration = $this->service->endSpan($spanId);

    // Duration should be >= 0 (in milliseconds).
    $this->assertGreaterThanOrEqual(0, $duration);
    $this->assertIsInt($duration);
  }

  /**
   * Tests that getParentSpanId() returns correct parent.
   *
   * @covers ::getParentSpanId
   */
  public function testGetParentSpanId(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('parent.op');
    $span2 = $this->service->startSpan('child.op');

    $this->assertNull($this->service->getParentSpanId($span1));
    $this->assertSame($span1, $this->service->getParentSpanId($span2));
  }

  /**
   * Tests that getParentSpanId() returns NULL for unknown spans.
   *
   * @covers ::getParentSpanId
   */
  public function testGetParentSpanIdUnknown(): void {
    $this->service->startTrace();

    $this->assertNull($this->service->getParentSpanId('unknown-span'));
  }

  /**
   * Tests that getOperationName() returns the correct operation.
   *
   * @covers ::getOperationName
   */
  public function testGetOperationName(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('SmartBaseAgent.execute');
    $span2 = $this->service->startSpan('JarabaRagService.query');

    $this->assertSame('SmartBaseAgent.execute', $this->service->getOperationName($span1));
    $this->assertSame('JarabaRagService.query', $this->service->getOperationName($span2));
  }

  /**
   * Tests that getOperationName() returns 'unknown' for unknown spans.
   *
   * @covers ::getOperationName
   */
  public function testGetOperationNameUnknown(): void {
    $this->service->startTrace();

    $this->assertSame('unknown', $this->service->getOperationName('nonexistent'));
  }

  /**
   * Tests explicit parent override in startSpan.
   *
   * @covers ::startSpan
   * @covers ::getParentSpanId
   */
  public function testStartSpanWithExplicitParent(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('first.op');
    $span2 = $this->service->startSpan('second.op');

    // span3 explicitly specifies span1 as parent (not the active span2).
    $span3 = $this->service->startSpan('third.op', $span1);

    $this->assertSame($span1, $this->service->getParentSpanId($span3));
  }

  /**
   * Tests deeply nested span hierarchy (4 levels).
   *
   * @covers ::startSpan
   * @covers ::endSpan
   * @covers ::getParentSpanId
   */
  public function testDeeplyNestedSpans(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('copilot.chat');
    $span2 = $this->service->startSpan('orchestrator.route');
    $span3 = $this->service->startSpan('agent.execute');
    $span4 = $this->service->startSpan('tool.run');

    // Verify full chain.
    $this->assertNull($this->service->getParentSpanId($span1));
    $this->assertSame($span1, $this->service->getParentSpanId($span2));
    $this->assertSame($span2, $this->service->getParentSpanId($span3));
    $this->assertSame($span3, $this->service->getParentSpanId($span4));

    // End all in reverse order.
    $this->service->endSpan($span4);
    $this->assertSame($span3, $this->service->getActiveSpanId());

    $this->service->endSpan($span3);
    $this->assertSame($span2, $this->service->getActiveSpanId());

    $this->service->endSpan($span2);
    $this->assertSame($span1, $this->service->getActiveSpanId());

    $this->service->endSpan($span1);
    $this->assertNull($this->service->getActiveSpanId());
  }

  /**
   * Tests that ending a non-active span does not affect the active span.
   *
   * @covers ::endSpan
   * @covers ::getActiveSpanId
   */
  public function testEndNonActiveSpanPreservesActive(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('first.op');
    $span2 = $this->service->startSpan('second.op');
    $span3 = $this->service->startSpan('third.op');

    // Active is span3. End span1 (not active) — should not change active.
    $this->service->endSpan($span1);
    $this->assertSame($span3, $this->service->getActiveSpanId());
  }

}
