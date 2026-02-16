<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical bridge service para el vertical JarabaLex.
 *
 * Evalua condiciones de usuario para sugerir transiciones a otros
 * verticales del ecosistema. Detecta oportunidades de cross-sell
 * basadas en la actividad legal del usuario.
 *
 * Bridges definidos:
 * - emprendimiento_legal: Profesional legal con interes emprendedor.
 * - empleabilidad_legal: Busqueda de empleo en sector juridico.
 * - fiscal_compliance: Actividad tributaria detectada (DGT/TEAC).
 * - formacion_continua: Formacion juridica especializada.
 *
 * Plan Elevacion JarabaLex v1 — Fase 8.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiCrossVerticalBridgeService
 */
class JarabaLexCrossVerticalBridgeService {

  /**
   * Definiciones de bridges cross-vertical.
   */
  protected const BRIDGES = [
    'emprendimiento_legal' => [
      'id' => 'emprendimiento_legal',
      'vertical' => 'emprendimiento',
      'icon_category' => 'business',
      'icon_name' => 'rocket',
      'color' => 'var(--ej-color-impulse, #FF8C42)',
      'message' => 'Tu conocimiento legal es un activo valioso para emprender. Descubre como convertirlo en un negocio.',
      'cta_label' => 'Diagnostico de Negocio Legal',
      'cta_url' => '/emprendimiento/diagnostico',
      'condition' => 'legal_plus_entrepreneur_interest',
      'priority' => 10,
    ],
    'empleabilidad_legal' => [
      'id' => 'empleabilidad_legal',
      'vertical' => 'empleabilidad',
      'icon_category' => 'business',
      'icon_name' => 'briefcase',
      'color' => 'var(--ej-color-primary, #2563eb)',
      'message' => 'Encuentra oportunidades laborales en el sector juridico. Tu experiencia con jurisprudencia es valorada.',
      'cta_label' => 'Ver ofertas juridicas',
      'cta_url' => '/empleo/sector/legal',
      'condition' => 'active_job_seeker',
      'priority' => 20,
    ],
    'fiscal_compliance' => [
      'id' => 'fiscal_compliance',
      'vertical' => 'fiscal',
      'icon_category' => 'business',
      'icon_name' => 'calculator',
      'color' => 'var(--ej-color-success, #10B981)',
      'message' => 'Detectamos actividad tributaria. Complementa tus consultas DGT con herramientas de cumplimiento fiscal.',
      'cta_label' => 'Explorar Compliance Fiscal',
      'cta_url' => '/fiscal/dashboard',
      'condition' => 'fiscal_search_activity',
      'priority' => 15,
    ],
    'formacion_continua' => [
      'id' => 'formacion_continua',
      'vertical' => 'formacion',
      'icon_category' => 'actions',
      'icon_name' => 'graduation-cap',
      'color' => 'var(--ej-color-info, #1976d2)',
      'message' => 'Refuerza tus areas de practica con formacion juridica especializada y acreditada.',
      'cta_label' => 'Catalogo formativo legal',
      'cta_url' => '/courses/category/legal',
      'condition' => 'high_search_activity',
      'priority' => 25,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evalua bridges disponibles para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Hasta 2 bridges ordenados por prioridad (menor = mayor prioridad).
   */
  public function evaluateBridges(int $userId): array {
    $bridges = [];
    $dismissed = $this->getDismissedBridges($userId);

    foreach (self::BRIDGES as $id => $bridge) {
      if (in_array($id, $dismissed, TRUE)) {
        continue;
      }
      if ($this->evaluateCondition($userId, $bridge['condition'])) {
        $bridges[] = $bridge;
      }
    }

    usort($bridges, fn($a, $b) => $a['priority'] <=> $b['priority']);
    return array_slice($bridges, 0, 2);
  }

  /**
   * Presenta un bridge al usuario con datos enriquecidos.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $bridgeId
   *   ID del bridge.
   *
   * @return array
   *   Datos del bridge para renderizado.
   */
  public function presentBridge(int $userId, string $bridgeId): array {
    if (!isset(self::BRIDGES[$bridgeId])) {
      return [];
    }

    return self::BRIDGES[$bridgeId];
  }

  /**
   * Registra la respuesta del usuario a un bridge.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $bridgeId
   *   ID del bridge.
   * @param string $response
   *   Respuesta: 'accepted', 'dismissed', 'deferred'.
   */
  public function trackBridgeResponse(int $userId, string $bridgeId, string $response): void {
    if ($response === 'dismissed') {
      $dismissed = $this->getDismissedBridges($userId);
      $dismissed[] = $bridgeId;
      \Drupal::state()->set("jarabalex_bridge_dismissed_{$userId}", array_unique($dismissed));
    }

    $this->logger->info('JarabaLex bridge @bridge: user @user responded @response', [
      '@bridge' => $bridgeId,
      '@user' => $userId,
      '@response' => $response,
    ]);
  }

  /**
   * Evalua una condicion de bridge.
   */
  protected function evaluateCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'legal_plus_entrepreneur_interest' => $this->checkEntrepreneurInterest($userId),
      'active_job_seeker' => $this->checkActiveJobSeeker($userId),
      'fiscal_search_activity' => $this->checkFiscalSearchActivity($userId),
      'high_search_activity' => $this->checkHighSearchActivity($userId),
      default => FALSE,
    };
  }

  /**
   * Verifica si el usuario tiene interes emprendedor.
   */
  protected function checkEntrepreneurInterest(int $userId): bool {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }
      // Verificar si el usuario tiene roles o campos de interes emprendedor.
      return $user->hasRole('entrepreneur') || $user->hasRole('business_owner');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Verifica si el usuario busca empleo activamente.
   */
  protected function checkActiveJobSeeker(int $userId): bool {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }
      return $user->hasRole('candidate') || $user->hasRole('job_seeker');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Verifica actividad de busqueda fiscal (DGT/TEAC).
   */
  protected function checkFiscalSearchActivity(int $userId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_bookmark');
      $bookmarks = $storage->loadByProperties([
        'user_id' => $userId,
      ]);
      foreach ($bookmarks as $bookmark) {
        $sourceId = $bookmark->get('source_id')->value ?? '';
        if (in_array($sourceId, ['dgt', 'teac'], TRUE)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Verifica alta actividad de busqueda (>= 20 busquedas).
   */
  protected function checkHighSearchActivity(int $userId): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.jarabalex_feature_gate')) {
        return FALSE;
      }
      /** @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService $featureGate */
      $featureGate = \Drupal::service('ecosistema_jaraba_core.jarabalex_feature_gate');
      $result = $featureGate->check($userId, 'searches_per_month');
      return ($result->used ?? 0) >= 20;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Obtiene bridges descartados por el usuario.
   */
  protected function getDismissedBridges(int $userId): array {
    return \Drupal::state()->get("jarabalex_bridge_dismissed_{$userId}", []);
  }

}
