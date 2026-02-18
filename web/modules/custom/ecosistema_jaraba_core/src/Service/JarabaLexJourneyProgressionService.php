<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Journey progression service para el vertical JarabaLex.
 *
 * Evalua reglas proactivas que determinan cuando y como el copiloto
 * debe intervenir para guiar al usuario en su recorrido legal.
 * Las reglas se basan en el estado del journey y la actividad del usuario.
 *
 * Reglas proactivas:
 * - inactivity_legal: Sin actividad 3 dias
 * - search_without_bookmark: Busca sin guardar resoluciones
 * - alert_milestone: Primera alerta configurada
 * - citation_ready: Listo para insertar citas
 * - upgrade_ready: Alto uso en plan free
 * - fiscal_cross_sell: Actividad DGT/TEAC detectada
 * - eu_exploration: Busquedas en fuentes europeas
 *
 * Plan Elevacion JarabaLex v1 — Fase 9.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiJourneyProgressionService
 */
class JarabaLexJourneyProgressionService {

  /**
   * Reglas proactivas de intervencion.
   */
  protected const PROACTIVE_RULES = [
    'inactivity_legal' => [
      'state' => 'discovery',
      'condition' => 'no_ia_3_days',
      'message' => 'Llevas unos dias sin buscar jurisprudencia. Puedo ayudarte con una busqueda rapida sobre tu area de practica.',
      'cta_label' => 'Buscar jurisprudencia',
      'cta_url' => '/legal/search',
      'channel' => 'fab_dot',
      'mode' => 'legal_copilot',
      'priority' => 10,
    ],
    'search_without_bookmark' => [
      'state' => 'activation',
      'condition' => 'searches_5_bookmarks_0',
      'message' => 'Has realizado varias busquedas pero no has guardado ninguna resolucion. Guarda las mas relevantes para acceder rapidamente.',
      'cta_label' => 'Ver mis busquedas',
      'cta_url' => '/legal/dashboard',
      'channel' => 'fab_expand',
      'mode' => 'legal_copilot',
      'priority' => 15,
    ],
    'alert_milestone' => [
      'state' => 'activation',
      'condition' => 'bookmarks_3_alerts_0',
      'message' => 'Tienes resoluciones guardadas. Configura alertas para que te notifique cuando se publiquen nuevas resoluciones similares.',
      'cta_label' => 'Configurar alertas',
      'cta_url' => '/legal/dashboard',
      'channel' => 'fab_dot',
      'mode' => 'legal_copilot',
      'priority' => 12,
    ],
    'citation_ready' => [
      'state' => 'engagement',
      'condition' => 'alerts_active_citations_0',
      'message' => 'Puedes insertar citas legales directamente en tus expedientes. Te muestro como funciona.',
      'cta_label' => 'Insertar cita',
      'cta_url' => '/legal/search',
      'channel' => 'fab_expand',
      'mode' => 'legal_copilot',
      'priority' => 8,
    ],
    'upgrade_ready' => [
      'state' => 'conversion',
      'condition' => 'usage_above_80_percent',
      'message' => 'Estas aprovechando JarabaLex al maximo. Con el plan Starter eliminas todos los limites y accedes a citas automaticas.',
      'cta_label' => 'Ver plan Starter',
      'cta_url' => '/upgrade?vertical=jarabalex&source=journey',
      'channel' => 'fab_expand',
      'mode' => 'legal_copilot',
      'priority' => 5,
    ],
    'fiscal_cross_sell' => [
      'state' => 'engagement',
      'condition' => 'dgt_teac_heavy_user',
      'message' => 'Veo que consultas doctrina tributaria frecuentemente. Complementa tus consultas con herramientas de compliance fiscal.',
      'cta_label' => 'Explorar compliance',
      'cta_url' => '/fiscal/dashboard',
      'channel' => 'fab_dot',
      'mode' => 'legal_copilot',
      'priority' => 20,
    ],
    'eu_exploration' => [
      'state' => 'engagement',
      'condition' => 'national_only_no_eu',
      'message' => 'Tus busquedas se limitan a fuentes nacionales. Las fuentes europeas (EUR-Lex, CURIA, HUDOC) pueden enriquecer tu analisis.',
      'cta_label' => 'Explorar fuentes UE',
      'cta_url' => '/legal/search?scope=eu',
      'channel' => 'fab_dot',
      'mode' => 'legal_copilot',
      'priority' => 25,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly TimeInterface $time,
    protected readonly StateInterface $state,
    protected readonly ?object $jarabalexFeatureGate = NULL,
  ) {}

  /**
   * Evalua reglas proactivas para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Primera regla que aplica con 'rule_id' anadido, o NULL.
   */
  public function evaluate(int $userId): ?array {
    $dismissed = $this->getDismissedRules($userId);

    foreach (self::PROACTIVE_RULES as $ruleId => $rule) {
      if (in_array($ruleId, $dismissed, TRUE)) {
        continue;
      }
      if ($this->checkCondition($userId, $rule['condition'])) {
        return array_merge($rule, ['rule_id' => $ruleId]);
      }
    }

    return NULL;
  }

  /**
   * Obtiene la accion pendiente cacheada (1h TTL).
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Regla pendiente o NULL.
   */
  public function getPendingAction(int $userId): ?array {
    $stateKey = "jarabalex_proactive_pending_{$userId}";
    $cached = $this->state->get($stateKey);

    if ($cached && ($cached['expires'] ?? 0) > $this->time->getRequestTime()) {
      return $cached['rule'];
    }

    $rule = $this->evaluate($userId);
    if ($rule) {
      $this->state->set($stateKey, [
        'rule' => $rule,
        'expires' => $this->time->getRequestTime() + 3600,
      ]);
    }

    return $rule;
  }

  /**
   * Descarta una regla para un usuario.
   */
  public function dismissAction(int $userId, string $ruleId): void {
    $dismissed = $this->getDismissedRules($userId);
    $dismissed[] = $ruleId;
    $this->state->set("jarabalex_proactive_dismissed_{$userId}", array_unique($dismissed));

    // Limpiar cache.
    $this->state->delete("jarabalex_proactive_pending_{$userId}");
  }

  /**
   * Evalua reglas en batch (para cron).
   *
   * @return int
   *   Numero de usuarios procesados.
   */
  public function evaluateBatch(): int {
    $processed = 0;
    try {
      $userIds = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

      foreach (array_slice(array_values($userIds), 0, 100) as $userId) {
        $this->getPendingAction((int) $userId);
        $processed++;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('JarabaLex journey batch evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $processed;
  }

  /**
   * Verifica una condicion de regla.
   */
  protected function checkCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'no_ia_3_days' => $this->checkNoActivity($userId, 3),
      'searches_5_bookmarks_0' => $this->checkSearchesWithoutBookmarks($userId),
      'bookmarks_3_alerts_0' => $this->checkBookmarksWithoutAlerts($userId),
      'alerts_active_citations_0' => $this->checkAlertsWithoutCitations($userId),
      'usage_above_80_percent' => $this->checkUsageAbove80($userId),
      'dgt_teac_heavy_user' => $this->checkFiscalHeavyUser($userId),
      'national_only_no_eu' => $this->checkNationalOnlyUser($userId),
      default => FALSE,
    };
  }

  /**
   * Sin actividad durante N dias.
   */
  protected function checkNoActivity(int $userId, int $days): bool {
    $stateKey = "jarabalex_last_activity_{$userId}";
    $lastActivity = (int) $this->state->get($stateKey, 0);
    if ($lastActivity === 0) {
      return FALSE;
    }
    $threshold = $this->time->getRequestTime() - ($days * 86400);
    return $lastActivity < $threshold;
  }

  /**
   * 5+ busquedas pero 0 bookmarks.
   */
  protected function checkSearchesWithoutBookmarks(int $userId): bool {
    try {
      if (!$this->jarabalexFeatureGate) {
        return FALSE;
      }
      $searchResult = $this->jarabalexFeatureGate->check($userId, 'searches_per_month');
      if (($searchResult->used ?? 0) < 5) {
        return FALSE;
      }
      $bookmarks = $this->entityTypeManager->getStorage('legal_bookmark')
        ->loadByProperties(['user_id' => $userId]);
      return empty($bookmarks);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * 3+ bookmarks pero 0 alertas.
   */
  protected function checkBookmarksWithoutAlerts(int $userId): bool {
    try {
      $bookmarks = $this->entityTypeManager->getStorage('legal_bookmark')
        ->loadByProperties(['user_id' => $userId]);
      if (count($bookmarks) < 3) {
        return FALSE;
      }
      $alerts = $this->entityTypeManager->getStorage('legal_alert')
        ->loadByProperties(['user_id' => $userId]);
      return empty($alerts);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Alertas activas pero 0 citas insertadas.
   */
  protected function checkAlertsWithoutCitations(int $userId): bool {
    try {
      $alerts = $this->entityTypeManager->getStorage('legal_alert')
        ->loadByProperties(['user_id' => $userId, 'is_active' => TRUE]);
      if (empty($alerts)) {
        return FALSE;
      }
      $citations = $this->entityTypeManager->getStorage('legal_citation')
        ->loadByProperties(['user_id' => $userId]);
      return empty($citations);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Uso > 80% del limite del plan.
   */
  protected function checkUsageAbove80(int $userId): bool {
    try {
      if (!$this->jarabalexFeatureGate) {
        return FALSE;
      }
      $plan = $this->jarabalexFeatureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return FALSE;
      }
      $result = $this->jarabalexFeatureGate->check($userId, 'searches_per_month');
      if ($result->limit <= 0) {
        return FALSE;
      }
      return (($result->used ?? 0) / $result->limit) >= 0.8;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Usuario con alta actividad en DGT/TEAC.
   */
  protected function checkFiscalHeavyUser(int $userId): bool {
    try {
      $bookmarks = $this->entityTypeManager->getStorage('legal_bookmark')
        ->loadByProperties(['user_id' => $userId]);
      $fiscalCount = 0;
      foreach ($bookmarks as $bookmark) {
        $sourceId = $bookmark->get('source_id')->value ?? '';
        if (in_array($sourceId, ['dgt', 'teac'], TRUE)) {
          $fiscalCount++;
        }
      }
      return $fiscalCount >= 3;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Solo busquedas nacionales, nunca europeas.
   */
  protected function checkNationalOnlyUser(int $userId): bool {
    try {
      if (!$this->jarabalexFeatureGate) {
        return FALSE;
      }
      $result = $this->jarabalexFeatureGate->check($userId, 'searches_per_month');
      if (($result->used ?? 0) < 10) {
        return FALSE;
      }
      $bookmarks = $this->entityTypeManager->getStorage('legal_bookmark')
        ->loadByProperties(['user_id' => $userId]);
      foreach ($bookmarks as $bookmark) {
        $sourceId = $bookmark->get('source_id')->value ?? '';
        if (in_array($sourceId, ['eurlex', 'curia', 'hudoc', 'edpb'], TRUE)) {
          return FALSE;
        }
      }
      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Obtiene reglas descartadas.
   */
  protected function getDismissedRules(int $userId): array {
    return $this->state->get("jarabalex_proactive_dismissed_{$userId}", []);
  }

}
