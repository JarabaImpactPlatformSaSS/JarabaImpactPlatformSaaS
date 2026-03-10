<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Logica de negocio para prospecciones empresariales del programa PIIL CV 2025.
 *
 * Gestiona el ciclo de vida de prospecciones: contacto, seguimiento,
 * vinculacion con inserciones y estadisticas agregadas.
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class ProspeccionService {

  /**
   * Estados validos de prospeccion.
   */
  private const VALID_ESTADOS = ['activa', 'contactada', 'colaborando', 'descartada'];

  /**
   * Constructs a ProspeccionService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene prospecciones filtradas por estado.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param string $estado
   *   Filtro de estado (vacio para todos).
   * @param int $limit
   *   Maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array{items: array, total: int}
   *   Resultados paginados con total.
   */
  public function getProspeccionesByEstado(?int $tenantId, string $estado = '', int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prospeccion_empresarial');

      $countQuery = $storage->getQuery()->accessCheck(TRUE);
      $dataQuery = $storage->getQuery()->accessCheck(TRUE);

      if ($estado !== '' && in_array($estado, self::VALID_ESTADOS, TRUE)) {
        $countQuery->condition('estado', $estado);
        $dataQuery->condition('estado', $estado);
      }

      $this->addTenantCondition($countQuery, $tenantId);
      $this->addTenantCondition($dataQuery, $tenantId);

      $total = (int) $countQuery->count()->execute();

      $ids = $dataQuery
        ->sort('changed', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $items = [];
      foreach ($storage->loadMultiple($ids) as $prospeccion) {
        $items[] = [
          'id' => (int) $prospeccion->id(),
          'empresa_nombre' => $prospeccion->get('empresa_nombre')->value ?? '',
          'sector' => $prospeccion->get('sector')->value ?? '',
          'estado' => $prospeccion->get('estado')->value ?? 'activa',
          'contacto_nombre' => $prospeccion->get('contacto_nombre')->value ?? '',
          'contacto_email' => $prospeccion->get('contacto_email')->value ?? '',
          'puestos_ofertados' => (int) ($prospeccion->get('puestos_ofertados')->value ?? 0),
          'puestos_cubiertos' => (int) ($prospeccion->get('puestos_cubiertos')->value ?? 0),
          'ultimo_contacto' => $prospeccion->get('ultimo_contacto')->value ?? NULL,
          'changed' => $prospeccion->get('changed')->value,
        ];
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo prospecciones: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene estadisticas agregadas de prospecciones.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Estadisticas: total, por_estado, puestos_ofertados, puestos_cubiertos.
   */
  public function getEstadisticas(?int $tenantId): array {
    $stats = [
      'total' => 0,
      'por_estado' => [
        'activa' => 0,
        'contactada' => 0,
        'colaborando' => 0,
        'descartada' => 0,
      ],
      'puestos_ofertados' => 0,
      'puestos_cubiertos' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('prospeccion_empresarial');

      // Total general.
      $totalQuery = $storage->getQuery()->accessCheck(TRUE)->count();
      $this->addTenantCondition($totalQuery, $tenantId);
      $stats['total'] = (int) $totalQuery->execute();

      // Conteo por estado.
      foreach (self::VALID_ESTADOS as $estado) {
        $estadoQuery = $storage->getQuery()->accessCheck(TRUE)
          ->condition('estado', $estado)
          ->count();
        $this->addTenantCondition($estadoQuery, $tenantId);
        $stats['por_estado'][$estado] = (int) $estadoQuery->execute();
      }

      // Sumas de puestos: cargar todas las prospecciones y sumar.
      $allQuery = $storage->getQuery()->accessCheck(TRUE);
      $this->addTenantCondition($allQuery, $tenantId);
      $allIds = $allQuery->execute();

      if (!empty($allIds)) {
        foreach ($storage->loadMultiple($allIds) as $prospeccion) {
          $stats['puestos_ofertados'] += (int) ($prospeccion->get('puestos_ofertados')->value ?? 0);
          $stats['puestos_cubiertos'] += (int) ($prospeccion->get('puestos_cubiertos')->value ?? 0);
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando estadisticas de prospeccion: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Registra un contacto con una prospeccion empresarial.
   *
   * @param int $prospeccionId
   *   ID de la entidad prospeccion_empresarial.
   * @param string $resultado
   *   Resultado del contacto (texto libre).
   * @param string $notas
   *   Notas adicionales del contacto.
   *
   * @return array{success: bool, message: string}
   *   Resultado de la operacion.
   */
  public function registrarContacto(int $prospeccionId, string $resultado, string $notas): array {
    try {
      $prospeccion = $this->entityTypeManager
        ->getStorage('prospeccion_empresarial')
        ->load($prospeccionId);

      if (!$prospeccion) {
        return ['success' => FALSE, 'message' => 'Prospección no encontrada.'];
      }

      $prospeccion->set('ultimo_contacto', date('Y-m-d\TH:i:s'));
      $prospeccion->set('notas_contacto', mb_substr($notas, 0, 2000));

      // Si el estado era 'activa', avanzar a 'contactada'.
      $estadoActual = $prospeccion->get('estado')->value ?? 'activa';
      if ($estadoActual === 'activa') {
        $prospeccion->set('estado', 'contactada');
      }

      $prospeccion->save();

      $this->logger->info('Contacto registrado en prospeccion #@id. Resultado: @resultado', [
        '@id' => $prospeccionId,
        '@resultado' => mb_substr($resultado, 0, 500),
      ]);

      return ['success' => TRUE, 'message' => 'Contacto registrado correctamente.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando contacto prospeccion #@id: @msg', [
        '@id' => $prospeccionId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al registrar el contacto.'];
    }
  }

  /**
   * Vincula una prospeccion con una insercion laboral.
   *
   * @param int $prospeccionId
   *   ID de la entidad prospeccion_empresarial.
   * @param int $insercionId
   *   ID de la entidad de insercion.
   *
   * @return array{success: bool, message: string}
   *   Resultado de la operacion.
   */
  public function vincularInsercion(int $prospeccionId, int $insercionId): array {
    try {
      $prospeccion = $this->entityTypeManager
        ->getStorage('prospeccion_empresarial')
        ->load($prospeccionId);

      if (!$prospeccion) {
        return ['success' => FALSE, 'message' => 'Prospección no encontrada.'];
      }

      $prospeccion->set('insercion_id', $insercionId);

      // Actualizar estado a colaborando si aun no lo esta.
      $estadoActual = $prospeccion->get('estado')->value ?? 'activa';
      if ($estadoActual !== 'colaborando' && $estadoActual !== 'descartada') {
        $prospeccion->set('estado', 'colaborando');
      }

      // Incrementar puestos cubiertos.
      $cubiertos = (int) ($prospeccion->get('puestos_cubiertos')->value ?? 0);
      $prospeccion->set('puestos_cubiertos', $cubiertos + 1);

      $prospeccion->save();

      $this->logger->info('Prospeccion #@pid vinculada a insercion #@iid', [
        '@pid' => $prospeccionId,
        '@iid' => $insercionId,
      ]);

      return ['success' => TRUE, 'message' => 'Inserción vinculada correctamente.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error vinculando insercion a prospeccion #@id: @msg', [
        '@id' => $prospeccionId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al vincular la inserción.'];
    }
  }

  /**
   * Agrega condicion de tenant a una query (TENANT-001).
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   La query a filtrar.
   * @param int|null $tenantId
   *   ID del tenant, o NULL para no filtrar.
   */
  private function addTenantCondition(QueryInterface $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

}
