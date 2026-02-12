<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestión de etapas del ciclo de vida del tenant.
 *
 * PROPÓSITO:
 * Trackea y transiciona tenants entre las etapas del ciclo:
 * trial → onboarding → adoption → expansion → renewal.
 *
 * LÓGICA:
 * - Cada tenant tiene un stage actual en State API.
 * - Las transiciones se registran con historial y timestamp.
 * - El growth score se calcula basado en la etapa y velocidad.
 */
class LifecycleStageService {

  /**
   * Constantes de etapas del ciclo de vida.
   */
  public const STAGE_TRIAL = 'trial';
  public const STAGE_ONBOARDING = 'onboarding';
  public const STAGE_ADOPTION = 'adoption';
  public const STAGE_EXPANSION = 'expansion';
  public const STAGE_RENEWAL = 'renewal';

  /**
   * Orden de las etapas (para validar transiciones).
   */
  protected const STAGE_ORDER = [
    self::STAGE_TRIAL => 0,
    self::STAGE_ONBOARDING => 1,
    self::STAGE_ADOPTION => 2,
    self::STAGE_EXPANSION => 3,
    self::STAGE_RENEWAL => 4,
  ];

  public function __construct(
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene la etapa actual de un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return string
   *   Etapa actual.
   */
  public function getCurrentStage(string $tenant_id): string {
    return $this->state->get("jaraba_cs.lifecycle.$tenant_id", self::STAGE_TRIAL);
  }

  /**
   * Transiciona un tenant a una nueva etapa.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   * @param string $new_stage
   *   Nueva etapa.
   *
   * @return bool
   *   TRUE si la transición fue exitosa.
   */
  public function transition(string $tenant_id, string $new_stage): bool {
    if (!isset(self::STAGE_ORDER[$new_stage])) {
      $this->logger->warning('Invalid lifecycle stage: @stage for tenant @id', [
        '@stage' => $new_stage,
        '@id' => $tenant_id,
      ]);
      return FALSE;
    }

    $current = $this->getCurrentStage($tenant_id);
    if ($current === $new_stage) {
      return TRUE;
    }

    // Registrar la transición en historial.
    $history = $this->getHistory($tenant_id);
    $history[] = [
      'from' => $current,
      'to' => $new_stage,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];

    $this->state->set("jaraba_cs.lifecycle.$tenant_id", $new_stage);
    $this->state->set("jaraba_cs.lifecycle_history.$tenant_id", $history);

    $this->logger->info('Tenant @id transitioned from @from to @to', [
      '@id' => $tenant_id,
      '@from' => $current,
      '@to' => $new_stage,
    ]);

    return TRUE;
  }

  /**
   * Obtiene el historial de transiciones de un tenant.
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return array
   *   Array de transiciones [{from, to, timestamp}, ...].
   */
  public function getHistory(string $tenant_id): array {
    return $this->state->get("jaraba_cs.lifecycle_history.$tenant_id", []);
  }

  /**
   * Calcula el growth score basado en el ciclo de vida (0-100).
   *
   * @param string $tenant_id
   *   ID del grupo tenant.
   *
   * @return int
   *   Puntuación de crecimiento (0-100).
   */
  public function getGrowthScore(string $tenant_id): int {
    $stage = $this->getCurrentStage($tenant_id);

    // Puntuación base por etapa.
    $stage_scores = [
      self::STAGE_TRIAL => 20,
      self::STAGE_ONBOARDING => 40,
      self::STAGE_ADOPTION => 60,
      self::STAGE_EXPANSION => 85,
      self::STAGE_RENEWAL => 75,
    ];

    $base = $stage_scores[$stage] ?? 30;

    // Bonus por velocidad de progresión.
    $history = $this->getHistory($tenant_id);
    if (count($history) >= 2) {
      $last = end($history);
      $prev = prev($history);
      $days = ($last['timestamp'] - $prev['timestamp']) / 86400;

      // Transición rápida (< 14 días) = bonus.
      if ($days < 14 && $days > 0) {
        $base = min(100, $base + 10);
      }
    }

    return $base;
  }

}
