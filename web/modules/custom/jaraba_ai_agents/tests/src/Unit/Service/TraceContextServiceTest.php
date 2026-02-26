<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\jaraba_ai_agents\Service\TraceContextService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TraceContextService.
 *
 * Tests trace lifecycle, span creation, and context retrieval
 * for distributed observability across the AI stack.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\TraceContextService
 * @group jaraba_ai_agents
 */
class TraceContextServiceTest extends TestCase {

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
        return sprintf('00000000-0000-0000-0000-%012d', $this->uuidCounter);
      });

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TraceContextService($this->uuid, $this->logger);
  }

  /**
   * Tests that startTrace() returns a UUID and getCurrentTraceId() matches.
   *
   * @covers ::startTrace
   * @covers ::getCurrentTraceId
   */
  public function testStartTrace(): void {
    $traceId = $this->service->startTrace();

    $this->assertSame('00000000-0000-0000-0000-000000000001', $traceId);
    $this->assertSame($traceId, $this->service->getCurrentTraceId());
  }

  /**
   * Tests that a second startTrace() clears spans from the first.
   *
   * @covers ::startTrace
   * @covers ::startSpan
   * @covers ::getActiveSpanId
   */
  public function testStartTraceResets(): void {
    // Start first trace and create a span.
    $this->service->startTrace();
    $span1 = $this->service->startSpan('first.operation');
    $this->assertNotNull($this->service->getActiveSpanId());

    // Start second trace — should clear everything.
    $traceId2 = $this->service->startTrace();

    $this->assertSame($traceId2, $this->service->getCurrentTraceId());
    $this->assertNull($this->service->getActiveSpanId());

    // The old span should not be accessible.
    $this->assertNull($this->service->getParentSpanId($span1));
    $this->assertSame('unknown', $this->service->getOperationName($span1));
  }

  /**
   * Tests that startSpan() creates a span with auto-parenting.
   *
   * @covers ::startSpan
   * @covers ::getActiveSpanId
   */
  public function testStartSpan(): void {
    $this->service->startTrace();

    $span1 = $this->service->startSpan('agent.execute');

    $this->assertNotNull($span1);
    $this->assertSame($span1, $this->service->getActiveSpanId());

    // First span should have no parent (root span).
    $this->assertNull($this->service->getParentSpanId($span1));
  }

  /**
   * Tests that calling startSpan() without startTrace() auto-starts a trace.
   *
   * @covers ::startSpan
   * @covers ::getCurrentTraceId
   */
  public function testStartSpanAutoTrace(): void {
    // No explicit startTrace() call.
    $this->assertNull($this->service->getCurrentTraceId());

    $spanId = $this->service->startSpan('auto.trace.operation');

    // A trace should have been auto-created.
    $this->assertNotNull($this->service->getCurrentTraceId());
    $this->assertNotNull($spanId);
    $this->assertSame($spanId, $this->service->getActiveSpanId());
  }

  /**
   * Tests that getSpanContext() returns correct data for an active span.
   *
   * @covers ::getSpanContext
   * @covers ::startTrace
   * @covers ::startSpan
   */
  public function testGetSpanContext(): void {
    $traceId = $this->service->startTrace();
    $span1 = $this->service->startSpan('copilot.chat');
    $span2 = $this->service->startSpan('agent.execute');

    $context = $this->service->getSpanContext($span2);

    $this->assertSame($traceId, $context['trace_id']);
    $this->assertSame($span2, $context['span_id']);
    $this->assertSame($span1, $context['parent_span_id']);
    $this->assertSame('agent.execute', $context['operation_name']);
  }

  /**
   * Tests that getSpanContext() returns trace_id with null spans when no span.
   *
   * @covers ::getSpanContext
   */
  public function testGetSpanContextNoSpan(): void {
    $traceId = $this->service->startTrace();

    $context = $this->service->getSpanContext();

    $this->assertSame($traceId, $context['trace_id']);
    $this->assertNull($context['span_id']);
    $this->assertNull($context['parent_span_id']);
    $this->assertNull($context['operation_name']);
  }

  /**
   * Tests that getSpanContext() for a specific span works even if not active.
   *
   * @covers ::getSpanContext
   */
  public function testGetSpanContextForSpecificSpan(): void {
    $traceId = $this->service->startTrace();
    $span1 = $this->service->startSpan('first.op');
    $span2 = $this->service->startSpan('second.op');

    // Ask for context of span1 (not the active one).
    $context = $this->service->getSpanContext($span1);

    $this->assertSame($traceId, $context['trace_id']);
    $this->assertSame($span1, $context['span_id']);
    $this->assertNull($context['parent_span_id']); // span1 is root.
    $this->assertSame('first.op', $context['operation_name']);
  }

  /**
   * Tests that getSpanContext() without arguments uses active span.
   *
   * @covers ::getSpanContext
   */
  public function testGetSpanContextUsesActiveSpan(): void {
    $this->service->startTrace();
    $span1 = $this->service->startSpan('root.op');

    $context = $this->service->getSpanContext();

    $this->assertSame($span1, $context['span_id']);
    $this->assertSame('root.op', $context['operation_name']);
  }

  /**
   * Tests that getCurrentTraceId() returns NULL before any trace is started.
   *
   * @covers ::getCurrentTraceId
   */
  public function testGetCurrentTraceIdBeforeTrace(): void {
    $this->assertNull($this->service->getCurrentTraceId());
  }

  /**
   * Tests that getActiveSpanId() returns NULL before any span is started.
   *
   * @covers ::getActiveSpanId
   */
  public function testGetActiveSpanIdBeforeSpan(): void {
    $this->service->startTrace();

    $this->assertNull($this->service->getActiveSpanId());
  }

}
