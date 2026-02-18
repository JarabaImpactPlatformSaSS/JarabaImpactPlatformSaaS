<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_agroconecta_core\Service\QrService;
use Drupal\jaraba_agroconecta_core\Service\TraceabilityService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para trazabilidad y QR.
 *
 * ENDPOINTS TRAZABILIDAD:
 * GET  /api/v1/agro/traceability/{code}      → Datos públicos de lote
 * POST /api/v1/agro/traceability/events       → Añadir evento
 * GET  /api/v1/agro/traceability/{id}/verify  → Verificar integridad
 * POST /api/v1/agro/traceability/proof        → Crear prueba de integridad
 *
 * ENDPOINTS QR:
 * POST /api/v1/agro/qr/generate              → Generar QR
 * POST /api/v1/agro/qr/scan                  → Registrar escaneo
 * POST /api/v1/agro/qr/lead                  → Capturar lead
 * GET  /api/v1/agro/qr/{id}/analytics        → Analytics de un QR
 * GET  /api/v1/agro/qr/list                  → Listar QRs del tenant
 */
class TraceabilityApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected TraceabilityService $traceabilityService,
        protected QrService $qrService,
        protected TenantContextService $tenantContext,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.traceability_service'), // AUDIT-CONS-N05: canonical prefix
            $container->get('jaraba_agroconecta_core.qr_service'), // AUDIT-CONS-N05: canonical prefix
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    // ===================================================
    // Trazabilidad
    // ===================================================

    /**
     * Datos públicos de trazabilidad de un lote por código.
     */
    public function getBatchTraceability(string $code): JsonResponse
    {
        $batch = $this->traceabilityService->findBatchByCode($code);
        if (!$batch) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Lote no encontrado.']], 404);
        }

        $data = $this->traceabilityService->getBatchTraceability((int) $batch->id());
        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Añadir evento de trazabilidad.
     */
    public function addTraceEvent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['batch_id']) || empty($data['event_type'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'batch_id y event_type son requeridos.']], 400);
        }

        $event = $this->traceabilityService->addTraceEvent(
            (int) $data['batch_id'],
            $data['event_type'],
            $data
        );

        if (!$event) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo crear el evento (lote sellado o inexistente).']], 400);
        }

        return new JsonResponse(['event' => $event], 201);
    }

    /**
     * Verificar integridad de la cadena de un lote.
     */
    public function verifyIntegrity(int $batch_id): JsonResponse
    {
        $result = $this->traceabilityService->verifyChainIntegrity($batch_id);
        return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Crear prueba de integridad.
     */
    public function createProof(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['batch_id'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'batch_id es requerido.']], 400);
        }

        $proof = $this->traceabilityService->createIntegrityProof(
            (int) $data['batch_id'],
            $data['anchor_type'] ?? 'internal'
        );

        if (!$proof) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo crear la prueba.']], 400);
        }

        return new JsonResponse(['proof' => $proof], 201);
    }

    // ===================================================
    // QR
    // ===================================================

    /**
     * Generar QR code.
     */
    public function generateQr(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['type']) || empty($data['target_id'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'type y target_id son requeridos.']], 400);
        }

        $tenantId = $this->tenantContext->getCurrentTenantId() ?? (int) ($data['tenant_id'] ?? 1);
        $campaign = $data['utm_campaign'] ?? NULL;

        $result = match ($data['type']) {
            'batch' => $this->qrService->generateForBatch((int) $data['target_id'], $tenantId, $campaign),
            'product' => $this->qrService->generateForProduct((int) $data['target_id'], $tenantId, $campaign),
            'producer' => $this->qrService->generateForProducer((int) $data['target_id'], $tenantId, $campaign),
            default => ['error' => 'Tipo no soportado: ' . $data['type']],
        };

        if (isset($result['error'])) {
            return new JsonResponse(['success' => FALSE, 'error' => $result], 400);
        }

        return new JsonResponse(['qr' => $result], 201);
    }

    /**
     * Registrar escaneo de QR.
     */
    public function trackScan(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['qr_code_id'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'qr_code_id es requerido.']], 400);
        }

        $scan = $this->qrService->trackScan((int) $data['qr_code_id'], $request);
        if (!$scan) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'QR no encontrado o inactivo.']], 404);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $scan, 'meta' => ['timestamp' => time()]], 201);
    }

    /**
     * Capturar lead desde landing QR.
     */
    public function captureLead(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['qr_code_id']) || empty($data['email'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'qr_code_id y email son requeridos.']], 400);
        }

        $lead = $this->qrService->captureLead(
            (int) $data['qr_code_id'],
            $data,
            isset($data['scan_event_id']) ? (int) $data['scan_event_id'] : NULL
        );

        if (!$lead) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'No se pudo capturar el lead.']], 400);
        }

        return new JsonResponse(['lead' => $lead], 201);
    }

    /**
     * Analytics de un QR.
     */
    public function qrAnalytics(int $qr_id): JsonResponse
    {
        $analytics = $this->qrService->getQrAnalytics($qr_id);
        if (empty($analytics)) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'QR no encontrado.']], 404);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $analytics, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Listar QR codes de un tenant.
     */
    public function listQrCodes(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $type = $request->query->get('type');

        $qrs = $this->qrService->getQrCodes($tenantId, $type);

        return new JsonResponse([
            'qr_codes' => $qrs,
            'total' => count($qrs),
        ]);
    }

}
