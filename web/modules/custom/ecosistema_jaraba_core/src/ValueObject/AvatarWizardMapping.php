<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ValueObject;

/**
 * Resultado inmutable de AvatarWizardBridgeService::resolveForCurrentUser().
 *
 * Contiene los identificadores necesarios para que el preprocess del perfil
 * pueda consultar SetupWizardRegistry y DailyActionsRegistry sin conocer
 * la lógica de mapping avatar→wizard.
 *
 * PROPOSITO:
 * Bridge entre la detección de avatar (quién es el usuario) y los registries
 * de onboarding (qué wizard/actions le corresponden).
 *
 * SINTAXIS:
 * Value Object inmutable — todos los campos son readonly.
 * Se instancia exclusivamente desde AvatarWizardBridgeService.
 *
 * EJEMPLO:
 *   $mapping = $bridge->resolveForCurrentUser();
 *   if ($mapping && $mapping->hasWizard()) {
 *     $wizard = $registry->getStepsForWizard($mapping->wizardId, $mapping->contextId);
 *   }
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService
 * @see \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry
 * @see \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry
 */
final class AvatarWizardMapping {

  /**
   * Construye un AvatarWizardMapping.
   *
   * @param string|null $wizardId
   *   ID del wizard de Setup Wizard (ej: 'candidato_empleo').
   *   NULL si el avatar no tiene wizard registrado.
   * @param string|null $dashboardId
   *   ID del dashboard de Daily Actions (ej: 'merchant_comercio').
   *   NULL si el avatar no tiene acciones diarias.
   * @param int $contextId
   *   ID de contexto para evaluar completitud de steps/actions.
   *   User-scoped: uid del usuario. Tenant-scoped: tenantId.
   * @param string $avatarType
   *   Tipo de avatar detectado (ej: 'jobseeker', 'merchant').
   * @param string|null $vertical
   *   Vertical asociada (ej: 'empleabilidad', 'comercioconecta').
   * @param string|null $dashboardRoute
   *   Nombre de ruta Drupal del dashboard vertical (para CTA "Ir a mi panel").
   */
  public function __construct(
    public readonly ?string $wizardId,
    public readonly ?string $dashboardId,
    public readonly int $contextId,
    public readonly string $avatarType,
    public readonly ?string $vertical,
    public readonly ?string $dashboardRoute,
  ) {}

  /**
   * Indica si hay wizard disponible para este mapping.
   */
  public function hasWizard(): bool {
    return $this->wizardId !== NULL;
  }

  /**
   * Indica si hay daily actions disponibles para este mapping.
   */
  public function hasDailyActions(): bool {
    return $this->dashboardId !== NULL;
  }

}
