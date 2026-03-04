<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Determina el nivel de revelacion del usuario actual.
 *
 * Estrategia "Submarinas con Periscopio": revelacion progresiva
 * en 4 niveles segun estado de autenticacion y plan del tenant.
 *
 * - landing: anonimo (no autenticado)
 * - trial: autenticado, plan free
 * - expansion: autenticado, plan starter/profesional/business
 * - enterprise: autenticado, plan enterprise o admin
 */
class RevelationLevelService {

  /**
   * Revelation level hierarchy.
   */
  private const LEVELS = ['landing', 'trial', 'expansion', 'enterprise'];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected TenantContextService $tenantContext,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Obtiene el nivel de revelacion actual para un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return string
   *   Nivel: landing, trial, expansion, enterprise.
   */
  public function getCurrentLevel(string $vertical): string {
    if ($this->currentUser->isAnonymous()) {
      return 'landing';
    }

    // Admin siempre tiene acceso enterprise.
    if ($this->currentUser->hasPermission('administer site configuration')) {
      return 'enterprise';
    }

    // Resolver plan del tenant.
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant === NULL) {
        return 'trial';
      }

      $plan = 'free';
      if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
        $plan = (string) $tenant->get('plan')->value;
      }

      return match ($plan) {
        'enterprise' => 'enterprise',
        'business', 'profesional', 'starter' => 'expansion',
        default => 'trial',
      };
    }
    catch (\Throwable) {
      return 'trial';
    }
  }

  /**
   * Verifica si el usuario puede acceder a un nivel requerido.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param string $requiredLevel
   *   Nivel minimo requerido.
   *
   * @return bool
   *   TRUE si el nivel actual >= nivel requerido.
   */
  public function canAccess(string $vertical, string $requiredLevel): bool {
    $currentLevel = $this->getCurrentLevel($vertical);
    $currentIndex = array_search($currentLevel, self::LEVELS, TRUE);
    $requiredIndex = array_search($requiredLevel, self::LEVELS, TRUE);

    if ($currentIndex === FALSE || $requiredIndex === FALSE) {
      return FALSE;
    }

    return $currentIndex >= $requiredIndex;
  }

  /**
   * Obtiene las features disponibles para el nivel actual.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Array de features disponibles con metadata.
   */
  public function getAvailableFeatures(string $vertical): array {
    $level = $this->getCurrentLevel($vertical);

    return [
      'level' => $level,
      'can_view_landing' => TRUE,
      'can_trial' => in_array($level, ['trial', 'expansion', 'enterprise'], TRUE),
      'can_expand' => in_array($level, ['expansion', 'enterprise'], TRUE),
      'is_enterprise' => $level === 'enterprise',
    ];
  }

}
