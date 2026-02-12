<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_page_builder\Service\OnboardingStateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para gestionar el estado del Onboarding Tour.
 *
 * Endpoints:
 * - GET  /api/v1/page-builder/onboarding → Estado actual del tour.
 * - POST /api/v1/page-builder/onboarding → Marcar como completado.
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §3
 */
class OnboardingController extends ControllerBase
{

    /**
     * Constructor del controlador.
     *
     * @param \Drupal\jaraba_page_builder\Service\OnboardingStateService $onboardingState
     *   Servicio de estado del onboarding.
     */
    public function __construct(
        protected OnboardingStateService $onboardingState,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_page_builder.onboarding_state'),
        );
    }

    /**
     * GET: Obtiene el estado actual del onboarding tour.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con { completed: bool }.
     */
    public function getState(): JsonResponse
    {
        return new JsonResponse([
            'completed' => $this->onboardingState->hasCompletedTour(),
        ]);
    }

    /**
     * POST: Marca el onboarding tour como completado.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Solicitud HTTP con { completed: true }.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con { success: true }.
     */
    public function setState(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);

        if (!empty($content['completed'])) {
            $this->onboardingState->markTourCompleted();
        } else {
            $this->onboardingState->resetTour();
        }

        return new JsonResponse([
            'success' => TRUE,
            'completed' => $this->onboardingState->hasCompletedTour(),
        ]);
    }

}
