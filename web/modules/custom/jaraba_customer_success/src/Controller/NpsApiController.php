<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\jaraba_customer_success\Service\NpsSurveyService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controlador REST API para encuestas NPS.
 *
 * PROPOSITO:
 * Endpoints JSON para obtener encuesta activa, enviar respuestas
 * y consultar resultados agregados del NPS.
 *
 * DIRECTRICES:
 * - Todos los endpoints devuelven JsonResponse.
 * - Validacion estricta de score (0-10).
 * - Logging de errores y acciones relevantes.
 */
class NpsApiController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected NpsSurveyService $npsSurveyService,
    protected LoggerInterface $logger,
    protected TenantContextService $tenantContext,
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
      $container->get('logger.channel.jaraba_customer_success'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * GET /api/v1/cs/nps/survey -- Obtiene la encuesta activa.
   *
   * LOGICA:
   * - Devuelve la configuracion de la encuesta NPS activa.
   * - Incluye pregunta, escala y estado de cooldown por tenant.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos de la encuesta activa.
   */
  public function getActiveSurvey(Request $request): JsonResponse {
    try {
      $tenant_id = $request->query->get('tenant_id', '');

      $can_send = TRUE;
      if ($tenant_id) {
        $can_send = $this->npsSurveyService->canSendSurvey($tenant_id);
      }

      return new JsonResponse([
        'data' => [
          'active' => TRUE,
          'can_send' => $can_send,
          'question' => (string) $this->t('How likely are you to recommend us to a friend or colleague?'),
          'scale' => [
            'min' => 0,
            'max' => 10,
            'labels' => [
              'detractors' => (string) $this->t('Not at all likely'),
              'passives' => (string) $this->t('Neutral'),
              'promoters' => (string) $this->t('Extremely likely'),
            ],
          ],
          'ranges' => [
            'detractors' => [0, 6],
            'passives' => [7, 8],
            'promoters' => [9, 10],
          ],
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching active NPS survey: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Failed to retrieve active survey.',
      ], 500);
    }
  }

  /**
   * POST /api/v1/cs/nps/submit -- Enviar respuesta NPS.
   *
   * LOGICA:
   * 1. Valida que el body contenga tenant_id y score.
   * 2. Valida que score este en rango 0-10.
   * 3. Guarda la respuesta via npsSurveyService.
   * 4. Marca la encuesta como enviada (cooldown).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request con JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con confirmacion o error.
   */
  public function submitResponse(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (empty($content) || !is_array($content)) {
        return new JsonResponse([
          'error' => 'Invalid JSON body.',
        ], 400);
      }

      $tenant_id = $this->tenantContext->getCurrentTenantId() ?? ($content['tenant_id'] ?? '');
      $score = $content['score'] ?? NULL;
      $comment = $content['comment'] ?? '';

      if (empty($tenant_id)) {
        return new JsonResponse([
          'error' => 'tenant_id is required.',
        ], 400);
      }

      if ($score === NULL || !is_numeric($score)) {
        return new JsonResponse([
          'error' => 'score is required and must be numeric.',
        ], 400);
      }

      $score = (int) $score;
      if ($score < 0 || $score > 10) {
        return new JsonResponse([
          'error' => 'score must be between 0 and 10.',
        ], 400);
      }

      // Check cooldown.
      if (!$this->npsSurveyService->canSendSurvey($tenant_id)) {
        return new JsonResponse([
          'error' => 'Survey cooldown period has not elapsed for this tenant.',
        ], 429);
      }

      // Save the response.
      $this->npsSurveyService->collectResponse($tenant_id, $score, (string) $comment);
      $this->npsSurveyService->markSurveySent($tenant_id);

      // Determine response category.
      $category = 'detractor';
      if ($score >= 9) {
        $category = 'promoter';
      }
      elseif ($score >= 7) {
        $category = 'passive';
      }

      $this->logger->info('NPS response collected: tenant=@tenant score=@score category=@cat', [
        '@tenant' => $tenant_id,
        '@score' => $score,
        '@cat' => $category,
      ]);

      return new JsonResponse([
        'data' => [
          'success' => TRUE,
          'tenant_id' => $tenant_id,
          'score' => $score,
          'category' => $category,
          'message' => (string) $this->t('Thank you for your feedback!'),
        ],
      ], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error submitting NPS response: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Failed to submit NPS response.',
      ], 500);
    }
  }

  /**
   * GET /api/v1/cs/nps/results -- Obtener resultados NPS.
   *
   * LOGICA:
   * 1. Recibe tenant_id opcional (si no, agrega todos).
   * 2. Calcula NPS score, distribucion y tendencia.
   * 3. Devuelve datos para graficos del dashboard.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con resultados NPS.
   */
  public function getResults(Request $request): JsonResponse {
    try {
      $tenant_id = $request->query->get('tenant_id', '');

      if (empty($tenant_id)) {
        // Aggregate from all tenants with health scores.
        $storage = $this->entityTypeManager->getStorage('customer_health');
        $ids = $storage->getQuery()
          ->accessCheck(TRUE)
          ->sort('calculated_at', 'DESC')
          ->range(0, 200)
          ->execute();
        $entities = $ids ? $storage->loadMultiple($ids) : [];

        $tenant_ids = [];
        foreach ($entities as $entity) {
          $tid = $entity->getTenantId();
          if ($tid && !in_array($tid, $tenant_ids, TRUE)) {
            $tenant_ids[] = $tid;
          }
        }

        $total_score = 0;
        $tenants_with_score = 0;
        $all_trends = [];

        foreach ($tenant_ids as $tid) {
          $score = $this->npsSurveyService->getScore($tid);
          if ($score !== NULL) {
            $total_score += $score;
            $tenants_with_score++;
          }

          $trend = $this->npsSurveyService->getTrend($tid, 12);
          foreach ($trend as $period) {
            $month = $period['month'];
            if (!isset($all_trends[$month])) {
              $all_trends[$month] = ['total_score' => 0, 'total_responses' => 0];
            }
            $all_trends[$month]['total_score'] += $period['score'] * $period['responses'];
            $all_trends[$month]['total_responses'] += $period['responses'];
          }
        }

        $nps_score = $tenants_with_score > 0
          ? (int) round($total_score / $tenants_with_score)
          : NULL;

        ksort($all_trends);
        $trend_data = [];
        foreach ($all_trends as $month => $data) {
          $trend_data[] = [
            'month' => $month,
            'score' => $data['total_responses'] > 0
              ? (int) round($data['total_score'] / $data['total_responses'])
              : 0,
            'responses' => $data['total_responses'],
          ];
        }

        return new JsonResponse([
          'data' => [
            'nps_score' => $nps_score,
            'tenants_surveyed' => $tenants_with_score,
            'trend' => $trend_data,
          ],
        ]);
      }

      // Single tenant results.
      $score = $this->npsSurveyService->getScore($tenant_id);
      $trend = $this->npsSurveyService->getTrend($tenant_id, 12);
      $satisfaction = $this->npsSurveyService->getSatisfactionScore($tenant_id);

      return new JsonResponse([
        'data' => [
          'tenant_id' => $tenant_id,
          'nps_score' => $score,
          'satisfaction_score' => $satisfaction,
          'trend' => $trend,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching NPS results: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Failed to retrieve NPS results.',
      ], 500);
    }
  }

}
