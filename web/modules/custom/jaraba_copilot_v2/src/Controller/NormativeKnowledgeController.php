<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\NormativeKnowledgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller para busqueda en base de conocimiento normativo.
 */
class NormativeKnowledgeController extends ControllerBase {

  protected NormativeKnowledgeService $knowledge;

  /**
   * Constructor.
   */
  public function __construct(NormativeKnowledgeService $knowledge) {
    $this->knowledge = $knowledge;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_copilot_v2.normative_knowledge'),
    );
  }

  /**
   * GET /api/v1/knowledge/search - Busqueda full-text en base normativa.
   */
  public function search(Request $request): JsonResponse {
    try {
      $query = $request->query->get('q', '');
      $domain = $request->query->get('domain');

      if (empty($query)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El parametro q es obligatorio.',
        ], 400);
      }

      if (!$this->knowledge->tableExists()) {
        return new JsonResponse([
          'success' => TRUE,
          'data' => [],
          'count' => 0,
          'message' => 'Base de conocimiento normativo no inicializada.',
        ]);
      }

      $results = [];

      if ($domain) {
        $topics = $this->knowledge->detectTopics($query, $domain);
        foreach ($topics as $topic) {
          $topicResults = $this->knowledge->getKnowledge($domain, $topic);
          $results = array_merge($results, $topicResults);
        }
      }
      else {
        // Buscar en ambos dominios
        foreach (['TAX', 'SOCIAL_SECURITY'] as $dom) {
          $topics = $this->knowledge->detectTopics($query, $dom);
          foreach ($topics as $topic) {
            $topicResults = $this->knowledge->getKnowledge($dom, $topic);
            $results = array_merge($results, $topicResults);
          }
        }
      }

      // Eliminar duplicados
      $uniqueResults = [];
      $seen = [];
      foreach ($results as $result) {
        $key = $result['content_key'] ?? '';
        if (!isset($seen[$key])) {
          $uniqueResults[] = $result;
          $seen[$key] = TRUE;
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $uniqueResults,
        'count' => count($uniqueResults),
        'query' => $query,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_copilot_v2')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

}
