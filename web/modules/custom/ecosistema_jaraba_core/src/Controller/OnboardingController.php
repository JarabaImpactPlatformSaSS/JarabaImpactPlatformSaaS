<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Drupal\ecosistema_jaraba_core\Entity\VerticalOnboardingConfig;
use Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para el flujo de onboarding de nuevos tenants.
 *
 * Este controlador gestiona todas las páginas y endpoints relacionados
 * con el registro y onboarding de nuevas organizaciones en la plataforma.
 *
 * El flujo de onboarding consta de varios pasos:
 *
 * 1. **Registro inicial** (/registro/{vertical}):
 *    - Formulario público para registrar una nueva organización
 *    - Recoge datos de la organización y del administrador
 *    - Valida y crea el tenant en estado "trial"
 *
 * 2. **Selección de plan** (/onboarding/seleccionar-plan):
 *    - Muestra los planes disponibles para la vertical
 *    - El usuario elige el plan que mejor se adapta a sus necesidades
 *
 * 3. **Configuración de pago** (/onboarding/configurar-pago):
 *    - Integración con Stripe Checkout o Elements
 *    - El usuario introduce sus datos de pago
 *
 * 4. **Bienvenida** (/onboarding/bienvenida):
 *    - Página de confirmación con los siguientes pasos
 *    - Acceso directo al panel del tenant
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService
 */
class OnboardingController extends ControllerBase {

  /**
   * El servicio de onboarding.
   *
   * Contiene toda la lógica de negocio para el registro de tenants.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService
   */
  protected TenantOnboardingService $onboardingService;

  /**
   * El gestor de tenants.
   *
   * Se usa para obtener información del tenant actual.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantManager
   */
  protected TenantManager $tenantManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->onboardingService = $container->get('ecosistema_jaraba_core.tenant_onboarding');
    $instance->tenantManager = $container->get('ecosistema_jaraba_core.tenant_manager');
    return $instance;
  }

  /**
   * Muestra el formulario de registro para una vertical específica.
   *
   * Esta página es pública y permite a nuevas organizaciones registrarse
   * en la plataforma. El formulario está personalizado según la vertical
   * seleccionada (colores, textos, etc.).
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
   *   La vertical para la cual se está registrando el tenant.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP actual.
   *
   * @return array
   *   Render array con el formulario de registro.
   */
  public function registerForm(VerticalInterface $vertical, Request $request): array {
    // Obtener configuración de tema de la vertical.
    $themeSettings = $vertical->getThemeSettings();

    // Obtener planes disponibles para mostrar preview.
    $plans = $this->entityTypeManager()
      ->getStorage('saas_plan')
      ->loadByProperties([
        'vertical' => $vertical->id(),
        'status' => TRUE,
      ]);

    // Ordenar planes por peso.
    usort($plans, fn($a, $b) => ($a->get('weight')->value ?? 0) - ($b->get('weight')->value ?? 0));

    // P1-01: Cargar config de onboarding vertical-aware.
    $onboardingConfig = VerticalOnboardingConfig::load($vertical->id());
    $benefits = $onboardingConfig ? $onboardingConfig->getBenefits() : [];
    $headline = $onboardingConfig ? $onboardingConfig->getHeadline() : '';
    $subheadline = $onboardingConfig ? $onboardingConfig->getSubheadline() : '';

    // P2-05: Check if Google OAuth is configured.
    $googleOAuthEnabled = FALSE;
    if (\Drupal::hasService('ecosistema_jaraba_core.google_oauth')) {
      try {
        $googleOAuthEnabled = \Drupal::service('ecosistema_jaraba_core.google_oauth')->isConfigured();
      }
      catch (\Throwable) {
        // Service unavailable — leave disabled.
      }
    }

    // GAP-WC-005: Social proof — count active tenants per vertical.
    $tenantCount = 0;
    try {
      $tenantCount = (int) $this->entityTypeManager()
        ->getStorage('tenant')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('vertical', $vertical->id())
        ->condition('subscription_status', ['trial', 'active'], 'IN')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      // Non-critical — show 0 if query fails.
    }

    // GAP-WC-005: Load testimonials from success cases per vertical.
    $testimonials = [];
    try {
      if (\Drupal::hasService('entity_type.manager')) {
        $cases = $this->entityTypeManager()
          ->getStorage('success_case')
          ->loadByProperties([
            'vertical' => $vertical->id(),
            'status' => TRUE,
            'featured' => TRUE,
          ]);
        foreach (array_slice(array_values($cases), 0, 2) as $case) {
          $name = $case->label() ?? '';
          $testimonials[] = [
            'name' => $name,
            'role' => $case->get('profession')->value ?? '',
            'text' => $case->get('result_after')->value ?? '',
            'avatar_initials' => mb_strtoupper(mb_substr($name, 0, 2)),
          ];
        }
      }
    }
    catch (\Throwable) {
      // Non-critical — show no testimonials.
    }

    return [
      '#theme' => 'ecosistema_jaraba_register_form',
      '#vertical' => $vertical,
      '#plans' => $plans,
      '#theme_settings' => $themeSettings,
      '#benefits' => $benefits,
      '#headline' => $headline,
      '#subheadline' => $subheadline,
      '#google_oauth_enabled' => $googleOAuthEnabled,
      '#tenant_count' => $tenantCount,
      '#testimonials' => $testimonials,
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/onboarding'],
        'drupalSettings' => [
          'ecosistemaJaraba' => [
            'verticalId' => $vertical->id(),
            'verticalName' => $vertical->getName(),
            'csrfToken' => \Drupal::service('csrf_token')->get('onboarding'),
            'registerProcessUrl' => Url::fromRoute('ecosistema_jaraba_core.onboarding.process')->toString(),
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['vertical:' . $vertical->id(), 'tenant_list', 'success_case_list'],
      ],
    ];
  }

  /**
   * Procesa el formulario de registro enviado.
   *
   * Valida los datos, crea el usuario y el tenant, e inicia el periodo
   * de prueba. Si todo es exitoso, redirige a la selección de plan.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con los datos del formulario.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del registro.
   */
  public function processRegistration(Request $request): JsonResponse {
    // Obtener datos del formulario.
    $data = json_decode($request->getContent(), TRUE);

    if (!$data) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Datos de formulario inválidos.',
      ], 400);
    }

    // Validar datos.
    $validation = $this->onboardingService->validateRegistrationData($data);

    if (!$validation['valid']) {
      return new JsonResponse([
        'success' => FALSE,
        'errors' => $validation['errors'],
      ], 422);
    }

    // Procesar registro.
    $result = $this->onboardingService->processRegistration($data);

    if (!$result['success']) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => $result['error'],
      ], 500);
    }

    // Autenticar al usuario recién creado.
    user_login_finalize($result['user']);

    // Generar URL de redirección.
    $redirectUrl = Url::fromRoute('ecosistema_jaraba_core.onboarding.select_plan')
      ->setAbsolute()
      ->toString();

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('¡Registro exitoso! Redirigiendo...'),
      'redirect' => $redirectUrl,
      'tenant' => [
        'id' => $result['tenant']->id(),
        'name' => $result['tenant']->getName(),
        'domain' => $result['tenant']->getDomain(),
      ],
    ]);
  }

  /**
   * Muestra la página de selección de plan.
   *
   * El usuario ya está autenticado y tiene un tenant en estado trial.
   * Puede elegir el plan que mejor se adapte a sus necesidades.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return array|RedirectResponse
   *   Render array con los planes o redirección si no hay tenant.
   */
  public function selectPlan(Request $request) {
    // Obtener el tenant del usuario actual.
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      $this->messenger()->addError($this->t('No tienes una organización asociada.'));
      return $this->redirect('<front>');
    }

    // Verificar que el tenant está en estado trial/pending.
    if (!in_array($tenant->getSubscriptionStatus(), ['trial', 'pending'])) {
      // Ya tiene un plan activo, ir al dashboard.
      return $this->redirect('ecosistema_jaraba_core.onboarding.welcome');
    }

    // Cargar vertical del tenant.
    $vertical = $tenant->getVertical();

    // Cargar planes disponibles.
    $plans = $this->entityTypeManager()
      ->getStorage('saas_plan')
      ->loadByProperties([
        'vertical' => $vertical->id(),
        'status' => TRUE,
      ]);

    // Ordenar por peso.
    usort($plans, fn($a, $b) => ($a->get('weight')->value ?? 0) - ($b->get('weight')->value ?? 0));

    return [
      '#theme' => 'ecosistema_jaraba_select_plan',
      '#tenant' => $tenant,
      '#vertical' => $vertical,
      '#plans' => $plans,
      '#current_plan' => $tenant->getSubscriptionPlan(),
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/onboarding'],
      ],
    ];
  }

  /**
   * Muestra la página de configuración de pago con Stripe.
   *
   * Integra Stripe Checkout o Elements para recoger los datos de pago
   * del usuario de forma segura.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return array|RedirectResponse
   *   Render array con el formulario de Stripe o redirección.
   */
  public function setupPayment(Request $request) {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return $this->redirect('<front>');
    }

    // Obtener el plan seleccionado de la sesión o query.
    $planId = $request->query->get('plan_id') ?: $request->getSession()->get('selected_plan_id');

    if (!$planId) {
      $this->messenger()->addWarning($this->t('Por favor, selecciona un plan primero.'));
      return $this->redirect('ecosistema_jaraba_core.onboarding.select_plan');
    }

    $plan = $this->entityTypeManager()->getStorage('saas_plan')->load($planId);

    if (!$plan) {
      $this->messenger()->addError($this->t('Plan no encontrado.'));
      return $this->redirect('ecosistema_jaraba_core.onboarding.select_plan');
    }

    // Verificar si el plan es gratuito.
    if ($plan->isFree()) {
      // Plan gratuito: activar directamente sin pago.
      $this->onboardingService->completeOnboarding($tenant, '', '');
      return $this->redirect('ecosistema_jaraba_core.onboarding.welcome');
    }

    // Preparar datos para Stripe.
    $stripePublicKey = $this->config('ecosistema_jaraba_core.stripe')->get('public_key');

    // P2-02: Pre-fill billing con datos del registro.
    $currentUser = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $prefillName = '';
    $prefillEmail = '';
    if ($currentUser) {
      $prefillEmail = $currentUser->getEmail() ?: '';
      $prefillName = $currentUser->getDisplayName() ?: '';
    }

    return [
      '#theme' => 'ecosistema_jaraba_setup_payment',
      '#tenant' => $tenant,
      '#plan' => $plan,
      '#stripe_public_key' => $stripePublicKey,
      '#prefill_name' => $prefillName,
      '#prefill_email' => $prefillEmail,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_core/onboarding',
          'ecosistema_jaraba_core/stripe',
        ],
        'drupalSettings' => [
          'ecosistemaJaraba' => [
            'stripePublicKey' => $stripePublicKey,
            'planId' => $plan->id(),
            'priceMonthly' => $plan->getMonthlyPrice(),
            'priceYearly' => $plan->getYearlyPrice(),
            'stripePriceId' => $plan->getStripePriceId(),
            'createSubscriptionUrl' => Url::fromRoute('ecosistema_jaraba_core.api.stripe.create_subscription')->toString(),
            'confirmSubscriptionUrl' => Url::fromRoute('ecosistema_jaraba_core.api.stripe.confirm_subscription')->toString(),
            'welcomeUrl' => Url::fromRoute('ecosistema_jaraba_core.onboarding.welcome')->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Muestra la página de bienvenida tras completar el onboarding.
   *
   * Esta página resume la información del tenant creado y proporciona
   * enlaces a los siguientes pasos: configurar organización, añadir
   * miembros, etc.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return array|RedirectResponse
   *   Render array con la página de bienvenida.
   */
  public function welcome(Request $request) {
    $tenant = $this->tenantManager->getCurrentTenant();

    if (!$tenant) {
      return $this->redirect('<front>');
    }

    $vertical = $tenant->getVertical();
    $plan = $tenant->getSubscriptionPlan();

    // Calcular días restantes de trial si aplica.
    $trialDaysRemaining = NULL;
    if ($tenant->isOnTrial() && $tenant->getTrialEndsAt()) {
      $trialEnd = new \DateTime($tenant->getTrialEndsAt());
      $now = new \DateTime();
      $diff = $now->diff($trialEnd);
      $trialDaysRemaining = $diff->invert ? 0 : $diff->days;
    }

    // P1-01 + P1-02: Cargar config de onboarding para next_steps y connect_required.
    $onboardingConfig = VerticalOnboardingConfig::load($vertical->id());
    $connectRequired = $onboardingConfig ? $onboardingConfig->isConnectRequired() : FALSE;

    return [
      '#theme' => 'ecosistema_jaraba_welcome',
      '#tenant' => $tenant,
      '#vertical' => $vertical,
      '#plan' => $plan,
      '#trial_days_remaining' => $trialDaysRemaining,
      '#tenant_url' => 'https://' . $tenant->getDomain() . '.jaraba.io',
      '#next_steps' => $this->getNextSteps($tenant),
      '#connect_required' => $connectRequired,
      '#connect_url' => Url::fromRoute('ecosistema_jaraba_core.api.stripe.connect.onboard')->toString(),
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/onboarding'],
      ],
    ];
  }

  /**
   * Genera la lista de siguientes pasos para el nuevo tenant.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant.
   *
   * @return array
   *   Array de pasos con título, descripción, URL e icono.
   */

  /**
   * Genera la lista de siguientes pasos para el nuevo tenant.
   *
   * P1-01: Lee de VerticalOnboardingConfig en vez de hardcoded.
   * Fallback a pasos genéricos si no hay config para la vertical.
   */
  protected function getNextSteps($tenant): array {
    $vertical = $tenant->getVertical();
    $onboardingConfig = VerticalOnboardingConfig::load($vertical->id());

    if ($onboardingConfig && !empty($onboardingConfig->getNextSteps())) {
      $steps = [];
      foreach ($onboardingConfig->getNextSteps() as $step) {
        $url = '';
        try {
          $routeParams = $step['route_params'] ?? [];
          // Inyectar tenant_id si la ruta lo necesita.
          if ($step['route'] === 'entity.tenant.edit_form') {
            $routeParams = ['tenant' => $tenant->id()];
          }
          $url = Url::fromRoute($step['route'], $routeParams)->toString();
        }
        catch (\Exception $e) {
          // Ruta no existe aún — usar fallback.
          $url = Url::fromRoute('entity.tenant.edit_form', ['tenant' => $tenant->id()])->toString();
        }

        $steps[] = [
          'title' => $this->t($step['title']),
          'description' => $this->t($step['description']),
          'url' => $url,
          'icon' => $step['icon'] ?? 'bi-arrow-right',
          'completed' => FALSE,
        ];
      }
      return $steps;
    }

    // Fallback genérico con rutas reales por vertical.
    $dashboardUrl = $this->resolveVerticalDashboardUrl($vertical);

    return [
          [
            'title' => $this->t('Ir a tu dashboard'),
            'description' => $this->t('Tu asistente de configuración te guiará paso a paso.'),
            'url' => $dashboardUrl,
            'icon' => 'bi-speedometer2',
            'completed' => FALSE,
            'primary' => TRUE,
          ],
          [
            'title' => $this->t('Completa tu perfil'),
            'description' => $this->t('Añade el logo y la información de tu organización.'),
            'url' => Url::fromRoute('entity.tenant.edit_form', ['tenant' => $tenant->id()])->toString(),
            'icon' => 'bi-building',
            'completed' => FALSE,
          ],
          [
            'title' => $this->t('Personaliza tu diseño'),
            'description' => $this->t('Configura colores, tipografía y marca de tu sitio.'),
            'url' => $this->resolveRouteOrFallback('ecosistema_jaraba_core.tenant_self_service.design', [], $dashboardUrl),
            'icon' => 'bi-palette',
            'completed' => FALSE,
          ],
    ];
  }

  /**
   * Resolves the frontend dashboard URL for a vertical.
   *
   * Maps vertical machine names to their dashboard route names.
   * Returns a safe fallback URL if the route doesn't exist.
   */
  protected function resolveVerticalDashboardUrl($vertical): string {
    $verticalId = $vertical->id();

    // Map verticals to their dashboard routes.
    $dashboardRoutes = [
      'empleabilidad' => 'jaraba_candidate.dashboard',
      'emprendimiento' => 'jaraba_copilot_v2.dashboard',
      'agroconecta' => 'jaraba_agroconecta_core.producer.dashboard',
      'comercioconecta' => 'jaraba_comercio_conecta.merchant_portal',
      'serviciosconecta' => 'jaraba_servicios_conecta.provider_portal',
      'jarabalex' => 'jaraba_legal.dashboard',
      'jaraba_content_hub' => 'jaraba_content_hub.dashboard',
      'formacion' => 'jaraba_lms.instructor.courses',
      'andalucia_ei' => 'jaraba_andalucia_ei.coordinador_dashboard',
    ];

    $routeName = $dashboardRoutes[$verticalId] ?? NULL;
    if ($routeName) {
      return $this->resolveRouteOrFallback($routeName, [], '/');
    }

    // Fallback: front page.
    return Url::fromRoute('<front>')->toString();
  }

  /**
   * Resolves a route name to URL with try-catch fallback.
   */
  protected function resolveRouteOrFallback(string $routeName, array $params, string $fallback): string {
    try {
      return Url::fromRoute($routeName, $params)->toString();
    }
    catch (\Throwable $e) {
      return $fallback;
    }
  }

}
