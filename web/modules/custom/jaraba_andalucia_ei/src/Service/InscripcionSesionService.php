<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dominio para inscripciones en sesiones del programa.
 *
 * Gestiona el ciclo de inscripción: alta, confirmación de asistencia,
 * generación automática de ActuacionSto al verificar asistencia,
 * y control de plazas.
 */
class InscripcionSesionService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
    private readonly mixed $actuacionStoService = NULL,
  ) {}

  /**
   * Inscribe a un participante en una sesión.
   *
   * @param int $sesionId
   *   ID de la sesión.
   * @param int $participanteId
   *   ID del participante.
   * @param int $uid
   *   ID del usuario que se inscribe.
   * @param int|null $tenantId
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface|null
   *   La inscripción creada, o NULL si ya existe o no hay plazas.
   */
  public function inscribir(int $sesionId, int $participanteId, int $uid, ?int $tenantId = NULL): ?InscripcionSesionEiInterface {
    // Verificar que no existe inscripción previa.
    if ($this->existeInscripcion($sesionId, $participanteId)) {
      $this->logger->notice('Inscripción duplicada rechazada: sesión @sesion, participante @part.', [
        '@sesion' => $sesionId,
        '@part' => $participanteId,
      ]);
      return NULL;
    }

    // Verificar plazas disponibles.
    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface|null $sesion */
    $sesion = $this->entityTypeManager->getStorage('sesion_programada_ei')->load($sesionId);
    if ($sesion === NULL) {
      return NULL;
    }

    if (!$sesion->hayPlazasDisponibles()) {
      $this->logger->notice('Inscripción rechazada por falta de plazas: sesión @sesion.', [
        '@sesion' => $sesionId,
      ]);
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('inscripcion_sesion_ei');
    /** @var \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface $inscripcion */
    $inscripcion = $storage->create([
      'uid' => $uid,
      'tenant_id' => $tenantId,
      'sesion_id' => $sesionId,
      'participante_id' => $participanteId,
      'estado' => 'inscrito',
      'fecha_inscripcion' => date('Y-m-d'),
    ]);
    $inscripcion->save();

    $this->logger->info('Inscripción creada: sesión @sesion, participante @part.', [
      '@sesion' => $sesionId,
      '@part' => $participanteId,
    ]);

    return $inscripcion;
  }

  /**
   * Confirma la asistencia de una inscripción y genera ActuacionSto.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface $inscripcion
   *   La inscripción a confirmar.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface
   *   La inscripción actualizada.
   */
  public function confirmarAsistencia(InscripcionSesionEiInterface $inscripcion): InscripcionSesionEiInterface {
    $inscripcion->set('estado', 'asistio');
    $inscripcion->set('fecha_asistencia', date('Y-m-d'));
    $inscripcion->set('asistencia_verificada', TRUE);

    // Calcular horas computadas desde la sesión.
    $sesionId = $inscripcion->getSesionId();
    if ($sesionId) {
      /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface|null $sesion */
      $sesion = $this->entityTypeManager->getStorage('sesion_programada_ei')->load($sesionId);
      if ($sesion instanceof SesionProgramadaEiInterface) {
        $horas = $sesion->getDuracionHoras();
        $inscripcion->set('horas_computadas', $horas);

        // Generar ActuacionSto automáticamente.
        $actuacionId = $this->generarActuacion($inscripcion, $sesion);
        if ($actuacionId !== NULL) {
          $inscripcion->set('actuacion_sto_id', $actuacionId);
        }
      }
    }

    $inscripcion->save();

    $this->logger->info('Asistencia confirmada: inscripción @id, horas @horas.', [
      '@id' => $inscripcion->id(),
      '@horas' => $inscripcion->getHorasComputadas(),
    ]);

    return $inscripcion;
  }

  /**
   * Genera una ActuacionSto automática desde la inscripción confirmada.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface $inscripcion
   *   La inscripción.
   * @param \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesion
   *   La sesión asociada.
   *
   * @return int|null
   *   ID de la actuación generada, o NULL si el servicio no está disponible.
   */
  private function generarActuacion(InscripcionSesionEiInterface $inscripcion, SesionProgramadaEiInterface $sesion): ?int {
    if ($this->actuacionStoService === NULL) {
      return NULL;
    }

    try {
      // Mapear tipo de sesión a tipo de actuación.
      $tipoActuacion = match ($sesion->getTipoSesion()) {
        'formacion_presencial', 'formacion_online' => 'formacion',
        'orientacion_individual' => 'orientacion_individual',
        'orientacion_grupal' => 'orientacion_grupal',
        'tutoria' => 'tutoria',
        'taller' => 'formacion',
        default => 'formacion',
      };

      $actuacionStorage = $this->entityTypeManager->getStorage('actuacion_sto');
      /** @var \Drupal\jaraba_andalucia_ei\Entity\ActuacionSto $actuacion */
      $actuacion = $actuacionStorage->create([
        'uid' => $inscripcion->getOwnerId(),
        'tenant_id' => $inscripcion->get('tenant_id')->target_id,
        'participante_id' => $inscripcion->getParticipanteId(),
        'tipo_actuacion' => $tipoActuacion,
        'fecha' => $sesion->get('fecha')->value,
        'hora_inicio' => $sesion->getHoraInicio(),
        'hora_fin' => $sesion->getHoraFin(),
        'contenido' => $sesion->getTitulo(),
        'lugar' => $sesion->getModalidad() === 'presencial' ? 'presencial_sede' : 'online_plataforma',
        'orientador_id' => $sesion->get('facilitador_id')->target_id,
      ]);
      $actuacion->save();

      // Incrementar horas del participante via ActuacionStoService.
      try {
        $this->actuacionStoService->incrementarHorasParticipante(
          (int) $inscripcion->getParticipanteId(),
          $sesion->getDuracionHoras(),
          $tipoActuacion
        );
      }
      catch (\Throwable $e) {
        $this->logger->warning('Error incrementando horas: @msg', ['@msg' => $e->getMessage()]);
      }

      return (int) $actuacion->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando ActuacionSto: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Verifica si ya existe una inscripción para una sesión y participante.
   *
   * @param int $sesionId
   *   ID de la sesión.
   * @param int $participanteId
   *   ID del participante.
   *
   * @return bool
   *   TRUE si ya existe inscripción activa.
   */
  public function existeInscripcion(int $sesionId, int $participanteId): bool {
    $storage = $this->entityTypeManager->getStorage('inscripcion_sesion_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('sesion_id', $sesionId)
      ->condition('participante_id', $participanteId)
      ->condition('estado', 'cancelado', '!=')
      ->range(0, 1)
      ->execute();

    return !empty($ids);
  }

  /**
   * Obtiene inscripciones de un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   * @param string|null $estado
   *   Filtrar por estado.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface[]
   *   Inscripciones del participante.
   */
  public function getInscripcionesPorParticipante(int $participanteId, ?string $estado = NULL): array {
    $storage = $this->entityTypeManager->getStorage('inscripcion_sesion_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('participante_id', $participanteId)
      ->sort('created', 'DESC');

    if ($estado !== NULL) {
      $query->condition('estado', $estado);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Cuenta inscripciones activas en una sesión.
   *
   * @param int $sesionId
   *   ID de la sesión.
   *
   * @return int
   *   Número de inscripciones activas (no canceladas).
   */
  public function contarInscripcionesActivas(int $sesionId): int {
    $storage = $this->entityTypeManager->getStorage('inscripcion_sesion_ei');
    return (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('sesion_id', $sesionId)
      ->condition('estado', 'cancelado', '!=')
      ->count()
      ->execute();
  }

  /**
   * Cancela una inscripción.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface $inscripcion
   *   La inscripción a cancelar.
   * @param string $motivo
   *   Motivo de la cancelación.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface
   *   La inscripción actualizada.
   */
  public function cancelar(InscripcionSesionEiInterface $inscripcion, string $motivo = ''): InscripcionSesionEiInterface {
    $inscripcion->set('estado', 'cancelado');
    if ($motivo !== '') {
      $inscripcion->set('motivo_cancelacion', $motivo);
    }
    $inscripcion->save();

    $this->logger->info('Inscripción @id cancelada. Motivo: @motivo.', [
      '@id' => $inscripcion->id(),
      '@motivo' => $motivo ?: '(sin motivo)',
    ]);

    return $inscripcion;
  }

}
