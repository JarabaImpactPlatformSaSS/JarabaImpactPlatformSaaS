<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Empleabilidad.
 *
 * Provee al copilot IA metricas del perfil del candidato,
 * estado de aplicaciones, skills, y sugerencias de mejora.
 */
class EmpleabilidadCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'empleabilidad';
  }

  /**
   * Obtiene contexto relevante del candidato para el copilot.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical con metricas del perfil.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'empleabilidad',
      'completion_percent' => 0,
      'has_profile' => FALSE,
      'skills_count' => 0,
      'experience_count' => 0,
      'education_count' => 0,
      'cv_generated' => FALSE,
      'is_public' => FALSE,
      'availability_status' => 'not_specified',
    ];

    try {
      $profiles = $this->entityTypeManager
        ->getStorage('candidate_profile')
        ->loadByProperties(['user_id' => $userId]);

      if (!empty($profiles)) {
        $profile = reset($profiles);
        $context['has_profile'] = TRUE;
        $context['completion_percent'] = (int) ($profile->get('completion_percent')->value ?? 0);
        $context['is_public'] = (bool) ($profile->get('is_public')->value ?? FALSE);
        $context['availability_status'] = $profile->get('availability_status')->value ?? 'not_specified';
        $context['cv_generated'] = !empty($profile->get('cv_generated_html')->value);
      }

      if ($this->entityTypeManager->hasDefinition('candidate_skill')) {
        $context['skills_count'] = (int) $this->entityTypeManager
          ->getStorage('candidate_skill')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('candidate_experience')) {
        $context['experience_count'] = (int) $this->entityTypeManager
          ->getStorage('candidate_experience')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('candidate_education')) {
        $context['education_count'] = (int) $this->entityTypeManager
          ->getStorage('candidate_education')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', $userId)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('EmpleabilidadCopilotBridge context error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Sugerencia soft de mejora contextual.
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

      if (!$context['has_profile']) {
        return [
          'message' => 'Crea tu perfil profesional para empezar a recibir ofertas personalizadas.',
          'cta' => ['label' => 'Crear perfil', 'route' => 'jaraba_candidate.my_profile'],
          'trigger' => 'no_profile',
        ];
      }

      if ($context['completion_percent'] < 60) {
        return [
          'message' => 'Tu perfil esta al ' . $context['completion_percent'] . '%. Completalo para mejorar tu visibilidad.',
          'cta' => ['label' => 'Completar perfil', 'route' => 'jaraba_candidate.my_profile.edit'],
          'trigger' => 'low_completion',
        ];
      }

      if ($context['skills_count'] < 3) {
        return [
          'message' => 'Anade al menos 3 competencias para que el matching de ofertas sea mas preciso.',
          'cta' => ['label' => 'Anadir competencias', 'route' => 'jaraba_candidate.my_profile.skills'],
          'trigger' => 'few_skills',
        ];
      }

      if (!$context['cv_generated']) {
        return [
          'message' => 'Genera tu CV profesional con IA para destacar en las candidaturas.',
          'cta' => ['label' => 'Generar CV', 'route' => 'jaraba_candidate.cv_builder'],
          'trigger' => 'no_cv',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('EmpleabilidadCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Insights de mercado laboral.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas de mercado.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_candidates' => 0,
      'public_profiles' => 0,
      'avg_completion' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('candidate_profile');

      $insights['total_candidates'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $insights['public_profiles'] = (int) $storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_public', TRUE)
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->warning('EmpleabilidadCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
