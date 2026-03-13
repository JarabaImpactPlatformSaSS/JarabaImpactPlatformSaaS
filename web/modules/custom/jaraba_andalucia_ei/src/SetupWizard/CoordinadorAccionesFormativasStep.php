<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Acciones Formativas — Coordinador Setup Wizard.
 *
 * Checks if at least one AccionFormativaEi exists for the tenant.
 * Hybrid: warns if VoBo is pending on any action.
 */
class CoordinadorAccionesFormativasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.acciones_formativas';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'coordinador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Acciones Formativas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea las acciones formativas y solicita el VoBo SAE');
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
      'category' => 'education',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.hub.accion_formativa.add';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getActionCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getActionCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin acciones formativas'),
      ];
    }

    $data = [
      'count' => $count,
      'label' => $this->t('@count acción(es) creada(s)', ['@count' => $count]),
    ];

    // Hybrid: check VoBo pending.
    $voboPending = $this->getVoboPendingCount($tenantId);
    if ($voboPending > 0) {
      $data['warning'] = $this->t('@count VoBo pendiente(s)', ['@count' => $voboPending]);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets total action count (excluding borrador).
   */
  protected function getActionCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'borrador', '<>')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Gets count of actions with VoBo pending status.
   */
  protected function getVoboPendingCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', ['pendiente_vobo', 'vobo_enviado'], 'IN')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
