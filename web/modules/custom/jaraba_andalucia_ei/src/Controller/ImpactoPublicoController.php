<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\JustificacionEconomicaService;
use Drupal\jaraba_andalucia_ei\Service\PuntosImpactoEiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard público de impacto Andalucía +ei.
 *
 * Sprint 11 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Muestra datos agregados anonimizados. Acceso público sin login.
 * NO muestra datos personales (RGPD).
 */
class ImpactoPublicoController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected LoggerInterface $logger,
    protected ?JustificacionEconomicaService $justificacionService = NULL,
    protected ?PuntosImpactoEiService $puntosImpactoService = NULL,
    protected ?object $sroiService = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('jaraba_andalucia_ei.justificacion_economica')
        ? $container->get('jaraba_andalucia_ei.justificacion_economica')
        : NULL,
      $container->has('jaraba_andalucia_ei.puntos_impacto')
        ? $container->get('jaraba_andalucia_ei.puntos_impacto')
        : NULL,
      $container->has('jaraba_business_tools.sroi_calculator')
        ? $container->get('jaraba_business_tools.sroi_calculator')
        : NULL,
    );
  }

  /**
   * Dashboard público de impacto.
   */
  public function dashboard(): array {
    $metricas = $this->getMetricasAgregadas();
    $distribucion = $this->getDistribucionColectivo();
    $iris = $this->getIndicadoresIris();

    return [
      '#theme' => 'andalucia_ei_impacto_publico',
      '#metricas' => $metricas,
      '#distribucion' => $distribucion,
      '#iris' => $iris,
      '#cache' => [
        'tags' => ['programa_participante_ei_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Obtiene métricas agregadas anonimizadas.
   */
  protected function getMetricasAgregadas(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Total participantes atendidos.
      $totalAtendidos = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'baja', '<>')
        ->count()
        ->execute();

      // Total inserciones (fase insercion o seguimiento con fecha).
      $totalInserciones = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', ['insercion', 'seguimiento'], 'IN')
        ->exists('fecha_insercion')
        ->count()
        ->execute();

      // Horas de formación totales.
      $horasFormacion = 0.0;
      if ($this->justificacionService) {
        try {
          $justData = $this->justificacionService->getResumen();
          $horasFormacion = (float) ($justData['horas_formacion_total'] ?? 0);
        }
        catch (\Throwable) {
          // Non-critical.
        }
      }

      // Emprendimientos lanzados.
      $emprendimientos = 0;
      try {
        $emprendimientos = (int) $this->entityTypeManager
          ->getStorage('plan_emprendimiento_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 1)
          ->condition('fase_emprendimiento', ['lanzamiento', 'consolidacion'], 'IN')
          ->count()
          ->execute();
      }
      catch (\Throwable) {
        // Entity may not exist yet.
      }

      // SROI estimado.
      $sroi = NULL;
      if ($this->sroiService) {
        try {
          $sroi = $this->sroiService->calculate([
            'tipo' => 'programa_global',
            'participantes' => $totalAtendidos,
            'inserciones' => $totalInserciones,
          ]);
        }
        catch (\Throwable) {
          // Non-critical.
        }
      }

      return [
        'total_atendidos' => $totalAtendidos,
        'total_inserciones' => $totalInserciones,
        'total_emprendimientos' => $emprendimientos,
        'horas_formacion' => $horasFormacion,
        'sroi' => $sroi,
        'ods' => [1, 4, 5, 8, 10],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo métricas de impacto: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Distribución por colectivo (datos anonimizados).
   */
  protected function getDistribucionColectivo(): array {
    // Datos agregados sin PII.
    return [
      'por_tipo_insercion' => [
        'cuenta_ajena' => 0,
        'cuenta_propia' => 0,
        'pendiente' => 0,
      ],
    ];
  }

  /**
   * Indicadores IRIS+ mapeados.
   */
  protected function getIndicadoresIris(): array {
    return [
      ['codigo' => 'PI1556', 'nombre' => 'Clients: Individuals', 'descripcion' => 'Total participantes atendidos'],
      ['codigo' => 'PI7734', 'nombre' => 'Client Outcomes: Employment', 'descripcion' => 'Inserciones laborales'],
      ['codigo' => 'PI3213', 'nombre' => 'Client Outcomes: Self-Employment', 'descripcion' => 'Emprendimientos en consolidación'],
      ['codigo' => 'OI5103', 'nombre' => 'Training Hours Provided', 'descripcion' => 'Horas formación + orientación'],
      ['codigo' => 'PI3468', 'nombre' => 'Jobs Created', 'descripcion' => 'Empleos generados por emprendimientos'],
    ];
  }

}
