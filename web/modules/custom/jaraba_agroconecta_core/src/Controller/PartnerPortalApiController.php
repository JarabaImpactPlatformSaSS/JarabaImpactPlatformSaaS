<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Controlador API para el Portal Partner — Vista Pública.
 *
 * Endpoints autenticados por token (magic link) para que los partners
 * accedan a documentos, productos y descarguen packs. Sprint AC6-2, Doc 82.
 */
class PartnerPortalApiController extends ControllerBase
{

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
     * Valida el token y retorna la relación partner.
     *
     * @param string $token
     *   Token de acceso.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship|null
     *   La relación partner activa o null.
     */
    protected function validateToken(string $token)
    {
        return $this->partnerService()->findByToken($token);
    }

    /**
     * Obtiene datos del portal partner.
     *
     * GET /api/v1/portal/{token}
     */
    public function getPortalData(string $token): JsonResponse
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        // Actualizar última fecha de acceso.
        $relationship->set('last_access_at', \Drupal::time()->getRequestTime());
        $relationship->save();

        // Obtener datos del productor.
        $producerStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $producer = $producerStorage->load($relationship->getProducerId());

        $documents = $this->partnerService()->getAccessibleDocuments($relationship);
        $products = $this->partnerService()->getAccessibleProducts($relationship);

        // AUDIT-CONS-N08: Standardized JSON envelope.
        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'partner' => [
                    'name' => $relationship->getPartnerName(),
                    'type' => $relationship->getPartnerType(),
                    'access_level' => $relationship->getAccessLevel(),
                    'last_access' => $relationship->get('last_access_at')->value,
                ],
                'producer' => [
                    'name' => $producer ? $producer->label() : '-',
                ],
                'products' => $products,
                'documents' => $documents,
            ],
            'meta' => ['total_documents' => count($documents), 'timestamp' => time()],
        ]);
    }

    /**
     * Lista productos accesibles para el partner.
     *
     * GET /api/v1/portal/{token}/products
     */
    public function listProducts(string $token): JsonResponse
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        $products = $this->partnerService()->getAccessibleProducts($relationship);
        return new JsonResponse(['success' => TRUE, 'data' => $products, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Obtiene documentos de un producto específico.
     *
     * GET /api/v1/portal/{token}/products/{product_id}/documents
     */
    public function getProductDocuments(string $token, int $product_id): JsonResponse
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        // Verificar que el producto está dentro de los permitidos.
        $allowedProducts = $relationship->getAllowedProducts();
        if ($allowedProducts !== NULL && !in_array($product_id, $allowedProducts, TRUE)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => (string) $this->t('No tiene acceso a este producto.')],
            ], 403);
        }

        $allDocuments = $this->partnerService()->getAccessibleDocuments($relationship);
        $productDocs = array_filter($allDocuments, function ($doc) use ($product_id) {
            return $doc['product_id'] === $product_id;
        });

        return new JsonResponse(['success' => TRUE, 'data' => array_values($productDocs), 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Lista todos los documentos accesibles.
     *
     * GET /api/v1/portal/{token}/documents
     */
    public function listAllDocuments(string $token): JsonResponse
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        $documents = $this->partnerService()->getAccessibleDocuments($relationship);
        return new JsonResponse(['success' => TRUE, 'data' => $documents, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Descarga un documento individual.
     *
     * GET /api/v1/portal/{token}/documents/{uuid}/download
     */
    public function downloadDocument(Request $request, string $token, string $uuid): Response
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        // Buscar el documento.
        $docStorage = \Drupal::entityTypeManager()->getStorage('product_document');
        $entities = $docStorage->loadByProperties(['uuid' => $uuid, 'is_active' => TRUE]);

        if (empty($entities)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Documento no encontrado.')],
            ], 404);
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $document */
        $document = reset($entities);

        // Verificar acceso por nivel.
        $accessLevels = ['basico' => 1, 'verificado' => 2, 'premium' => 3];
        $partnerLevel = $accessLevels[$relationship->getAccessLevel()] ?? 1;
        $requiredLevel = $accessLevels[$document->getMinAccessLevel()] ?? 1;

        if ($partnerLevel < $requiredLevel) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'ACCESS_DENIED', 'message' => (string) $this->t('Nivel de acceso insuficiente para este documento.')],
            ], 403);
        }

        // Obtener archivo.
        $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
        $file = $fileStorage->load($document->get('file_id')->target_id);

        if (!$file) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Archivo no disponible.')],
            ], 404);
        }

        // Registrar descarga.
        $this->partnerService()->logDownload(
            (int) $document->id(),
            (int) $relationship->id(),
            (int) $relationship->get('tenant_id')->target_id,
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );

        // Servir archivo.
        $uri = $file->getFileUri();
        $realpath = \Drupal::service('file_system')->realpath($uri);

        if (!$realpath || !file_exists($realpath)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Archivo no encontrado en el sistema de archivos.')],
            ], 404);
        }

        $response = new BinaryFileResponse($realpath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getFilename()
        );
        return $response;
    }

    /**
     * Descarga un pack ZIP de todos los documentos de un producto.
     *
     * POST /api/v1/portal/{token}/products/{product_id}/download-pack
     */
    public function downloadPack(Request $request, string $token, int $product_id): Response
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        $allDocuments = $this->partnerService()->getAccessibleDocuments($relationship);
        $productDocs = array_filter($allDocuments, function ($doc) use ($product_id) {
            return $doc['product_id'] === $product_id;
        });

        if (empty($productDocs)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('No hay documentos disponibles para este producto.')],
            ], 404);
        }

        return $this->buildZipResponse($productDocs, $relationship, $request, "producto-{$product_id}");
    }

    /**
     * Descarga todos los documentos accesibles en ZIP.
     *
     * POST /api/v1/portal/{token}/download-all
     */
    public function downloadAll(Request $request, string $token): Response
    {
        $relationship = $this->validateToken($token);
        if (!$relationship) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INVALID_TOKEN', 'message' => (string) $this->t('Token de acceso no válido o expirado.')],
            ], 403);
        }

        $documents = $this->partnerService()->getAccessibleDocuments($relationship);

        if (empty($documents)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('No hay documentos disponibles.')],
            ], 404);
        }

        return $this->buildZipResponse($documents, $relationship, $request, 'todos-documentos');
    }

    /**
     * Solicita un nuevo magic link por email.
     *
     * POST /api/v1/portal/request-link
     */
    public function requestMagicLink(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['email'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('El campo email es obligatorio.')],
            ], 400);
        }

        // Buscar relaciones activas para este email.
        $storage = \Drupal::entityTypeManager()->getStorage('partner_relationship');
        $entities = $storage->loadByProperties([
            'partner_email' => $data['email'],
            'status' => 'active',
        ]);

        // Siempre devolver respuesta positiva por seguridad (no revelar existencia).
        return new JsonResponse([
            'success' => TRUE,
            'data' => ['message' => (string) $this->t('Si existe una cuenta asociada, recibirá un email con el enlace de acceso.')],
            'meta' => ['timestamp' => time()],
        ]);
    }

    /**
     * Construye una respuesta ZIP con múltiples documentos.
     *
     * @param array $documents
     *   Lista de documentos a incluir.
     * @param mixed $relationship
     *   Relación partner.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request actual.
     * @param string $prefix
     *   Prefijo para el nombre del ZIP.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   BinaryFileResponse con el ZIP o JsonResponse de error.
     */
    protected function buildZipResponse(array $documents, $relationship, Request $request, string $prefix): Response
    {
        $docStorage = \Drupal::entityTypeManager()->getStorage('product_document');
        $fileStorage = \Drupal::entityTypeManager()->getStorage('file');
        $fileSystem = \Drupal::service('file_system');

        $zipPath = $fileSystem->getTempDirectory() . '/hub-pack-' . time() . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => (string) $this->t('Error al generar el archivo ZIP.')],
            ], 500);
        }

        foreach ($documents as $docData) {
            $document = $docStorage->load($docData['id']);
            if (!$document) {
                continue;
            }

            $file = $fileStorage->load($document->get('file_id')->target_id);
            if (!$file) {
                continue;
            }

            $realpath = $fileSystem->realpath($file->getFileUri());
            if ($realpath && file_exists($realpath)) {
                $zip->addFile($realpath, $file->getFilename());

                // Registrar descarga.
                $this->partnerService()->logDownload(
                    (int) $document->id(),
                    (int) $relationship->id(),
                    (int) $relationship->get('tenant_id')->target_id,
                    $request->getClientIp(),
                    $request->headers->get('User-Agent')
                );
            }
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => (string) $this->t('Error al generar el archivo ZIP.')],
            ], 500);
        }

        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "documentos-{$prefix}-" . date('Ymd') . '.zip'
        );
        $response->deleteFileAfterSend(TRUE);
        return $response;
    }

}
