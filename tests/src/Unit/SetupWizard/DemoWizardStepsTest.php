<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\SetupWizard;

use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;
use Drupal\ecosistema_jaraba_core\SetupWizard\DemoConvertirCuentaRealStep;
use Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep;
use Drupal\ecosistema_jaraba_core\SetupWizard\DemoGenerarContenidoIAStep;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para los 3 Setup Wizard steps del vertical demo.
 *
 * SETUP-WIZARD-DAILY-001: Valida contratos de interfaz.
 * KERNEL-TEST-001: UnitTestCase porque NO necesita DB — solo reflexión.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep
 */
class DemoWizardStepsTest extends UnitTestCase {

  /**
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::getId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::getWizardId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::getWeight
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::getIcon
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::isOptional
   */
  public function testExplorarDashboardStepContract(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $requestStack = new RequestStack();

    $step = new DemoExplorarDashboardStep($demoService, $requestStack);

    $this->assertSame('demo_visitor.explorar_dashboard', $step->getId());
    $this->assertSame('demo_visitor', $step->getWizardId());
    $this->assertSame(10, $step->getWeight());
    $this->assertFalse($step->isOptional());
    $this->assertFalse($step->useSlidePanel());
    $this->assertSame('ecosistema_jaraba_core.demo_landing', $step->getRoute());

    $icon = $step->getIcon();
    $this->assertSame('dashboard', $icon['category']);
    $this->assertSame('chart-bar', $icon['name']);
    $this->assertSame('duotone', $icon['variant']);
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::isComplete
   */
  public function testExplorarDashboardIncompleteWithoutSession(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $requestStack = new RequestStack();

    $step = new DemoExplorarDashboardStep($demoService, $requestStack);

    // Sin request en stack → isComplete devuelve FALSE.
    $this->assertFalse($step->isComplete(0));
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoExplorarDashboardStep::isComplete
   */
  public function testExplorarDashboardCompleteWithAction(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $demoService->method('getDemoSession')
      ->willReturn([
        'actions' => [
          ['action' => 'view_dashboard', 'timestamp' => time()],
        ],
      ]);

    $request = new Request([], [], ['sessionId' => 'demo_abc123']);
    $requestStack = new RequestStack();
    $requestStack->push($request);

    $step = new DemoExplorarDashboardStep($demoService, $requestStack);

    $this->assertTrue($step->isComplete(0));
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoGenerarContenidoIAStep::getId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoGenerarContenidoIAStep::getWizardId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoGenerarContenidoIAStep::getWeight
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoGenerarContenidoIAStep::getIcon
   */
  public function testGenerarContenidoIAStepContract(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $requestStack = new RequestStack();

    $step = new DemoGenerarContenidoIAStep($demoService, $requestStack);

    $this->assertSame('demo_visitor.generar_contenido_ia', $step->getId());
    $this->assertSame('demo_visitor', $step->getWizardId());
    $this->assertSame(20, $step->getWeight());
    $this->assertFalse($step->isOptional());

    $icon = $step->getIcon();
    $this->assertSame('ai', $icon['category']);
    $this->assertSame('sparkles', $icon['name']);
    $this->assertSame('duotone', $icon['variant']);
  }

  /**
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoConvertirCuentaRealStep::getId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoConvertirCuentaRealStep::getWizardId
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoConvertirCuentaRealStep::getWeight
   * @covers \Drupal\ecosistema_jaraba_core\SetupWizard\DemoConvertirCuentaRealStep::isComplete
   */
  public function testConvertirCuentaStepAlwaysIncomplete(): void {
    $step = new DemoConvertirCuentaRealStep();

    $this->assertSame('demo_visitor.convertir_cuenta', $step->getId());
    $this->assertSame('demo_visitor', $step->getWizardId());
    $this->assertSame(30, $step->getWeight());
    $this->assertSame('user.register', $step->getRoute());
    // Siempre incompleto — es el CTA de conversión.
    $this->assertFalse($step->isComplete(0));
    $this->assertFalse($step->isComplete(999));

    $icon = $step->getIcon();
    $this->assertSame('business', $icon['category']);
    $this->assertSame('achievement', $icon['name']);
    $this->assertSame('duotone', $icon['variant']);
  }

  /**
   * Valida que todos los steps usan wizard_id = 'demo_visitor'.
   */
  public function testAllStepsShareWizardId(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $requestStack = new RequestStack();

    $steps = [
      new DemoExplorarDashboardStep($demoService, $requestStack),
      new DemoGenerarContenidoIAStep($demoService, $requestStack),
      new DemoConvertirCuentaRealStep(),
    ];

    foreach ($steps as $step) {
      $this->assertSame('demo_visitor', $step->getWizardId(), sprintf(
        'Step %s should have wizardId demo_visitor',
        $step->getId(),
      ));
    }
  }

  /**
   * Valida que los weights están en orden ascendente.
   */
  public function testStepsWeightOrder(): void {
    $demoService = $this->createMock(DemoInteractiveService::class);
    $requestStack = new RequestStack();

    $steps = [
      new DemoExplorarDashboardStep($demoService, $requestStack),
      new DemoGenerarContenidoIAStep($demoService, $requestStack),
      new DemoConvertirCuentaRealStep(),
    ];

    $previousWeight = -999;
    foreach ($steps as $step) {
      $this->assertGreaterThan($previousWeight, $step->getWeight(), sprintf(
        'Step %s weight (%d) should be > previous (%d)',
        $step->getId(),
        $step->getWeight(),
        $previousWeight,
      ));
      $previousWeight = $step->getWeight();
    }
  }

}
