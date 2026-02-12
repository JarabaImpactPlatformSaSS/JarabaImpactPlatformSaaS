<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\QrService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador REST API para gestión de leads capturados vía QR.
 *
 * Doc 81 — Lead Management endpoints.
 *
 * ENDPOINTS:
 * POST   /api/v1/agro/leads              → Capturar lead (público desde landing)
 * GET    /api/v1/agro/leads              → Listar leads del productor
 * GET    /api/v1/agro/leads/{id}         → Detalle de un lead
 * DELETE /api/v1/agro/leads/{id}         → Eliminar lead
 * GET    /api/v1/agro/leads/export       → Exportar leads a CSV
 * POST   /api/v1/agro/leads/{id}/sync    → Sincronizar lead a CRM
 */
class LeadApiController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected QrService $qrService,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.qr_service'),
        );
    }

    /**
     * Capturar lead desde landing QR (público, sin autenticación).
     *
     * Acepta: email, name, phone, consent_marketing, qr_code_id, scan_event_id,
     * capture_type (newsletter|discount|recipe|contest|review_request).
     */
    public function capture(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        if (!$data || empty($data['qr_code_id']) || empty($data['email'])) {
            return new JsonResponse([
                'error' => $this->t('qr_code_id y email son requeridos.')->render(),
            ], 400);
        }

        // Validar email.
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'error' => $this->t('Email no válido.')->render(),
            ], 400);
        }

        // Verificar consentimiento.
        if (empty($data['consent_marketing'])) {
            return new JsonResponse([
                'error' => $this->t('Se requiere el consentimiento de marketing.')->render(),
            ], 400);
        }

        $lead = $this->qrService->captureLead(
            (int) $data['qr_code_id'],
            $data,
            isset($data['scan_event_id']) ? (int) $data['scan_event_id'] : NULL
        );

        if (!$lead) {
            return new JsonResponse([
                'error' => $this->t('No se pudo capturar el lead.')->render(),
            ], 400);
        }

        return new JsonResponse(['lead' => $lead], 201);
    }

    /**
     * Listar leads del productor con filtros.
     */
    public function list(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 0);
        $limit = min((int) $request->query->get('limit', 50), 100);
        $captureType = $request->query->get('capture_type');
        $qrId = $request->query->get('qr_id') ? (int) $request->query->get('qr_id') : NULL;
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $filters = array_filter([
            'capture_type' => $captureType,
            'qr_id' => $qrId,
            'from' => $from,
            'to' => $to,
        ]);

        $result = $this->qrService->getLeads($filters, $page, $limit);

        return new JsonResponse([
            'leads' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Detalle de un lead.
     */
    public function get(int $lead_id): JsonResponse
    {
        $lead = $this->qrService->getLeadDetail($lead_id);
        if (!$lead) {
            return new JsonResponse([
                'error' => $this->t('Lead no encontrado.')->render(),
            ], 404);
        }

        return new JsonResponse(['lead' => $lead]);
    }

    /**
     * Eliminar un lead.
     */
    public function delete(int $lead_id): JsonResponse
    {
        $result = $this->qrService->deleteLead($lead_id);
        if (!$result) {
            return new JsonResponse([
                'error' => $this->t('Lead no encontrado.')->render(),
            ], 404);
        }

        return new JsonResponse(['deleted' => TRUE]);
    }

    /**
     * Exportar leads a CSV.
     *
     * Acepta filtros por capture_type, qr_id, rango de fechas.
     */
    public function export(Request $request): Response
    {
        $captureType = $request->query->get('capture_type');
        $qrId = $request->query->get('qr_id') ? (int) $request->query->get('qr_id') : NULL;
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $filters = array_filter([
            'capture_type' => $captureType,
            'qr_id' => $qrId,
            'from' => $from,
            'to' => $to,
        ]);

        $csv = $this->qrService->exportLeadsCsv($filters);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="leads_export_' . date('Ymd_His') . '.csv"');

        return $response;
    }

    /**
     * Sincronizar lead a CRM externo.
     */
    public function syncCrm(int $lead_id): JsonResponse
    {
        $result = $this->qrService->syncLeadToCrm($lead_id);
        if (isset($result['error'])) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

}
