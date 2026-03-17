<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Entrepreneur Dashboard — premium SaaS dashboard.
 *
 * Carga datos reales del emprendedor: diagnóstico de negocio, canvas,
 * progreso de itinerario formativo, sesiones de mentoría programadas.
 *
 * PIPELINE-E2E-001: L1 (service injection + data loading)
 * PRESAVE-RESILIENCE-001: hasDefinition() + try-catch para cada entity
 * SETUP-WIZARD-DAILY-001: wizard + daily actions integrados
 * CONTROLLER-READONLY-001: no readonly en entityTypeManager heredado
 */
class EntrepreneurDashboardController extends ControllerBase {

  /**
   * Constructs an EntrepreneurDashboardController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
      $container->has('ecosistema_jaraba_core.daily_actions_registry')
        ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
    );
  }

  /**
   * Renders the entrepreneur dashboard with real data.
   */
  public function dashboard(): array {
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $uid = (int) $user->id();

    // Resolve tenant context for wizard/actions.
    $contextId = $uid;
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenantId = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenantId();
        if ($tenantId !== NULL && $tenantId > 0) {
          $contextId = $tenantId;
        }
      }
      catch (\Throwable $e) {
        // Fallback to uid.
      }
    }

    // Load real data — each in try-catch for resilience.
    $diagnostic = $this->loadLatestDiagnostic($uid);
    $canvas = $this->loadLatestCanvas($uid);
    $pathProgress = $this->loadPathProgress($uid);
    $upcomingSessions = $this->loadUpcomingSessions($uid);

    // Build KPIs from real data.
    $kpis = [
      'maturity_score' => $diagnostic ? (float) ($diagnostic->get('overall_score')->value ?? 0) : 0,
      'canvas_completeness' => $canvas ? (float) ($canvas->get('completeness_score')->value ?? 0) : 0,
      'path_progress' => $pathProgress['percentage'] ?? 0,
      'estimated_loss' => $diagnostic ? (float) ($diagnostic->get('estimated_loss')->value ?? 0) : 0,
    ];

    // Build next steps dynamically based on actual state.
    $nextSteps = $this->buildDynamicNextSteps($diagnostic, $canvas, $pathProgress);

    // Build canvas blocks mini-preview.
    $canvasBlocks = $canvas ? $this->buildCanvasBlocksSummary($canvas) : [];

    // Setup Wizard + Daily Actions.
    $setupWizard = NULL;
    $dailyActions = [];
    if ($this->wizardRegistry && $this->wizardRegistry->hasWizard('entrepreneur_tools')) {
      $setupWizard = $this->wizardRegistry->getStepsForWizard('entrepreneur_tools', $contextId);
    }
    if ($this->dailyActionsRegistry && $this->dailyActionsRegistry->hasDashboard('entrepreneur_tools')) {
      $dailyActions = $this->dailyActionsRegistry->getActionsForDashboard('entrepreneur_tools', $contextId);
    }

    // Maturity label mapping.
    $maturityLabel = '';
    if ($diagnostic && $diagnostic->hasField('maturity_level')) {
      $raw = $diagnostic->get('maturity_level')->value;
      $maturityLabels = [
        'analogico' => $this->t('Analógico'),
        'basico' => $this->t('Básico'),
        'intermedio' => $this->t('Intermedio'),
        'avanzado' => $this->t('Avanzado'),
        'lider' => $this->t('Líder digital'),
      ];
      $maturityLabel = $maturityLabels[$raw] ?? $raw ?? '';
    }

    // User avatar.
    $avatarUrl = '';
    if ($user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
      $file = $user->get('user_picture')->entity;
      if ($file) {
        $avatarUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      }
    }

    return [
      '#theme' => 'entrepreneur_dashboard',
      '#user_name' => $user->getDisplayName(),
      '#user_avatar_url' => $avatarUrl,
      '#user_initials' => mb_strtoupper(mb_substr($user->getDisplayName(), 0, 2)),
      '#diagnostic' => $diagnostic,
      '#canvas' => $canvas,
      '#canvas_blocks' => $canvasBlocks,
      '#canvas_id' => $canvas ? (int) $canvas->id() : 0,
      '#path_progress' => $pathProgress,
      '#upcoming_sessions' => $upcomingSessions,
      '#next_steps' => $nextSteps,
      '#kpis' => $kpis,
      '#maturity_label' => $maturityLabel,
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
      '#attached' => [
        'library' => [
          'jaraba_business_tools/entrepreneur-dashboard',
          'ecosistema_jaraba_theme/setup-wizard',
        ],
      ],
      '#cache' => [
        'tags' => array_merge(
          ['user:' . $uid, 'business_model_canvas_list', 'business_diagnostic_list'],
          $diagnostic ? $diagnostic->getCacheTags() : [],
          $canvas ? $canvas->getCacheTags() : [],
        ),
        'contexts' => ['user'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Loads the latest completed diagnostic for the user.
   */
  protected function loadLatestDiagnostic(int $uid): ?object {
    try {
      $etm = $this->entityTypeManager();
      if (!$etm->hasDefinition('business_diagnostic')) {
        return NULL;
      }
      $ids = $etm->getStorage('business_diagnostic')->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $uid)
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();
      return $ids ? $etm->getStorage('business_diagnostic')->load(reset($ids)) : NULL;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Loads the latest canvas for the user.
   */
  protected function loadLatestCanvas(int $uid): ?object {
    try {
      $etm = $this->entityTypeManager();
      if (!$etm->hasDefinition('business_model_canvas')) {
        return NULL;
      }
      $ids = $etm->getStorage('business_model_canvas')->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $uid)
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();
      return $ids ? $etm->getStorage('business_model_canvas')->load(reset($ids)) : NULL;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Loads learning path progress for the user.
   */
  protected function loadPathProgress(int $uid): array {
    try {
      $etm = $this->entityTypeManager();
      if (!$etm->hasDefinition('path_enrollment')) {
        return [];
      }
      $ids = $etm->getStorage('path_enrollment')->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $uid)
        ->condition('status', 'active')
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->execute();
      if (empty($ids)) {
        return [];
      }
      $enrollment = $etm->getStorage('path_enrollment')->load(reset($ids));
      return [
        'percentage' => (float) ($enrollment->get('progress_percent')->value ?? 0),
        'current_phase' => $enrollment->hasField('current_phase_id') && $enrollment->get('current_phase_id')->entity
          ? $enrollment->get('current_phase_id')->entity->label()
          : '',
      ];
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Loads upcoming mentoring sessions for the user.
   */
  protected function loadUpcomingSessions(int $uid): array {
    try {
      $etm = $this->entityTypeManager();
      if (!$etm->hasDefinition('mentoring_session')) {
        return [];
      }
      $ids = $etm->getStorage('mentoring_session')->getQuery()
        ->accessCheck(TRUE)
        ->condition('mentee_id', $uid)
        ->condition('status', ['scheduled', 'confirmed'], 'IN')
        ->condition('scheduled_start', date('Y-m-d\TH:i:s'), '>=')
        ->sort('scheduled_start', 'ASC')
        ->range(0, 3)
        ->execute();
      $sessions = [];
      foreach ($etm->getStorage('mentoring_session')->loadMultiple($ids) as $s) {
        $mentor = $s->hasField('mentor_id') ? $s->get('mentor_id')->entity : NULL;
        $sessions[] = [
          'mentor_name' => $mentor ? $mentor->getDisplayName() : $this->t('Mentor'),
          'scheduled_at' => $s->get('scheduled_start')->value ?? '',
          'status' => $s->get('status')->value ?? 'scheduled',
        ];
      }
      return $sessions;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Builds canvas blocks summary for mini-preview.
   */
  protected function buildCanvasBlocksSummary(object $canvas): array {
    try {
      $etm = $this->entityTypeManager();
      if (!$etm->hasDefinition('canvas_block')) {
        return [];
      }
      $blockTypes = [
        'key_partners', 'key_activities', 'key_resources',
        'value_propositions', 'customer_relationships', 'channels',
        'customer_segments', 'cost_structure', 'revenue_streams',
      ];
      $blocks = [];
      foreach ($blockTypes as $type) {
        $count = $etm->getStorage('canvas_block')->getQuery()
          ->accessCheck(TRUE)
          ->condition('canvas_id', $canvas->id())
          ->condition('block_type', $type)
          ->count()
          ->execute();
        $blocks[$type] = [
          'type' => $type,
          'count' => (int) $count,
          'has_items' => $count > 0,
        ];
      }
      return $blocks;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Builds dynamic next steps based on actual user state.
   */
  protected function buildDynamicNextSteps(?object $diagnostic, ?object $canvas, array $pathProgress): array {
    $steps = [];

    // Step 1: Diagnóstico.
    $hasDiagnostic = $diagnostic !== NULL;
    $steps[] = [
      'title' => $this->t('Completa tu diagnóstico de negocio'),
      'description' => $hasDiagnostic
        ? $this->t('Diagnóstico completado. Puedes repetirlo para actualizar tu puntuación.')
        : $this->t('Evalúa el estado digital actual de tu negocio.'),
      'completed' => $hasDiagnostic,
      'priority' => $hasDiagnostic ? 'low' : 'high',
      'icon_category' => 'analytics',
      'icon_name' => 'gauge',
      'url' => Url::fromRoute('jaraba_diagnostic.wizard.start')->toString(),
      'action_label' => $hasDiagnostic ? $this->t('Repetir') : $this->t('Empezar'),
    ];

    // Step 2: Canvas.
    $hasCanvas = $canvas !== NULL;
    $canvasComplete = $hasCanvas && (float) ($canvas->get('completeness_score')->value ?? 0) >= 80;
    $steps[] = [
      'title' => $this->t('Crea tu Business Model Canvas'),
      'description' => $canvasComplete
        ? $this->t('Canvas al @pct%. ¡Buen trabajo!', ['@pct' => number_format((float) $canvas->get('completeness_score')->value, 0)])
        : ($hasCanvas
          ? $this->t('Canvas al @pct%. Complétalo para desbloquear análisis IA.', ['@pct' => number_format((float) $canvas->get('completeness_score')->value, 0)])
          : $this->t('Define los 9 bloques de tu modelo de negocio.')),
      'completed' => $canvasComplete,
      'priority' => $canvasComplete ? 'low' : 'high',
      'icon_category' => 'business',
      'icon_name' => 'canvas',
      'url' => Url::fromRoute('jaraba_copilot_v2.bmc_dashboard')->toString(),
      'action_label' => $hasCanvas ? $this->t('Continuar') : $this->t('Crear'),
    ];

    // Step 3: Itinerario formativo.
    $hasPath = !empty($pathProgress);
    $steps[] = [
      'title' => $this->t('Explora itinerarios formativos'),
      'description' => $hasPath
        ? $this->t('Progreso: @pct%', ['@pct' => number_format($pathProgress['percentage'], 0)])
        : $this->t('Aprende habilidades digitales para tu negocio.'),
      'completed' => $hasPath && ($pathProgress['percentage'] ?? 0) >= 100,
      'priority' => $hasPath ? 'low' : 'medium',
      'icon_category' => 'education',
      'icon_name' => 'book-open',
      'url' => Url::fromRoute('jaraba_lms.my_learning')->toString(),
      'action_label' => $hasPath ? $this->t('Continuar') : $this->t('Explorar'),
    ];

    return $steps;
  }

}
