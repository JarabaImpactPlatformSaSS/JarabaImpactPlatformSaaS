<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantOnboardingService;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
class OnboardingController extends ControllerBase
{

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
    public static function create(ContainerInterface $container)
    {
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
    public function registerForm(VerticalInterface $vertical, Request $request): array
    {
        // Obtener configuración de tema de la vertical
        $themeSettings = $vertical->getThemeSettings();

        // Obtener planes disponibles para mostrar preview
        $plans = $this->entityTypeManager()
            ->getStorage('saas_plan')
            ->loadByProperties([
                'vertical' => $vertical->id(),
                'status' => TRUE,
            ]);

        // Ordenar planes por peso
        usort($plans, fn($a, $b) => ($a->get('weight')->value ?? 0) - ($b->get('weight')->value ?? 0));

        return [
            '#theme' => 'ecosistema_jaraba_register_form',
            '#vertical' => $vertical,
            '#plans' => $plans,
            '#theme_settings' => $themeSettings,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/onboarding'],
                'drupalSettings' => [
                    'ecosistemaJaraba' => [
                        'verticalId' => $vertical->id(),
                        'verticalName' => $vertical->getName(),
                        'csrfToken' => \Drupal::service('csrf_token')->get('onboarding'),
                    ],
                ],
            ],
            '#cache' => [
                'contexts' => ['url.path'],
                'tags' => ['vertical:' . $vertical->id()],
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
    public function processRegistration(Request $request): JsonResponse
    {
        // Obtener datos del formulario
        $data = json_decode($request->getContent(), TRUE);

        if (!$data) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Datos de formulario inválidos.',
            ], 400);
        }

        // Validar datos
        $validation = $this->onboardingService->validateRegistrationData($data);

        if (!$validation['valid']) {
            return new JsonResponse([
                'success' => FALSE,
                'errors' => $validation['errors'],
            ], 422);
        }

        // Procesar registro
        $result = $this->onboardingService->processRegistration($data);

        if (!$result['success']) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $result['error'],
            ], 500);
        }

        // Autenticar al usuario recién creado
        user_login_finalize($result['user']);

        // Generar URL de redirección
        $redirectUrl = Url::fromRoute('ecosistema_jaraba_core.onboarding.select_plan')
            ->setAbsolute()
            ->toString();

        return new JsonResponse([
            'success' => TRUE,
            'message' => '¡Registro exitoso! Redirigiendo...',
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
    public function selectPlan(Request $request)
    {
        // Obtener el tenant del usuario actual
        $tenant = $this->tenantManager->getCurrentTenant();

        if (!$tenant) {
            $this->messenger()->addError($this->t('No tienes una organización asociada.'));
            return $this->redirect('<front>');
        }

        // Verificar que el tenant está en estado trial/pending
        if (!in_array($tenant->getSubscriptionStatus(), ['trial', 'pending'])) {
            // Ya tiene un plan activo, ir al dashboard
            return $this->redirect('ecosistema_jaraba_core.onboarding.welcome');
        }

        // Cargar vertical del tenant
        $vertical = $tenant->getVertical();

        // Cargar planes disponibles
        $plans = $this->entityTypeManager()
            ->getStorage('saas_plan')
            ->loadByProperties([
                'vertical' => $vertical->id(),
                'status' => TRUE,
            ]);

        // Ordenar por peso
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
    public function setupPayment(Request $request)
    {
        $tenant = $this->tenantManager->getCurrentTenant();

        if (!$tenant) {
            return $this->redirect('<front>');
        }

        // Obtener el plan seleccionado de la sesión o query
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

        // Verificar si el plan es gratuito
        if ($plan->isFree()) {
            // Plan gratuito: activar directamente sin pago
            $this->onboardingService->completeOnboarding($tenant, '', '');
            return $this->redirect('ecosistema_jaraba_core.onboarding.welcome');
        }

        // Preparar datos para Stripe
        $stripePublicKey = $this->config('ecosistema_jaraba_core.stripe')->get('public_key');

        return [
            '#theme' => 'ecosistema_jaraba_setup_payment',
            '#tenant' => $tenant,
            '#plan' => $plan,
            '#stripe_public_key' => $stripePublicKey,
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
    public function welcome(Request $request)
    {
        $tenant = $this->tenantManager->getCurrentTenant();

        if (!$tenant) {
            return $this->redirect('<front>');
        }

        $vertical = $tenant->getVertical();
        $plan = $tenant->getSubscriptionPlan();

        // Calcular días restantes de trial si aplica
        $trialDaysRemaining = NULL;
        if ($tenant->isOnTrial() && $tenant->getTrialEndsAt()) {
            $trialEnd = new \DateTime($tenant->getTrialEndsAt());
            $now = new \DateTime();
            $diff = $now->diff($trialEnd);
            $trialDaysRemaining = $diff->invert ? 0 : $diff->days;
        }

        return [
            '#theme' => 'ecosistema_jaraba_welcome',
            '#tenant' => $tenant,
            '#vertical' => $vertical,
            '#plan' => $plan,
            '#trial_days_remaining' => $trialDaysRemaining,
            '#tenant_url' => 'https://' . $tenant->getDomain() . '.jaraba.io',
            '#next_steps' => $this->getNextSteps($tenant),
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
    protected function getNextSteps($tenant): array
    {
        return [
            [
                'title' => $this->t('Completa tu perfil'),
                'description' => $this->t('Añade el logo y la información de tu organización.'),
                'url' => Url::fromRoute('entity.tenant.edit_form', ['tenant' => $tenant->id()])->toString(),
                'icon' => 'bi-building',
                'completed' => FALSE,
            ],
            [
                'title' => $this->t('Invita a tu equipo'),
                'description' => $this->t('Añade productores y colaboradores a tu organización.'),
                'url' => '/admin/people/invite',
                'icon' => 'bi-people',
                'completed' => FALSE,
            ],
            [
                'title' => $this->t('Configura tus productos'),
                'description' => $this->t('Crea tu catálogo de productos para trazabilidad.'),
                'url' => '/admin/content/product',
                'icon' => 'bi-box-seam',
                'completed' => FALSE,
            ],
            [
                'title' => $this->t('Crea tu primer lote'),
                'description' => $this->t('Registra un lote de producción con trazabilidad.'),
                'url' => '/node/add/lote_produccion',
                'icon' => 'bi-upc-scan',
                'completed' => FALSE,
            ],
        ];
    }

}
