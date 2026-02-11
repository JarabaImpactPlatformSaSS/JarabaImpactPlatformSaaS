<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for mentor dashboard.
 */
class MentorDashboardController extends ControllerBase
{

    /**
     * Displays the mentor dashboard.
     *
     * @return array
     *   Render array.
     */
    public function dashboard(): array
    {
        $current_user = $this->currentUser();

        // Get mentor profile for current user.
        $storage = $this->entityTypeManager()->getStorage('mentor_profile');
        $profiles = $storage->loadByProperties(['user_id' => $current_user->id()]);

        if (empty($profiles)) {
            return [
                '#markup' => '<p>' . $this->t('No tienes un perfil de mentor activo.') . '</p>',
            ];
        }

        $mentor_profile = reset($profiles);

        // Get KPIs.
        $kpis = $this->calculateKpis($mentor_profile);

        // Get upcoming sessions.
        $upcoming_sessions = $this->getUpcomingSessions($mentor_profile);

        // Get pipeline (active engagements).
        $pipeline = $this->getPipeline($mentor_profile);

        return [
            '#theme' => 'mentor_dashboard',
            '#kpis' => $kpis,
            '#pipeline' => $pipeline,
            '#upcoming_sessions' => $upcoming_sessions,
            '#attached' => [
                'library' => [
                    'jaraba_mentoring/mentor_dashboard',
                ],
            ],
        ];
    }

    /**
     * Calculates KPIs for the mentor.
     */
    protected function calculateKpis($mentor_profile): array
    {
        return [
            'total_sessions' => $mentor_profile->get('total_sessions')->value ?? 0,
            'average_rating' => number_format((float) ($mentor_profile->get('average_rating')->value ?? 0), 1),
            'total_reviews' => $mentor_profile->get('total_reviews')->value ?? 0,
            'active_clients' => $this->countActiveClients($mentor_profile),
        ];
    }

    /**
     * Gets upcoming sessions.
     */
    protected function getUpcomingSessions($mentor_profile): array
    {
        $session_storage = $this->entityTypeManager()->getStorage('mentoring_session');

        $session_ids = $session_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $mentor_profile->id())
            ->condition('status', ['scheduled', 'confirmed'], 'IN')
            ->condition('scheduled_start', date('Y-m-d\TH:i:s'), '>=')
            ->sort('scheduled_start', 'ASC')
            ->range(0, 5)
            ->execute();

        $sessions = $session_storage->loadMultiple($session_ids);

        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                'id' => $session->id(),
                'scheduled_start' => $session->get('scheduled_start')->value,
                'status' => $session->get('status')->value,
                'mentee_id' => $session->get('mentee_id')->target_id,
            ];
        }

        return $data;
    }

    /**
     * Gets pipeline of active engagements.
     */
    protected function getPipeline($mentor_profile): array
    {
        $engagement_storage = $this->entityTypeManager()->getStorage('mentoring_engagement');

        $engagement_ids = $engagement_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $mentor_profile->id())
            ->condition('status', 'active')
            ->sort('created', 'DESC')
            ->execute();

        $engagements = $engagement_storage->loadMultiple($engagement_ids);

        $data = [];
        foreach ($engagements as $engagement) {
            $data[] = [
                'id' => $engagement->id(),
                'mentee_id' => $engagement->get('mentee_id')->target_id,
                'sessions_remaining' => $engagement->get('sessions_remaining')->value,
                'sessions_total' => $engagement->get('sessions_total')->value,
                'expiry_date' => $engagement->get('expiry_date')->value,
            ];
        }

        return $data;
    }

    /**
     * Counts active clients.
     */
    protected function countActiveClients($mentor_profile): int
    {
        $engagement_storage = $this->entityTypeManager()->getStorage('mentoring_engagement');

        return (int) $engagement_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $mentor_profile->id())
            ->condition('status', 'active')
            ->count()
            ->execute();
    }

}
