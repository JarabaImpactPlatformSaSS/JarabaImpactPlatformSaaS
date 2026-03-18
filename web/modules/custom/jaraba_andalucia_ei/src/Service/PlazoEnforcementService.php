<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enforcement de plazos normativos ICV 2025.
 *
 * Detecta y alerta sobre incumplimientos de plazos definidos en las
 * "Pautas de Gestión Técnica del Programa PIIL Colectivo Vulnerable 2025":
 *
 * - 15 días naturales: subida de recibos de servicio al STO (§5.1.A/B)
 * - 10 días hábiles: solicitud VoBo formación antes de inicio curso (§5.1.B.3)
 * - 2 meses: pago incentivo tras persona atendida (§5.1.C)
 * - 18 meses: duración máxima del programa (§3.1)
 *
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class PlazoEnforcementService {

  /**
   * Festivos nacionales + Andalucía 2026 (para cálculo días hábiles).
   *
   * Fuente: BOE + BOJA calendario laboral 2026.
   */
  private const FESTIVOS_2026 = [
    '2026-01-01', '2026-01-06', '2026-02-28', // Año Nuevo, Reyes, Día Andalucía
    '2026-04-02', '2026-04-03',               // Jueves Santo, Viernes Santo
    '2026-05-01', '2026-08-15',               // Trabajo, Asunción
    '2026-10-12', '2026-11-02',               // Hispanidad, Todos los Santos (lun)
    '2026-12-07', '2026-12-08', '2026-12-25', // Puente Constitución, Inmaculada, Navidad
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TimeInterface $time,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todas las alertas de plazos para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array<int, array{tipo: string, nivel: string, mensaje: string, plazo: string, dias_restantes: int}>
   */
  public function getAlertasPlazos(int $tenantId): array {
    $alertas = [];
    $now = $this->time->getCurrentTime();

    try {
      $alertas = array_merge(
        $alertas,
        $this->checkRecibosVencidos($tenantId, $now),
        $this->checkVobosPendientes($tenantId, $now),
        $this->checkIncentivosVencidos($tenantId, $now),
        $this->checkProgramaExpiry($tenantId, $now),
      );
    }
    catch (\Throwable $e) {
      $this->logger->error('PlazoEnforcementService error: @msg', ['@msg' => $e->getMessage()]);
    }

    return $alertas;
  }

  /**
   * §5.1.A/B: Recibos de servicio deben subirse al STO en 15 días naturales.
   */
  private function checkRecibosVencidos(int $tenantId, int $now): array {
    $alertas = [];

    if (!$this->entityTypeManager->hasDefinition('actuacion_sto')) {
      return $alertas;
    }

    $storage = $this->entityTypeManager->getStorage('actuacion_sto');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->notExists('recibo_servicio_id')
      ->execute();

    if (!$ids) {
      return $alertas;
    }

    foreach ($storage->loadMultiple($ids) as $actuacion) {
      $fechaStr = $actuacion->get('fecha')->value;
      if (!$fechaStr) {
        continue;
      }

      $fechaActuacion = strtotime(str_replace('T', ' ', $fechaStr));
      $deadline = $fechaActuacion + (15 * 86400);
      $diasRestantes = (int) ceil(($deadline - $now) / 86400);

      if ($diasRestantes <= 0) {
        $alertas[] = [
          'tipo' => 'recibo_vencido',
          'nivel' => AlertasNormativasService::NIVEL_CRITICO,
          'mensaje' => (string) t('Recibo de actuación @id vencido: @dias días fuera de plazo.', [
            '@id' => $actuacion->id(),
            '@dias' => abs($diasRestantes),
          ]),
          'plazo' => '15_dias_naturales',
          'dias_restantes' => $diasRestantes,
          'entity_id' => (int) $actuacion->id(),
        ];
      }
      elseif ($diasRestantes <= 3) {
        $alertas[] = [
          'tipo' => 'recibo_urgente',
          'nivel' => AlertasNormativasService::NIVEL_ALTO,
          'mensaje' => (string) t('Recibo de actuación @id vence en @dias días.', [
            '@id' => $actuacion->id(),
            '@dias' => $diasRestantes,
          ]),
          'plazo' => '15_dias_naturales',
          'dias_restantes' => $diasRestantes,
          'entity_id' => (int) $actuacion->id(),
        ];
      }
    }

    return $alertas;
  }

  /**
   * §5.1.B.3: VoBo formación con 10 días hábiles de antelación.
   */
  private function checkVobosPendientes(int $tenantId, int $now): array {
    $alertas = [];

    if (!$this->entityTypeManager->hasDefinition('accion_formativa_ei')) {
      return $alertas;
    }

    $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('estado', ['borrador', 'pendiente_vobo'], 'IN')
      ->execute();

    if (!$ids) {
      return $alertas;
    }

    foreach ($storage->loadMultiple($ids) as $accion) {
      $estado = $accion->get('estado')->value;

      // Solo alertar acciones en borrador que aún no se han enviado.
      if ($estado !== 'borrador') {
        continue;
      }

      // Buscar sesiones vinculadas para conocer la fecha de inicio.
      $sesionIds = $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('accion_formativa_id', $accion->id())
        ->sort('fecha', 'ASC')
        ->range(0, 1)
        ->execute();

      if (empty($sesionIds)) {
        continue;
      }

      $primeraSesion = $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->load(reset($sesionIds));
      if (!$primeraSesion) {
        continue;
      }

      $fechaInicioStr = $primeraSesion->get('fecha')->value;
      if (!$fechaInicioStr) {
        continue;
      }

      $fechaInicio = new \DateTimeImmutable(str_replace('T', ' ', $fechaInicioStr));
      $deadlineEnvio = $this->restarDiasHabiles(10, $fechaInicio);
      $nowDate = new \DateTimeImmutable('@' . $now);

      if ($nowDate > $deadlineEnvio) {
        $alertas[] = [
          'tipo' => 'vobo_vencido',
          'nivel' => AlertasNormativasService::NIVEL_CRITICO,
          'mensaje' => (string) t('Acción formativa "@titulo" sin VoBo: plazo de 10 días hábiles superado.', [
            '@titulo' => $accion->label() ?? $accion->id(),
          ]),
          'plazo' => '10_dias_habiles',
          'dias_restantes' => 0,
          'entity_id' => (int) $accion->id(),
        ];
      }
      elseif ($nowDate > $this->restarDiasHabiles(15, $fechaInicio)) {
        $alertas[] = [
          'tipo' => 'vobo_urgente',
          'nivel' => AlertasNormativasService::NIVEL_ALTO,
          'mensaje' => (string) t('Acción formativa "@titulo": enviar VoBo antes de @fecha.', [
            '@titulo' => $accion->label() ?? $accion->id(),
            '@fecha' => $deadlineEnvio->format('d/m/Y'),
          ]),
          'plazo' => '10_dias_habiles',
          'dias_restantes' => (int) $nowDate->diff($deadlineEnvio)->days,
          'entity_id' => (int) $accion->id(),
        ];
      }
    }

    return $alertas;
  }

  /**
   * §5.1.C: Incentivo a la participación máximo 2 meses tras persona atendida.
   */
  private function checkIncentivosVencidos(int $tenantId, int $now): array {
    $alertas = [];

    if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
      return $alertas;
    }

    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('es_persona_atendida', TRUE)
      ->condition('incentivo_recibido', FALSE)
      ->condition('incentivo_renuncia', FALSE)
      ->execute();

    if (!$ids) {
      return $alertas;
    }

    foreach ($storage->loadMultiple($ids) as $participante) {
      $changedTimestamp = (int) ($participante->get('changed')->value ?? $now);
      $deadline = $changedTimestamp + (60 * 86400); // 2 meses ≈ 60 días
      $diasRestantes = (int) ceil(($deadline - $now) / 86400);

      if ($diasRestantes <= 0) {
        $alertas[] = [
          'tipo' => 'incentivo_vencido',
          'nivel' => AlertasNormativasService::NIVEL_CRITICO,
          'mensaje' => (string) t('Incentivo participante @nombre (@dni): plazo de 2 meses superado.', [
            '@nombre' => $participante->label() ?? '',
            '@dni' => $participante->get('dni_nie')->value ?? '',
          ]),
          'plazo' => '2_meses_incentivo',
          'dias_restantes' => $diasRestantes,
          'entity_id' => (int) $participante->id(),
        ];
      }
      elseif ($diasRestantes <= 15) {
        $alertas[] = [
          'tipo' => 'incentivo_urgente',
          'nivel' => AlertasNormativasService::NIVEL_ALTO,
          'mensaje' => (string) t('Incentivo participante @nombre: quedan @dias días para pagar.', [
            '@nombre' => $participante->label() ?? '',
            '@dias' => $diasRestantes,
          ]),
          'plazo' => '2_meses_incentivo',
          'dias_restantes' => $diasRestantes,
          'entity_id' => (int) $participante->id(),
        ];
      }
    }

    return $alertas;
  }

  /**
   * §3.1: Programa tiene duración máxima de 18 meses.
   */
  private function checkProgramaExpiry(int $tenantId, int $now): array {
    $alertas = [];

    // Duración del programa: 18 meses desde fecha de inicio.
    // La fecha de inicio se toma del campo más antiguo de actuación registrada.
    if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
      return $alertas;
    }

    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'ASC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return $alertas;
    }

    $primerParticipante = $storage->load(reset($ids));
    if (!$primerParticipante) {
      return $alertas;
    }

    $created = (int) ($primerParticipante->get('created')->value ?? $now);
    $finPrograma = $created + (18 * 30 * 86400); // 18 meses ≈ 540 días
    $diasRestantes = (int) ceil(($finPrograma - $now) / 86400);

    if ($diasRestantes <= 0) {
      $alertas[] = [
        'tipo' => 'programa_expirado',
        'nivel' => AlertasNormativasService::NIVEL_CRITICO,
        'mensaje' => (string) t('El programa PIIL ha superado los 18 meses de duración.'),
        'plazo' => '18_meses_programa',
        'dias_restantes' => $diasRestantes,
        'entity_id' => 0,
      ];
    }
    elseif ($diasRestantes <= 30) {
      $alertas[] = [
        'tipo' => 'programa_expira_pronto',
        'nivel' => AlertasNormativasService::NIVEL_ALTO,
        'mensaje' => (string) t('El programa PIIL termina en @dias días.', ['@dias' => $diasRestantes]),
        'plazo' => '18_meses_programa',
        'dias_restantes' => $diasRestantes,
        'entity_id' => 0,
      ];
    }

    return $alertas;
  }

  /**
   * Resta N días hábiles a una fecha (excluye fines de semana y festivos).
   *
   * §5.1.B.3 especifica "10 días hábiles" para solicitud VoBo.
   *
   * @param int $diasHabiles
   *   Número de días hábiles a restar.
   * @param \DateTimeImmutable $desde
   *   Fecha de referencia.
   *
   * @return \DateTimeImmutable
   *   Fecha resultante tras restar los días hábiles.
   */
  public function restarDiasHabiles(int $diasHabiles, \DateTimeImmutable $desde): \DateTimeImmutable {
    $fecha = $desde;
    $restados = 0;

    while ($restados < $diasHabiles) {
      $fecha = $fecha->modify('-1 day');

      // Excluir sábado (6) y domingo (7).
      $diaSemana = (int) $fecha->format('N');
      if ($diaSemana >= 6) {
        continue;
      }

      // Excluir festivos.
      $fechaStr = $fecha->format('Y-m-d');
      if (in_array($fechaStr, self::FESTIVOS_2026, TRUE)) {
        continue;
      }

      $restados++;
    }

    return $fecha;
  }

}
