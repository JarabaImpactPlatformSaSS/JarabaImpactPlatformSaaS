<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Entrepreneur Dashboard.
 */
class EntrepreneurDashboardController extends ControllerBase
{

    /**
     * Renders the entrepreneur dashboard.
     */
    public function dashboard(): array
    {
        $user = $this->currentUser();

        return [
            '#theme' => 'entrepreneur_dashboard',
            '#user' => $this->entityTypeManager()->getStorage('user')->load($user->id()),
            '#diagnostic' => NULL,
            '#canvas' => NULL,
            '#path_progress' => [],
            '#upcoming_sessions' => [],
            '#next_steps' => $this->getDefaultNextSteps(),
            '#kpis' => [
                'maturity_score' => 0,
                'canvas_completeness' => 0,
                'path_progress' => 0,
                'estimated_loss' => 0,
            ],
            '#attached' => [
                'library' => [
                    'jaraba_business_tools/entrepreneur-dashboard',
                ],
            ],
        ];
    }

    /**
     * Returns default next steps for new users.
     */
    protected function getDefaultNextSteps(): array
    {
        return [
            [
                'title' => $this->t('Completa tu diagnóstico de negocio'),
                'description' => $this->t('Evalúa el estado actual de tu negocio.'),
                'link' => '/entrepreneur/diagnostic/start',
                'priority' => 'high',
                'completed' => FALSE,
            ],
            [
                'title' => $this->t('Crea tu Business Model Canvas'),
                'description' => $this->t('Define los elementos clave de tu modelo de negocio.'),
                'link' => '/entrepreneur/canvas/new',
                'priority' => 'high',
                'completed' => FALSE,
            ],
            [
                'title' => $this->t('Explora itinerarios formativos'),
                'description' => $this->t('Aprende habilidades digitales para tu negocio.'),
                'link' => '/entrepreneur/paths',
                'priority' => 'medium',
                'completed' => FALSE,
            ],
        ];
    }

}
