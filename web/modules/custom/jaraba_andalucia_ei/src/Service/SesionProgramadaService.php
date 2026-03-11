<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dominio para sesiones programadas.
 *
 * Gestiona la calendarización de sesiones, expansión de recurrencia,
 * y consultas relacionadas con la programación del programa formativo.
 */
class SesionProgramadaService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene sesiones programadas de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string|null $estado
   *   Filtrar por estado.
   * @param string|null $desde
   *   Fecha mínima (Y-m-d).
   * @param string|null $hasta
   *   Fecha máxima (Y-m-d).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones ordenadas por fecha ASC.
   */
  public function getSesionesPorTenant(int $tenantId, ?string $estado = NULL, ?string $desde = NULL, ?string $hasta = NULL): array {
    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('fecha', 'ASC');

    if ($estado !== NULL) {
      $query->condition('estado', $estado);
    }
    if ($desde !== NULL) {
      $query->condition('fecha', $desde, '>=');
    }
    if ($hasta !== NULL) {
      $query->condition('fecha', $hasta, '<=');
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Obtiene sesiones futuras de una acción formativa.
   *
   * @param int $accionFormativaId
   *   ID de la acción formativa.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones futuras.
   */
  public function getSesionesFuturas(int $accionFormativaId): array {
    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
    $hoy = date('Y-m-d');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('accion_formativa_id', $accionFormativaId)
      ->condition('fecha', $hoy, '>=')
      ->condition('estado', ['cancelada', 'aplazada'], 'NOT IN')
      ->sort('fecha', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Expande la recurrencia de una sesión padre.
   *
   * Crea sesiones hijas según el patrón de recurrencia JSON.
   * Patrón simplificado: {frequency: weekly|biweekly|monthly, count: N}
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesionPadre
   *   La sesión padre con recurrencia.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones hijas generadas.
   */
  public function expandirRecurrencia(SesionProgramadaEiInterface $sesionPadre): array {
    if (!$sesionPadre->isRecurrente()) {
      return [];
    }

    $patronJson = $sesionPadre->get('recurrencia_patron')->value;
    if (empty($patronJson)) {
      return [];
    }

    try {
      $patron = json_decode($patronJson, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      $this->logger->warning('Patrón de recurrencia inválido para sesión @id.', [
        '@id' => $sesionPadre->id(),
      ]);
      return [];
    }

    $frequency = $patron['frequency'] ?? 'weekly';
    $count = min((int) ($patron['count'] ?? 4), 52);

    $intervalMap = [
      'weekly' => '+1 week',
      'biweekly' => '+2 weeks',
      'monthly' => '+1 month',
    ];
    $interval = $intervalMap[$frequency] ?? '+1 week';

    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
    $fechaBase = $sesionPadre->get('fecha')->value;
    $sesionesGeneradas = [];

    for ($i = 1; $i <= $count; $i++) {
      try {
        // Calcular la nueva fecha sumando el intervalo i veces.
        $nuevaFecha = new \DateTimeImmutable($fechaBase);
        for ($j = 0; $j < $i; $j++) {
          $nuevaFecha = $nuevaFecha->modify($interval);
        }

        // Verificar que no exista ya una sesión hija para esta fecha.
        $existentes = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('sesion_padre_id', $sesionPadre->id())
          ->condition('fecha', $nuevaFecha->format('Y-m-d'))
          ->execute();

        if (!empty($existentes)) {
          continue;
        }

        /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesionHija */
        $sesionHija = $storage->create([
          'titulo' => $sesionPadre->getTitulo() . ' (' . ($i + 1) . ')',
          'tenant_id' => $sesionPadre->get('tenant_id')->target_id,
          'uid' => $sesionPadre->getOwnerId(),
          'accion_formativa_id' => $sesionPadre->get('accion_formativa_id')->target_id,
          'tipo_sesion' => $sesionPadre->getTipoSesion(),
          'fase_programa' => $sesionPadre->getFasePrograma(),
          'fecha' => $nuevaFecha->format('Y-m-d'),
          'hora_inicio' => $sesionPadre->getHoraInicio(),
          'hora_fin' => $sesionPadre->getHoraFin(),
          'modalidad' => $sesionPadre->getModalidad(),
          'lugar_descripcion' => $sesionPadre->get('lugar_descripcion')->value,
          'lugar_url' => $sesionPadre->get('lugar_url')->value,
          'facilitador_id' => $sesionPadre->get('facilitador_id')->target_id,
          'facilitador_nombre' => $sesionPadre->get('facilitador_nombre')->value,
          'max_plazas' => $sesionPadre->getMaxPlazas(),
          'estado' => 'programada',
          'es_recurrente' => FALSE,
          'sesion_padre_id' => $sesionPadre->id(),
        ]);

        $sesionHija->save();
        $sesionesGeneradas[] = $sesionHija;
      }
      catch (\Throwable) {
        continue;
      }
    }

    $this->logger->info('Expansión de recurrencia: sesión @id generó @count sesiones hijas.', [
      '@id' => $sesionPadre->id(),
      '@count' => count($sesionesGeneradas),
    ]);

    return $sesionesGeneradas;
  }

  /**
   * Obtiene sesiones para una participante (su carril + comunes).
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $carril
   *   Carril de la participante.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones aplicables, futuras, con plazas.
   */
  public function getSesionesParaParticipante(int $tenantId, string $carril): array {
    $sesiones = $this->getSesionesPorTenant($tenantId, 'programada', date('Y-m-d'));
    // Filtrar por acción formativa aplicable al carril.
    return array_filter($sesiones, function (SesionProgramadaEiInterface $sesion) use ($carril) {
      // Sesiones con plazas disponibles.
      if (!$sesion->hayPlazasDisponibles()) {
        return FALSE;
      }
      // Si tiene acción formativa asociada, verificar carril.
      $accionId = $sesion->get('accion_formativa_id')->target_id;
      if ($accionId) {
        try {
          $accion = $this->entityTypeManager->getStorage('accion_formativa_ei')->load($accionId);
          if ($accion) {
            $carrilAccion = $accion->get('carril')->value ?? 'comun';
            return $carrilAccion === $carril || $carrilAccion === 'comun';
          }
        }
        catch (\Throwable) {
          // PRESAVE-RESILIENCE-001.
        }
      }
      // Sin acción formativa asociada → disponible para todos.
      return TRUE;
    });
  }

}
