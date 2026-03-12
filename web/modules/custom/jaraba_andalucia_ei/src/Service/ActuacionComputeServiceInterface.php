<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

/**
 * Interface para el servicio de cómputo de indicadores de actuaciones.
 *
 * Sprint 14: Calcula es_persona_atendida y es_persona_insertada
 * a partir de las inscripciones a sesiones del participante.
 */
interface ActuacionComputeServiceInterface {

  /**
   * Recalcula todos los indicadores de cumplimiento de un participante.
   *
   * Consulta InscripcionSesionEi para sumar horas por tipo/fase,
   * evalúa persona atendida e insertada, y actualiza ProgramaParticipanteEi.
   *
   * @param int $participante_id
   *   ID del ProgramaParticipanteEi.
   *
   * @return array<string, mixed>
   *   Array con los indicadores calculados.
   */
  public function recalcularIndicadores(int $participante_id): array;

  /**
   * Recalcula indicadores para todos los participantes de un programa (tenant).
   *
   * @param int|null $tenant_id
   *   ID del tenant, o NULL para todos.
   *
   * @return int
   *   Número de participantes actualizados.
   */
  public function recalcularPrograma(?int $tenant_id = NULL): int;

}
