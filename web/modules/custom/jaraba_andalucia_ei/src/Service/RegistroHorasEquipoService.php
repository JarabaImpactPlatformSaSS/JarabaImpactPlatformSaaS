<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for tracking program staff hours (director, formador, orientador).
 *
 * Registers and aggregates dedicated hours from actuacion_sto entities
 * with tipo_actuacion='dedicacion_equipo' for FSE+ economic justification.
 *
 * Staff roles tracked: coordinador, orientador, formador.
 */
final class RegistroHorasEquipoService {

  /**
   * Tipo de actuación usado para registrar dedicación de equipo.
   */
  private const TIPO_DEDICACION = 'dedicacion_equipo';

  /**
   * Roles de equipo válidos para el registro de horas.
   */
  private const ROLES_EQUIPO = [
    'coordinador',
    'orientador',
    'formador',
  ];

  /**
   * Constructs a RegistroHorasEquipoService.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra horas de dedicación de un miembro del equipo.
   *
   * Crea una entidad actuacion_sto con tipo_actuacion='dedicacion_equipo',
   * duracion_minutos calculado desde las horas, y contenido con la descripción.
   *
   * @param int $uid
   *   ID del usuario staff.
   * @param string $rol
   *   Rol del staff: coordinador, orientador o formador.
   * @param float $horas
   *   Horas dedicadas (decimal, e.g. 2.5 = 2h 30min).
   * @param string $descripcion
   *   Descripción de la actividad realizada.
   * @param int|null $tenantId
   *   Tenant ID para aislamiento multi-tenant. NULL si no aplica.
   *
   * @return bool
   *   TRUE si el registro fue exitoso, FALSE en caso contrario.
   */
  public function registrarHoras(int $uid, string $rol, float $horas, string $descripcion, ?int $tenantId = NULL): bool {
    try {
      if ($horas <= 0.0) {
        $this->logger->warning('Intento de registrar horas <= 0 para uid @uid, rol @rol.', [
          '@uid' => $uid,
          '@rol' => $rol,
        ]);
        return FALSE;
      }

      if (!in_array($rol, self::ROLES_EQUIPO, TRUE)) {
        $this->logger->warning('Rol no válido "@rol" para registro de horas equipo.', [
          '@rol' => $rol,
        ]);
        return FALSE;
      }

      $duracionMinutos = (int) round($horas * 60);

      $storage = $this->entityTypeManager->getStorage('actuacion_sto');

      /** @var \Drupal\Core\Entity\ContentEntityInterface $actuacion */
      $actuacion = $storage->create([
        'tipo_actuacion' => self::TIPO_DEDICACION,
        'orientador_id' => $uid,
        'duracion_minutos' => $duracionMinutos,
        'contenido' => $descripcion,
        'uid' => $this->currentUser->id(),
        'lugar' => 'presencial_sede',
        'fecha' => date('Y-m-d'),
      ]);

      if ($tenantId !== NULL) {
        $actuacion->set('tenant_id', $tenantId);
      }

      $actuacion->save();

      $this->logger->info('Registradas @horas horas para uid @uid (rol: @rol).', [
        '@horas' => $horas,
        '@uid' => $uid,
        '@rol' => $rol,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando horas equipo: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Suma las horas dedicadas por un rol de equipo específico.
   *
   * Filtra actuacion_sto donde tipo_actuacion='dedicacion_equipo' y el
   * usuario referenciado en orientador_id tiene el rol Drupal indicado.
   *
   * @param string $rol
   *   Nombre del rol Drupal (coordinador, orientador, formador).
   * @param int|null $tenantId
   *   Tenant ID para filtro multi-tenant.
   *
   * @return float
   *   Total de horas dedicadas por ese rol.
   */
  public function getHorasPorRol(string $rol, ?int $tenantId = NULL): float {
    try {
      // Obtener UIDs de usuarios con el rol indicado.
      $userStorage = $this->entityTypeManager->getStorage('user');
      $userQuery = $userStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', $rol)
        ->condition('status', 1);

      $userIds = $userQuery->execute();

      if (count($userIds) === 0) {
        return 0.0;
      }

      $storage = $this->entityTypeManager->getStorage('actuacion_sto');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tipo_actuacion', self::TIPO_DEDICACION)
        ->condition('orientador_id', array_values($userIds), 'IN');

      // TENANT-001: Filtrar por tenant si se proporciona.
      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (count($ids) === 0) {
        return 0.0;
      }

      return $this->sumarHorasDeEntidades($ids);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo horas por rol @rol: @message', [
        '@rol' => $rol,
        '@message' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Suma las horas dedicadas por un usuario específico.
   *
   * @param int $uid
   *   ID del usuario.
   * @param int|null $tenantId
   *   Tenant ID para filtro multi-tenant.
   *
   * @return float
   *   Total de horas dedicadas por ese usuario.
   */
  public function getHorasPorUsuario(int $uid, ?int $tenantId = NULL): float {
    try {
      $storage = $this->entityTypeManager->getStorage('actuacion_sto');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tipo_actuacion', self::TIPO_DEDICACION)
        ->condition('orientador_id', $uid);

      // TENANT-001: Filtrar por tenant si se proporciona.
      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (count($ids) === 0) {
        return 0.0;
      }

      return $this->sumarHorasDeEntidades($ids);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo horas para uid @uid: @message', [
        '@uid' => $uid,
        '@message' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Obtiene un resumen de horas por tipo de rol del equipo.
   *
   * @param int|null $tenantId
   *   Tenant ID para filtro multi-tenant.
   *
   * @return array<string, float>
   *   Array con keys: coordinador, orientador, formador, total.
   */
  public function getResumenEquipo(?int $tenantId = NULL): array {
    try {
      $coordinador = $this->getHorasPorRol('coordinador', $tenantId);
      $orientador = $this->getHorasPorRol('orientador', $tenantId);
      $formador = $this->getHorasPorRol('formador', $tenantId);

      return [
        'coordinador' => $coordinador,
        'orientador' => $orientador,
        'formador' => $formador,
        'total' => round($coordinador + $orientador + $formador, 2),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo resumen equipo: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'coordinador' => 0.0,
        'orientador' => 0.0,
        'formador' => 0.0,
        'total' => 0.0,
      ];
    }
  }

  /**
   * Suma duracion_minutos de las entidades cargadas y convierte a horas.
   *
   * @param array<int|string> $ids
   *   IDs de entidades actuacion_sto.
   *
   * @return float
   *   Total de horas (decimal).
   */
  private function sumarHorasDeEntidades(array $ids): float {
    $storage = $this->entityTypeManager->getStorage('actuacion_sto');
    $entities = $storage->loadMultiple($ids);

    $totalMinutos = 0;

    foreach ($entities as $entity) {
      if (!($entity instanceof ContentEntityInterface)) {
        continue;
      }
      $minutos = $entity->get('duracion_minutos')->value;
      $totalMinutos += (int) ($minutos ?? 0);
    }

    return round($totalMinutos / 60, 2);
  }

}
