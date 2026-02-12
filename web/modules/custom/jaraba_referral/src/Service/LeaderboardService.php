<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de leaderboard y gamificación del programa de referidos.
 *
 * ESTRUCTURA:
 * Servicio que genera rankings de usuarios referidores, calcula niveles
 * de embajador basados en el número de referidos exitosos, y proporciona
 * estadísticas agregadas del leaderboard por tenant.
 *
 * LÓGICA:
 * El ranking se calcula contando los referidos confirmados y recompensados
 * de cada usuario dentro de un tenant. Los niveles de embajador son:
 * - Bronce: 0-4 referidos
 * - Plata: 5-14 referidos
 * - Oro: 15-29 referidos
 * - Platino: 30-49 referidos
 * - Diamante: 50+ referidos
 *
 * RELACIONES:
 * - LeaderboardService -> EntityTypeManager (dependencia)
 * - LeaderboardService -> ReferralCode entity (consulta contadores)
 * - LeaderboardService -> Referral entity (consulta estados)
 * - LeaderboardService <- ReferralApiController (consumido por)
 */
class LeaderboardService {

  /**
   * Definición de niveles de embajador con umbrales de referidos.
   */
  protected const AMBASSADOR_LEVELS = [
    ['name' => 'Diamante', 'min' => 50],
    ['name' => 'Platino', 'min' => 30],
    ['name' => 'Oro', 'min' => 15],
    ['name' => 'Plata', 'min' => 5],
    ['name' => 'Bronce', 'min' => 0],
  ];

  /**
   * Constructor del servicio de leaderboard.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones de leaderboard.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el leaderboard de referidos para un tenant.
   *
   * ESTRUCTURA: Método público principal del leaderboard.
   *
   * LÓGICA: Consulta todos los códigos de referido del tenant, agrupa
   *   por usuario y ordena por total_conversions descendente. Devuelve
   *   los top N usuarios con sus métricas y nivel de embajador.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param int $tenantId
   *   ID del tenant para filtrar el leaderboard.
   * @param int $limit
   *   Número máximo de usuarios en el ranking (default 20).
   *
   * @return array
   *   Array de posiciones del ranking, cada una con:
   *   - 'rank' (int): Posición en el ranking.
   *   - 'user_id' (int): ID del usuario.
   *   - 'user_name' (string): Nombre del usuario.
   *   - 'total_referrals' (int): Total de conversiones.
   *   - 'total_revenue' (float): Revenue total generado.
   *   - 'level' (string): Nivel de embajador.
   */
  public function getLeaderboard(int $tenantId, int $limit = 20): array {
    try {
      $codeStorage = $this->entityTypeManager->getStorage('referral_code');
      $ids = $codeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('is_active', TRUE)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $codes = $codeStorage->loadMultiple($ids);

      // Agrupar métricas por usuario.
      $userMetrics = [];
      foreach ($codes as $code) {
        $userId = (int) $code->get('user_id')->target_id;
        if (!isset($userMetrics[$userId])) {
          $userMetrics[$userId] = [
            'user_id' => $userId,
            'user_entity' => $code->get('user_id')->entity,
            'total_referrals' => 0,
            'total_revenue' => 0.0,
          ];
        }
        $userMetrics[$userId]['total_referrals'] += (int) ($code->get('total_conversions')->value ?? 0);
        $userMetrics[$userId]['total_revenue'] += (float) ($code->get('total_revenue')->value ?? 0);
      }

      // Ordenar por total_referrals descendente.
      usort($userMetrics, function (array $a, array $b): int {
        return $b['total_referrals'] <=> $a['total_referrals'];
      });

      // Limitar resultados y formatear.
      $leaderboard = [];
      $rank = 1;
      foreach (array_slice($userMetrics, 0, $limit) as $metrics) {
        $userName = $metrics['user_entity'] ? $metrics['user_entity']->getDisplayName() : '-';
        $leaderboard[] = [
          'rank' => $rank,
          'user_id' => $metrics['user_id'],
          'user_name' => $userName,
          'total_referrals' => $metrics['total_referrals'],
          'total_revenue' => round($metrics['total_revenue'], 2),
          'level' => $this->calculateLevel($metrics['total_referrals']),
        ];
        $rank++;
      }

      return $leaderboard;
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando leaderboard para tenant #@id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene la posición de un usuario en el ranking del tenant.
   *
   * ESTRUCTURA: Método público de consulta de posición individual.
   *
   * LÓGICA: Genera el leaderboard completo (sin límite) y busca la
   *   posición del usuario especificado. Si no aparece, devuelve rank 0.
   *
   * RELACIONES: Consume getLeaderboard() internamente.
   *
   * @param int $userId
   *   ID del usuario para buscar en el ranking.
   * @param int $tenantId
   *   ID del tenant para filtrar el ranking.
   *
   * @return array
   *   Array con 'rank' (int), 'total_referrals' (int), 'total_revenue' (float)
   *   y 'level' (string).
   */
  public function getUserRank(int $userId, int $tenantId): array {
    $leaderboard = $this->getLeaderboard($tenantId, 1000);

    foreach ($leaderboard as $entry) {
      if ($entry['user_id'] === $userId) {
        return [
          'rank' => $entry['rank'],
          'total_referrals' => $entry['total_referrals'],
          'total_revenue' => $entry['total_revenue'],
          'level' => $entry['level'],
        ];
      }
    }

    return [
      'rank' => 0,
      'total_referrals' => 0,
      'total_revenue' => 0.0,
      'level' => $this->calculateLevel(0),
    ];
  }

  /**
   * Obtiene el nivel de embajador de un usuario dentro de un tenant.
   *
   * ESTRUCTURA: Método público de consulta de nivel de gamificación.
   *
   * LÓGICA: Cuenta los referidos exitosos (conversiones) del usuario
   *   en el tenant y devuelve el nombre del nivel correspondiente
   *   según los umbrales definidos en AMBASSADOR_LEVELS.
   *
   * RELACIONES: Consume ReferralCode storage.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return string
   *   Nombre del nivel de embajador (Bronce, Plata, Oro, Platino, Diamante).
   */
  public function getAmbassadorLevel(int $userId, int $tenantId): string {
    try {
      $codeStorage = $this->entityTypeManager->getStorage('referral_code');
      $ids = $codeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($ids)) {
        return $this->calculateLevel(0);
      }

      $codes = $codeStorage->loadMultiple($ids);
      $totalConversions = 0;

      foreach ($codes as $code) {
        $totalConversions += (int) ($code->get('total_conversions')->value ?? 0);
      }

      return $this->calculateLevel($totalConversions);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo nivel de embajador para usuario #@uid en tenant #@tid: @error', [
        '@uid' => $userId,
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return $this->calculateLevel(0);
    }
  }

  /**
   * Obtiene estadísticas generales del leaderboard de un tenant.
   *
   * ESTRUCTURA: Método público de métricas agregadas.
   *
   * LÓGICA: Calcula métricas globales del programa: total de participantes,
   *   total de referidos, revenue acumulado, promedio de referidos por usuario
   *   y distribución por niveles de embajador.
   *
   * RELACIONES: Consume getLeaderboard() internamente.
   *
   * @param int $tenantId
   *   ID del tenant para calcular estadísticas.
   *
   * @return array
   *   Array con:
   *   - 'total_participants' (int): Número de usuarios con códigos.
   *   - 'total_referrals' (int): Suma total de referidos.
   *   - 'total_revenue' (float): Revenue total acumulado.
   *   - 'avg_referrals_per_user' (float): Promedio de referidos por usuario.
   *   - 'level_distribution' (array): Distribución de usuarios por nivel.
   */
  public function getLeaderboardStats(int $tenantId): array {
    $leaderboard = $this->getLeaderboard($tenantId, 10000);

    $stats = [
      'total_participants' => count($leaderboard),
      'total_referrals' => 0,
      'total_revenue' => 0.0,
      'avg_referrals_per_user' => 0.0,
      'level_distribution' => [
        'Bronce' => 0,
        'Plata' => 0,
        'Oro' => 0,
        'Platino' => 0,
        'Diamante' => 0,
      ],
    ];

    foreach ($leaderboard as $entry) {
      $stats['total_referrals'] += $entry['total_referrals'];
      $stats['total_revenue'] += $entry['total_revenue'];
      $level = $entry['level'];
      if (isset($stats['level_distribution'][$level])) {
        $stats['level_distribution'][$level]++;
      }
    }

    if ($stats['total_participants'] > 0) {
      $stats['avg_referrals_per_user'] = round(
        $stats['total_referrals'] / $stats['total_participants'],
        1
      );
    }

    $stats['total_revenue'] = round($stats['total_revenue'], 2);

    return $stats;
  }

  /**
   * Calcula el nivel de embajador basado en el número de referidos.
   *
   * ESTRUCTURA: Método protegido auxiliar de cálculo de nivel.
   *
   * LÓGICA: Recorre los niveles de mayor a menor y devuelve el primer
   *   nivel cuyo umbral mínimo es menor o igual al conteo de referidos.
   *
   * @param int $referralCount
   *   Número total de referidos exitosos.
   *
   * @return string
   *   Nombre del nivel de embajador.
   */
  protected function calculateLevel(int $referralCount): string {
    foreach (self::AMBASSADOR_LEVELS as $level) {
      if ($referralCount >= $level['min']) {
        return $level['name'];
      }
    }
    return 'Bronce';
  }

}
