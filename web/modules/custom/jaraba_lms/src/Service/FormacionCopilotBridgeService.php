<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Formacion (LMS).
 *
 * Provee al copilot IA metricas del progreso formativo:
 * cursos activos, progreso, certificados obtenidos, y sugerencias.
 */
class FormacionCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'formacion';
  }

  /**
   * Obtiene contexto formativo relevante para el copilot.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical con metricas formativas.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'formacion',
      'active_enrollments' => 0,
      'completed_courses' => 0,
      'certificates_earned' => 0,
      'avg_progress' => 0,
      'total_available_courses' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('lms_enrollment')) {
        $storage = $this->entityTypeManager->getStorage('lms_enrollment');

        $context['active_enrollments'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->condition('status', 'active')
          ->count()
          ->execute();

        $context['completed_courses'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->condition('status', 'completed')
          ->count()
          ->execute();

        $context['certificates_earned'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->condition('certificate_issued', TRUE)
          ->count()
          ->execute();

        // Average progress of active enrollments.
        $activeEnrollments = $storage->loadByProperties([
          'user_id' => $userId,
          'status' => 'active',
        ]);

        if (!empty($activeEnrollments)) {
          $totalProgress = 0;
          foreach ($activeEnrollments as $enrollment) {
            $totalProgress += (float) ($enrollment->get('progress_percent')->value ?? 0);
          }
          $context['avg_progress'] = round($totalProgress / count($activeEnrollments), 1);
        }
      }

      if ($this->entityTypeManager->hasDefinition('lms_course')) {
        $context['total_available_courses'] = (int) $this->entityTypeManager
          ->getStorage('lms_course')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('is_published', TRUE)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('FormacionCopilotBridge context error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Sugerencia soft de mejora formativa.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Sugerencia o NULL si no aplica.
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $context = $this->getRelevantContext($userId);

      if ($context['active_enrollments'] === 0 && $context['completed_courses'] === 0) {
        return [
          'message' => 'Explora el catalogo de cursos y empieza tu formacion gratuita.',
          'cta' => ['label' => 'Ver cursos', 'route' => 'jaraba_lms.catalog'],
          'trigger' => 'no_enrollments',
        ];
      }

      if ($context['active_enrollments'] > 0 && $context['avg_progress'] < 30) {
        return [
          'message' => 'Tienes cursos en progreso con una media del ' . $context['avg_progress'] . '%. Dedica unos minutos a avanzar.',
          'cta' => ['label' => 'Continuar formacion', 'route' => 'jaraba_lms.my_learning'],
          'trigger' => 'low_progress',
        ];
      }

      if ($context['completed_courses'] > 0 && $context['certificates_earned'] === 0) {
        return [
          'message' => 'Has completado cursos pero no tienes certificados. Revisa tus logros.',
          'cta' => ['label' => 'Ver certificados', 'route' => 'jaraba_lms.my_certificates'],
          'trigger' => 'no_certificates',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('FormacionCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Insights del ecosistema formativo.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas del ecosistema.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_courses' => 0,
      'total_enrollments' => 0,
      'user_enrollments' => 0,
      'platform_completion_rate' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('lms_course')) {
        $insights['total_courses'] = (int) $this->entityTypeManager
          ->getStorage('lms_course')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('is_published', TRUE)
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('lms_enrollment')) {
        $storage = $this->entityTypeManager->getStorage('lms_enrollment');

        $insights['total_enrollments'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();

        $insights['user_enrollments'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->count()
          ->execute();

        if ($insights['total_enrollments'] > 0) {
          $completed = (int) $storage
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'completed')
            ->count()
            ->execute();

          $insights['platform_completion_rate'] = round(
            ($completed / $insights['total_enrollments']) * 100,
            1
          );
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('FormacionCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
