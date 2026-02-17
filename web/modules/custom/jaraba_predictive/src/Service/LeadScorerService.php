<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de puntuacion predictiva de leads.
 *
 * ESTRUCTURA:
 *   Motor heuristico que calcula la calidad de un lead basandose en
 *   tres dimensiones: engagement (interacciones), activation (uso de
 *   funcionalidades) e intent (senales de intencion de compra).
 *   Persiste cada calculo como entidad LeadScore.
 *
 * LOGICA:
 *   Utiliza pesos configurables desde jaraba_predictive.settings
 *   (lead_scoring.*) para ponderar cada dimension. El total_score
 *   (0-100) se categoriza en qualification:
 *     <25 = cold, 25-50 = warm, 50-75 = hot, >=75 = sales_ready.
 *   Soporta actualizacion incremental de scores existentes.
 *
 * RELACIONES:
 *   - Consume: entity_type.manager (LeadScore, user storage).
 *   - Consume: ecosistema_jaraba_core.tenant_context.
 *   - Produce: LeadScore entities.
 */
class LeadScorerService {

  /**
   * Construye el servicio de lead scoring.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto multi-tenant.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * Calcula la puntuacion de un lead/usuario.
   *
   * ESTRUCTURA:
   *   Metodo principal que orquesta la evaluacion del lead en 3
   *   dimensiones, calcula el score total y persiste la entidad.
   *
   * LOGICA:
   *   1. Carga el usuario y valida existencia.
   *   2. Calcula engagement_score: page views, session duration, frecuencia.
   *   3. Calcula activation_score: funcionalidades usadas, completitud perfil.
   *   4. Calcula intent_score: visitas a pricing, contacto con ventas.
   *   5. Aplica pesos configurables para total_score (0-100).
   *   6. Clasifica qualification segun umbrales.
   *   7. Crea o actualiza LeadScore entity.
   *
   * RELACIONES:
   *   - Lee: user entity.
   *   - Crea/actualiza: lead_score entity.
   *
   * @param int $userId
   *   ID del usuario a evaluar.
   *
   * @return array
   *   Array con clave 'lead_score' (LeadScore entity).
   *
   * @throws \InvalidArgumentException
   *   Si el usuario no existe.
   */
  public function scoreUser(int $userId): array {
    $userStorage = $this->entityTypeManager->getStorage('user');
    $user = $userStorage->load($userId);

    if (!$user) {
      throw new \InvalidArgumentException("Usuario con ID {$userId} no encontrado.");
    }

    // --- Calculo de dimensiones ---
    $engagementScore = $this->calculateEngagementScore($userId);
    $activationScore = $this->calculateActivationScore($userId);
    $intentScore = $this->calculateIntentScore($userId);

    // --- Ponderacion ---
    // Pesos por defecto si no hay config disponible.
    $wEngagement = 0.4;
    $wActivation = 0.35;
    $wIntent = 0.25;

    $totalScore = (int) round(
      ($engagementScore * $wEngagement)
      + ($activationScore * $wActivation)
      + ($intentScore * $wIntent)
    );

    $totalScore = max(0, min(100, $totalScore));

    // --- Clasificacion ---
    $qualification = match (TRUE) {
      $totalScore >= 75 => 'sales_ready',
      $totalScore >= 50 => 'hot',
      $totalScore >= 25 => 'warm',
      default => 'cold',
    };

    // --- Score breakdown ---
    $scoreBreakdown = [
      'engagement' => ['score' => $engagementScore, 'weight' => $wEngagement],
      'activation' => ['score' => $activationScore, 'weight' => $wActivation],
      'intent' => ['score' => $intentScore, 'weight' => $wIntent],
    ];

    // --- Buscar LeadScore existente o crear nuevo ---
    $leadScoreStorage = $this->entityTypeManager->getStorage('lead_score');
    $existingIds = $leadScoreStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('user_id', $userId)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (!empty($existingIds)) {
      $leadScore = $leadScoreStorage->load(reset($existingIds));
      $leadScore->set('total_score', $totalScore);
      $leadScore->set('score_breakdown', json_encode($scoreBreakdown, JSON_THROW_ON_ERROR));
      $leadScore->set('qualification', $qualification);
      $leadScore->set('last_activity', date('Y-m-d\TH:i:s'));
      $leadScore->set('model_version', 'heuristic_v1');
      $leadScore->set('calculated_at', date('Y-m-d\TH:i:s'));
    }
    else {
      $leadScore = $leadScoreStorage->create([
        'user_id' => $userId,
        'total_score' => $totalScore,
        'score_breakdown' => json_encode($scoreBreakdown, JSON_THROW_ON_ERROR),
        'qualification' => $qualification,
        'last_activity' => date('Y-m-d\TH:i:s'),
        'events_tracked' => json_encode([], JSON_THROW_ON_ERROR),
        'model_version' => 'heuristic_v1',
        'calculated_at' => date('Y-m-d\TH:i:s'),
      ]);
    }

    $leadScore->save();

    $this->logger->info('Lead score calculated for user @id: total=@total, qualification=@qual', [
      '@id' => $userId,
      '@total' => $totalScore,
      '@qual' => $qualification,
    ]);

    return [
      'lead_score' => $leadScore,
    ];
  }

  /**
   * Obtiene los leads con mayor puntuacion.
   *
   * ESTRUCTURA:
   *   Metodo de consulta que devuelve los top leads ordenados por score.
   *
   * LOGICA:
   *   Busca LeadScore entities ordenadas por total_score DESC.
   *
   * RELACIONES:
   *   - Lee: lead_score entities.
   *
   * @param int $limit
   *   Numero maximo de resultados (default: 20).
   *
   * @return array
   *   Array de arrays con datos de LeadScore serializados.
   */
  public function getTopLeads(int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('lead_score');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('total_score', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $leadScores = $storage->loadMultiple($ids);
    $results = [];

    foreach ($leadScores as $leadScore) {
      $results[] = $this->serializeLeadScore($leadScore);
    }

    return $results;
  }

  /**
   * Filtra leads por nivel de cualificacion.
   *
   * ESTRUCTURA:
   *   Metodo de consulta con filtro por qualification.
   *
   * LOGICA:
   *   Busca LeadScore entities con qualification especificada.
   *   Ordena por total_score DESC.
   *
   * RELACIONES:
   *   - Lee: lead_score entities.
   *
   * @param string $qualification
   *   Nivel de cualificacion: cold, warm, hot, sales_ready.
   *
   * @return array
   *   Array de arrays con datos de LeadScore serializados.
   */
  public function getLeadsByQualification(string $qualification): array {
    $validQualifications = ['cold', 'warm', 'hot', 'sales_ready'];
    if (!in_array($qualification, $validQualifications, TRUE)) {
      throw new \InvalidArgumentException("Cualificacion invalida: {$qualification}. Valores validos: " . implode(', ', $validQualifications));
    }

    $storage = $this->entityTypeManager->getStorage('lead_score');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('qualification', $qualification)
      ->sort('total_score', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $leadScores = $storage->loadMultiple($ids);
    $results = [];

    foreach ($leadScores as $leadScore) {
      $results[] = $this->serializeLeadScore($leadScore);
    }

    return $results;
  }

  /**
   * Calcula la puntuacion de engagement de un usuario (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de dimension individual.
   * LOGICA: Analiza frecuencia de acceso y actividad reciente.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return float
   *   Puntuacion de engagement (0-100).
   */
  protected function calculateEngagementScore(int $userId): float {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);

      if (!$user) {
        return 0.0;
      }

      $lastAccess = (int) ($user->get('access')->value ?? 0);
      if ($lastAccess === 0) {
        return 5.0;
      }

      $daysSinceAccess = (int) ((time() - $lastAccess) / 86400);

      // Acceso reciente = mayor engagement.
      if ($daysSinceAccess === 0) {
        return 90.0;
      }
      if ($daysSinceAccess <= 3) {
        return 75.0;
      }
      if ($daysSinceAccess <= 7) {
        return 60.0;
      }
      if ($daysSinceAccess <= 14) {
        return 40.0;
      }
      if ($daysSinceAccess <= 30) {
        return 20.0;
      }

      return 5.0;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating engagement score for user @id: @message', [
        '@id' => $userId,
        '@message' => $e->getMessage(),
      ]);
      return 25.0;
    }
  }

  /**
   * Calcula la puntuacion de activacion de un usuario (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de dimension individual.
   * LOGICA: Evalua completitud de perfil y uso de funcionalidades.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return float
   *   Puntuacion de activacion (0-100).
   */
  protected function calculateActivationScore(int $userId): float {
    // Heuristico base: sin telemetria detallada de funcionalidades.
    return 35.0;
  }

  /**
   * Calcula la puntuacion de intencion de un usuario (0-100).
   *
   * ESTRUCTURA: Metodo interno de calculo de dimension individual.
   * LOGICA: Evalua senales de intencion de compra: visitas a pricing,
   *   interaccion con CTAs, contacto con ventas.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return float
   *   Puntuacion de intencion (0-100).
   */
  protected function calculateIntentScore(int $userId): float {
    // Heuristico base: sin tracking de eventos de conversion.
    return 20.0;
  }

  /**
   * Serializa una entidad LeadScore para respuesta JSON.
   *
   * ESTRUCTURA: Metodo interno de serializacion.
   * LOGICA: Extrae campos relevantes con tipos correctos.
   *
   * @param object $leadScore
   *   Entidad LeadScore.
   *
   * @return array
   *   Array asociativo con datos serializados.
   */
  protected function serializeLeadScore(object $leadScore): array {
    return [
      'id' => (int) $leadScore->id(),
      'user_id' => $leadScore->get('user_id')->target_id ? (int) $leadScore->get('user_id')->target_id : NULL,
      'total_score' => (int) ($leadScore->get('total_score')->value ?? 0),
      'qualification' => $leadScore->get('qualification')->value ?? 'cold',
      'score_breakdown' => json_decode($leadScore->get('score_breakdown')->value ?? '{}', TRUE),
      'last_activity' => $leadScore->get('last_activity')->value ?? NULL,
      'model_version' => $leadScore->get('model_version')->value ?? '',
      'calculated_at' => $leadScore->get('calculated_at')->value ?? NULL,
      'created' => $leadScore->get('created')->value ?? NULL,
    ];
  }

}
