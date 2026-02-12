<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_lms\Entity\CourseInterface;
use Drupal\jaraba_lms\Service\CourseService;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for public course catalog.
 */
class CatalogController extends ControllerBase
{

    /**
     * The course service.
     */
    protected CourseService $courseService;

    /**
     * The enrollment service.
     */
    protected EnrollmentService $enrollmentService;

    /**
     * Constructor.
     */
    public function __construct(CourseService $course_service, EnrollmentService $enrollment_service)
    {
        $this->courseService = $course_service;
        $this->enrollmentService = $enrollment_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_lms.course'),
            $container->get('jaraba_lms.enrollment')
        );
    }

    /**
     * Displays the course catalog.
     */
    public function index(Request $request): array
    {
        $filters = [
            'difficulty' => $request->query->get('difficulty'),
            'vertical' => $request->query->get('vertical'),
            'duration' => $request->query->get('duration'),
            'search' => $request->query->get('q'),
        ];

        $courses = $this->entityTypeManager()
            ->getStorage('lms_course')
            ->loadByProperties(['is_published' => TRUE]);

        // Apply filters
        $filtered_courses = $this->applyFilters($courses, $filters);

        // Get user enrollments for progress display
        $user_enrollments = [];
        if ($this->currentUser()->isAuthenticated()) {
            $user_enrollments = $this->enrollmentService->getUserEnrollments(
                (int) $this->currentUser()->id()
            );
        }

        return [
            '#theme' => 'lms_catalog',
            '#courses' => $this->formatCoursesForDisplay($filtered_courses, $user_enrollments),
            '#filters' => $filters,
            '#facets' => $this->buildFacets($courses),
            '#attached' => [
                'library' => ['jaraba_lms/catalog'],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.query_args'],
                'tags' => ['lms_course_list'],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Displays a single course detail page.
     */
    public function detail(CourseInterface $lms_course): array
    {
        $user_id = (int) $this->currentUser()->id();
        $enrollment = NULL;
        $can_enroll = TRUE;
        $prerequisites_met = TRUE;

        if ($this->currentUser()->isAuthenticated()) {
            $enrollment = $this->enrollmentService->getEnrollment($user_id, (int) $lms_course->id());
            $prerequisites_met = $this->enrollmentService->checkPrerequisites($user_id, (int) $lms_course->id());
            $can_enroll = $enrollment === NULL && $prerequisites_met;
        }

        // Get lessons for curriculum display
        $lessons = $this->getLessons($lms_course);

        // Get related courses
        $related = $this->getRelatedCourses($lms_course);

        // Note: views_count field can be added later for analytics
        // For now, skip view counting to avoid exceptions

        return [
            '#theme' => 'lms_course_detail',
            '#course' => [
                'id' => $lms_course->id(),
                'title' => $lms_course->getTitle(),
                'description' => $lms_course->getDescription(),
                'summary' => $lms_course->getSummary(),
                'duration_minutes' => $lms_course->getDurationMinutes(),
                'duration_formatted' => $this->formatDuration($lms_course->getDurationMinutes()),
                'difficulty' => $lms_course->getDifficultyLevel(),
                'difficulty_label' => $this->getDifficultyLabel($lms_course->getDifficultyLevel()),
                'is_premium' => $lms_course->isPremium(),
                'price' => $lms_course->getPrice(),
                'credits' => $lms_course->getCompletionCredits(),
                'thumbnail_url' => $this->getThumbnailUrl($lms_course),
                'lessons_count' => count($lessons),
            ],
            '#lessons' => $lessons,
            '#enrollment' => $enrollment ? [
                'id' => $enrollment->id(),
                'progress' => $enrollment->getProgressPercent(),
                'status' => $enrollment->getStatus(),
                'enrolled_at' => $enrollment->getEnrolledAt(),
            ] : NULL,
            '#can_enroll' => $can_enroll,
            '#prerequisites_met' => $prerequisites_met,
            '#related_courses' => $related,
            '#attached' => [
                'library' => ['jaraba_lms/course_detail'],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['lms_course:' . $lms_course->id()],
            ],
        ];
    }

    /**
     * Title callback for course detail page.
     */
    public function courseTitle(CourseInterface $lms_course): string
    {
        return $lms_course->getTitle();
    }

    /**
     * Applies filters to courses.
     */
    protected function applyFilters(array $courses, array $filters): array
    {
        return array_filter($courses, function ($course) use ($filters) {
            if (!empty($filters['difficulty']) && $course->getDifficultyLevel() !== $filters['difficulty']) {
                return FALSE;
            }
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $title = strtolower($course->getTitle());
                $desc = strtolower($course->getDescription() ?? '');
                if (strpos($title, $search) === FALSE && strpos($desc, $search) === FALSE) {
                    return FALSE;
                }
            }
            return TRUE;
        });
    }

    /**
     * Formats courses for display.
     */
    protected function formatCoursesForDisplay(array $courses, array $enrollments): array
    {
        $enrollment_by_course = [];
        foreach ($enrollments as $e) {
            $enrollment_by_course[$e->getCourseId()] = $e;
        }

        $formatted = [];
        foreach ($courses as $course) {
            $enrollment = $enrollment_by_course[$course->id()] ?? NULL;
            $formatted[] = [
                'id' => $course->id(),
                'title' => $course->getTitle(),
                'summary' => $course->getSummary(),
                'duration_formatted' => $this->formatDuration($course->getDurationMinutes()),
                'difficulty' => $course->getDifficultyLevel(),
                'difficulty_label' => $this->getDifficultyLabel($course->getDifficultyLevel()),
                'is_premium' => $course->isPremium(),
                'price' => $course->getPrice(),
                'thumbnail_url' => $this->getThumbnailUrl($course),
                'is_enrolled' => $enrollment !== NULL,
                'progress' => $enrollment ? $enrollment->getProgressPercent() : 0,
                'url' => '/course/' . $course->id(),
            ];
        }
        return $formatted;
    }

    /**
     * Builds facets for filtering.
     */
    protected function buildFacets(array $courses): array
    {
        $difficulties = [];
        foreach ($courses as $course) {
            $level = $course->getDifficultyLevel();
            $difficulties[$level] = ($difficulties[$level] ?? 0) + 1;
        }

        return [
            'difficulty' => [
                'label' => $this->t('Difficulty'),
                'options' => [
                    'beginner' => ['label' => $this->t('Beginner'), 'count' => $difficulties['beginner'] ?? 0],
                    'intermediate' => ['label' => $this->t('Intermediate'), 'count' => $difficulties['intermediate'] ?? 0],
                    'advanced' => ['label' => $this->t('Advanced'), 'count' => $difficulties['advanced'] ?? 0],
                ],
            ],
        ];
    }

    /**
     * Gets lessons for a course.
     */
    protected function getLessons(CourseInterface $course): array
    {
        $lessonStorage = $this->entityTypeManager()->getStorage('lms_lesson');
        $lessonIds = $lessonStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('course_id', $course->id())
            ->sort('weight', 'ASC')
            ->execute();

        if (empty($lessonIds)) {
            return [];
        }

        $lessons = [];
        foreach ($lessonStorage->loadMultiple($lessonIds) as $lesson) {
            $lessons[] = [
                'id' => $lesson->id(),
                'title' => $lesson->getTitle(),
                'duration_minutes' => $lesson->get('duration_minutes')->value ?? 0,
                'type' => $lesson->get('type')->value ?? 'content',
                'weight' => $lesson->get('weight')->value ?? 0,
            ];
        }

        return $lessons;
    }

    /**
     * Gets related courses.
     */
    protected function getRelatedCourses(CourseInterface $course): array
    {
        // Basic recommendation logic: tag/category similarity, popular courses,
        // and courses complementary to user's history.
        $related = [];
        try {
            $courseStorage = $this->entityTypeManager()->getStorage('lms_course');

            // 1. Similarity by difficulty level (same difficulty).
            $similarIds = $courseStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('is_published', TRUE)
                ->condition('difficulty', $course->getDifficultyLevel())
                ->condition('id', $course->id(), '<>')
                ->sort('created', 'DESC')
                ->range(0, 6)
                ->execute();

            // 2. Complementary: different difficulty to encourage progression.
            $nextDifficulty = match ($course->getDifficultyLevel()) {
                'beginner' => 'intermediate',
                'intermediate' => 'advanced',
                default => 'beginner',
            };
            $complementaryIds = $courseStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('is_published', TRUE)
                ->condition('difficulty', $nextDifficulty)
                ->sort('created', 'DESC')
                ->range(0, 3)
                ->execute();

            // Merge and deduplicate, exclude current course.
            $allIds = array_unique(array_merge($similarIds, $complementaryIds));
            unset($allIds[array_search($course->id(), $allIds)]);
            $allIds = array_slice($allIds, 0, 4);

            if (!empty($allIds)) {
                foreach ($courseStorage->loadMultiple($allIds) as $relatedCourse) {
                    $related[] = [
                        'id' => $relatedCourse->id(),
                        'title' => $relatedCourse->getTitle(),
                        'summary' => $relatedCourse->getSummary(),
                        'difficulty' => $relatedCourse->getDifficultyLevel(),
                        'difficulty_label' => $this->getDifficultyLabel($relatedCourse->getDifficultyLevel()),
                        'duration_formatted' => $this->formatDuration($relatedCourse->getDurationMinutes()),
                        'thumbnail_url' => $this->getThumbnailUrl($relatedCourse),
                        'url' => '/course/' . $relatedCourse->id(),
                    ];
                }
            }
        }
        catch (\Exception $e) {
            // Entity or field may not exist yet; return empty.
        }

        return $related;
    }

    /**
     * Formats duration in human-readable format.
     */
    protected function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . ($mins > 0 ? $mins . ' min' : '');
    }

    /**
     * Gets difficulty label.
     */
    protected function getDifficultyLabel(string $level): string
    {
        $labels = [
            'beginner' => $this->t('Beginner'),
            'intermediate' => $this->t('Intermediate'),
            'advanced' => $this->t('Advanced'),
        ];
        return (string) ($labels[$level] ?? $level);
    }

    /**
     * Gets thumbnail URL for a course.
     */
    protected function getThumbnailUrl(CourseInterface $course): ?string
    {
        $file_id = $course->get('thumbnail')->target_id;
        if (!$file_id) {
            return '/modules/custom/jaraba_lms/images/default-course.jpg';
        }
        $file = $this->entityTypeManager()->getStorage('file')->load($file_id);
        return $file ? \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()) : NULL;
    }

}
