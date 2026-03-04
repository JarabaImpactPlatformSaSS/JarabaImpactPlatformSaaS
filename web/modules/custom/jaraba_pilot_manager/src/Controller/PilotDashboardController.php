<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\jaraba_pilot_manager\Service\PilotEvaluatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Pilot Dashboard.
 *
 * CONTROLLER-READONLY-001: No `protected readonly` on inherited properties.
 * Assigns entityTypeManager manually in create().
 */
class PilotDashboardController extends ControllerBase {

  /**
   * The pilot evaluator service.
   *
   * @var \Drupal\jaraba_pilot_manager\Service\PilotEvaluatorService
   */
  protected PilotEvaluatorService $evaluator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    // CONTROLLER-READONLY-001: Assign manually, not via constructor promotion.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->evaluator = $container->get('jaraba_pilot_manager.pilot_evaluator');
    return $instance;
  }

  /**
   * Renders the pilot dashboard with program metrics.
   *
   * @return array
   *   Render array with table of active/completed programs.
   */
  public function dashboard(): array {
    $build = [];
    try {
      $storage = $this->entityTypeManager()->getStorage('pilot_program');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', ['active', 'completed'], 'IN')
        ->sort('start_date', 'DESC')
        ->execute();

      $programs = $storage->loadMultiple($ids);
      $rows = [];

      foreach ($programs as $program) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $program */
        $eval = $this->evaluator->evaluatePilot($program);
        $rows[] = [
          $program->get('name')->value ?? '-',
          $program->get('vertical')->value ?? '-',
          $program->get('status')->value ?? '-',
          (string) $eval['total_enrolled'],
          (string) $eval['total_converted'],
          round($eval['conversion_rate'] * 100, 1) . '%',
        ];
      }

      $build['programs'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Programa'),
          $this->t('Vertical'),
          $this->t('Estado'),
          $this->t('Inscritos'),
          $this->t('Convertidos'),
          $this->t('Conversion'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No hay programas piloto activos.'),
      ];
    }
    catch (\Throwable) {
      $build['error'] = ['#markup' => '<p>' . $this->t('Error cargando datos.') . '</p>'];
    }

    return $build;
  }

}
