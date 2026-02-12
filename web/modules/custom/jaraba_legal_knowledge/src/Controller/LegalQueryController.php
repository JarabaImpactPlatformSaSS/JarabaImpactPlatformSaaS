<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_knowledge\Service\LegalQueryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de consultas legales (frontend + API).
 *
 * PROPOSITO:
 * Renderiza la pagina principal de consultas legales para el usuario final
 * (no admin) y expone el endpoint API para procesar consultas RAG contra
 * la base de conocimiento normativo.
 *
 * FUNCIONALIDADES:
 * - Pagina frontend con panel de consulta, historial y alertas
 * - API de consulta legal con respuesta RAG, citas y disclaimer
 * - Multi-tenant: datos filtrados por tenant del usuario actual
 *
 * RUTAS:
 * - GET /legal -> queryPage()
 * - POST /api/v1/legal/query -> apiQuery()
 *
 * @package Drupal\jaraba_legal_knowledge\Controller
 */
class LegalQueryController extends ControllerBase {

  /**
   * El servicio de consulta legal RAG.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\LegalQueryService
   */
  protected LegalQueryService $legalQuery;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->legalQuery = $container->get('jaraba_legal_knowledge.legal_query');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Renderiza la pagina principal de consultas legales.
   *
   * Pagina frontend (no admin) que muestra el panel de consulta,
   * historial de consultas recientes, alertas normativas activas
   * y acceso a las calculadoras fiscales.
   *
   * @return array
   *   Render array con #theme => 'page__legal'.
   */
  public function queryPage(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;

    return [
      '#theme' => 'page__legal',
      '#tenant_id' => $tenant_id,
      '#labels' => [
        'title' => $this->t('Consulta Legal'),
        'subtitle' => $this->t('Base de conocimiento normativo con inteligencia artificial'),
        'query_placeholder' => $this->t('Escriba su consulta legal aqui...'),
        'submit' => $this->t('Consultar'),
        'recent_queries' => $this->t('Consultas recientes'),
        'alerts' => $this->t('Alertas normativas'),
        'calculators' => $this->t('Calculadoras fiscales'),
        'disclaimer' => $this->t('La informacion proporcionada tiene caracter orientativo y no constituye asesoramiento legal profesional.'),
        'no_results' => $this->t('No se encontraron resultados para su consulta.'),
        'loading' => $this->t('Procesando consulta...'),
      ],
      '#urls' => [
        'api_query' => Url::fromRoute('jaraba_legal_knowledge.api.query')->toString(),
        'calculators' => Url::fromRoute('jaraba_legal_knowledge.calculators_page')->toString(),
        'settings' => Url::fromRoute('jaraba_legal_knowledge.settings')->toString(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_legal_knowledge/legal-dashboard',
        ],
        'drupalSettings' => [
          'jarabaLegalKnowledge' => [
            'tenantId' => $tenant_id,
            'apiQueryUrl' => Url::fromRoute('jaraba_legal_knowledge.api.query')->toString(),
            'apiCalculatorsIrpfUrl' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.irpf')->toString(),
            'apiCalculatorsIvaUrl' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.iva')->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Endpoint API: Procesa una consulta legal mediante RAG pipeline.
   *
   * POST /api/v1/legal/query
   *
   * Recibe una pregunta en lenguaje natural, busca normas relevantes
   * en el vector store (Qdrant), genera una respuesta con citas
   * y devuelve un disclaimer segun el nivel configurado.
   *
   * Body JSON esperado:
   * {
   *   "question": "Pregunta legal en lenguaje natural",
   *   "filters": {
   *     "scope": "estatal|autonomico|local",
   *     "subject_areas": ["laboral", "fiscal", "mercantil"]
   *   }
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {answer, citations, disclaimer, confidence}}.
   */
  public function apiQuery(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Decodificar el body JSON.
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || empty($content['question'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere el campo question.'),
        ], 400);
      }

      $question = mb_substr(trim($content['question']), 0, 2000);

      if (mb_strlen($question) < 5) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('La pregunta es demasiado corta. Proporcione al menos 5 caracteres.'),
        ], 400);
      }

      // Parsear filtros opcionales.
      $filters = [];
      if (!empty($content['filters']) && is_array($content['filters'])) {
        // Validar scope.
        $allowed_scopes = ['estatal', 'autonomico', 'local'];
        $scope = $content['filters']['scope'] ?? NULL;
        if ($scope !== NULL && in_array($scope, $allowed_scopes, TRUE)) {
          $filters['scope'] = $scope;
        }

        // Validar subject_areas.
        if (!empty($content['filters']['subject_areas']) && is_array($content['filters']['subject_areas'])) {
          $allowed_areas = ['laboral', 'fiscal', 'mercantil', 'civil', 'penal', 'administrativo', 'constitucional', 'seguridad_social'];
          $filters['subject_areas'] = array_values(array_intersect(
            $content['filters']['subject_areas'],
            $allowed_areas
          ));
        }
      }

      // Procesar la consulta via RAG pipeline.
      $result = $this->legalQuery->processQuery($question, $tenant_id, $filters);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'answer' => $result['answer'] ?? '',
          'citations' => $result['citations'] ?? [],
          'disclaimer' => $result['disclaimer'] ?? '',
          'confidence' => $result['confidence'] ?? 0.0,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_legal_knowledge')->error('Error al procesar consulta legal: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al procesar la consulta legal.'),
      ], 500);
    }
  }

}
