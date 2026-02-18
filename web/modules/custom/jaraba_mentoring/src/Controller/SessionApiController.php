<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_mentoring\Service\SessionSchedulerService;
use Drupal\jaraba_mentoring\Service\VideoMeetingService;

/**
 * API Controller for session endpoints.
 *
 * PROPÓSITO:
 * Gestiona las API REST para sesiones de mentoría:
 * - Reservar sesiones
 * - Actualizar estado/agenda
 * - Cancelar y reprogramar
 * - Obtener URL de videollamada
 *
 * SPEC: 32_Emprendimiento_Mentoring_Sessions_v1
 */
class SessionApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SessionSchedulerService $scheduler,
        protected VideoMeetingService $videoService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_mentoring.session_scheduler'),
            $container->get('jaraba_mentoring.video_meeting'),
        );
    }

    /**
     * Lists sessions for the current user.
     *
     * GET /api/v1/sessions
     * Query params: ?status=scheduled&engagement_id=123
     */
    public function list(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $storage = $this->entityTypeManager()->getStorage('mentoring_session');

        $query = $storage->getQuery()
            ->accessCheck(TRUE);

        // Filtrar por mentor o mentee del usuario actual.
        $or_group = $query->orConditionGroup()
            ->condition('mentee_id', $user->id())
            ->condition('mentor_id.entity:mentor_profile.user_id', $user->id());
        $query->condition($or_group);

        // Filtros opcionales.
        if ($status = $request->query->get('status')) {
            $query->condition('status', $status);
        }
        if ($engagement_id = $request->query->get('engagement_id')) {
            $query->condition('engagement_id', (int) $engagement_id);
        }

        $query->sort('scheduled_start', 'ASC')
            ->range(0, 50);

        $ids = $query->execute();
        $sessions = $storage->loadMultiple($ids);

        $data = [];
        foreach ($sessions as $session) {
            $data[] = $this->serializeSession($session);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'count' => count($data)]);
    }

    /**
     * Creates a new session (booking).
     *
     * POST /api/v1/sessions
     * Body: { engagement_id, slot_datetime, agenda }
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response.
     */
    public function bookSession(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE) ?? [];

        // Validar campos requeridos.
        if (empty($content['engagement_id']) || empty($content['slot_datetime'])) {
            return new JsonResponse([
                'error' => 'engagement_id and slot_datetime are required',
            ], 400);
        }

        // Cargar engagement.
        $engagement = $this->entityTypeManager()
            ->getStorage('mentoring_engagement')
            ->load($content['engagement_id']);

        if (!$engagement) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Engagement not found']], 404);
        }

        // Verificar que el usuario es el mentee del engagement.
        if ((int) $engagement->get('mentee_id')->target_id !== (int) $this->currentUser()->id()) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        // Verificar sesiones disponibles.
        if ($engagement->getSessionsRemaining() <= 0) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No sessions remaining in this engagement']], 400);
        }

        try {
            $slot_datetime = new \DateTime($content['slot_datetime']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid datetime format']], 400);
        }

        // Cargar mentor.
        $mentor = $engagement->get('mentor_id')->entity;
        if (!$mentor) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Mentor not found']], 404);
        }

        // Verificar disponibilidad del slot.
        if (!$this->scheduler->isSlotAvailable($mentor, $slot_datetime)) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Slot not available']], 409);
        }

        // Crear la sesión.
        $session = $this->entityTypeManager()->getStorage('mentoring_session')->create([
            'engagement_id' => $engagement->id(),
            'mentor_id' => $mentor->id(),
            'mentee_id' => $this->currentUser()->id(),
            'scheduled_start' => $slot_datetime->format('Y-m-d\TH:i:s'),
            'scheduled_end' => (clone $slot_datetime)->modify('+60 minutes')->format('Y-m-d\TH:i:s'),
            'status' => 'scheduled',
            'agenda' => $content['agenda'] ?? '',
            'session_type' => $content['session_type'] ?? 'followup',
        ]);

        $session->save();

        // Decrementar sesiones del engagement.
        $engagement->useSession();
        $engagement->save();

        // Generar sala de video.
        $meeting_url = $this->videoService->createRoom($session);
        $session->set('meeting_url', $meeting_url);
        $session->set('meeting_provider', 'jitsi');
        $session->save();

        return new JsonResponse([
            'data' => $this->serializeSession($session), 'meta' => ['timestamp' => time()]], 201);
    }

    /**
     * Updates a session (agenda, status).
     *
     * PATCH /api/v1/sessions/{session_id}
     */
    public function update(int $session_id, Request $request): JsonResponse
    {
        $session = $this->entityTypeManager()
            ->getStorage('mentoring_session')
            ->load($session_id);

        if (!$session) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Session not found']], 404);
        }

        // Verificar acceso.
        $user_id = (int) $this->currentUser()->id();
        $mentor = $session->get('mentor_id')->entity;
        $is_mentor = $mentor && (int) $mentor->get('user_id')->target_id === $user_id;
        $is_mentee = (int) $session->get('mentee_id')->target_id === $user_id;

        if (!$is_mentor && !$is_mentee) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        $content = json_decode($request->getContent(), TRUE) ?? [];

        // Actualizar campos permitidos.
        if (isset($content['agenda'])) {
            $session->set('agenda', $content['agenda']);
        }
        if (isset($content['status']) && $is_mentor) {
            // Solo el mentor puede cambiar el estado.
            $allowed_statuses = ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled'];
            if (in_array($content['status'], $allowed_statuses, TRUE)) {
                $session->set('status', $content['status']);
            }
        }

        $session->save();

        return new JsonResponse(['success' => TRUE, 'data' => $this->serializeSession($session), 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Cancels a session.
     *
     * POST /api/v1/sessions/{session_id}/cancel
     */
    public function cancel(int $session_id, Request $request): JsonResponse
    {
        $session = $this->entityTypeManager()
            ->getStorage('mentoring_session')
            ->load($session_id);

        if (!$session) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Session not found']], 404);
        }

        // Verificar que la sesión puede ser cancelada.
        $current_status = $session->get('status')->value;
        if (!in_array($current_status, ['scheduled', 'confirmed'], TRUE)) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Session cannot be cancelled']], 400);
        }

        // Verificar acceso (mentor o mentee).
        $user_id = (int) $this->currentUser()->id();
        $mentor = $session->get('mentor_id')->entity;
        $is_mentor = $mentor && (int) $mentor->get('user_id')->target_id === $user_id;
        $is_mentee = (int) $session->get('mentee_id')->target_id === $user_id;

        if (!$is_mentor && !$is_mentee) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        $content = json_decode($request->getContent(), TRUE) ?? [];

        // Cancelar la sesión.
        $session->set('status', 'cancelled');
        $session->save();

        // Devolver la sesión al engagement si es posible.
        $engagement = $session->get('engagement_id')->entity;
        if ($engagement) {
            $remaining = (int) $engagement->get('sessions_remaining')->value;
            $engagement->set('sessions_remaining', $remaining + 1);
            $used = (int) $engagement->get('sessions_used')->value;
            $engagement->set('sessions_used', max(0, $used - 1));
            $engagement->save();
        }

        return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Session cancelled successfully', 'data' => $this->serializeSession($session)], 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Reschedules a session.
     *
     * POST /api/v1/sessions/{session_id}/reschedule
     * Body: { new_datetime }
     */
    public function reschedule(int $session_id, Request $request): JsonResponse
    {
        $session = $this->entityTypeManager()
            ->getStorage('mentoring_session')
            ->load($session_id);

        if (!$session) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Session not found']], 404);
        }

        $content = json_decode($request->getContent(), TRUE) ?? [];

        if (empty($content['new_datetime'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'new_datetime is required']], 400);
        }

        try {
            $new_datetime = new \DateTime($content['new_datetime']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid datetime format']], 400);
        }

        // Verificar disponibilidad del nuevo slot.
        $mentor = $session->get('mentor_id')->entity;
        if (!$this->scheduler->isSlotAvailable($mentor, $new_datetime)) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'New slot not available']], 409);
        }

        // Actualizar fechas.
        $session->set('scheduled_start', $new_datetime->format('Y-m-d\TH:i:s'));
        $session->set('scheduled_end', (clone $new_datetime)->modify('+60 minutes')->format('Y-m-d\TH:i:s'));
        $session->set('status', 'scheduled');
        $session->save();

        return new JsonResponse(['success' => TRUE, 'data' => ['message' => 'Session rescheduled successfully', 'data' => $this->serializeSession($session)], 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Gets the join URL for a session.
     *
     * GET /api/v1/sessions/{session_id}/join-url
     */
    public function joinUrl(int $session_id): JsonResponse
    {
        $session = $this->entityTypeManager()
            ->getStorage('mentoring_session')
            ->load($session_id);

        if (!$session) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Session not found']], 404);
        }

        // Verificar que la sesión puede unirse (dentro de ventana de tiempo).
        if (!$session->canJoin()) {
            return new JsonResponse([
                'error' => 'Session cannot be joined yet. Available 15 minutes before start.',
            ], 400);
        }

        $meeting_url = $session->getMeetingUrl();
        if (empty($meeting_url)) {
            // Generar URL si no existe.
            $meeting_url = $this->videoService->createRoom($session);
            $session->set('meeting_url', $meeting_url);
            $session->save();
        }

        return new JsonResponse([
            'data' => [
                'meeting_url' => $meeting_url,
                'provider' => $session->get('meeting_provider')->value ?? 'jitsi',
            ],
        ]);
    }

    /**
     * Serializes a session entity.
     */
    protected function serializeSession($session): array
    {
        return [
            'id' => (int) $session->id(),
            'uuid' => $session->uuid(),
            'engagement_id' => (int) $session->get('engagement_id')->target_id,
            'mentor_id' => (int) $session->get('mentor_id')->target_id,
            'mentee_id' => (int) $session->get('mentee_id')->target_id,
            'scheduled_start' => $session->get('scheduled_start')->value,
            'scheduled_end' => $session->get('scheduled_end')->value,
            'status' => $session->get('status')->value,
            'session_type' => $session->get('session_type')->value,
            'agenda' => $session->get('agenda')->value ?? '',
            'meeting_provider' => $session->get('meeting_provider')->value ?? 'jitsi',
            'can_join' => $session->canJoin(),
        ];
    }

}
