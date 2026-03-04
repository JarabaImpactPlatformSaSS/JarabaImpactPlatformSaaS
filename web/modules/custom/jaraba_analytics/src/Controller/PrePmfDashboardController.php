<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Service\NpsSurveyService;
use Drupal\jaraba_analytics\Service\ProductMetricsAggregatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para el dashboard de metricas Pre-PMF.
 *
 * Muestra dashboard admin con activacion, retencion, NPS, churn
 * y alertas de kill criteria. Tambien expone API NPS.
 */
class PrePmfDashboardController extends ControllerBase {

  /**
   * The NPS survey service.
   *
   * @var \Drupal\jaraba_analytics\Service\NpsSurveyService
   */
  protected NpsSurveyService $npsSurvey;

  /**
   * The product metrics aggregator service.
   *
   * @var \Drupal\jaraba_analytics\Service\ProductMetricsAggregatorService
   */
  protected ProductMetricsAggregatorService $metricsAggregator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    // CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->npsSurvey = $container->get('jaraba_analytics.nps_survey');
    $instance->metricsAggregator = $container->get('jaraba_analytics.product_metrics_aggregator');
    return $instance;
  }

  /**
   * Dashboard de metricas Pre-PMF.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    $build = [];

    $verticals = [
      'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
      'jarabalex', 'serviciosconecta', 'andalucia_ei', 'jaraba_content_hub',
      'formacion', 'demo',
    ];

    $snapshots = [];
    try {
      $storage = $this->entityTypeManager()->getStorage('product_metric_snapshot');

      foreach ($verticals as $vertical) {
        $ids = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('vertical', $vertical)
          ->sort('snapshot_date', 'DESC')
          ->range(0, 1)
          ->execute();

        if ($ids !== []) {
          $snapshot = $storage->load(reset($ids));
          if ($snapshot instanceof \Drupal\Core\Entity\ContentEntityInterface) {
            $snapshots[$vertical] = $snapshot;
          }
        }
      }
    }
    catch (\Throwable) {
      // Dashboard renders gracefully without data.
    }

    $rows = [];
    foreach ($verticals as $vertical) {
      $snapshot = $snapshots[$vertical] ?? NULL;
      if ($snapshot instanceof \Drupal\Core\Entity\ContentEntityInterface) {
        $rows[] = [
          'vertical' => $vertical,
          'date' => $snapshot->get('snapshot_date')->value ?? '-',
          'activation' => round((float) ($snapshot->get('activation_rate')->value ?? 0) * 100, 1) . '%',
          'retention_d30' => round((float) ($snapshot->get('retention_d30_rate')->value ?? 0) * 100, 1) . '%',
          'nps' => (string) round((float) ($snapshot->get('nps_score')->value ?? 0), 1),
          'churn' => round((float) ($snapshot->get('monthly_churn_rate')->value ?? 0) * 100, 1) . '%',
          'kill' => ((bool) ($snapshot->get('kill_criteria_triggered')->value ?? FALSE)) ? 'SI' : 'No',
        ];
      }
      else {
        $rows[] = [
          'vertical' => $vertical,
          'date' => '-',
          'activation' => '-',
          'retention_d30' => '-',
          'nps' => '-',
          'churn' => '-',
          'kill' => '-',
        ];
      }
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Vertical'),
        $this->t('Fecha'),
        $this->t('Activacion'),
        $this->t('Retencion D30'),
        $this->t('NPS'),
        $this->t('Churn'),
        $this->t('Kill?'),
      ],
      '#rows' => array_map(function ($row) {
        return array_values($row);
      }, $rows),
      '#empty' => $this->t('No hay snapshots de metricas disponibles. Ejecuta la agregacion diaria.'),
    ];

    return $build;
  }

  /**
   * API para registrar respuesta NPS.
   *
   * POST /api/v1/analytics/nps
   * Body: {"score": 8, "vertical": "empleabilidad"}
   */
  public function npsApi(Request $request): JsonResponse {
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!is_array($data) || !isset($data['score']) || !isset($data['vertical'])) {
      return new JsonResponse(['error' => 'Missing required fields: score, vertical'], 400);
    }

    $score = (int) $data['score'];
    if ($score < 0 || $score > 10) {
      return new JsonResponse(['error' => 'Score must be between 0 and 10'], 400);
    }

    $vertical = (string) $data['vertical'];
    $validVerticals = [
      'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
      'jarabalex', 'serviciosconecta', 'andalucia_ei', 'jaraba_content_hub',
      'formacion', 'demo',
    ];

    if (!in_array($vertical, $validVerticals, TRUE)) {
      return new JsonResponse(['error' => 'Invalid vertical'], 400);
    }

    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      return new JsonResponse(['error' => 'Authentication required'], 403);
    }

    $this->npsSurvey->recordResponse($user, $score, $vertical);

    return new JsonResponse(['status' => 'ok', 'message' => 'NPS response recorded']);
  }

}
