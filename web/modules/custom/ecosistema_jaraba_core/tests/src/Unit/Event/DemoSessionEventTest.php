<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Event;

use Drupal\ecosistema_jaraba_core\Event\DemoSessionEvent;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemoSessionEvent.
 *
 * Verifica las constantes del ciclo de vida, las value actions,
 * y las propiedades readonly del evento.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Event\DemoSessionEvent
 * @group ecosistema_jaraba_core
 */
class DemoSessionEventTest extends TestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructorSetsReadonlyProperties(): void {
    $context = ['action' => 'generate_story', 'vertical' => 'agroconecta'];
    $event = new DemoSessionEvent('sess-001', 'producer', $context);

    $this->assertSame('sess-001', $event->sessionId);
    $this->assertSame('producer', $event->profileId);
    $this->assertSame($context, $event->context);
  }

  /**
   * @covers ::__construct
   */
  public function testConstructorDefaultsContextToEmptyArray(): void {
    $event = new DemoSessionEvent('sess-002', 'winery');

    $this->assertSame([], $event->context);
  }

  /**
   * Verifica que las 4 constantes de lifecycle están definidas.
   */
  public function testEventConstantsAreDefined(): void {
    $this->assertSame(
      'ecosistema_jaraba_core.demo_session.created',
      DemoSessionEvent::CREATED,
    );
    $this->assertSame(
      'ecosistema_jaraba_core.demo_session.value_action',
      DemoSessionEvent::VALUE_ACTION,
    );
    $this->assertSame(
      'ecosistema_jaraba_core.demo_session.conversion',
      DemoSessionEvent::CONVERSION,
    );
    $this->assertSame(
      'ecosistema_jaraba_core.demo_session.expired',
      DemoSessionEvent::EXPIRED,
    );
  }

  /**
   * Verifica las value actions definidas.
   */
  public function testValueActionsContainsExpectedActions(): void {
    $expected = [
      'generate_story',
      'browse_marketplace',
      'view_products',
      'click_cta',
    ];

    $this->assertSame($expected, DemoSessionEvent::VALUE_ACTIONS);
  }

  /**
   * Verifica que el evento extiende Event de Drupal.
   */
  public function testExtendsSymfonyCompatibleEvent(): void {
    $event = new DemoSessionEvent('sess-003', 'teacher');

    $this->assertInstanceOf(\Drupal\Component\EventDispatcher\Event::class, $event);
  }

}
