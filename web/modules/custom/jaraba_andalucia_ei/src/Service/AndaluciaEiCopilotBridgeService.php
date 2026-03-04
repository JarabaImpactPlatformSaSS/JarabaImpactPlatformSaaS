<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Andalucia Emprende e Innova.
 *
 * Provee al copilot IA metricas de solicitudes, convocatorias,
 * expedientes y documentacion pendiente.
 */
class AndaluciaEiCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'andalucia_ei';
  }

  /**
   * Obtiene contexto relevante del usuario para el copilot.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical con metricas de solicitudes.
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'andalucia_ei',
      'active_requests' => 0,
      'pending_documents' => 0,
      'approved_requests' => 0,
      'active_programs' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('ei_request')) {
        $storage = $this->entityTypeManager->getStorage('ei_request');

        $context['active_requests'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', ['pending', 'in_review', 'documentation'], 'IN')
          ->count()
          ->execute();

        $context['approved_requests'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', 'approved')
          ->count()
          ->execute();

        $context['pending_documents'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', 'documentation')
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('ei_program')) {
        $context['active_programs'] = (int) $this->entityTypeManager
          ->getStorage('ei_program')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge context error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Sugerencia soft contextual.
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

      if ($context['pending_documents'] > 0) {
        return [
          'message' => 'Tienes ' . $context['pending_documents'] . ' solicitud(es) pendiente(s) de documentacion.',
          'cta' => ['label' => 'Ver solicitudes', 'route' => 'jaraba_andalucia_ei.my_requests'],
          'trigger' => 'pending_docs',
        ];
      }

      if ($context['active_requests'] === 0 && $context['active_programs'] > 0) {
        return [
          'message' => 'Hay ' . $context['active_programs'] . ' programa(s) activo(s). Consulta las convocatorias abiertas.',
          'cta' => ['label' => 'Ver programas', 'route' => 'jaraba_andalucia_ei.programs'],
          'trigger' => 'no_requests',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Insights del ecosistema de innovacion.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas del ecosistema.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_programs' => 0,
      'active_programs' => 0,
      'total_participants' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('ei_program')) {
        $storage = $this->entityTypeManager->getStorage('ei_program');

        $insights['total_programs'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();

        $insights['active_programs'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
          ->count()
          ->execute();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
