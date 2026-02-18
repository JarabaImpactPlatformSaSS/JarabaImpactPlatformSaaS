<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\jaraba_rag\Service\JarabaRagService;
use Drupal\jaraba_rag\Service\KbIndexerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

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
        protected RateLimiterService $rateLimiter,
        protected readonly TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
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
            $container->get('ecosistema_jaraba_core.rate_limiter'),
            $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
        );
    }

    /**
     * Endpoint para consultas RAG.
     *
     * POST /api/v1/jaraba-rag/query (AUDIT-CONS-N07)
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
            // AI-01: Rate limiting para proteger contra abuso y costes excesivos.
            $userId = (string) $this->currentUser()->id();
            $rateLimitResult = $this->rateLimiter->consume($userId, 'ai');
            if (!$rateLimitResult['allowed']) {
                $response = new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Demasiadas solicitudes. Por favor, inténtalo de nuevo más tarde.',
                ], 429);
                foreach ($this->rateLimiter->getHeaders($rateLimitResult) as $header => $value) {
                    $response->headers->set($header, $value);
                }
                return $response;
            }

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
     * SEC-09: Verifica que la entidad pertenece al tenant del usuario
     * antes de permitir la reindexación.
     *
     * POST /api/v1/jaraba-rag/reindex/{entity_type}/{entity_id} (AUDIT-CONS-N07)
     */
    public function reindex(string $entity_type, int $entity_id): JsonResponse
    {
        try {
            // Validar entity_type contra whitelist de tipos permitidos.
            $allowedTypes = ['node', 'commerce_product', 'entrepreneur_profile'];
            if (!in_array($entity_type, $allowedTypes, TRUE)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Tipo de entidad no permitido para reindexación.',
                ], 400);
            }

            // Cargar entidad.
            $storage = $this->entityTypeManager()->getStorage($entity_type);
            $entity = $storage->load($entity_id);

            if (!$entity) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Entidad no encontrada.',
                ], 404);
            }

            // SEC-09: Verificar tenant ownership si el usuario no es admin global.
            if (!$this->currentUser()->hasPermission('administer site configuration')) {
                $entityTenantId = NULL;

                // Obtener tenant_id de la entidad (campo field_tenant).
                if ($entity->hasField('field_tenant') && !$entity->get('field_tenant')->isEmpty()) {
                    $entityTenantId = (string) $entity->get('field_tenant')->target_id;
                }

                // Obtener tenant del usuario actual via TenantContextService.
                try {
                    $tenantContext = $this->tenantContext;
                    $currentTenant = $tenantContext->getCurrentTenant();
                    $userTenantId = $currentTenant ? (string) $currentTenant->id() : NULL;
                } catch (\Exception $e) {
                    $userTenantId = NULL;
                }

                // Verificar que coinciden.
                if ($entityTenantId && $userTenantId && $entityTenantId !== $userTenantId) {
                    $this->getLogger('jaraba_rag')->warning('SEC-09: Intento de reindex cross-tenant denegado. User tenant: @user, Entity tenant: @entity', [
                        '@user' => $userTenantId,
                        '@entity' => $entityTenantId,
                    ]);

                    return new JsonResponse([
                        'success' => FALSE,
                        'error' => 'No tienes permisos para reindexar esta entidad.',
                    ], 403);
                }
            }

            // Reindexar.
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
                'error' => 'Error interno del servidor.',
            ], 500);
        }
    }

}
