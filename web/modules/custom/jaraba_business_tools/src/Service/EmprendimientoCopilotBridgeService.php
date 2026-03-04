<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Emprendimiento.
 *
 * Provee al copilot IA metricas del plan de negocio,
 * estado del perfil emprendedor, y sugerencias de mejora.
 */
class EmprendimientoCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'emprendimiento';
  }

  /**
   * Obtiene contexto relevante del emprendedor para el copilot.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical con metricas del emprendimiento.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'emprendimiento',
      'has_business_plan' => FALSE,
      'business_plans_count' => 0,
      'has_startup_profile' => FALSE,
      'mentoring_sessions' => 0,
      'funding_requested' => FALSE,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('business_plan')) {
        $plans = $this->entityTypeManager
          ->getStorage('business_plan')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->count()
          ->execute();

        $context['business_plans_count'] = (int) $plans;
        $context['has_business_plan'] = $context['business_plans_count'] > 0;
      }

      if ($this->entityTypeManager->hasDefinition('startup_profile')) {
        $profiles = $this->entityTypeManager
          ->getStorage('startup_profile')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->count()
          ->execute();

        $context['has_startup_profile'] = ((int) $profiles) > 0;
      }

      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $context['mentoring_sessions'] = (int) $this->entityTypeManager
          ->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('mentee_id', $userId)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('EmprendimientoCopilotBridge context error: @error', [
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

      if (!$context['has_business_plan']) {
        return [
          'message' => 'Crea tu primer plan de negocio con ayuda de la IA para estructurar tu idea.',
          'cta' => ['label' => 'Crear plan', 'route' => 'jaraba_business_tools.plan.add'],
          'trigger' => 'no_plan',
        ];
      }

      if (!$context['has_startup_profile']) {
        return [
          'message' => 'Completa tu perfil de startup para conectar con inversores y mentores.',
          'cta' => ['label' => 'Crear perfil', 'route' => 'jaraba_business_tools.startup.add'],
          'trigger' => 'no_startup_profile',
        ];
      }

      if ($context['mentoring_sessions'] === 0) {
        return [
          'message' => 'Solicita una sesion de mentoria gratuita para validar tu modelo de negocio.',
          'cta' => ['label' => 'Buscar mentor', 'route' => 'jaraba_mentoring.find'],
          'trigger' => 'no_mentoring',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('EmprendimientoCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Insights del ecosistema emprendedor.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas del ecosistema.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_startups' => 0,
      'total_plans' => 0,
      'active_mentors' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('startup_profile')) {
        $insights['total_startups'] = (int) $this->entityTypeManager
          ->getStorage('startup_profile')
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('business_plan')) {
        $insights['total_plans'] = (int) $this->entityTypeManager
          ->getStorage('business_plan')
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('EmprendimientoCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
