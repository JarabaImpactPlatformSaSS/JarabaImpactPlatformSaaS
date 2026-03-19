<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\DailyActions;

use Drupal\ecosistema_jaraba_core\DailyActions\ChatCopilotDemoAction;
use Drupal\ecosistema_jaraba_core\DailyActions\ConvertirCuentaDemoAction;
use Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para las 3 Daily Actions del vertical demo.
 *
 * SETUP-WIZARD-DAILY-001: Valida contratos de interfaz.
 * KERNEL-TEST-001: UnitTestCase porque NO necesita DB.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction
 */
class DemoDailyActionsTest extends UnitTestCase {

  /**
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction::getId
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction::getDashboardId
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction::isPrimary
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ExplorarVerticalDemoAction::getColor
   */
  public function testExplorarVerticalActionContract(): void {
    $action = new ExplorarVerticalDemoAction();

    $this->assertSame('demo_visitor.explorar_vertical', $action->getId());
    $this->assertSame('demo_visitor', $action->getDashboardId());
    $this->assertTrue($action->isPrimary());
    $this->assertSame('azul-corporativo', $action->getColor());
    $this->assertSame(10, $action->getWeight());
    $this->assertSame('#metrics', $action->getHrefOverride());
    $this->assertFalse($action->useSlidePanel());

    $icon = $action->getIcon();
    $this->assertSame('analytics', $icon['category']);
    $this->assertSame('duotone', $icon['variant']);
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ChatCopilotDemoAction::getId
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ChatCopilotDemoAction::getDashboardId
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ChatCopilotDemoAction::getColor
   */
  public function testChatCopilotActionContract(): void {
    $action = new ChatCopilotDemoAction();

    $this->assertSame('demo_visitor.chat_copilot', $action->getId());
    $this->assertSame('demo_visitor', $action->getDashboardId());
    $this->assertFalse($action->isPrimary());
    $this->assertSame('naranja-impulso', $action->getColor());
    $this->assertSame(20, $action->getWeight());
    $this->assertNull($action->getHrefOverride());
    $this->assertSame('ecosistema_jaraba_core.demo_ai_playground', $action->getRoute());

    $icon = $action->getIcon();
    $this->assertSame('ai', $icon['category']);
    $this->assertSame('brain', $icon['name']);
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ConvertirCuentaDemoAction::getId
   * @covers \Drupal\ecosistema_jaraba_core\DailyActions\ConvertirCuentaDemoAction::getContext
   */
  public function testConvertirCuentaActionWithBadge(): void {
    $action = new ConvertirCuentaDemoAction();

    $this->assertSame('demo_visitor.convertir_cuenta', $action->getId());
    $this->assertSame('demo_visitor', $action->getDashboardId());
    $this->assertFalse($action->isPrimary());
    $this->assertSame('verde-innovacion', $action->getColor());
    $this->assertSame(30, $action->getWeight());
    $this->assertSame('user.register', $action->getRoute());

    // Badge tipo 'warning' con valor 1 para generar urgencia.
    $context = $action->getContext(0);
    $this->assertSame(1, $context['badge']);
    $this->assertSame('warning', $context['badge_type']);
    $this->assertTrue($context['visible']);
  }

  /**
   * Valida que SOLO la acción primaria tiene isPrimary() = TRUE.
   */
  public function testOnlyOneActionIsPrimary(): void {
    $actions = [
      new ExplorarVerticalDemoAction(),
      new ChatCopilotDemoAction(),
      new ConvertirCuentaDemoAction(),
    ];

    $primaryCount = 0;
    foreach ($actions as $action) {
      if ($action->isPrimary()) {
        $primaryCount++;
      }
    }

    $this->assertSame(1, $primaryCount, 'Solo UNA acción debe ser primaria por dashboard.');
  }

  /**
   * Valida que todos comparten dashboardId = 'demo_visitor'.
   */
  public function testAllActionsShareDashboardId(): void {
    $actions = [
      new ExplorarVerticalDemoAction(),
      new ChatCopilotDemoAction(),
      new ConvertirCuentaDemoAction(),
    ];

    foreach ($actions as $action) {
      $this->assertSame('demo_visitor', $action->getDashboardId());
    }
  }

  /**
   * Valida que weights están en orden ascendente y son múltiplos de 10.
   */
  public function testActionsWeightOrderAndConvention(): void {
    $actions = [
      new ExplorarVerticalDemoAction(),
      new ChatCopilotDemoAction(),
      new ConvertirCuentaDemoAction(),
    ];

    $previousWeight = -999;
    foreach ($actions as $action) {
      $weight = $action->getWeight();
      $this->assertSame(0, $weight % 10, sprintf(
        'Acción %s weight (%d) debe ser múltiplo de 10.',
        $action->getId(),
        $weight,
      ));
      $this->assertGreaterThan($previousWeight, $weight);
      $previousWeight = $weight;
    }
  }

  /**
   * Valida que todas las acciones son siempre visibles para demo.
   */
  public function testAllActionsAlwaysVisible(): void {
    $actions = [
      new ExplorarVerticalDemoAction(),
      new ChatCopilotDemoAction(),
      new ConvertirCuentaDemoAction(),
    ];

    foreach ($actions as $action) {
      $context = $action->getContext(0);
      $this->assertTrue($context['visible'], sprintf(
        'Acción %s debe ser visible en demo.',
        $action->getId(),
      ));
    }
  }

}
