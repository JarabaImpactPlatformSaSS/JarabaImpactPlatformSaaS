<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de gestion del plan y facturacion del tenant.
 *
 * Muestra el plan actual, estado de suscripcion, opciones de upgrade
 * y enlace al portal de facturacion de Stripe.
 */
class TenantPlanSettingsForm extends FormBase {

  use TenantFormHeroPremiumTrait;

  public function __construct(
    protected TenantContextService $tenantContext,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tenant_plan_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenantEntity = $this->resolveTenantEntity();

    if (!$tenantEntity) {
      $form['error'] = [
        '#markup' => '<div class="tenant-form__alert tenant-form__alert--warning">' . $this->t('No tienes un tenant asignado.') . '</div>',
      ];
      return $form;
    }

    $this->attachTenantFormHero(
      $form,
      'wallet',
      (string) $this->t('Plan y Facturacion'),
      (string) $this->t('Gestiona tu plan, uso y metodos de pago.'),
    );

    // Current plan section.
    $plan = $tenantEntity->getSubscriptionPlan();
    $status = $tenantEntity->getSubscriptionStatus();

    $form['current_plan'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['current_plan']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Tu Plan Actual') . '</h3>',
    ];

    $planName = $plan ? ($plan->label() ?? $this->t('Sin nombre')) : $this->t('Sin plan asignado');
    $statusBadge = $this->buildStatusBadge($status);

    $form['current_plan']['plan_info'] = [
      '#markup' => '<div class="tenant-plan__current">'
      . '<div class="tenant-plan__name">' . $planName . '</div>'
      . $statusBadge
      . '</div>',
    ];

    // Trial info.
    if ($status === TenantInterface::STATUS_TRIAL) {
      $trialEnds = $tenantEntity->get('trial_ends')->value ?? NULL;
      if ($trialEnds) {
        try {
          $trialDate = new \DateTime($trialEnds);
          $now = new \DateTime();
          $diff = $now->diff($trialDate);
          $daysLeft = $diff->invert ? 0 : $diff->days;

          $form['current_plan']['trial_info'] = [
            '#markup' => '<div class="tenant-form__alert tenant-form__alert--warning">'
            . $this->t('Tu periodo de prueba termina en @days dias (@date).', [
              '@days' => $daysLeft,
              '@date' => $trialDate->format('d/m/Y'),
            ])
            . '</div>',
          ];
        }
        catch (\Throwable) {
          // Ignore invalid date.
        }
      }
    }

    // Upgrade section.
    $form['upgrade'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['upgrade']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Cambiar de Plan') . '</h3>',
    ];

    $pricingUrl = Url::fromRoute('ecosistema_jaraba_core.pricing.page')->toString();

    $form['upgrade']['info'] = [
      '#markup' => '<p class="tenant-form__hint">'
      . $this->t('Compara planes y elige el que mejor se adapte a las necesidades de tu organizacion.')
      . '</p>'
      . '<a href="' . $pricingUrl . '" class="tenant-form__btn tenant-form__btn--primary">'
      . $this->t('Ver planes disponibles')
      . '</a>',
    ];

    // Billing management section.
    $form['billing'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['billing']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Facturacion y Pagos') . '</h3>',
    ];

    $form['billing']['info'] = [
      '#markup' => '<p class="tenant-form__hint">'
      . $this->t('Gestiona tus metodos de pago, consulta facturas y actualiza tus datos de facturacion desde el portal seguro de Stripe.')
      . '</p>',
    ];

    // Boton del portal de Stripe via JS (data-portal-trigger).
    // NO es un submit de formulario — abre el portal de Stripe via API AJAX.
    // stripe-portal.js conecta [data-portal-trigger] con /api/v1/billing/portal-session.
    $returnUrl = Url::fromRoute('ecosistema_jaraba_core.tenant_self_service.plan')->toString();
    $form['billing']['portal_button'] = [
      '#markup' => '<button type="button" class="tenant-form__btn tenant-form__btn--secondary" data-portal-trigger data-portal-return-url="' . $returnUrl . '">'
      . $this->t('Abrir portal de facturacion')
      . '</button>',
    ];

    // Adjuntar JS del portal de Stripe.
    $form['#attached']['library'][] = 'ecosistema_jaraba_theme/stripe-portal';

    return $form;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   *
   * Este form es informativo — no tiene campos editables.
   * El boton "Abrir portal de facturacion" usa JS (data-portal-trigger),
   * no submit de formulario.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op: form is read-only display + JS buttons.
  }

  /**
   * Resuelve la entidad Tenant del usuario actual.
   */
  protected function resolveTenantEntity(): ?TenantInterface {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return NULL;
    }

    $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
    return $tenant instanceof TenantInterface ? $tenant : NULL;
  }

  /**
   * Genera el badge HTML del estado de suscripcion.
   */
  protected function buildStatusBadge(string $status): string {
    $labels = [
      TenantInterface::STATUS_TRIAL => $this->t('Prueba gratuita'),
      TenantInterface::STATUS_ACTIVE => $this->t('Activo'),
      TenantInterface::STATUS_PAST_DUE => $this->t('Pago pendiente'),
      TenantInterface::STATUS_SUSPENDED => $this->t('Suspendido'),
      TenantInterface::STATUS_CANCELLED => $this->t('Cancelado'),
      TenantInterface::STATUS_PENDING => $this->t('Pendiente'),
    ];

    $variants = [
      TenantInterface::STATUS_ACTIVE => 'success',
      TenantInterface::STATUS_TRIAL => 'warning',
      TenantInterface::STATUS_PAST_DUE => 'warning',
      TenantInterface::STATUS_SUSPENDED => 'warning',
      TenantInterface::STATUS_CANCELLED => 'warning',
      TenantInterface::STATUS_PENDING => 'warning',
    ];

    $label = $labels[$status] ?? $status;
    $variant = $variants[$status] ?? 'warning';

    return '<span class="tenant-form__badge tenant-form__badge--' . $variant . '">' . $label . '</span>';
  }

}
