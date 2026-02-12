<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\jaraba_tenant_knowledge\Service\TenantKnowledgeManager;
use Drupal\jaraba_tenant_knowledge\Service\KnowledgeIndexerService;

/**
 * CONTROLLER API REST DE KNOWLEDGE TRAINING
 *
 * PROPÓSITO:
 * Expone endpoints REST para acceso al contexto de conocimiento
 * del tenant desde otros servicios y agentes IA.
 *
 * ENDPOINTS:
 * - GET /api/v1/knowledge/context: Obtiene contexto XML para prompt
 * - GET /api/v1/knowledge/search: Búsqueda semántica de conocimiento
 */
class KnowledgeApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantKnowledgeManager $knowledgeManager,
        protected KnowledgeIndexerService $indexer,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_tenant_knowledge.manager'),
            $container->get('jaraba_tenant_knowledge.indexer'),
        );
    }

    /**
     * Obtiene el contexto de conocimiento del tenant para prompts.
     *
     * GET /api/v1/knowledge/context
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con el contexto XML.
     */
    public function getContext(): JsonResponse
    {
        try {
            $context = $this->knowledgeManager->generatePromptContext();
            $config = $this->knowledgeManager->getConfig();

            return new JsonResponse([
                'success' => TRUE,
                'has_config' => $config !== NULL,
                'completeness' => $config ? $config->calculateCompletenessScore() : 0,
                'context' => $context,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca conocimiento similar usando búsqueda semántica.
     *
     * GET /api/v1/knowledge/search?q={query}&type=faq&limit=5
     *
     * PARÁMETROS:
     * - q: Query de búsqueda (obligatorio)
     * - type: Tipo de conocimiento (faq, policy, document)
     * - category: Filtro de categoría
     * - limit: Número de resultados (default 5, max 20)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultados de búsqueda ordenados por score.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $type = $request->query->get('type');
        $category = $request->query->get('category');
        $limit = min(20, max(1, (int) $request->query->get('limit', 5)));

        if (empty($query)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'El parámetro q es obligatorio.',
            ], 400);
        }

        // Obtener tenant ID actual.
        $tenantId = $this->getCurrentTenantId();
        if (!$tenantId) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'No se pudo determinar el tenant.',
            ], 403);
        }

        try {
            $results = $this->indexer->searchKnowledge($query, $tenantId, [
                'type' => $type,
                'category' => $category,
                'limit' => $limit,
                'threshold' => 0.65,
            ]);

            return new JsonResponse([
                'success' => TRUE,
                'query' => $query,
                'count' => count($results),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        // Fallback: usar el grupo del usuario actual.
        $user = $this->currentUser();
        // Lógica de fallback simplificada.
        return NULL;
    }

    /**
     * Procesa una pregunta de prueba del Copiloto.
     *
     * POST /api/v1/knowledge/test
     * Body: { "question": "¿Cuál es el horario?" }
     *
     * PROPÓSITO:
     * Permite al tenant probar cómo respondería el Copiloto
     * usando el conocimiento que ha configurado.
     *
     * FLUJO:
     * 1. Recibe pregunta
     * 2. Busca conocimiento relevante (FAQs, policies, docs)
     * 3. Genera respuesta usando contexto encontrado
     * 4. Devuelve respuesta + fuentes usadas
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP con la pregunta.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta del Copiloto con fuentes.
     */
    public function test(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $question = $data['question'] ?? '';

        if (empty($question)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'El campo question es obligatorio.',
            ], 400);
        }

        $tenantId = $this->getCurrentTenantId();
        if (!$tenantId) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'No se pudo determinar el tenant.',
            ], 403);
        }

        try {
            // Buscar conocimiento relevante.
            $results = $this->indexer->searchKnowledge($question, $tenantId, [
                'limit' => 5,
                'threshold' => 0.6,
            ]);

            // Formatear fuentes.
            $sources = [];
            $contextParts = [];

            foreach ($results as $result) {
                $type = $result['type'] ?? 'unknown';
                $sources[] = [
                    'type' => $type,
                    'label' => $this->getSourceLabel($result),
                    'score' => round($result['score'] ?? 0, 2),
                ];

                // Construir contexto para respuesta.
                $contextParts[] = $this->formatContextPart($result);
            }

            // Generar respuesta basada en el contexto.
            $answer = $this->generateTestAnswer($question, $contextParts, $results);

            return new JsonResponse([
                'success' => TRUE,
                'question' => $question,
                'answer' => $answer,
                'sources' => $sources,
                'context_used' => count($results),
            ]);

        } catch (\Exception $e) {
            \Drupal::logger('jaraba_tenant_knowledge')->error(
                'Error en test console: @error',
                ['@error' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Error procesando la pregunta.',
            ], 500);
        }
    }

    /**
     * Genera una respuesta de prueba basada en el contexto.
     */
    protected function generateTestAnswer(string $question, array $contextParts, array $results): string
    {
        if (empty($results)) {
            return $this->t('No encontré información específica sobre eso en mi base de conocimiento. ¿Podrías reformular la pregunta o añadir más contenido al entrenamiento?')->render();
        }

        // Si hay un FAQ muy relevante (score > 0.8), usar su respuesta directamente.
        foreach ($results as $result) {
            if (($result['type'] ?? '') === 'faq' && ($result['score'] ?? 0) > 0.8) {
                return $result['content'] ?? $this->t('Encontré una FAQ relevante pero no pude extraer su contenido.')->render();
            }
        }

        // Construir respuesta sintética basada en el mejor resultado.
        $bestResult = $results[0] ?? [];
        $content = $bestResult['content'] ?? '';

        if (!empty($content)) {
            // Truncar si es muy largo.
            if (strlen($content) > 500) {
                $content = substr($content, 0, 497) . '...';
            }
            return $content;
        }

        return $this->t('Encontré información relacionada pero necesito más detalles en mi entrenamiento para responder con precisión.')->render();
    }

    /**
     * Obtiene etiqueta legible para una fuente.
     */
    protected function getSourceLabel(array $result): string
    {
        $type = $result['type'] ?? 'unknown';
        $title = $result['title'] ?? '';

        if (!empty($title)) {
            return $title;
        }

        return match ($type) {
            'faq' => $this->t('FAQ')->render(),
            'policy' => $this->t('Política')->render(),
            'document', 'document_chunk' => $this->t('Documento')->render(),
            'product' => $this->t('Producto')->render(),
            default => $this->t('Contenido')->render(),
        };
    }

    /**
     * Formatea una parte del contexto para el prompt.
     */
    protected function formatContextPart(array $result): string
    {
        $type = $result['type'] ?? 'unknown';
        $content = $result['content'] ?? '';

        return sprintf('[%s] %s', strtoupper($type), $content);
    }

}

