<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de onboarding de vendedores para Stripe Connect.
 *
 * PROPÓSITO:
 * Permite a los administradores invitar a vendedores a conectar
 * sus cuentas de Stripe (Standard Accounts) para recibir pagos.
 *
 * FLUJO:
 * 1. Admin ingresa email y nombre del negocio
 * 2. Sistema crea cuenta Connected en Stripe
 * 3. Se genera enlace de onboarding
 * 4. Se muestra enlace o se envía por email al vendedor
 * 5. Vendedor completa verificación KYC en Stripe
 */
class VendorOnboardingForm extends FormBase implements ContainerInjectionInterface
{

    /**
     * Constructor del formulario.
     *
     * @param \Drupal\jaraba_foc\Service\StripeConnectService $stripeConnect
     *   El servicio de Stripe Connect.
     */
    public function __construct(
        protected StripeConnectService $stripeConnect
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_foc.stripe_connect')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_foc_vendor_onboarding_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div class="foc-onboarding-form">';
        $form['#suffix'] = '</div>';

        $form['info'] = [
            '#type' => 'markup',
            '#markup' => '<div class="messages messages--info">' .
                '<h3>' . $this->t('Onboarding de Vendedor - Stripe Connect') . '</h3>' .
                '<p>' . $this->t('Este formulario crea una cuenta de Stripe Connect (Standard) para un vendedor. El vendedor recibirá un enlace para completar su verificación de identidad (KYC) directamente en Stripe.') . '</p>' .
                '</div>',
        ];

        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email del Vendedor'),
            '#description' => $this->t('Email donde el vendedor recibirá el enlace de onboarding.'),
            '#required' => TRUE,
        ];

        $form['business_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre del Negocio'),
            '#description' => $this->t('Nombre comercial que aparecerá en los recibos.'),
            '#required' => TRUE,
            '#maxlength' => 255,
        ];

        $form['tenant_id'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Tenant'),
            '#description' => $this->t('Tenant al que pertenece este vendedor.'),
            '#target_type' => 'group',
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Crear Cuenta y Generar Enlace'),
            '#button_type' => 'primary',
        ];

        // Mostrar resultado si existe
        $onboardingUrl = $form_state->get('onboarding_url');
        if ($onboardingUrl) {
            $form['result'] = [
                '#type' => 'markup',
                '#markup' => '<div class="messages messages--status">' .
                    '<h4>' . $this->t('¡Cuenta creada exitosamente!') . '</h4>' .
                    '<p>' . $this->t('Comparte este enlace con el vendedor para que complete su verificación:') . '</p>' .
                    '<p><a href="' . $onboardingUrl . '" target="_blank" class="button">' . $this->t('Enlace de Onboarding') . '</a></p>' .
                    '<p><small>' . $this->t('Este enlace expira en 24 horas.') . '</small></p>' .
                    '</div>',
                '#weight' => -10,
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        $email = $form_state->getValue('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_state->setErrorByName('email', $this->t('El email no es válido.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $vendorData = [
            'email' => $form_state->getValue('email'),
            'business_name' => $form_state->getValue('business_name'),
            'tenant_id' => $form_state->getValue('tenant_id'),
        ];

        try {
            $result = $this->stripeConnect->createVendorAccount($vendorData);

            $form_state->set('onboarding_url', $result['onboarding_url']);
            $form_state->setRebuild(TRUE);

            $this->messenger()->addStatus($this->t('Cuenta de Stripe Connect creada: @id', [
                '@id' => $result['account_id'],
            ]));
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('Error creando cuenta: @error', [
                '@error' => $e->getMessage(),
            ]));
        }
    }

}
