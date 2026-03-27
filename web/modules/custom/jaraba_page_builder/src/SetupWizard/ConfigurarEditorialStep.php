<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Wizard step global: Verificar pagina editorial.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * Wizard ID: __global__ (inyectado en todos los wizards admin).
 * Siempre completado (pagina de plataforma, no configurable por tenant).
 */
class ConfigurarEditorialStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.configurar_editorial';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Pagina editorial publicada');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Landing de conversion del libro Equilibrio Autonomo disponible en /editorial/equilibrio-autonomo.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 97;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'content',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_page_builder.editorial_equilibrio_autonomo';
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
   * Siempre completado: la pagina editorial es estatica y no requiere
   * configuracion por parte del tenant.
   */
  public function isComplete(int $tenantId): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [
      'label' => $this->t('Publicada'),
      'count' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
