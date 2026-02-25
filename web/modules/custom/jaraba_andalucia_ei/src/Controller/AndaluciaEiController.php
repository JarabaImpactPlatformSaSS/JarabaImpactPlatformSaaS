<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\AndaluciaEiCrossVerticalBridgeService;
use Drupal\ecosistema_jaraba_core\Service\AndaluciaEiHealthScoreService;
use Drupal\ecosistema_jaraba_core\Service\AndaluciaEiJourneyProgressionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador principal del modulo Andalucia +ei.
 */
class AndaluciaEiController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiHealthScoreService|null $healthScoreService
   *   Servicio opcional de health score.
   * @param \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiCrossVerticalBridgeService|null $bridgeService
   *   Servicio opcional de cross-vertical bridges.
   * @param \Drupal\ecosistema_jaraba_core\Service\AndaluciaEiJourneyProgressionService|null $journeyService
   *   Servicio opcional de journey progression.
   */
  public function __construct(
    protected readonly ?AndaluciaEiHealthScoreService $healthScoreService,
    protected readonly ?AndaluciaEiCrossVerticalBridgeService $bridgeService,
    protected readonly ?AndaluciaEiJourneyProgressionService $journeyService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->has('ecosistema_jaraba_core.andalucia_ei_health_score')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_health_score') : NULL,
      $container->has('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge') : NULL,
      $container->has('ecosistema_jaraba_core.andalucia_ei_journey_progression')
        ? $container->get('ecosistema_jaraba_core.andalucia_ei_journey_progression') : NULL,
    );
  }

  /**
   * Dashboard frontend del programa Andalucia +ei.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(Request $request): array {
    $user = $this->currentUser();

    // Verificar si el usuario es participante.
    $participante = $this->entityTypeManager()
      ->getStorage('programa_participante_ei')
      ->loadByProperties(['uid' => $user->id()]);

    $participante = reset($participante) ?: NULL;

    // Si no es participante, buscar solicitud pendiente por email.
    $solicitud = NULL;
    if (!$participante && $user->isAuthenticated()) {
      $userEntity = $this->entityTypeManager()
        ->getStorage('user')
        ->load($user->id());
      $email = $userEntity?->getEmail();
      if ($email) {
        $solicitudes = $this->entityTypeManager()
          ->getStorage('solicitud_ei')
          ->loadByProperties(['email' => $email]);
        $solicitud = reset($solicitudes) ?: NULL;
      }
    }

    // Determinar si el usuario tiene permisos de gestion (AND-001).
    $isAdmin = $user->hasPermission('create programa participante ei')
      || $user->hasPermission('administer andalucia ei');

    // Plan Elevacion Andalucia +ei v1 — Fase 12: Enriquecer dashboard.
    $healthScore = NULL;
    $bridges = [];
    $proactiveAction = NULL;
    $userId = (int) $user->id();

    if ($participante) {
      // Health score del participante.
      if ($this->healthScoreService) {
        try {
          $healthScore = $this->healthScoreService
            ->calculateUserHealth($userId);
        }
        catch (\Exception $e) {
          // Non-critical.
        }
      }

      // Cross-vertical bridges disponibles.
      if ($this->bridgeService) {
        try {
          $bridges = $this->bridgeService
            ->evaluateBridges($userId);
        }
        catch (\Exception $e) {
          // Non-critical.
        }
      }

      // Proactive AI action (FAB dot/expand).
      if ($this->journeyService) {
        try {
          $proactiveAction = $this->journeyService
            ->getPendingAction($userId);
        }
        catch (\Exception $e) {
          // Non-critical.
        }
      }
    }

    return [
      '#theme' => 'andalucia_ei_dashboard',
      '#participante' => $participante,
      '#solicitud' => $solicitud,
      '#is_admin' => $isAdmin,
      '#health_score' => $healthScore,
      '#bridges' => $bridges,
      '#proactive_action' => $proactiveAction,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
          'ecosistema_jaraba_theme/slide-panel',
        ],
        'drupalSettings' => [
          'andaluciaEi' => [
            'proactiveAction' => $proactiveAction,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['programa_participante_ei_list'],
        'max-age' => 300,
      ],
    ];
  }

}
