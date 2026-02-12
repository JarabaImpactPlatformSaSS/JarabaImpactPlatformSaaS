<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_emprendimiento\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Tracker del journey de emprendimiento con mapa visual.
 */
class EmprendimientoJourneyTracker {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EmprendimientoCredentialService $credentialService;
  protected EmprendimientoExpertiseService $expertiseService;
  protected LoggerInterface $logger;

  /**
   * Definición de fases del journey.
   */
  public const PHASES = [
    'diagnostico' => [
      'label' => 'Diagnóstico',
      'description' => 'Evaluación inicial del negocio',
      'badges' => ['diagnostico_completado'],
      'order' => 1,
    ],
    'formacion' => [
      'label' => 'Formación Digital',
      'description' => 'Desarrollo de competencias digitales',
      'badges' => ['madurez_digital_basica', 'madurez_digital_intermedia', 'madurez_digital_avanzada'],
      'order' => 2,
    ],
    'ideacion' => [
      'label' => 'Ideación y Canvas',
      'description' => 'Modelado y validación del negocio',
      'badges' => ['business_canvas_creator', 'business_canvas_validated', 'financial_architect'],
      'order' => 3,
    ],
    'prototipado' => [
      'label' => 'Prototipado y MVP',
      'description' => 'Creación y lanzamiento del producto mínimo viable',
      'badges' => ['pitch_ready', 'mvp_launched', 'mvp_validated'],
      'order' => 4,
    ],
    'comercializacion' => [
      'label' => 'Comercialización',
      'description' => 'Primeras ventas y tracción',
      'badges' => ['first_sale', 'first_mentoring_session'],
      'order' => 5,
    ],
    'escalamiento' => [
      'label' => 'Escalamiento',
      'description' => 'Diplomas y reconocimiento experto',
      'badges' => ['emprendedor_digital_basico', 'emprendedor_digital_avanzado', 'transformador_digital_expert'],
      'order' => 6,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EmprendimientoCredentialService $credentialService,
    EmprendimientoExpertiseService $expertiseService,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->credentialService = $credentialService;
    $this->expertiseService = $expertiseService;
    $this->logger = $loggerFactory->get('jaraba_credentials_emprendimiento');
  }

  /**
   * Genera el mapa visual del journey con progreso.
   */
  public function getJourneyMap(int $uid): array {
    $userTemplates = $this->credentialService->getUserEmprendimientoTemplates($uid);
    $phases = [];

    foreach (self::PHASES as $key => $phase) {
      $totalBadges = count($phase['badges']);
      $completedBadges = array_intersect($phase['badges'], $userTemplates);
      $percent = $totalBadges > 0 ? (int) round((count($completedBadges) / $totalBadges) * 100) : 0;

      $badges = [];
      foreach ($phase['badges'] as $machineName) {
        $badges[] = [
          'machine_name' => $machineName,
          'completed' => in_array($machineName, $userTemplates, TRUE),
        ];
      }

      $phases[$key] = [
        'key' => $key,
        'label' => $phase['label'],
        'description' => $phase['description'],
        'order' => $phase['order'],
        'badges' => $badges,
        'total' => $totalBadges,
        'completed_count' => count($completedBadges),
        'percent' => $percent,
        'status' => $percent >= 100 ? 'completed' : ($percent > 0 ? 'in_progress' : 'locked'),
      ];
    }

    return $phases;
  }

  /**
   * Obtiene el siguiente badge recomendado.
   */
  public function getNextRecommendedBadge(int $uid): ?array {
    $userTemplates = $this->credentialService->getUserEmprendimientoTemplates($uid);

    foreach (self::PHASES as $key => $phase) {
      foreach ($phase['badges'] as $machineName) {
        if (!in_array($machineName, $userTemplates, TRUE)) {
          return [
            'machine_name' => $machineName,
            'phase' => $key,
            'phase_label' => $phase['label'],
          ];
        }
      }
    }

    return NULL;
  }

  /**
   * Obtiene el progreso por fase.
   */
  public function getProgressByPhase(int $uid): array {
    $journeyMap = $this->getJourneyMap($uid);
    $result = [];

    foreach ($journeyMap as $key => $phase) {
      $result[$key] = [
        'label' => $phase['label'],
        'percent' => $phase['percent'],
        'completed' => $phase['completed_count'],
        'total' => $phase['total'],
      ];
    }

    return $result;
  }

}
