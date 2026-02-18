<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para el Hub Documental B2B — Vista Productor.
 *
 * Endpoints autenticados para gestión de partners, documentos y analytics.
 * Sprint AC6-2, Doc 82.
 */
class PartnerHubApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Obtiene el servicio de documentos partner.
     *
     * @return \Drupal\jaraba_agroconecta_core\Service\PartnerDocumentService
     *   Servicio del hub documental.
     */
    protected function partnerService()
    {
        return \Drupal::service('jaraba_agroconecta_core.partner_document');
    }

    /**
     * Crea una nueva relación con un partner.
     *
     * POST /api/v1/hub/partners
     */
    public function createPartner(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['partner_email']) || empty($data['partner_name']) || empty($data['partner_type'])) {
            return new JsonResponse([
                'error' => $this->t('Los campos partner_email, partner_name y partner_type son obligatorios.'),
            ], 400);
        }

        $validTypes = ['distribuidor', 'exportador', 'comercial', 'horeca', 'mayorista', 'importador'];
        if (!in_array($data['partner_type'], $validTypes, TRUE)) {
            return new JsonResponse([
                'error' => $this->t('Tipo de partner no válido.'),
            ], 400);
        }

        $validLevels = ['basico', 'verificado', 'premium'];
        $accessLevel = $data['access_level'] ?? 'basico';
        if (!in_array($accessLevel, $validLevels, TRUE)) {
            return new JsonResponse([
                'error' => $this->t('Nivel de acceso no válido.'),
            ], 400);
        }

        $result = $this->partnerService()->createRelationship(
            (int) ($data['producer_id'] ?? $this->currentUser()->id()),
            $data['partner_email'],
            $data['partner_name'],
            $data['partner_type'],
            $accessLevel,
            $this->tenantContext->getCurrentTenantId() ?? (int) ($data['tenant_id'] ?? 1)
        );

        if (isset($result['error'])) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => $result], 409);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
    }

    /**
     * Lista partners del productor.
     *
     * GET /api/v1/hub/partners
     */
    public function listPartners(Request $request): JsonResponse
    {
        $producerId = (int) $request->query->get('producer_id', $this->currentUser()->id());
        $page = (int) $request->query->get('page', 0);
        $limit = min((int) $request->query->get('limit', 20), 100);

        $result = $this->partnerService()->getPartnersByProducer($producerId, $page, $limit);
        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Actualiza una relación partner.
     *
     * PATCH /api/v1/hub/partners/{uuid}
     */
    public function updatePartner(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $storage = \Drupal::entityTypeManager()->getStorage('partner_relationship');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        if (empty($entities)) {
            return new JsonResponse([
                'error' => $this->t('Relación partner no encontrada.'),
            ], 404);
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $entity = reset($entities);

        $updatable = ['access_level', 'status', 'notes', 'allowed_products', 'allowed_categories'];
        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if (in_array($field, ['allowed_products', 'allowed_categories']) && is_array($value)) {
                    $value = json_encode($value);
                }
                $entity->set($field, $value);
            }
        }
        $entity->save();

        return new JsonResponse([
            'uuid' => $entity->uuid(),
            'partner_name' => $entity->getPartnerName(),
            'access_level' => $entity->getAccessLevel(),
            'status' => $entity->getStatus(),
            'updated' => TRUE,
        ]);
    }

    /**
     * Revoca acceso de un partner.
     *
     * DELETE /api/v1/hub/partners/{uuid}
     */
    public function revokePartner(string $uuid): JsonResponse
    {
        $revoked = $this->partnerService()->revokeRelationship($uuid);

        if (!$revoked) {
            return new JsonResponse([
                'error' => $this->t('Relación partner no encontrada.'),
            ], 404);
        }

        return new JsonResponse(['revoked' => TRUE, 'uuid' => $uuid]);
    }

    /**
     * Sube un nuevo documento.
     *
     * POST /api/v1/hub/documents
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['title']) || empty($data['document_type']) || empty($data['file_id'])) {
            return new JsonResponse([
                'error' => $this->t('Los campos title, document_type y file_id son obligatorios.'),
            ], 400);
        }

        $validTypes = ['ficha_tecnica', 'analitica', 'certificacion', 'marketing', 'especificacion', 'catalogo', 'otro'];
        if (!in_array($data['document_type'], $validTypes, TRUE)) {
            return new JsonResponse([
                'error' => $this->t('Tipo de documento no válido.'),
            ], 400);
        }

        $data['producer_id'] = $data['producer_id'] ?? $this->currentUser()->id();
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? (int) ($data['tenant_id'] ?? 1);

        $result = $this->partnerService()->uploadDocument($data, $tenantId);
        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
    }

    /**
     * Lista documentos del productor.
     *
     * GET /api/v1/hub/documents
     */
    public function listDocuments(Request $request): JsonResponse
    {
        $producerId = (int) $request->query->get('producer_id', $this->currentUser()->id());
        $page = (int) $request->query->get('page', 0);
        $limit = min((int) $request->query->get('limit', 20), 100);

        $result = $this->partnerService()->getDocumentsByProducer($producerId, $page, $limit);
        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Actualiza un documento.
     *
     * PATCH /api/v1/hub/documents/{uuid}
     */
    public function updateDocument(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $result = $this->partnerService()->updateDocument($uuid, $data);

        if ($result === NULL) {
            return new JsonResponse([
                'error' => $this->t('Documento no encontrado.'),
            ], 404);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Desactiva un documento (soft delete).
     *
     * DELETE /api/v1/hub/documents/{uuid}
     */
    public function deleteDocument(string $uuid): JsonResponse
    {
        $deleted = $this->partnerService()->deleteDocument($uuid);

        if (!$deleted) {
            return new JsonResponse([
                'error' => $this->t('Documento no encontrado.'),
            ], 404);
        }

        return new JsonResponse(['deleted' => TRUE, 'uuid' => $uuid]);
    }

    /**
     * Obtiene analytics del hub para el productor.
     *
     * GET /api/v1/hub/analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $producerId = (int) $request->query->get('producer_id', $this->currentUser()->id());
        $result = $this->partnerService()->getProducerAnalytics($producerId);
        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
    }

}
