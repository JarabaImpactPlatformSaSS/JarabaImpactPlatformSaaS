<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Setup Wizard step: Generar contenido con IA.
 *
 * S11-02: Segundo paso específico del vertical demo.
 * Se completa al ejecutar 'generate_story' en la sesión demo.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class DemoGenerarContenidoIAStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected DemoInteractiveService $demoService,
    protected RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'demo_visitor.generar_contenido_ia';
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
    return $this->t('Genera contenido con IA');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Descubre cómo la IA crea contenido para tu negocio');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ai',
      'name' => 'sparkles',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.demo_ai_playground';
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
      if (($action['action'] ?? '') === 'generate_story') {
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
      'label' => $complete ? $this->t('Contenido generado') : $this->t('Pendiente'),
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
   * Resuelve sessionId desde el request.
   */
  protected function resolveSessionId(): ?string {
    $request = $this->requestStack->getCurrentRequest();
    if ($request === NULL) {
      return NULL;
    }

    $sessionId = $request->attributes->get('sessionId');
    if (is_string($sessionId) && $sessionId !== '') {
      return $sessionId;
    }

    $sessionId = $request->query->get('session_id');
    if (is_string($sessionId) && $sessionId !== '') {
      return $sessionId;
    }

    return NULL;
  }

}
