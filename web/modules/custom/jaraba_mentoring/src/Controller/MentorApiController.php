<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_mentoring\Entity\MentorProfile;
use Drupal\jaraba_mentoring\Service\MentorMatchingService;

/**
 * API Controller for mentor endpoints.
 */
class MentorApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected MentorMatchingService $matchingService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_mentoring.mentor_matching'),
        );
    }

    /**
     * Lists mentors with optional filters.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with mentor list.
     */
    public function list(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mentor_profile');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'active')
            ->condition('is_available', TRUE);

        // Apply filters.
        if ($sector = $request->query->get('sector')) {
            $query->condition('sectors', $sector, 'CONTAINS');
        }
        if ($stage = $request->query->get('stage')) {
            $query->condition('business_stages', $stage, 'CONTAINS');
        }
        if ($max_price = $request->query->get('max_price')) {
            $query->condition('hourly_rate', (float) $max_price, '<=');
        }

        $query->sort('average_rating', 'DESC')
            ->range(0, 20);

        $mentor_ids = $query->execute();
        $mentors = $storage->loadMultiple($mentor_ids);

        $data = [];
        foreach ($mentors as $mentor) {
            /** @var MentorProfile $mentor */
            $data[] = $this->serializeMentor($mentor);
        }

        return new JsonResponse(['data' => $data, 'count' => count($data)]);
    }

    /**
     * Gets a single mentor profile.
     *
     * @param \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor_profile
     *   The mentor profile.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response.
     */
    public function get(MentorProfile $mentor_profile): JsonResponse
    {
        $data = $this->serializeMentor($mentor_profile, TRUE);
        return new JsonResponse(['data' => $data]);
    }

    /**
     * Gets mentor availability.
     *
     * @param \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor_profile
     *   The mentor profile.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with available slots.
     */
    public function availability(MentorProfile $mentor_profile, Request $request): JsonResponse
    {
        $start = new \DateTime($request->query->get('start', 'now'));
        $end = new \DateTime($request->query->get('end', '+14 days'));

        $scheduler = \Drupal::service('jaraba_mentoring.session_scheduler');
        $slots = $scheduler->getAvailableSlots($mentor_profile, $start, $end);

        return new JsonResponse(['data' => $slots, 'count' => count($slots)]);
    }

    /**
     * Matches mentors for an entrepreneur.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request with matching criteria.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with matched mentors.
     */
    public function match(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE) ?? [];

        $criteria = [
            'sector' => $content['sector'] ?? NULL,
            'stage' => $content['stage'] ?? NULL,
            'specializations' => $content['specializations'] ?? [],
            'max_price' => $content['max_price'] ?? NULL,
            'diagnostic_id' => $content['diagnostic_id'] ?? NULL,
        ];

        $matches = $this->matchingService->findMatches($criteria, 10);

        $data = [];
        foreach ($matches as $match) {
            /** @var MentorProfile $mentor */
            $mentor = $match['mentor'];
            $data[] = [
                'mentor' => $this->serializeMentor($mentor),
                'score' => round($match['score'], 1),
                'breakdown' => $match['breakdown'],
            ];
        }

        return new JsonResponse(['data' => $data, 'count' => count($data)]);
    }

    /**
     * Serializes a mentor profile.
     *
     * @param \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor
     *   The mentor.
     * @param bool $full
     *   Whether to include full details.
     *
     * @return array
     *   Serialized data.
     */
    protected function serializeMentor(MentorProfile $mentor, bool $full = FALSE): array
    {
        $data = [
            'id' => (int) $mentor->id(),
            'uuid' => $mentor->uuid(),
            'display_name' => $mentor->getDisplayName(),
            'headline' => $mentor->getHeadline(),
            'sectors' => array_column($mentor->get('sectors')->getValue(), 'value'),
            'certification_level' => $mentor->getCertificationLevel(),
            'average_rating' => $mentor->getAverageRating(),
            'total_sessions' => (int) ($mentor->get('total_sessions')->value ?? 0),
            'hourly_rate' => $mentor->getHourlyRate(),
            'is_available' => $mentor->isAvailable(),
        ];

        if ($full) {
            $data['bio'] = $mentor->get('bio')->value;
            $data['business_stages'] = array_column($mentor->get('business_stages')->getValue(), 'value');
            $data['specializations'] = array_column($mentor->get('specializations')->getValue(), 'value');
            $data['total_reviews'] = (int) ($mentor->get('total_reviews')->value ?? 0);
        }

        return $data;
    }

}
