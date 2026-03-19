<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Setup Wizard step: Explorar dashboard demo.
 *
 * S11-01: Primer paso específico del vertical demo.
 * Se completa automáticamente al entrar al dashboard (view_dashboard action).
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * ZEIGARNIK-PRELOAD-001: Este paso se auto-completa rápido, reforzando progreso.
 */
class DemoExplorarDashboardStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected DemoInteractiveService $demoService,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'demo_visitor.explorar_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'demo_visitor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Explora tu dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Visualiza las métricas de tu negocio simulado');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'analytics',
      'name' => 'chart-bar',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.demo_landing';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   *
   * Se completa cuando el usuario ha ejecutado 'view_dashboard' en su sesión.
   * Para demo, $tenantId es 0 — resolución via sessionId del request.
   */
  public function isComplete(int $tenantId): bool {
    $sessionId = $this->resolveSessionId();
    if ($sessionId === NULL) {
      return FALSE;
    }

    $session = $this->demoService->getDemoSession($sessionId);
    if ($session === NULL) {
      return FALSE;
    }

    foreach ($session['actions'] ?? [] as $action) {
      if (($action['action'] ?? '') === 'view_dashboard') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $complete = $this->isComplete($tenantId);
    return [
      'label' => $complete ? $this->t('Dashboard explorado') : $this->t('Pendiente'),
      'count' => $complete ? 1 : 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Resuelve el sessionId desde el request actual.
   *
   * El dashboard demo inyecta data-session-id en el DOM y
   * drupalSettings.demo.sessionId vía JS. En el request PHP,
   * usamos el route parameter o query string.
   */
  protected function resolveSessionId(): ?string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return NULL;
    }

    // Primero: route parameter (en /demo/dashboard/{sessionId}).
    $sessionId = $request->attributes->get('sessionId');
    if (is_string($sessionId) && $sessionId !== '') {
      return $sessionId;
    }

    // Segundo: query parameter (en /demo/start?lead_id=...).
    $sessionId = $request->query->get('session_id');
    if (is_string($sessionId) && $sessionId !== '') {
      return $sessionId;
    }

    return NULL;
  }

}
