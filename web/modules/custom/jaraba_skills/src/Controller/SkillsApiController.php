<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_skills\Entity\AiSkill;
use Drupal\jaraba_skills\Service\SkillManager;
use Drupal\jaraba_skills\Service\SkillEmbeddingService;
use Drupal\jaraba_skills\Service\SkillRevisionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * CONTROLLER API REST DE AI SKILLS
 *
 * PROPÓSITO:
 * Expone endpoints REST para resolución jerárquica de skills (resolve)
 * y búsqueda semántica por similitud vectorial (search).
 *
 * ENDPOINTS:
 * - GET/POST /api/v1/skills/resolve: Resolución jerárquica tradicional
 * - GET /api/v1/skills/search: Búsqueda semántica por query de texto
 * - GET /api/v1/skills/{ai_skill}/revisions: Historial de revisiones
 * - GET /api/v1/skills/revisions/compare: Comparar dos revisiones
 */
class SkillsApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SkillManager $skillManager,
        protected SkillEmbeddingService $embeddingService,
        protected SkillRevisionService $revisionService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_skills.skill_manager'),
            $container->get('jaraba_skills.skill_embedding'),
            $container->get('jaraba_skills.revision_service'),
        );
    }

    /**
     * Resuelve habilidades para un contexto dado.
     *
     * GET/POST /api/v1/skills/resolve
     * Query params o body:
     *   - vertical: ID de la vertical
     *   - agent_type: Tipo de agente
     *   - tenant_id: ID del tenant
     *   - format: 'json' (default) o 'xml' (prompt section)
     */
    public function resolve(Request $request): JsonResponse
    {
        $context = [];

        // Obtener parámetros de query o body.
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), TRUE) ?? [];
            $context = [
                'vertical' => $data['vertical'] ?? NULL,
                'agent_type' => $data['agent_type'] ?? NULL,
                'tenant_id' => $data['tenant_id'] ?? NULL,
            ];
            $format = $data['format'] ?? 'json';
        } else {
            $context = [
                'vertical' => $request->query->get('vertical'),
                'agent_type' => $request->query->get('agent_type'),
                'tenant_id' => $request->query->get('tenant_id'),
            ];
            $format = $request->query->get('format', 'json');
        }

        // Limpiar valores nulos.
        $context = array_filter($context);

        // Resolver skills.
        $skills = $this->skillManager->resolveSkills($context);

        if ($format === 'xml') {
            // Devolver sección XML para prompt.
            $promptSection = $this->skillManager->generatePromptSection($context);
            return new JsonResponse([
                'success' => TRUE,
                'count' => count($skills),
                'prompt_section' => $promptSection,
            ]);
        }

        // Devolver JSON con detalles.
        $skillsData = [];
        foreach ($skills as $skill) {
            /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
            $skillsData[] = [
                'id' => $skill->id(),
                'name' => $skill->label(),
                'type' => $skill->getSkillType(),
                'priority' => (int) ($skill->get('priority')->value ?? 0),
                'content' => $skill->getContent(),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'count' => count($skillsData),
            'context' => $context,
            'skills' => $skillsData,
        ]);
    }

    /**
     * Búsqueda semántica de skills.
     *
     * GET /api/v1/skills/search?q={query}
     *
     * PROPÓSITO:
     * Permite encontrar skills relevantes basándose en similitud semántica
     * con el texto de búsqueda, usando embeddings y Qdrant.
     *
     * Query params:
     *   - q: Texto de búsqueda (requerido)
     *   - vertical: Filtrar por vertical (opcional)
     *   - agent_type: Filtrar por tipo de agente (opcional)
     *   - tenant_id: Filtrar por tenant (opcional)
     *   - limit: Número máximo de resultados (default: 5)
     *   - threshold: Score mínimo de similitud 0-1 (default: 0.7)
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con skills encontradas y sus scores.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        // Validar query.
        if (empty(trim($query))) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se requiere el parámetro q con el texto de búsqueda.',
            ], 400);
        }

        // Construir contexto de filtrado.
        $context = [
            'vertical' => $request->query->get('vertical'),
            'agent_type' => $request->query->get('agent_type'),
            'tenant_id' => $request->query->get('tenant_id'),
        ];
        $context = array_filter($context);

        // Parámetros de búsqueda.
        $limit = (int) $request->query->get('limit', 5);
        $threshold = (float) $request->query->get('threshold', 0.7);

        // Limitar valores razonables.
        $limit = max(1, min(20, $limit));
        $threshold = max(0.0, min(1.0, $threshold));

        try {
            // Ejecutar búsqueda semántica.
            $results = $this->embeddingService->searchSimilar(
                $query,
                $context,
                $limit,
                $threshold
            );

            // Formatear respuesta.
            $skillsData = [];
            foreach ($results['skills'] as $skill) {
                /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
                $skillId = $skill->id();
                $skillsData[] = [
                    'id' => $skillId,
                    'name' => $skill->label(),
                    'type' => $skill->getSkillType(),
                    'priority' => (int) ($skill->get('priority')->value ?? 0),
                    'score' => round($results['scores'][$skillId] ?? 0, 4),
                    'content' => $skill->getContent(),
                ];
            }

            return new JsonResponse([
                'success' => TRUE,
                'query' => $query,
                'context' => $context,
                'count' => count($skillsData),
                'skills' => $skillsData,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Error en la búsqueda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener historial de revisiones de un skill.
     *
     * GET /api/v1/skills/{ai_skill}/revisions
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $ai_skill
     *   Entidad skill.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de revisiones en JSON.
     */
    public function getRevisions(AiSkill $ai_skill): JsonResponse
    {
        try {
            $revisions = $this->revisionService->getRevisions((int) $ai_skill->id());

            $revisionsData = [];
            foreach ($revisions as $revision) {
                /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $revision */
                $revisionsData[] = [
                    'id' => $revision->id(),
                    'skill_id' => $ai_skill->id(),
                    'revision_number' => $revision->getRevisionNumber(),
                    'name' => $revision->getName(),
                    'change_summary' => $revision->getChangeSummary(),
                    'created' => $revision->getCreatedTime(),
                    'changed_by' => $revision->get('changed_by')->entity
                        ? $revision->get('changed_by')->entity->getDisplayName()
                        : NULL,
                ];
            }

            return new JsonResponse([
                'success' => TRUE,
                'skill_id' => $ai_skill->id(),
                'skill_name' => $ai_skill->label(),
                'count' => count($revisionsData),
                'revisions' => $revisionsData,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Error obteniendo revisiones: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Comparar dos revisiones de un skill.
     *
     * GET /api/v1/skills/revisions/compare?revision_a={id}&revision_b={id}
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Objeto request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Diferencias entre revisiones.
     */
    public function compareRevisions(Request $request): JsonResponse
    {
        $revisionAId = (int) $request->query->get('revision_a', 0);
        $revisionBId = (int) $request->query->get('revision_b', 0);

        if (!$revisionAId || !$revisionBId) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se requieren parámetros revision_a y revision_b.',
            ], 400);
        }

        try {
            $diff = $this->revisionService->compareRevisions($revisionAId, $revisionBId);

            return new JsonResponse([
                'success' => TRUE,
                'revision_a' => $revisionAId,
                'revision_b' => $revisionBId,
                'differences' => $diff,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Error comparando revisiones: ' . $e->getMessage(),
            ], 500);
        }
    }

}
