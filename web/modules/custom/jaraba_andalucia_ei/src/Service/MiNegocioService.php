<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Proporciona métricas de negocio para el dashboard "Mi Negocio".
 *
 * Durante la fase de inserción, los participantes comercializan packs de
 * servicios digitales a negocios locales. Este servicio calcula KPIs
 * de rendimiento: clientes captados, tasa de conversión piloto→activo,
 * facturación estimada y precio/hora efectivo.
 *
 * Todas las queries filtran por tenant_id (TENANT-001).
 */
class MiNegocioService {

  /**
   * Horas estándar estimadas por pack/mes.
   *
   * Basado en la estructura de los 5 packs vertebradores.
   *
   * @var array<string, float>
   */
  private const HORAS_POR_PACK = [
    'contenido_digital' => 20.0,
    'asistente_virtual' => 25.0,
    'presencia_online' => 15.0,
    'tienda_digital' => 30.0,
    'community_manager' => 20.0,
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las métricas de negocio de un participante.
   *
   * @param int $participanteId
   *   ID de la entidad ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo).
   *
   * @return array{
   *   clientes_total: int,
   *   clientes_activos: int,
   *   clientes_piloto: int,
   *   clientes_pagando: int,
   *   tasa_conversion_piloto: float,
   *   facturacion_mensual_estimada: float,
   *   pack_confirmado: string|null,
   *   pack_publicado: bool,
   *   horas_estimadas_mes: float,
   *   precio_hora_efectivo: float,
   * }
   *   Array con las métricas calculadas.
   */
  public function getMetricasNegocio(int $participanteId, int $tenantId): array {
    $metricas = [
      'clientes_total' => 0,
      'clientes_activos' => 0,
      'clientes_piloto' => 0,
      'clientes_pagando' => 0,
      'tasa_conversion_piloto' => 0.0,
      'facturacion_mensual_estimada' => 0.0,
      'pack_confirmado' => NULL,
      'pack_publicado' => FALSE,
      'horas_estimadas_mes' => 0.0,
      'precio_hora_efectivo' => 0.0,
    ];

    try {
      $clientes = $this->loadClientes($participanteId, $tenantId);
      $clientesCount = count($clientes);
      $metricas['clientes_total'] = $clientesCount;

      $activos = 0;
      $pilotos = 0;
      $pagando = 0;
      $facturacionTotal = 0.0;
      $horasTotal = 0.0;

      foreach ($clientes as $cliente) {
        $estado = $cliente->getEstado();

        if ($estado === 'activo') {
          $activos++;
          $pagando++;
          $precio = (float) $cliente->getPrecioMensual();
          $facturacionTotal += $precio;
          $pack = $cliente->getPackContratado();
          $horasTotal += self::HORAS_POR_PACK[$pack] ?? 20.0;
        }

        if ($estado === 'piloto') {
          $pilotos++;
        }

        if ($cliente->isPiloto() && $estado === 'activo') {
          // Cliente que fue piloto y ahora paga.
          $pagando = $pagando; // Already counted above.
        }
      }

      $metricas['clientes_activos'] = $activos;
      $metricas['clientes_piloto'] = $pilotos;
      $metricas['clientes_pagando'] = $pagando;

      // Tasa de conversión: pilotos que pasaron a activo / total pilotos.
      $totalPilotos = $pilotos + $this->countConvertedPilotos($clientes);
      if ($totalPilotos > 0) {
        $convertidos = $this->countConvertedPilotos($clientes);
        $metricas['tasa_conversion_piloto'] = round(($convertidos / $totalPilotos) * 100, 1);
      }

      $metricas['facturacion_mensual_estimada'] = round($facturacionTotal, 2);
      $metricas['horas_estimadas_mes'] = round($horasTotal, 1);

      if ($horasTotal > 0.0) {
        $metricas['precio_hora_efectivo'] = round($facturacionTotal / $horasTotal, 2);
      }

      // Pack confirmado del participante.
      $participanteData = $this->loadParticipante($participanteId, $tenantId);
      if ($participanteData !== NULL) {
        $packValue = $participanteData->get('pack_confirmado')->value;
        $metricas['pack_confirmado'] = $packValue !== NULL && $packValue !== '' ? $packValue : NULL;
        $metricas['pack_publicado'] = $metricas['pack_confirmado'] !== NULL && $clientesCount > 0;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando métricas Mi Negocio para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $metricas;
  }

  /**
   * Obtiene el resumen de clientes de un participante.
   *
   * @param int $participanteId
   *   ID de la entidad ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo).
   *
   * @return array<int, array{
   *   nombre: string,
   *   sector: string,
   *   pack: string,
   *   estado: string,
   *   precio: float,
   *   fecha_inicio: string|null,
   * }>
   *   Lista de resúmenes de clientes.
   */
  public function getResumenClientes(int $participanteId, int $tenantId): array {
    $resumen = [];

    try {
      $clientes = $this->loadClientes($participanteId, $tenantId);

      foreach ($clientes as $cliente) {
        $fechaInicio = $cliente->get('fecha_inicio')->value;

        $resumen[] = [
          'nombre' => $cliente->getNombreNegocio(),
          'sector' => $cliente->getSector(),
          'pack' => $cliente->getPackContratado(),
          'estado' => $cliente->getEstado(),
          'precio' => (float) $cliente->getPrecioMensual(),
          'fecha_inicio' => $fechaInicio !== NULL && $fechaInicio !== '' ? $fechaInicio : NULL,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo resumen de clientes para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $resumen;
  }

  /**
   * Obtiene la evolución mensual de clientes y facturación.
   *
   * @param int $participanteId
   *   ID de la entidad ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo).
   * @param int $meses
   *   Número de meses a incluir (default 6).
   *
   * @return array<int, array{
   *   mes: string,
   *   clientes: int,
   *   facturacion: float,
   * }>
   *   Evolución mensual ordenada cronológicamente.
   */
  public function getEvolucionMensual(int $participanteId, int $tenantId, int $meses = 6): array {
    $evolucion = [];

    try {
      $clientes = $this->loadClientes($participanteId, $tenantId);

      // Generar meses vacíos para el rango.
      $ahora = new \DateTimeImmutable('first day of this month');
      $mesesMap = [];
      for ($i = $meses - 1; $i >= 0; $i--) {
        $mesKey = $ahora->modify("-{$i} months")->format('Y-m');
        $mesesMap[$mesKey] = [
          'mes' => $mesKey,
          'clientes' => 0,
          'facturacion' => 0.0,
        ];
      }

      // Agrupar clientes por mes de creación.
      foreach ($clientes as $cliente) {
        $created = $cliente->get('created')->value;
        if ($created === NULL) {
          continue;
        }

        try {
          $fecha = (new \DateTimeImmutable())->setTimestamp((int) $created);
          $mesKey = $fecha->format('Y-m');
        }
        catch (\Throwable) {
          continue;
        }

        if (isset($mesesMap[$mesKey])) {
          $mesesMap[$mesKey]['clientes']++;
          $precio = (float) $cliente->getPrecioMensual();
          $mesesMap[$mesKey]['facturacion'] = round($mesesMap[$mesKey]['facturacion'] + $precio, 2);
        }
      }

      $evolucion = array_values($mesesMap);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo evolución mensual para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $evolucion;
  }

  /**
   * Carga los clientes de un participante filtrados por tenant.
   *
   * @param int $participanteId
   *   ID de la entidad ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ClienteParticipanteEi[]
   *   Lista de entidades ClienteParticipanteEi.
   */
  private function loadClientes(int $participanteId, int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('cliente_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('participante_id', $participanteId)
      ->condition('tenant_id', $tenantId)
      ->sort('created', 'DESC')
      ->execute();

    if (count($ids) === 0) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\ClienteParticipanteEi[] $clientes */
    $clientes = $storage->loadMultiple($ids);

    return $clientes;
  }

  /**
   * Carga la entidad participante filtrada por tenant.
   *
   * @param int $participanteId
   *   ID de la entidad ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo).
   *
   * @return object|null
   *   Entidad ProgramaParticipanteEi o NULL.
   */
  private function loadParticipante(int $participanteId, int $tenantId): ?object {
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $participanteId)
      ->condition('tenant_id', $tenantId)
      ->range(0, 1)
      ->execute();

    if (count($ids) === 0) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Cuenta pilotos que se convirtieron a activos.
   *
   * Un cliente convertido es aquel que tiene es_piloto=TRUE y estado='activo'.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ClienteParticipanteEi[] $clientes
   *   Lista de clientes.
   *
   * @return int
   *   Número de pilotos convertidos.
   */
  private function countConvertedPilotos(array $clientes): int {
    $count = 0;
    foreach ($clientes as $cliente) {
      if ($cliente->isPiloto() && $cliente->getEstado() === 'activo') {
        $count++;
      }
    }
    return $count;
  }

}
