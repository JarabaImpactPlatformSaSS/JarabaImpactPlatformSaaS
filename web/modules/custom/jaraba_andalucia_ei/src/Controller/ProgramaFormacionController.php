<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formación contextualizada para el programa Andalucía +ei.
 *
 * Muestra cursos relevantes para participantes del programa, filtrados
 * por la vertical andalucia_ei y el tenant_id del participante.
 * No reutiliza /courses (marketplace genérico).
 */
class ProgramaFormacionController extends ControllerBase {

  /**
   * Constructs a ProgramaFormacionController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the contextualized courses page for Andalucía +ei.
   *
   * @return array
   *   Render array.
   */
  public function formacion(): array {
    $participante = $this->getParticipanteActual();
    $courses = $this->loadProgramCourses($participante);

    $courseData = [];
    foreach ($courses as $course) {
      $courseData[] = [
        'id' => $course->id(),
        'title' => $course->get('title')->value ?? '',
        'summary' => $course->get('summary')->value ?? '',
        'duration_minutes' => (int) ($course->get('duration_minutes')->value ?? 0),
        'difficulty_level' => $course->get('difficulty_level')->value ?? 'beginner',
        'is_premium' => (bool) ($course->get('is_premium')->value ?? FALSE),
        'price' => (float) ($course->get('price')->value ?? 0),
        'completion_credits' => (int) ($course->get('completion_credits')->value ?? 0),
        'course_url' => Url::fromRoute('entity.lms_course.canonical', ['lms_course' => $course->id()])->toString(),
      ];
    }

    // Check enrollment status for participante.
    $enrollments = [];
    if ($participante) {
      $enrollments = $this->getEnrollmentStatus($participante, array_column($courseData, 'id'));
    }

    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'programa_formacion',
      '#courses' => $courseData,
      '#total_courses' => count($courseData),
      '#enrollments' => $enrollments,
      '#participante' => $participante ? [
        'id' => $participante->id(),
        'nombre' => $participante->label(),
        'fase' => $participante->get('fase_actual')->value ?? 'acogida',
        'carril' => $participante->get('carril')->value ?? '',
      ] : NULL,
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/programa-formacion',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.path'],
        'tags' => ['lms_course_list', 'config:jaraba_andalucia_ei.settings'],
        'max-age' => 900,
      ],
    ];
  }

  /**
   * Loads courses relevant to the Andalucía +ei program.
   *
   * @param mixed $participante
   *   The participant entity, or NULL.
   *
   * @return array
   *   Array of lms_course entities.
   */
  protected function loadProgramCourses(mixed $participante): array {
    if (!$this->entityTypeManager->hasDefinition('lms_course')) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('lms_course');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_published', TRUE)
      ->sort('completion_credits', 'DESC')
      ->sort('title', 'ASC')
      ->range(0, 24);

    // Filter by participant's tenant if available.
    if ($participante) {
      $tenantId = $participante->get('tenant_id')->target_id;
      if ($tenantId) {
        $query->condition('tenant_id', $tenantId);
      }
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Gets enrollment status for courses.
   *
   * @param mixed $participante
   *   Participant entity.
   * @param array $courseIds
   *   Course IDs to check.
   *
   * @return array
   *   Keyed by course_id => ['status' => string, 'progress' => int].
   */
  protected function getEnrollmentStatus(mixed $participante, array $courseIds): array {
    if (empty($courseIds) || !$this->entityTypeManager->hasDefinition('lms_enrollment')) {
      return [];
    }

    try {
      $userId = $participante->getOwnerId();
      $storage = $this->entityTypeManager->getStorage('lms_enrollment');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId)
        ->condition('course_id', $courseIds, 'IN')
        ->execute();

      $enrollments = [];
      foreach ($storage->loadMultiple($ids) as $enrollment) {
        $courseId = $enrollment->get('course_id')->target_id;
        $enrollments[$courseId] = [
          'status' => $enrollment->get('status')->value ?? 'active',
          'progress' => (int) ($enrollment->get('progress_percent')->value ?? 0),
        ];
      }
      return $enrollments;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading enrollments: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets the current user's participant entity.
   *
   * @return mixed
   *   The participante or NULL.
   */
  protected function getParticipanteActual(): mixed {
    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      return NULL;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', $user->id())
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        return $storage->load(reset($ids));
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading participante for user @uid: @msg', [
        '@uid' => $user->id(),
        '@msg' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

}
