<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_agroconecta_core\Service\QrService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador REST API para el Sistema QR Dinámico.
 *
 * Complementa TraceabilityApiController con los endpoints
 * avanzados del Doc 81: redirect público, CRUD, batch,
 * descarga de imágenes y analytics extendido.
 *
 * ENDPOINTS:
 * GET    /q/{code}                           → Redirect público
 * GET    /api/v1/agro/qr/{id}               → Detalle QR
 * PATCH  /api/v1/agro/qr/{id}               → Actualizar QR
 * DELETE /api/v1/agro/qr/{id}               → Eliminar QR
 * GET    /api/v1/agro/qr/{id}/scans         → Listar escaneos
 * POST   /api/v1/agro/qr/batch              → Generar QRs en lote
 * GET    /api/v1/agro/qr/{id}/download/{fmt} → Descargar imagen
 * GET    /api/v1/agro/qr/dashboard          → Dashboard analytics
 */
class QrApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected QrService $qrService,
        protected TenantContextService $tenantContext,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.qr_service'), // AUDIT-CONS-N05: canonical prefix
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    // ===================================================
    // Redirect público
    // ===================================================

    /**
     * Redirect público: escaneo de QR.
     *
     * Busca el QR por código, registra el escaneo y redirige
     * al destino configurado. Es la pieza clave del puente
     * phy-gital: convierte un escaneo físico en tráfico digital.
     */
    public function redirectQr(string $code, Request $request): Response
    {
        // Buscar QR por short_code.
        $qr = $this->qrService->findByCode($code);

        if (!$qr) {
            return new JsonResponse([
                'error' => 'qr_not_found',
                'message' => $this->t('Código QR no válido.')->render(),
            ], 404);
        }

        if (!$qr->isActive()) {
            return new JsonResponse([
                'error' => 'qr_expired',
                'message' => $this->t('Este código QR ha expirado.')->render(),
            ], 410);
        }

        // Registrar escaneo (async - no bloqueamos el redirect).
        $scanResult = $this->qrService->trackScan((int) $qr->id(), $request);

        // Construir URL destino con parámetros de tracking.
        $destinationUrl = $qr->get('destination_url')->value ?? '/';
        $separator = str_contains($destinationUrl, '?') ? '&' : '?';
        $trackingParams = 'qr=1';
        if ($scanResult && !empty($scanResult['scan_id'])) {
            $trackingParams .= '&scan=' . $scanResult['scan_id'];
        }

        return new RedirectResponse($destinationUrl . $separator . $trackingParams, 302);
    }

    // ===================================================
    // CRUD de QR Codes
    // ===================================================

    /**
     * Detalle de un QR code.
     */
    public function getQr(int $qr_id): JsonResponse
    {
        $qr = $this->qrService->getQrDetail($qr_id);
        if (!$qr) {
            return new JsonResponse(['error' => $this->t('QR no encontrado.')->render()], 404);
        }

        return new JsonResponse(['qr' => $qr]);
    }

    /**
     * Actualizar un QR code (destino, estado, estilo).
     */
    public function updateQr(int $qr_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data) {
            return new JsonResponse(['error' => $this->t('Datos JSON inválidos.')->render()], 400);
        }

        $result = $this->qrService->updateQrCode($qr_id, $data);
        if (isset($result['error'])) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => $result], 400);
        }

        return new JsonResponse(['qr' => $result]);
    }

    /**
     * Eliminar (soft-delete) un QR code.
     */
    public function deleteQr(int $qr_id): JsonResponse
    {
        $result = $this->qrService->deleteQrCode($qr_id);
        if (!$result) {
            return new JsonResponse(['error' => $this->t('QR no encontrado.')->render()], 404);
        }

        return new JsonResponse(['deleted' => TRUE]);
    }

    // ===================================================
    // Escaneos & Analytics
    // ===================================================

    /**
     * Lista los escaneos de un QR específico.
     */
    public function listScans(int $qr_id, Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 0);
        $limit = min((int) $request->query->get('limit', 50), 100);

        $scans = $this->qrService->getQrScans($qr_id, $page, $limit);
        if ($scans === NULL) {
            return new JsonResponse(['error' => $this->t('QR no encontrado.')->render()], 404);
        }

        return new JsonResponse([
            'scans' => $scans['items'],
            'total' => $scans['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Dashboard analytics del productor (todos sus QRs).
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '30d');
        $tenantId = (int) $request->query->get('tenant_id', 1);

        $data = $this->qrService->getProducerDashboard($tenantId, $period);

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
    }

    // ===================================================
    // Generación en lote
    // ===================================================

    /**
     * Genera QR codes en lote para múltiples entidades.
     */
    public function batchGenerate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['items'])) {
            return new JsonResponse([
                'error' => $this->t('Se requiere una lista de items para generar QRs.')->render(),
            ], 400);
        }

        $tenantId = $this->tenantContext->getCurrentTenantId() ?? (int) ($data['tenant_id'] ?? 1);
        $results = [];
        $errors = [];

        foreach ($data['items'] as $item) {
            if (empty($item['type']) || empty($item['target_id'])) {
                $errors[] = ['item' => $item, 'error' => 'type y target_id requeridos'];
                continue;
            }

            $result = match ($item['type']) {
                'batch' => $this->qrService->generateForBatch((int) $item['target_id'], $tenantId, $item['utm_campaign'] ?? NULL),
                'product' => $this->qrService->generateForProduct((int) $item['target_id'], $tenantId, $item['utm_campaign'] ?? NULL),
                'producer' => $this->qrService->generateForProducer((int) $item['target_id'], $tenantId, $item['utm_campaign'] ?? NULL),
                default => ['error' => 'Tipo no soportado: ' . $item['type']],
            };

            if (isset($result['error'])) {
                $errors[] = ['item' => $item, 'error' => $result['error']];
            } else {
                $results[] = $result;
            }
        }

        return new JsonResponse([
            'generated' => $results,
            'count' => count($results),
            'errors' => $errors,
        ], count($results) > 0 ? 201 : 400);
    }

    // ===================================================
    // Descarga de imagen QR
    // ===================================================

    /**
     * Descarga la imagen del QR en el formato solicitado.
     *
     * Formatos: png, svg, pdf.
     */
    public function downloadQr(int $qr_id, string $format): Response
    {
        $allowedFormats = ['png', 'svg', 'pdf'];
        if (!in_array($format, $allowedFormats, TRUE)) {
            return new JsonResponse([
                'error' => $this->t('Formato no soportado. Usa: png, svg o pdf.')->render(),
            ], 400);
        }

        $file = $this->qrService->getQrFile($qr_id, $format);
        if (!$file) {
            return new JsonResponse([
                'error' => $this->t('QR no encontrado o archivo no disponible.')->render(),
            ], 404);
        }

        $contentTypes = [
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];

        $response = new Response($file['content']);
        $response->headers->set('Content-Type', $contentTypes[$format]);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file['filename'] . '"');
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }

}
