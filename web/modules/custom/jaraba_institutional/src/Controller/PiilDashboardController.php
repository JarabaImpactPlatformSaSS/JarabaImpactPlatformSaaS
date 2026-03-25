<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_institutional\Service\PiilMetricsService;
use Drupal\jaraba_institutional\Service\StoSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard de metricas PIIL/FUNDAE/FSE+.
 */
class PiilDashboardController extends ControllerBase {

  protected PiilMetricsService $piilMetrics;

  protected StoSyncService $stoSync;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    // CONTROLLER-READONLY-001: No readonly on inherited properties.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->piilMetrics = $container->get('jaraba_institutional.piil_metrics');
    $instance->stoSync = $container->get('jaraba_institutional.sto_sync');
    return $instance;
  }

  /**
   * Dashboard principal PIIL.
   */
  public function dashboard(): array {
    $build = [];

    try {
      $storage = $this->entityTypeManager()->getStorage('institutional_program');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->execute();

      $programs = $storage->loadMultiple($ids);
      $rows = [];

      foreach ($programs as $program) {
        $programId = (int) $program->id();
        $report = $this->piilMetrics->generatePiilReport($programId);
        /** @var \Drupal\Core\Entity\ContentEntityInterface $program */

        $rows[] = [
          $program->label() ?? $this->t('Programa @id', ['@id' => $programId]),
          $program->get('piil_program_code')->value ?? '-',
          $report['employment_outcomes']['total'],
          round($report['employment_outcomes']['rate'] * 100, 1) . '%',
          $report['certifications']['certified'],
          round($report['certifications']['rate'] * 100, 1) . '%',
        ];
      }

      $build['programs_table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Programa'),
          $this->t('Codigo PIIL'),
          $this->t('Participantes'),
          $this->t('Insercion'),
          $this->t('Certificados'),
          $this->t('Tasa Cert.'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No hay programas activos con datos PIIL.'),
      ];
    }
    catch (\Throwable) {
      $build['error'] = [
        '#markup' => '<p>' . $this->t('No se pudieron cargar los datos PIIL.') . '</p>',
      ];
    }

    return $build;
  }

}
