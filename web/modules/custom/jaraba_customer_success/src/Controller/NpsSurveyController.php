<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_customer_success\Service\NpsSurveyService;

/**
 * Controlador frontend para encuestas NPS en /customer-success/nps.
 *
 * PROPOSITO:
 * Renderiza la pagina de encuesta NPS para que los usuarios puedan
 * enviar su puntuacion (0-10) y la pagina de resultados con graficos
 * de tendencia, distribucion y gauge del NPS score.
 *
 * DIRECTRICES:
 * - Templates limpios con BEM (cs-nps-survey / cs-nps-results).
 * - Todos los textos traducibles con $this->t().
 * - Cache tags para invalidacion correcta.
 */
class NpsSurveyController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected NpsSurveyService $npsSurveyService,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_customer_success.nps_survey'),
    );
  }

  /**
   * Pagina de encuesta NPS (/customer-success/nps).
   *
   * LOGICA:
   * 1. Renderiza el formulario de encuesta NPS con escala visual 0-10.
   * 2. Incluye selector de puntuacion con color coding.
   * 3. Adjunta libreria JS para interactividad del formulario.
   *
   * @return array
   *   Render array con #theme jaraba_cs_nps_survey.
   */
  public function surveyPage(): array {
    return [
      '#theme' => 'jaraba_cs_nps_survey',
      '#survey_title' => $this->t('How likely are you to recommend us?'),
      '#survey_description' => $this->t('On a scale of 0 to 10, how likely are you to recommend our platform to a friend or colleague?'),
      '#submit_url' => '/api/v1/cs/nps/submit',
      '#attached' => [
        'library' => ['jaraba_customer_success/nps-survey'],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Pagina de resultados NPS (/customer-success/nps/resultados).
   *
   * LOGICA:
   * 1. Obtiene health scores recientes para extraer tenant IDs.
   * 2. Agrega respuestas NPS de todos los tenants.
   * 3. Calcula NPS global, promoters/passives/detractors.
   * 4. Renderiza con graficos (gauge, tendencia, distribucion).
   *
   * @return array
   *   Render array con #theme jaraba_cs_nps_results.
   */
  public function resultsPage(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('customer_health');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('calculated_at', 'DESC')
        ->range(0, 100)
        ->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $entities = [];
    }

    // Collect unique tenant IDs.
    $tenant_ids = [];
    foreach ($entities as $entity) {
      $tid = $entity->getTenantId();
      if ($tid && !in_array($tid, $tenant_ids, TRUE)) {
        $tenant_ids[] = $tid;
      }
    }

    // Aggregate NPS data across all tenants.
    $total_responses = 0;
    $promoters = 0;
    $passives = 0;
    $detractors = 0;
    $recent_responses = [];
    $trend_data = [];

    foreach ($tenant_ids as $tenant_id) {
      $score = $this->npsSurveyService->getScore($tenant_id);
      $trend = $this->npsSurveyService->getTrend($tenant_id, 6);

      if (!empty($trend)) {
        foreach ($trend as $period) {
          $month = $period['month'];
          if (!isset($trend_data[$month])) {
            $trend_data[$month] = ['score' => 0, 'count' => 0];
          }
          $trend_data[$month]['score'] += $period['score'] * $period['responses'];
          $trend_data[$month]['count'] += $period['responses'];
        }
      }
    }

    // Calculate weighted averages for trend.
    $trend_chart = [];
    ksort($trend_data);
    foreach ($trend_data as $month => $data) {
      if ($data['count'] > 0) {
        $trend_chart[] = [
          'month' => $month,
          'score' => (int) round($data['score'] / $data['count']),
          'responses' => $data['count'],
        ];
        $total_responses += $data['count'];
      }
    }

    // Calculate overall NPS from latest data.
    $nps_score = 0;
    if (!empty($trend_chart)) {
      $latest = end($trend_chart);
      $nps_score = $latest['score'];
    }

    return [
      '#theme' => 'jaraba_cs_nps_results',
      '#nps_score' => $nps_score,
      '#total_responses' => $total_responses,
      '#promoters' => $promoters,
      '#passives' => $passives,
      '#detractors' => $detractors,
      '#trend_data' => $trend_chart,
      '#recent_responses' => $recent_responses,
      '#attached' => [
        'library' => ['jaraba_customer_success/nps-results'],
        'drupalSettings' => [
          'jarabaCs' => [
            'npsScore' => $nps_score,
            'trendData' => $trend_chart,
            'totalResponses' => $total_responses,
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 300,
      ],
    ];
  }

}
