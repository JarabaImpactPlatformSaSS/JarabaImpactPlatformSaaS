<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controlador para el flujo de onboarding de vendedores en Stripe Connect.
 */
class VendorOnboardingController extends ControllerBase
{

    /**
     * Callback de refresh cuando el usuario abandona el flujo.
     */
    public function refresh(): RedirectResponse
    {
        $this->messenger()->addWarning($this->t('El proceso de onboarding fue interrumpido. Por favor, inténtalo de nuevo.'));
        return new RedirectResponse(Url::fromRoute('jaraba_foc.vendor.onboarding')->toString());
    }

    /**
     * Callback cuando el onboarding se completa.
     */
    public function complete(): array
    {
        $this->messenger()->addStatus($this->t('¡Onboarding completado! Tu cuenta de Stripe está siendo verificada.'));

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['foc-onboarding-complete']],
            'message' => [
                '#type' => 'html_tag',
                '#tag' => 'div',
                '#attributes' => ['class' => ['foc-success-message']],
                '#value' => $this->t('El proceso de verificación de Stripe puede tardar hasta 48 horas. Recibirás una notificación cuando esté completo.'),
            ],
            'actions' => [
                '#type' => 'link',
                '#title' => $this->t('Volver al Dashboard'),
                '#url' => Url::fromRoute('jaraba_foc.dashboard'),
                '#attributes' => ['class' => ['button', 'button--primary']],
            ],
        ];
    }

}
