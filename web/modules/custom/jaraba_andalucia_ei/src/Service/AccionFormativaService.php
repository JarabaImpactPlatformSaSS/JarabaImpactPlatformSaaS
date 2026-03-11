<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dominio para acciones formativas del programa.
 *
 * Encapsula consultas, validaciones y lógica de negocio relacionada
 * con las acciones formativas fuera del workflow VoBo SAE.
 */
class AccionFormativaService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
    private readonly ?VoboSaeWorkflowService $voboWorkflow = NULL,
  ) {}

  /**
   * Obtiene acciones formativas de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant (group).
   * @param string|null $carril
   *   Filtrar por carril (NULL = todos).
   * @param string|null $estado
   *   Filtrar por estado (NULL = todos).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[]
   *   Acciones formativas ordenadas por 'orden' ASC.
   */
  public function getAccionesPorTenant(int $tenantId, ?string $carril = NULL, ?string $estado = NULL): array {
    $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('orden', 'ASC');

    if ($carril !== NULL) {
      $query->condition('carril', $carril);
    }
    if ($estado !== NULL) {
      $query->condition('estado', $estado);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Obtiene acciones formativas aplicables a un carril.
   *
   * Incluye acciones del carril específico + acciones comunes.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $carril
   *   Carril del participante.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[]
   *   Acciones aplicables.
   */
  public function getAccionesPorCarril(int $tenantId, string $carril): array {
    $storage = $this->entityTypeManager->getStorage('accion_formativa_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId);

    $group = $query->orConditionGroup()
      ->condition('carril', $carril)
      ->condition('carril', 'comun');
    $query->condition($group)
      ->sort('orden', 'ASC');

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Calcula el total de horas previstas para un carril.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $carril
   *   Carril del participante.
   *
   * @return array{formacion: float, orientacion: float, total: float}
   *   Horas desglosadas.
   */
  public function calcularHorasPorCarril(int $tenantId, string $carril): array {
    $acciones = $this->getAccionesPorCarril($tenantId, $carril);
    $horasFormacion = 0.0;
    $horasOrientacion = 0.0;

    foreach ($acciones as $accion) {
      $horas = $accion->getHorasPrevistas();
      $tipo = $accion->getTipoFormacion();

      if (in_array($tipo, ['mentoria_grupal'], TRUE)) {
        $horasOrientacion += $horas;
      }
      else {
        $horasFormacion += $horas;
      }
    }

    return [
      'formacion' => round($horasFormacion, 2),
      'orientacion' => round($horasOrientacion, 2),
      'total' => round($horasFormacion + $horasOrientacion, 2),
    ];
  }

  /**
   * Obtiene acciones formativas ejecutables (con VoBo aprobado o no requerido).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\AccionFormativaEiInterface[]
   *   Acciones que pueden ejecutarse.
   */
  public function getAccionesEjecutables(int $tenantId): array {
    $todas = $this->getAccionesPorTenant($tenantId);
    return array_filter($todas, fn(AccionFormativaEiInterface $a) => $a->canExecute());
  }

  /**
   * Valida que un plan formativo cumple los requisitos mínimos normativos.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $carril
   *   Carril a validar.
   *
   * @return array{valido: bool, errores: string[]}
   *   Resultado de validación con errores descriptivos.
   */
  public function validarRequisitosPlan(int $tenantId, string $carril): array {
    $horas = $this->calcularHorasPorCarril($tenantId, $carril);
    $errores = [];

    if ($horas['formacion'] < 50.0) {
      $errores[] = sprintf(
        'Horas de formación insuficientes: %.1f/50h mínimo requerido.',
        $horas['formacion']
      );
    }

    if ($horas['orientacion'] < 10.0) {
      $errores[] = sprintf(
        'Horas de orientación insuficientes: %.1f/10h mínimo requerido.',
        $horas['orientacion']
      );
    }

    // Verificar que las acciones de formación tienen VoBo.
    $acciones = $this->getAccionesPorCarril($tenantId, $carril);
    $sinVobo = array_filter($acciones, fn(AccionFormativaEiInterface $a) =>
      $a->requiereVoboSae() && !$a->isVoboAprobado()
    );

    if (!empty($sinVobo)) {
      $titulos = array_map(fn(AccionFormativaEiInterface $a) => $a->getTitulo(), $sinVobo);
      $errores[] = sprintf(
        '%d acción(es) sin VoBo SAE aprobado: %s',
        count($sinVobo),
        implode(', ', $titulos)
      );
    }

    return [
      'valido' => empty($errores),
      'errores' => $errores,
    ];
  }

}
