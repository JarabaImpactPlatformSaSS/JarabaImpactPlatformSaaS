<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alertas normativas para coordinadores.
 *
 * Detecta participantes en riesgo de incumplimiento normativo:
 * - Sin Acuerdo de Participacion firmado despues de la primera semana
 * - Sin indicadores FSE+ de entrada después de la segunda semana
 * - Sin carril asignado después de 3 semanas
 * - Horas insuficientes próximos a fin de programa
 * - Formación sin VoBo SAE
 *
 * TENANT-001: Queries filtradas por tenant.
 */
class AlertasNormativasService {

  /**
   * Niveles de alerta.
   */
  public const NIVEL_CRITICO = 'critico';
  public const NIVEL_ALTO = 'alto';
  public const NIVEL_MEDIO = 'medio';
  public const NIVEL_INFO = 'info';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todas las alertas activas para un tenant.
   *
   * TENANT-001: tenantId es obligatorio. Sin él devuelve array vacío
   * y loguea warning para detectar llamadores no adaptados.
   *
   * @param int|null $tenantId
   *   The tenant (group) ID. NULL returns empty array.
   *
   * @return array<int, array{tipo: string, nivel: string, mensaje: string, participante_id: int, accion: string}>
   */
  public function getAlertas(?int $tenantId = NULL): array {
    // TENANT-001: Sin tenant no cargamos datos — previene fuga cross-tenant.
    if (!$tenantId) {
      $this->logger->warning('getAlertas() called without tenantId — returning empty.');
      return [];
    }

    $alertas = [];

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Participantes activos (no en baja).
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'baja', '!=')
        ->condition('tenant_id', $tenantId);

      $ids = $query->execute();
      $participantes = $storage->loadMultiple($ids);

      foreach ($participantes as $participante) {
        $pid = (int) $participante->id();
        $fase = $participante->get('fase_actual')->value ?? 'acogida';
        $semana = (int) ($participante->get('semana_actual')->value ?? 0);
        $nombre = $participante->label() ?? "Participante #$pid";

        // 1. Acuerdo de Participación no firmado después de semana 1.
        if ($semana >= 1 && !((bool) ($participante->get('acuerdo_participacion_firmado')->value ?? FALSE))) {
          $alertas[] = [
            'tipo' => 'acuerdo_participacion_pendiente',
            'nivel' => $semana >= 2 ? self::NIVEL_CRITICO : self::NIVEL_ALTO,
            'mensaje' => sprintf('%s: Acuerdo de Participación sin firmar (semana %d).', $nombre, $semana),
            'participante_id' => $pid,
            'accion' => 'Generar y firmar Acuerdo de Participación inmediatamente.',
          ];
        }

        // 1b. DACI (Aceptación de Compromisos e Información) no firmado después de semana 1.
        if ($semana >= 1 && !((bool) ($participante->get('daci_firmado')->value ?? FALSE))) {
          $alertas[] = [
            'tipo' => 'daci_pendiente',
            'nivel' => $semana >= 2 ? self::NIVEL_CRITICO : self::NIVEL_ALTO,
            'mensaje' => sprintf('%s: DACI (Aceptación de Compromisos) sin firmar (semana %d).', $nombre, $semana),
            'participante_id' => $pid,
            'accion' => 'Generar y firmar DACI inmediatamente.',
          ];
        }

        // 2. FSE+ entrada no completados después de semana 2.
        if ($semana >= 2 && !((bool) ($participante->get('fse_entrada_completado')->value ?? FALSE))) {
          $alertas[] = [
            'tipo' => 'fse_entrada_pendiente',
            'nivel' => $semana >= 4 ? self::NIVEL_CRITICO : self::NIVEL_ALTO,
            'mensaje' => sprintf('%s: Indicadores FSE+ de entrada pendientes (semana %d).', $nombre, $semana),
            'participante_id' => $pid,
            'accion' => 'Completar recogida de indicadores FSE+ de entrada.',
          ];
        }

        // 3. Sin carril después de semana 3 (debería estar en diagnóstico o posterior).
        if ($semana >= 3 && empty($participante->get('carril')->value) && $fase !== 'acogida') {
          $alertas[] = [
            'tipo' => 'carril_pendiente',
            'nivel' => self::NIVEL_ALTO,
            'mensaje' => sprintf('%s: Sin carril asignado en fase %s (semana %d).', $nombre, $fase, $semana),
            'participante_id' => $pid,
            'accion' => 'Completar diagnóstico DIME y asignar carril.',
          ];
        }

        // 4. Horas insuficientes después de semana 30.
        if ($semana >= 30 && $fase === 'atencion') {
          $horasOrientacion = (float) ($participante->get('horas_orientacion_ind')->value ?? 0)
            + (float) ($participante->get('horas_orientacion_grup')->value ?? 0)
            + (float) ($participante->get('horas_mentoria_ia')->value ?? 0)
            + (float) ($participante->get('horas_mentoria_humana')->value ?? 0);
          $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);

          if ($horasOrientacion < 10) {
            $alertas[] = [
              'tipo' => 'horas_orientacion_insuficientes',
              'nivel' => $semana >= 36 ? self::NIVEL_CRITICO : self::NIVEL_MEDIO,
              'mensaje' => sprintf('%s: %.1fh orientación de 10h requeridas (semana %d).', $nombre, $horasOrientacion, $semana),
              'participante_id' => $pid,
              'accion' => 'Programar sesiones de orientación adicionales.',
            ];
          }

          if ($horasFormacion < 50) {
            $alertas[] = [
              'tipo' => 'horas_formacion_insuficientes',
              'nivel' => $semana >= 36 ? self::NIVEL_CRITICO : self::NIVEL_MEDIO,
              'mensaje' => sprintf('%s: %.1fh formación de 50h requeridas (semana %d).', $nombre, $horasFormacion, $semana),
              'participante_id' => $pid,
              'accion' => 'Inscribir en acciones formativas adicionales.',
            ];
          }
        }
      }

      // 5. Formación sin VoBo SAE (actuaciones STO legacy).
      // TENANT-001: $tenantId ya garantizado non-null por el guard clause inicial.
      if ($this->entityTypeManager->hasDefinition('actuacion_sto')) {
        $actQuery = $this->entityTypeManager->getStorage('actuacion_sto')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tipo_actuacion', 'formacion')
          ->condition('vobo_sae_status', 'pendiente')
          ->condition('tenant_id', $tenantId);

        $pendientes = (int) $actQuery->count()->execute();
        if ($pendientes > 0) {
          $alertas[] = [
            'tipo' => 'vobo_sae_pendiente',
            'nivel' => self::NIVEL_ALTO,
            'mensaje' => sprintf('%d actuaciones de formación pendientes de VoBo SAE.', $pendientes),
            'participante_id' => 0,
            'accion' => 'Gestionar VoBo SAE para las acciones formativas.',
          ];
        }
      }

      // 6. Sprint 13: VoBo SAE timeout en acciones formativas.
      if ($this->entityTypeManager->hasDefinition('accion_formativa_ei')) {
        $accionStorage = $this->entityTypeManager->getStorage('accion_formativa_ei');
        $voboQuery = $accionStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('estado', ['pendiente_vobo', 'vobo_enviado'], 'IN')
          ->condition('tenant_id', $tenantId);
        $voboIds = $voboQuery->execute();

        if (!empty($voboIds)) {
          $now = time();
          foreach ($accionStorage->loadMultiple($voboIds) as $accion) {
            $changed = (int) ($accion->get('changed')->value ?? $now);
            $diasPendiente = (int) round(($now - $changed) / 86400);

            if ($diasPendiente >= 30) {
              $alertas[] = [
                'tipo' => 'vobo_sae_timeout_critico',
                'nivel' => self::NIVEL_CRITICO,
                'mensaje' => sprintf('Acción "%s": VoBo SAE pendiente hace %d días (límite: 30).', $accion->getTitulo(), $diasPendiente),
                'participante_id' => 0,
                'accion' => 'Resolver VoBo SAE inmediatamente o contactar con SAE.',
              ];
            }
            elseif ($diasPendiente >= 15) {
              $alertas[] = [
                'tipo' => 'vobo_sae_timeout',
                'nivel' => self::NIVEL_ALTO,
                'mensaje' => sprintf('Acción "%s": VoBo SAE pendiente hace %d días (alerta: 15).', $accion->getTitulo(), $diasPendiente),
                'participante_id' => 0,
                'accion' => 'Hacer seguimiento del VoBo SAE con el organismo correspondiente.',
              ];
            }
          }
        }
      }

      // 7. Sprint 13: Indicadores ESF+ de salida pendientes en seguimiento.
      foreach ($participantes as $participante) {
        $pid = (int) $participante->id();
        $fase = $participante->get('fase_actual')->value ?? 'acogida';
        $nombre = $participante->label() ?? "Participante #$pid";

        if (in_array($fase, ['insercion', 'seguimiento'], TRUE)) {
          if (!((bool) ($participante->get('fse_salida_completado')->value ?? FALSE))) {
            $changed = (int) ($participante->get('changed')->value ?? 0);
            $diasEnFase = $changed > 0 ? (int) round((time() - $changed) / 86400) : 0;

            if ($diasEnFase >= 30) {
              $alertas[] = [
                'tipo' => 'fse_salida_pendiente',
                'nivel' => $diasEnFase >= 60 ? self::NIVEL_CRITICO : self::NIVEL_ALTO,
                'mensaje' => sprintf('%s: Indicadores FSE+ de salida pendientes en fase %s (%d días).', $nombre, $fase, $diasEnFase),
                'participante_id' => $pid,
                'accion' => 'Completar recogida de indicadores FSE+ de salida para justificación ante financiador.',
              ];
            }
          }
        }
      }

      // Ordenar por nivel de gravedad.
      usort($alertas, function ($a, $b) {
        $orden = [self::NIVEL_CRITICO => 0, self::NIVEL_ALTO => 1, self::NIVEL_MEDIO => 2, self::NIVEL_INFO => 3];
        return ($orden[$a['nivel']] ?? 9) <=> ($orden[$b['nivel']] ?? 9);
      });
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo alertas normativas: @msg', ['@msg' => $e->getMessage()]);
    }

    return $alertas;
  }

  /**
   * Obtiene un resumen de alertas agrupadas por nivel.
   *
   * @return array<string, int>
   */
  public function getResumenAlertas(?int $tenantId = NULL): array {
    $alertas = $this->getAlertas($tenantId);
    $resumen = [
      self::NIVEL_CRITICO => 0,
      self::NIVEL_ALTO => 0,
      self::NIVEL_MEDIO => 0,
      self::NIVEL_INFO => 0,
    ];

    foreach ($alertas as $alerta) {
      $resumen[$alerta['nivel']] = ($resumen[$alerta['nivel']] ?? 0) + 1;
    }

    return $resumen;
  }

}
