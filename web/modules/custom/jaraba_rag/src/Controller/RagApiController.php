<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_rag\Service\JarabaRagService;
use Drupal\jaraba_rag\Service\KbIndexerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controlador API para el módulo Jaraba RAG.
 *
 * Expone endpoints para:
 * - Consultas RAG desde los Copilots
 * - Reindexación de entidades
 */
class RagApiController extends ControllerBase
{

    /**
     * Constructs a RagApiController object.
     */
    public function __construct(
        protected JarabaRagService $ragService,
        protected KbIndexerService $indexerService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_rag.rag_service'),
            $container->get('jaraba_rag.indexer'),
        );
    }

    /**
     * Endpoint para consultas RAG.
     *
     * POST /api/jaraba-rag/query
     *
     * Body:
     * {
     *   "query": "¿Tenéis algo para ensaladas?",
     *   "options": {
     *     "top_k": 5,
     *     "include_sources": true
     *   }
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "response": "Te recomiendo...",
     *     "sources": [...],
     *     "confidence": 0.89,
     *     "classification": "ANSWERED_FULL"
     *   }
     * }
     */
    public function query(Request $request): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), TRUE);

            if (empty($content['query'])) {
                throw new BadRequestHttpException('El campo "query" es obligatorio.');
            }

            $query = trim($content['query']);
            $options = $content['options'] ?? [];

            // Validar longitud
            if (strlen($query) < 2) {
                throw new BadRequestHttpException('La query debe tener al menos 2 caracteres.');
            }

            if (strlen($query) > 500) {
                throw new BadRequestHttpException('La query no puede exceder 500 caracteres.');
            }

            // Ejecutar consulta RAG
            $result = $this->ragService->query($query, $options);

            return new JsonResponse([
                'success' => TRUE,
                'data' => $result,
            ]);

        } catch (BadRequestHttpException $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->getLogger('jaraba_rag')->error('Error en API query: @message', [
                '@message' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Error interno del servidor.',
            ], 500);
        }
    }

    /**
     * Endpoint para reindexar una entidad.
     *
     * POST /api/jaraba-rag/reindex/{entity_type}/{entity_id}
     */
    public function reindex(string $entity_type, int $entity_id): JsonResponse
    {
        try {
            // Cargar entidad
            $storage = $this->entityTypeManager()->getStorage($entity_type);
            $entity = $storage->load($entity_id);

            if (!$entity) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Entidad no encontrada.',
                ], 404);
            }

            // Reindexar
            $this->indexerService->indexEntity($entity);

            return new JsonResponse([
                'success' => TRUE,
                'message' => "Entidad {$entity_type}/{$entity_id} reindexada.",
            ]);

        } catch (\Exception $e) {
            $this->getLogger('jaraba_rag')->error('Error reindexando: @message', [
                '@message' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
