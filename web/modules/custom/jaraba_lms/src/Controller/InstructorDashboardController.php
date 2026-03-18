<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller para el dashboard de instructores LMS.
 *
 * P0-05: Frontend instructor con rutas /mis-cursos, lecciones y alumnos.
 * CONTROLLER-READONLY-001: No usar readonly en propiedades heredadas.
 * ZERO-REGION-001: Variables y drupalSettings via hook_preprocess_page().
 */
class InstructorDashboardController extends ControllerBase {

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * Database connection.
   */
  protected Connection $database;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    Connection $database,
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
  ) {
    // CONTROLLER-READONLY-001: asignar manualmente propiedad heredada.
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $current_user;
    $this->database = $database;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('database'),
      $container->has('ecosistema_jaraba_core.setup_wizard_registry')
        ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
      $container->has('ecosistema_jaraba_core.daily_actions_registry')
        ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
    );
  }

  /**
   * Listado de cursos del instructor.
   *
   * Muestra todos los cursos donde el usuario actual es author_id.
   */
  public function courses(): array {
    $uid = (int) $this->account->id();
    $course_storage = $this->entityTypeManager()->getStorage('lms_course');

    $ids = $course_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('author_id', $uid)
      ->sort('changed', 'DESC')
      ->execute();

    // SETUP-WIZARD-DAILY-001: Wizard + daily actions data.
    $tenantId = \Drupal::hasService('ecosistema_jaraba_core.tenant_context')
      ? (int) \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenantId()
      : 0;
    $setupWizard = $this->wizardRegistry?->hasWizard('instructor_lms')
      ? $this->wizardRegistry->getStepsForWizard('instructor_lms', $tenantId)
      : NULL;
    $dailyActions = $this->dailyActionsRegistry?->getActionsForDashboard('instructor_lms', $tenantId) ?? [];

    $courses = [];
    foreach ($course_storage->loadMultiple($ids) as $course) {
      // Contar lecciones.
      $lesson_count = (int) $this->entityTypeManager()->getStorage('lms_lesson')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('course_id', $course->id())
        ->count()
        ->execute();

      // Contar alumnos activos.
      $student_count = (int) $this->entityTypeManager()->getStorage('lms_enrollment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('course_id', $course->id())
        ->condition('status', 'active')
        ->count()
        ->execute();

      $duration = (int) ($course->get('duration_minutes')->value ?? 0);
      $hours = floor($duration / 60);
      $mins = $duration % 60;

      $courses[] = [
        'id' => (int) $course->id(),
        'title' => $course->label() ?? '',
        'difficulty_level' => $course->get('difficulty_level')->value ?? 'beginner',
        'is_published' => (bool) ($course->get('is_published')->value ?? FALSE),
        'is_premium' => (bool) ($course->get('is_premium')->value ?? FALSE),
        'price' => $course->get('price')->value ?? '0.00',
        'duration_formatted' => $hours > 0
          ? $hours . 'h ' . ($mins > 0 ? $mins . 'min' : '')
          : $mins . 'min',
        'lesson_count' => $lesson_count,
        'student_count' => $student_count,
      ];
    }

    return [
      '#theme' => 'lms_instructor_courses',
      '#courses' => $courses,
      '#total' => count($courses),
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
      '#attached' => [
        'library' => ['jaraba_lms/instructor-dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['lms_course_list'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Listado de lecciones de un curso del instructor.
   */
  public function lessons(int $course_id): array {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      throw new NotFoundHttpException();
    }

    // Verificar que el curso pertenece al instructor.
    $uid = (int) $this->account->id();
    $author_id = (int) ($course->get('author_id')->target_id ?? 0);
    if ($author_id !== $uid && !$this->account->hasPermission('administer lms courses')) {
      throw new AccessDeniedHttpException();
    }

    $lesson_storage = $this->entityTypeManager()->getStorage('lms_lesson');
    $ids = $lesson_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('course_id', $course_id)
      ->sort('weight', 'ASC')
      ->execute();

    $lessons = [];
    foreach ($lesson_storage->loadMultiple($ids) as $lesson) {
      $lessons[] = [
        'id' => (int) $lesson->id(),
        'title' => $lesson->label() ?? '',
        'lesson_type' => $lesson->get('lesson_type')->value ?? 'text',
        'estimated_duration' => (int) ($lesson->get('estimated_duration')->value ?? 10),
        'weight' => (int) ($lesson->get('weight')->value ?? 0),
      ];
    }

    return [
      '#theme' => 'lms_instructor_lessons',
      '#course' => [
        'id' => (int) $course->id(),
        'title' => $course->label() ?? '',
        'is_published' => (bool) ($course->get('is_published')->value ?? FALSE),
      ],
      '#lessons' => $lessons,
      '#total' => count($lessons),
      '#attached' => [
        'library' => ['jaraba_lms/instructor-dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['lms_course:' . $course_id],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Titulo dinámico para la página de lecciones.
   */
  public function lessonsTitle(int $course_id): string {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      return (string) $this->t('Lecciones');
    }
    return (string) $this->t('Lecciones: @title', ['@title' => $course->label() ?? '']);
  }

  /**
   * Listado de alumnos de un curso del instructor.
   */
  public function students(int $course_id): array {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      throw new NotFoundHttpException();
    }

    // Verificar que el curso pertenece al instructor.
    $uid = (int) $this->account->id();
    $author_id = (int) ($course->get('author_id')->target_id ?? 0);
    if ($author_id !== $uid && !$this->account->hasPermission('administer lms courses')) {
      throw new AccessDeniedHttpException();
    }

    $enrollment_storage = $this->entityTypeManager()->getStorage('lms_enrollment');
    $ids = $enrollment_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('course_id', $course_id)
      ->sort('created', 'DESC')
      ->execute();

    $user_storage = $this->entityTypeManager()->getStorage('user');
    $students = [];
    foreach ($enrollment_storage->loadMultiple($ids) as $enrollment) {
      $user_id = (int) ($enrollment->get('user_id')->target_id ?? 0);
      $user = $user_storage->load($user_id);

      $students[] = [
        'enrollment_id' => (int) $enrollment->id(),
        'user_id' => $user_id,
        'user_name' => $user ? $user->getDisplayName() : $this->t('Usuario desconocido'),
        'user_email' => $user ? $user->getEmail() : '',
        'status' => $enrollment->get('status')->value ?? 'active',
        'progress' => (int) ($enrollment->get('progress_percent')->value ?? 0),
        'enrollment_type' => $enrollment->get('enrollment_type')->value ?? 'free',
        'enrolled_date' => $enrollment->get('created')->value ?? 0,
      ];
    }

    return [
      '#theme' => 'lms_instructor_students',
      '#course' => [
        'id' => (int) $course->id(),
        'title' => $course->label() ?? '',
        'is_published' => (bool) ($course->get('is_published')->value ?? FALSE),
      ],
      '#students' => $students,
      '#total' => count($students),
      '#attached' => [
        'library' => ['jaraba_lms/instructor-dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['lms_course:' . $course_id],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Titulo dinámico para la página de alumnos.
   */
  public function studentsTitle(int $course_id): string {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      return (string) $this->t('Alumnos');
    }
    return (string) $this->t('Alumnos: @title', ['@title' => $course->label() ?? '']);
  }

  /**
   * P2-08: Analytics de un curso para el instructor.
   *
   * Métricas: tasa de completado, tiempo medio por lección, drop-off,
   * engagement semanal y tendencia de matrículas.
   * Chart data inyectado via drupalSettings (ZERO-REGION-001).
   */
  public function courseAnalytics(int $course_id): array {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      throw new NotFoundHttpException();
    }

    // Verificar ownership.
    $uid = (int) $this->account->id();
    $author_id = (int) ($course->get('author_id')->target_id ?? 0);
    if ($author_id !== $uid && !$this->account->hasPermission('administer lms courses')) {
      throw new AccessDeniedHttpException();
    }

    $enrollment_storage = $this->entityTypeManager()->getStorage('lms_enrollment');
    $lesson_storage = $this->entityTypeManager()->getStorage('lms_lesson');

    // Total alumnos.
    $total_students = (int) $enrollment_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('course_id', $course_id)
      ->count()
      ->execute();

    // Alumnos completados (progress_percent = 100).
    $completed_students = (int) $enrollment_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('course_id', $course_id)
      ->condition('progress_percent', 100)
      ->count()
      ->execute();

    $completion_rate = $total_students > 0
      ? round(($completed_students / $total_students) * 100, 1)
      : 0;

    // Progreso promedio.
    $enrollments = $enrollment_storage->loadByProperties(['course_id' => $course_id]);
    $total_progress = 0;
    foreach ($enrollments as $enrollment) {
      $total_progress += (int) ($enrollment->get('progress_percent')->value ?? 0);
    }
    $avg_progress = $total_students > 0
      ? round($total_progress / $total_students, 1)
      : 0;

    // Lecciones del curso con estadísticas.
    $lesson_ids = $lesson_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('course_id', $course_id)
      ->sort('weight', 'ASC')
      ->execute();

    $lessons_data = [];
    foreach ($lesson_storage->loadMultiple($lesson_ids) as $lesson) {
      $lesson_id = (int) $lesson->id();
      $title = $lesson->label() ?? '';

      // Contar views por lección usando tabla lms_lesson_progress
      // si existe, sino fallback a 0.
      $views = 0;
      $completions = 0;
      try {
        if ($this->database->schema()->tableExists('lms_lesson_progress')) {
          $views = (int) $this->database->select('lms_lesson_progress', 'p')
            ->condition('p.lesson_id', $lesson_id)
            ->countQuery()
            ->execute()
            ->fetchField();

          $completions = (int) $this->database->select('lms_lesson_progress', 'p')
            ->condition('p.lesson_id', $lesson_id)
            ->condition('p.status', 'completed')
            ->countQuery()
            ->execute()
            ->fetchField();
        }
      }
      catch (\Throwable) {
        // Tabla no existe aún — valores por defecto.
      }

      $lessons_data[] = [
        'id' => $lesson_id,
        'title' => $title,
        'views' => $views,
        'completions' => $completions,
        'drop_off_rate' => $views > 0
          ? round(100 - ($completions / $views * 100), 1)
          : 0,
      ];
    }

    // Tendencia de matrículas (últimas 8 semanas).
    $enrollment_trend = [];
    for ($i = 7; $i >= 0; $i--) {
      $week_start = strtotime("-{$i} weeks monday");
      $week_end = $week_start + (7 * 86400);

      $count = (int) $enrollment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('course_id', $course_id)
        ->condition('created', $week_start, '>=')
        ->condition('created', $week_end, '<')
        ->count()
        ->execute();

      $enrollment_trend[] = [
        'label' => date('d M', $week_start),
        'value' => $count,
      ];
    }

    // Chart data para engagement (lessons views por semana).
    $engagement_chart = [];
    for ($i = 7; $i >= 0; $i--) {
      $week_start = strtotime("-{$i} weeks monday");
      $week_end = $week_start + (7 * 86400);
      $views = 0;

      try {
        if ($this->database->schema()->tableExists('lms_lesson_progress')) {
          $views = (int) $this->database->select('lms_lesson_progress', 'p')
            ->condition('p.lesson_id', array_column($lessons_data, 'id'), 'IN')
            ->condition('p.created', $week_start, '>=')
            ->condition('p.created', $week_end, '<')
            ->countQuery()
            ->execute()
            ->fetchField();
        }
      }
      catch (\Throwable) {
        // Fallback.
      }

      $engagement_chart[] = [
        'label' => date('d M', $week_start),
        'value' => $views,
      ];
    }

    return [
      '#theme' => 'lms_instructor_analytics',
      '#course' => [
        'id' => (int) $course->id(),
        'title' => $course->label() ?? '',
        'is_published' => (bool) ($course->get('is_published')->value ?? FALSE),
      ],
      '#kpis' => [
        'total_students' => $total_students,
        'completed_students' => $completed_students,
        'completion_rate' => $completion_rate,
        'avg_progress' => $avg_progress,
        'total_lessons' => count($lessons_data),
      ],
      '#lessons' => $lessons_data,
      '#attached' => [
        'library' => ['jaraba_lms/instructor-analytics'],
        'drupalSettings' => [
          'lmsAnalytics' => [
            'enrollmentTrend' => $enrollment_trend,
            'engagementChart' => $engagement_chart,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['lms_course:' . $course_id],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Título dinámico para la página de analytics.
   */
  public function analyticsTitle(int $course_id): string {
    $course = $this->entityTypeManager()->getStorage('lms_course')->load($course_id);
    if (!$course) {
      return (string) $this->t('Analytics');
    }
    return (string) $this->t('Analytics: @title', ['@title' => $course->label() ?? '']);
  }

}
