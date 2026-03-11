<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona el workflow VoBo SAE para acciones formativas.
 *
 * Máquina de 8 estados con transiciones validadas, generación de
 * documentación de solicitud, registro de fechas, alertas por timeout
 * y ciclo de subsanación.
 *
 * Workflow: borrador → pendiente_vobo → vobo_enviado → vobo_aprobado
 *                                                    → vobo_rechazado → en_subsanacion → vobo_enviado
 *           vobo_aprobado → en_ejecucion → finalizada
 */
class VoboSaeWorkflowService {

  /**
   * Transiciones válidas del workflow VoBo SAE.
   *
   * @var array<string, string[]>
   */
  private const TRANSITIONS = [
    'borrador' => ['pendiente_vobo'],
    'pendiente_vobo' => ['vobo_enviado', 'borrador'],
    'vobo_enviado' => ['vobo_aprobado', 'vobo_rechazado'],
    'vobo_aprobado' => ['en_ejecucion'],
    'vobo_rechazado' => ['en_subsanacion', 'borrador'],
    'en_subsanacion' => ['vobo_enviado', 'borrador'],
    'en_ejecucion' => ['finalizada'],
    'finalizada' => [],
  ];

  /**
   * Días antes de generar alerta por timeout.
   */
  private const ALERTA_TIMEOUT_DIAS = 15;

  /**
   * Días para alerta crítica.
   */
  private const ALERTA_CRITICA_DIAS = 30;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
    private readonly mixed $expedienteService = NULL,
  ) {}

  /**
   * Valida si una transición de estado es permitida.
   *
   * @param string $estadoActual
   *   Estado actual de la acción formativa.
   * @param string $nuevoEstado
   *   Estado objetivo.
   *
   * @return bool
   *   TRUE si la transición es válida.
   */
  public function isTransitionValid(string $estadoActual, string $nuevoEstado): bool {
    if (!isset(self::TRANSITIONS[$estadoActual])) {
      return FALSE;
    }
    return in_array($nuevoEstado, self::TRANSITIONS[$estadoActual], TRUE);
  }

  /**
   * Obtiene las transiciones posibles desde un estado.
   *
   * @param string $estado
   *   Estado actual.
   *
   * @return string[]
   *   Estados a los que se puede transicionar.
   */
  public function getTransicionesPosibles(string $estado): array {
    return self::TRANSITIONS[$estado] ?? [];
  }

  /**
   * Transiciona una acción formativa a un nuevo estado.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface $accion
   *   La acción formativa.
   * @param string $nuevoEstado
   *   Estado objetivo.
   * @param string $motivo
   *   Motivo de la transición (para log de revisión).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface
   *   La acción formativa actualizada.
   *
   * @throws \InvalidArgumentException
   *   Si la transición no es válida.
   */
  public function transicionar(AccionFormativaEiInterface $accion, string $nuevoEstado, string $motivo = ''): AccionFormativaEiInterface {
    $estadoActual = $accion->getEstado();

    if (!$this->isTransitionValid($estadoActual, $nuevoEstado)) {
      throw new \InvalidArgumentException(
        sprintf(
          'Transición inválida: %s → %s. Permitidas: %s',
          $estadoActual,
          $nuevoEstado,
          implode(', ', $this->getTransicionesPosibles($estadoActual))
        )
      );
    }

    $accion->set('estado', $nuevoEstado);

    // Registrar fechas según tipo de transición.
    if ($nuevoEstado === 'vobo_enviado') {
      $accion->set('vobo_fecha_envio', date('Y-m-d'));
    }
    elseif (in_array($nuevoEstado, ['vobo_aprobado', 'vobo_rechazado'], TRUE)) {
      $accion->set('vobo_fecha_respuesta', date('Y-m-d'));
    }

    // Revision log para audit trail.
    $logMessage = sprintf(
      'VoBo SAE: %s → %s',
      AccionFormativaEiInterface::ESTADOS[$estadoActual] ?? $estadoActual,
      AccionFormativaEiInterface::ESTADOS[$nuevoEstado] ?? $nuevoEstado
    );
    if ($motivo !== '') {
      $logMessage .= ' — ' . $motivo;
    }
    $accion->setRevisionLogMessage($logMessage);
    $accion->setNewRevision(TRUE);

    $accion->save();

    $this->logger->info('VoBo workflow: acción @id transicionó de @from a @to. Motivo: @motivo', [
      '@id' => $accion->id(),
      '@from' => $estadoActual,
      '@to' => $nuevoEstado,
      '@motivo' => $motivo ?: '(sin motivo)',
    ]);

    return $accion;
  }

  /**
   * Registra la respuesta del SAE a una solicitud de VoBo.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface $accion
   *   La acción formativa.
   * @param bool $aprobado
   *   TRUE si aprobado, FALSE si rechazado.
   * @param string $codigo
   *   Código de aprobación del SAE (solo si aprobado).
   * @param string $motivo
   *   Motivo del rechazo (solo si rechazado).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface
   *   La acción formativa actualizada.
   */
  public function registrarRespuesta(AccionFormativaEiInterface $accion, bool $aprobado, string $codigo = '', string $motivo = ''): AccionFormativaEiInterface {
    if ($aprobado) {
      $accion->set('vobo_codigo', $codigo);
      return $this->transicionar($accion, 'vobo_aprobado', 'Aprobado con código: ' . $codigo);
    }

    $accion->set('vobo_motivo_rechazo', $motivo);
    return $this->transicionar($accion, 'vobo_rechazado', 'Rechazado: ' . $motivo);
  }

  /**
   * Obtiene acciones formativas pendientes de respuesta VoBo.
   *
   * @param int|null $tenantId
   *   Filtrar por tenant (NULL = todos).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[]
   *   Acciones en estado 'vobo_enviado' ordenadas por fecha de envío.
   */
  public function getAccionesPendientesVobo(?int $tenantId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('estado', 'vobo_enviado')
      ->sort('vobo_fecha_envio', 'ASC');

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[] $acciones */
    $acciones = $storage->loadMultiple($ids);
    return $acciones;
  }

  /**
   * Obtiene acciones sin VoBo que llevan más de N días.
   *
   * Usado por AlertasNormativasService para generar alertas de timeout.
   *
   * @param int $diasMinimo
   *   Mínimo de días sin respuesta.
   * @param int|null $tenantId
   *   Filtrar por tenant.
   *
   * @return array<int, array{accion: AccionFormativaEiInterface, dias: int}>
   *   Acciones con días transcurridos.
   */
  public function getAccionesSinVobo(int $diasMinimo = 0, ?int $tenantId = NULL): array {
    $pendientes = $this->getAccionesPendientesVobo($tenantId);
    $resultado = [];
    $hoy = new \DateTimeImmutable('today');

    foreach ($pendientes as $accion) {
      $fechaEnvio = $accion->get('vobo_fecha_envio')->value;
      if ($fechaEnvio === NULL) {
        continue;
      }

      try {
        $fecha = new \DateTimeImmutable($fechaEnvio);
        $dias = (int) $hoy->diff($fecha)->days;
      }
      catch (\Throwable) {
        continue;
      }

      if ($dias >= $diasMinimo) {
        $resultado[(int) $accion->id()] = [
          'accion' => $accion,
          'dias' => $dias,
        ];
      }
    }

    return $resultado;
  }

  /**
   * Genera alertas por timeout de VoBo SAE.
   *
   * @param int|null $tenantId
   *   Filtrar por tenant.
   *
   * @return array<int, array{tipo: string, accion: AccionFormativaEiInterface, dias: int, mensaje: string}>
   *   Alertas generadas.
   */
  public function generarAlertasTimeout(?int $tenantId = NULL): array {
    $alertas = [];

    $sinVobo = $this->getAccionesSinVobo(self::ALERTA_TIMEOUT_DIAS, $tenantId);

    foreach ($sinVobo as $id => $data) {
      $tipo = $data['dias'] >= self::ALERTA_CRITICA_DIAS ? 'critica' : 'advertencia';
      $alertas[$id] = [
        'tipo' => $tipo,
        'accion' => $data['accion'],
        'dias' => $data['dias'],
        'mensaje' => sprintf(
          'La acción "%s" lleva %d días pendiente de VoBo SAE.',
          $data['accion']->getTitulo(),
          $data['dias']
        ),
      ];
    }

    return $alertas;
  }

  /**
   * Genera el documento de solicitud VoBo para el SAE.
   *
   * Utiliza ExpedienteService (si está disponible) para crear el documento
   * dentro del expediente del programa.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface $accion
   *   La acción formativa.
   *
   * @return int|null
   *   ID del documento generado, o NULL si ExpedienteService no está disponible.
   */
  public function generarDocumentoSolicitud(AccionFormativaEiInterface $accion): ?int {
    if ($this->expedienteService === NULL) {
      $this->logger->warning('ExpedienteService no disponible. No se puede generar documento VoBo para acción @id.', [
        '@id' => $accion->id(),
      ]);
      return NULL;
    }

    try {
      // PRESAVE-RESILIENCE-001: try-catch para servicio opcional.
      $documentoId = $this->expedienteService->crearDocumento([
        'titulo' => 'Solicitud VoBo SAE — ' . $accion->getTitulo(),
        'categoria' => 'programa_vobo_sae',
        'tenant_id' => $accion->get('tenant_id')->target_id ?? NULL,
      ]);

      if ($documentoId) {
        $accion->set('vobo_documento_id', $documentoId);
        $accion->save();
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando documento VoBo: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Indica si un estado es terminal (no permite más transiciones).
   *
   * @param string $estado
   *   Estado a verificar.
   *
   * @return bool
   *   TRUE si es terminal.
   */
  public function isEstadoTerminal(string $estado): bool {
    return empty(self::TRANSITIONS[$estado] ?? []);
  }

}
