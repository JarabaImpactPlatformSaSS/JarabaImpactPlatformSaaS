<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_onboarding\Entity\TenantOnboardingProgress;
use Drupal\jaraba_onboarding\Service\LogoColorExtractorService;
use Drupal\jaraba_onboarding\Service\TenantOnboardingWizardService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del wizard de onboarding de 7 pasos.
 *
 * Gestiona la interfaz frontend del wizard de configuracion
 * inicial del tenant. Cada paso es una ruta independiente
 * con template dedicado.
 *
 * Fase 5 — Doc 179.
 *
 * Rutas:
 *  - /onboarding/wizard/welcome     (Paso 1)
 *  - /onboarding/wizard/identity    (Paso 2)
 *  - /onboarding/wizard/fiscal      (Paso 3, solo commerce)
 *  - /onboarding/wizard/payments    (Paso 4, solo commerce)
 *  - /onboarding/wizard/team        (Paso 5, saltable)
 *  - /onboarding/wizard/content     (Paso 6)
 *  - /onboarding/wizard/launch      (Paso 7)
 *  - /onboarding/wizard/stripe-callback
 */
class TenantOnboardingWizardController extends ControllerBase {

  /**
   * Mapeo de step number a route name.
   */
  protected const STEP_ROUTES = [
    1 => 'jaraba_onboarding.wizard.welcome',
    2 => 'jaraba_onboarding.wizard.identity',
    3 => 'jaraba_onboarding.wizard.fiscal',
    4 => 'jaraba_onboarding.wizard.payments',
    5 => 'jaraba_onboarding.wizard.team',
    6 => 'jaraba_onboarding.wizard.content',
    7 => 'jaraba_onboarding.wizard.launch',
  ];

  /**
   * Mapeo de vertical a color token.
   */
  protected const VERTICAL_COLORS = [
    'agroconecta' => 'agro',
    'comercioconecta' => 'success',
    'serviciosconecta' => 'innovation',
    'empleabilidad' => 'innovation',
    'emprendimiento' => 'impulse',
  ];

  /**
   * Mapeo de vertical a label.
   */
  protected const VERTICAL_LABELS = [
    'agroconecta' => 'AgroConecta',
    'comercioconecta' => 'ComercioConecta',
    'serviciosconecta' => 'ServiciosConecta',
    'empleabilidad' => 'Empleabilidad',
    'emprendimiento' => 'Emprendimiento',
  ];

  public function __construct(
    protected TenantOnboardingWizardService $wizardService,
    protected LogoColorExtractorService $logoColorExtractor,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_onboarding.wizard'),
      $container->get('jaraba_onboarding.logo_color_extractor'),
    );
  }

  // =========================================================================
  // STEP 1: WELCOME
  // =========================================================================

  /**
   * Paso 1: Bienvenida — Confirmar vertical detectada.
   */
  public function stepWelcome(Request $request): array|RedirectResponse {
    $vertical = $request->query->get('vertical', 'emprendimiento');
    $progress = $this->wizardService->getOrCreateProgress($vertical);

    if (!$progress) {
      $this->messenger()->addError($this->t('Error iniciando el wizard de configuracion.'));
      return new RedirectResponse(Url::fromRoute('jaraba_onboarding.dashboard')->toString());
    }

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_WELCOME, [
      '#theme' => 'onboarding_wizard_step_welcome',
      '#vertical' => $vertical,
      '#vertical_label' => self::VERTICAL_LABELS[$vertical] ?? $vertical,
      '#vertical_color' => self::VERTICAL_COLORS[$vertical] ?? 'impulse',
      '#step_data' => $progress->getStepData()[1] ?? [],
    ]);
  }

  // =========================================================================
  // STEP 2: IDENTITY
  // =========================================================================

  /**
   * Paso 2: Identidad de Marca — Logo + IA paleta + nombre.
   */
  public function stepIdentity(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';
    $stepData = $progress->getStepData()[2] ?? [];

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_IDENTITY, [
      '#theme' => 'onboarding_wizard_step_identity',
      '#vertical' => $vertical,
      '#colors' => $stepData['colors'] ?? [
        'primary' => '#233D63',
        'secondary' => '#FF8C42',
        'accent' => '#00A9A5',
      ],
      '#step_data' => $stepData,
    ]);
  }

  // =========================================================================
  // STEP 3: FISCAL
  // =========================================================================

  /**
   * Paso 3: Datos Fiscales — NIF/CIF + direccion (solo commerce).
   */
  public function stepFiscal(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';

    // Auto-saltar si no es vertical commerce.
    if (!in_array($vertical, ['agroconecta', 'comercioconecta', 'serviciosconecta'], TRUE)) {
      return new RedirectResponse(
        Url::fromRoute('jaraba_onboarding.wizard.team')->toString()
      );
    }

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_FISCAL, [
      '#theme' => 'onboarding_wizard_step_fiscal',
      '#vertical' => $vertical,
      '#step_data' => $progress->getStepData()[3] ?? [],
    ]);
  }

  // =========================================================================
  // STEP 4: PAYMENTS
  // =========================================================================

  /**
   * Paso 4: Configurar Pagos — Stripe Connect redirect.
   */
  public function stepPayments(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';

    if (!in_array($vertical, ['agroconecta', 'comercioconecta', 'serviciosconecta'], TRUE)) {
      return new RedirectResponse(
        Url::fromRoute('jaraba_onboarding.wizard.team')->toString()
      );
    }

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_PAYMENTS, [
      '#theme' => 'onboarding_wizard_step_payments',
      '#vertical' => $vertical,
      '#stripe_url' => '',
      '#step_data' => $progress->getStepData()[4] ?? [],
    ]);
  }

  // =========================================================================
  // STEP 5: TEAM
  // =========================================================================

  /**
   * Paso 5: Tu Equipo — Invitar colaboradores (saltable).
   */
  public function stepTeam(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_TEAM, [
      '#theme' => 'onboarding_wizard_step_team',
      '#vertical' => $vertical,
      '#step_data' => $progress->getStepData()[5] ?? [],
    ]);
  }

  // =========================================================================
  // STEP 6: CONTENT
  // =========================================================================

  /**
   * Paso 6: Contenido Inicial — Primer producto/servicio segun vertical.
   */
  public function stepContent(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';
    $contentLabel = match ($vertical) {
      'agroconecta', 'comercioconecta' => $this->t('tu primer producto'),
      'serviciosconecta' => $this->t('tu primer servicio'),
      'empleabilidad' => $this->t('tu perfil profesional'),
      'emprendimiento' => $this->t('tu plan de negocio'),
      default => $this->t('tu contenido inicial'),
    };

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_CONTENT, [
      '#theme' => 'onboarding_wizard_step_content',
      '#vertical' => $vertical,
      '#content_type_label' => $contentLabel,
      '#step_data' => $progress->getStepData()[6] ?? [],
    ]);
  }

  // =========================================================================
  // STEP 7: LAUNCH
  // =========================================================================

  /**
   * Paso 7: Lanzamiento — Confetti + preview + compartir.
   */
  public function stepLaunch(): array|RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $vertical = $progress->get('vertical')->value ?? '';
    $stepData = $progress->getStepData();
    $tenantName = $stepData[2]['business_name'] ?? $stepData[1]['vertical_label'] ?? 'Mi Negocio';

    return $this->buildWizardPage($progress, TenantOnboardingProgress::STEP_LAUNCH, [
      '#theme' => 'onboarding_wizard_step_launch',
      '#vertical' => $vertical,
      '#tenant_name' => $tenantName,
      '#preview_url' => '',
      '#step_data' => $stepData,
    ]);
  }

  // =========================================================================
  // STRIPE CALLBACK
  // =========================================================================

  /**
   * Callback de Stripe Connect Onboarding.
   */
  public function stripeCallback(Request $request): RedirectResponse {
    $progress = $this->wizardService->getCurrentProgress();
    if (!$progress) {
      return $this->redirectToStart();
    }

    $stripeAccountId = $request->query->get('account_id', '');
    if ($stripeAccountId) {
      $this->wizardService->advanceStep($progress, [
        'stripe_account_id' => $stripeAccountId,
        'connected_at' => date('c'),
      ]);
    }

    return new RedirectResponse(
      Url::fromRoute('jaraba_onboarding.wizard.team')->toString()
    );
  }

  // =========================================================================
  // API ENDPOINTS
  // =========================================================================

  /**
   * POST /api/v1/onboarding/wizard/advance
   *
   * Avanza al siguiente paso del wizard.
   */
  public function apiAdvanceStep(Request $request): JsonResponse {
    try {
      $progress = $this->wizardService->getCurrentProgress();
      if (!$progress) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No hay wizard activo.',
        ], 404);
      }

      $data = json_decode($request->getContent(), TRUE) ?? [];
      $stepData = $data['step_data'] ?? [];
      $success = $this->wizardService->advanceStep($progress, $stepData);

      if (!$success) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No se pudo avanzar al siguiente paso.',
        ], 422);
      }

      $currentStep = (int) $progress->get('current_step')->value;
      $nextRoute = self::STEP_ROUTES[$currentStep] ?? 'jaraba_onboarding.wizard.launch';

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'current_step' => $currentStep,
          'progress_percentage' => $progress->getProgressPercentage(),
          'next_url' => Url::fromRoute($nextRoute)->toString(),
          'is_complete' => $progress->isComplete(),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API wizard advance error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error procesando avance del wizard.',
      ], 500);
    }
  }

  /**
   * POST /api/v1/onboarding/wizard/skip
   *
   * Omite el paso actual del wizard.
   */
  public function apiSkipStep(Request $request): JsonResponse {
    try {
      $progress = $this->wizardService->getCurrentProgress();
      if (!$progress) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No hay wizard activo.',
        ], 404);
      }

      $success = $this->wizardService->skipStep($progress);
      if (!$success) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Este paso no se puede omitir.',
        ], 422);
      }

      $currentStep = (int) $progress->get('current_step')->value;
      $nextRoute = self::STEP_ROUTES[$currentStep] ?? 'jaraba_onboarding.wizard.launch';

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'current_step' => $currentStep,
          'progress_percentage' => $progress->getProgressPercentage(),
          'next_url' => Url::fromRoute($nextRoute)->toString(),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API wizard skip error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error procesando omision del paso.',
      ], 500);
    }
  }

  /**
   * POST /api/v1/onboarding/wizard/logo-colors
   *
   * Extrae colores de un logo subido.
   */
  public function apiExtractLogoColors(Request $request): JsonResponse {
    try {
      $data = json_decode($request->getContent(), TRUE) ?? [];
      $fileUri = $data['file_uri'] ?? '';

      if (empty($fileUri)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Se requiere file_uri.',
        ], 400);
      }

      $palette = $this->logoColorExtractor->extractPalette($fileUri);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $palette,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_onboarding')->error('API logo colors error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error extrayendo colores del logo.',
      ], 500);
    }
  }

  // =========================================================================
  // HELPERS
  // =========================================================================

  /**
   * Construye la pagina del wizard con wrapper comun.
   */
  protected function buildWizardPage(TenantOnboardingProgress $progress, int $stepNumber, array $stepContent): array {
    $vertical = $progress->get('vertical')->value ?? 'emprendimiento';
    $stepsConfig = $this->wizardService->getStepsConfig($vertical);

    return [
      '#theme' => 'onboarding_wizard',
      '#step' => $stepsConfig[$stepNumber]['key'] ?? 'unknown',
      '#step_number' => $stepNumber,
      '#steps_config' => $stepsConfig,
      '#progress' => [
        'id' => $progress->id(),
        'current_step' => (int) $progress->get('current_step')->value,
        'completed_steps' => $progress->getCompletedSteps(),
        'skipped_steps' => $progress->getSkippedSteps(),
        'percentage' => $progress->getProgressPercentage(),
        'vertical' => $vertical,
      ],
      '#vertical' => $vertical,
      '#step_content' => $stepContent,
      '#attached' => [
        'library' => [
          'jaraba_onboarding/wizard',
        ],
        'drupalSettings' => [
          'jarabaWizard' => [
            'currentStep' => $stepNumber,
            'totalSteps' => TenantOnboardingProgress::TOTAL_STEPS,
            'vertical' => $vertical,
            'progressId' => $progress->id(),
            'stepsConfig' => $stepsConfig,
            'apiUrls' => [
              'advance' => Url::fromRoute('jaraba_onboarding.api.wizard.advance')->toString(),
              'skip' => Url::fromRoute('jaraba_onboarding.api.wizard.skip')->toString(),
              'logoColors' => Url::fromRoute('jaraba_onboarding.api.wizard.logo_colors')->toString(),
            ],
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['tenant_onboarding_progress:' . $progress->id()],
      ],
    ];
  }

  /**
   * Redirige al inicio del wizard.
   */
  protected function redirectToStart(): RedirectResponse {
    return new RedirectResponse(
      Url::fromRoute('jaraba_onboarding.wizard.welcome')->toString()
    );
  }

}
