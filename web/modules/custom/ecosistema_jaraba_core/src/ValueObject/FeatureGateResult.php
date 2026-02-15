<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ValueObject;

/**
 * Value Object inmutable para resultado de verificación de feature gating.
 *
 * Encapsula el resultado de verificar si un usuario puede acceder a una
 * feature limitada por plan en un vertical. Incluye datos para renderizar
 * el modal de upgrade si el límite fue alcanzado.
 *
 * Plan Elevación Empleabilidad v1 — Fase 4
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService
 * @see \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimit
 */
final class FeatureGateResult {

  /**
   * Construye un FeatureGateResult.
   *
   * @param bool $allowed
   *   Si el acceso a la feature está permitido.
   * @param int $remaining
   *   Usos restantes (-1 = ilimitado).
   * @param int $limit
   *   Límite total configurado (-1 = ilimitado).
   * @param int $used
   *   Usos consumidos en el período actual.
   * @param string $featureKey
   *   Clave de la feature verificada.
   * @param string $currentPlan
   *   Plan actual del usuario.
   * @param string $upgradeMessage
   *   Mensaje contextual para upgrade (vacío si allowed=true).
   * @param string $upgradePlan
   *   Plan recomendado para upgrade.
   */
  public function __construct(
    public readonly bool $allowed,
    public readonly int $remaining,
    public readonly int $limit,
    public readonly int $used,
    public readonly string $featureKey,
    public readonly string $currentPlan,
    public readonly string $upgradeMessage = '',
    public readonly string $upgradePlan = '',
  ) {
  }

  /**
   * Factory: crea un resultado permitido (sin límite o dentro del límite).
   */
  public static function allowed(string $featureKey, string $plan, int $remaining = -1, int $limit = -1, int $used = 0): self {
    return new self(
      allowed: TRUE,
      remaining: $remaining,
      limit: $limit,
      used: $used,
      featureKey: $featureKey,
      currentPlan: $plan,
    );
  }

  /**
   * Factory: crea un resultado denegado (límite alcanzado).
   */
  public static function denied(string $featureKey, string $plan, int $limit, int $used, string $upgradeMessage, string $upgradePlan): self {
    return new self(
      allowed: FALSE,
      remaining: 0,
      limit: $limit,
      used: $used,
      featureKey: $featureKey,
      currentPlan: $plan,
      upgradeMessage: $upgradeMessage,
      upgradePlan: $upgradePlan,
    );
  }

  /**
   * Si el acceso fue permitido.
   */
  public function isAllowed(): bool {
    return $this->allowed;
  }

  /**
   * Obtiene el mensaje de upgrade contextual.
   */
  public function getUpgradeMessage(): string {
    return $this->upgradeMessage;
  }

  /**
   * Obtiene el plan actual.
   */
  public function getCurrentPlan(): string {
    return $this->currentPlan;
  }

  /**
   * Obtiene el plan recomendado para upgrade.
   */
  public function getUpgradePlan(): string {
    return $this->upgradePlan;
  }

  /**
   * Convierte a array para respuestas JSON API.
   */
  public function toArray(): array {
    return [
      'allowed' => $this->allowed,
      'remaining' => $this->remaining,
      'limit' => $this->limit,
      'used' => $this->used,
      'feature_key' => $this->featureKey,
      'current_plan' => $this->currentPlan,
      'upgrade_message' => $this->upgradeMessage,
      'upgrade_plan' => $this->upgradePlan,
    ];
  }

}
