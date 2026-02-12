<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Entity\QrCodeAgro;
use Drupal\jaraba_agroconecta_core\Entity\QrScanEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Servicio de QR para AgroConecta.
 *
 * RESPONSABILIDADES:
 * - Generación de QR codes para lotes, productos y productores.
 * - Tracking de escaneos con geolocalización.
 * - Captura de leads desde landings de QR.
 * - Analytics de campañas phygital.
 */
class QrService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    // ===================================================
    // Generación de QR
    // ===================================================

    /**
     * Genera un QR dinámico para un lote (trazabilidad).
     */
    public function generateForBatch(int $batchId, int $tenantId, ?string $utmCampaign = NULL): array
    {
        $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);
        if (!$batch) {
            return ['error' => 'Lote no encontrado.'];
        }

        return $this->createQrCode([
            'tenant_id' => $tenantId,
            'label' => 'QR Lote: ' . $batch->label(),
            'qr_type' => 'batch',
            'target_entity_type' => 'agro_batch',
            'target_entity_id' => $batchId,
            'utm_source' => 'qr_code',
            'utm_medium' => 'packaging',
            'utm_campaign' => $utmCampaign ?? 'traceability_' . $batch->label(),
        ]);
    }

    /**
     * Genera un QR para un producto.
     */
    public function generateForProduct(int $productId, int $tenantId, ?string $utmCampaign = NULL): array
    {
        $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
        if (!$product) {
            return ['error' => 'Producto no encontrado.'];
        }

        return $this->createQrCode([
            'tenant_id' => $tenantId,
            'label' => 'QR Producto: ' . $product->label(),
            'qr_type' => 'product',
            'target_entity_type' => 'product_agro',
            'target_entity_id' => $productId,
            'utm_source' => 'qr_code',
            'utm_medium' => 'product_label',
            'utm_campaign' => $utmCampaign,
        ]);
    }

    /**
     * Genera un QR para un productor.
     */
    public function generateForProducer(int $producerId, int $tenantId, ?string $utmCampaign = NULL): array
    {
        return $this->createQrCode([
            'tenant_id' => $tenantId,
            'label' => 'QR Productor #' . $producerId,
            'qr_type' => 'producer',
            'target_entity_type' => 'producer_profile',
            'target_entity_id' => $producerId,
            'utm_source' => 'qr_code',
            'utm_medium' => 'business_card',
            'utm_campaign' => $utmCampaign,
        ]);
    }

    // ===================================================
    // Tracking de escaneos
    // ===================================================

    /**
     * Registra un escaneo de QR.
     */
    public function trackScan(int $qrCodeId, Request $request): ?array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrCodeId);
        if (!$qr instanceof QrCodeAgro || !$qr->isActive()) {
            return NULL;
        }

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $userAgent = $request->headers->get('User-Agent', '');

        // Determinar si es un escaneo único.
        $isUnique = $this->isUniqueScan($qrCodeId, $ip);

        $storage = $this->entityTypeManager->getStorage('qr_scan_event');
        /** @var QrScanEvent $scan */
        $scan = $storage->create([
            'qr_code_id' => $qrCodeId,
            'ip_address' => substr($ip, 0, 45),
            'user_agent' => substr($userAgent, 0, 512),
            'device_type' => $this->detectDeviceType($userAgent),
            'referrer' => substr($request->headers->get('Referer', ''), 0, 512),
            'is_unique' => $isUnique,
            'converted' => FALSE,
        ]);
        $scan->save();

        // Actualizar contadores del QR.
        $qr->set('scan_count', $qr->getScanCount() + 1);
        if ($isUnique) {
            $unique = (int) ($qr->get('unique_scan_count')->value ?? 0);
            $qr->set('unique_scan_count', $unique + 1);
        }
        $qr->save();

        return [
            'scan_id' => (int) $scan->id(),
            'qr_type' => $qr->getQrType(),
            'target_entity_type' => $qr->get('target_entity_type')->value,
            'target_entity_id' => (int) $qr->get('target_entity_id')->value,
            'destination_url' => $qr->get('destination_url')->value ?? '',
            'is_unique' => $isUnique,
        ];
    }

    /**
     * Marca un escaneo como convertido (lead capturado o compra).
     */
    public function markConversion(int $scanEventId): bool
    {
        $scan = $this->entityTypeManager->getStorage('qr_scan_event')->load($scanEventId);
        if (!$scan instanceof QrScanEvent) {
            return FALSE;
        }

        $scan->set('converted', TRUE);
        $scan->save();

        // Incrementar conversiones del QR.
        $qrId = $scan->get('qr_code_id')->target_id;
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if ($qr instanceof QrCodeAgro) {
            $conv = (int) ($qr->get('conversion_count')->value ?? 0);
            $qr->set('conversion_count', $conv + 1);
            $qr->save();
        }

        return TRUE;
    }

    // ===================================================
    // Lead capture
    // ===================================================

    /**
     * Captura un lead desde una landing de QR.
     */
    public function captureLead(int $qrCodeId, array $leadData, ?int $scanEventId = NULL): ?array
    {
        if (empty($leadData['email'])) {
            return NULL;
        }

        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrCodeId);
        if (!$qr instanceof QrCodeAgro) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('qr_lead_capture');
        $lead = $storage->create([
            'tenant_id' => $qr->get('tenant_id')->target_id,
            'qr_code_id' => $qrCodeId,
            'scan_event_id' => $scanEventId,
            'email' => $leadData['email'],
            'name' => $leadData['name'] ?? '',
            'phone' => $leadData['phone'] ?? '',
            'source' => $leadData['source'] ?? $qr->get('utm_campaign')->value ?? '',
            'consent_given' => !empty($leadData['consent']),
            'metadata' => !empty($leadData['metadata']) ? json_encode($leadData['metadata']) : NULL,
        ]);

        // Auto-asignar cupón de descuento si hay uno disponible.
        if (!empty($leadData['assign_discount']) && $leadData['assign_discount']) {
            $discountCode = 'QR' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $lead->set('discount_code', $discountCode);
        }

        $lead->save();

        // Marcar conversión si hay scanEventId.
        if ($scanEventId) {
            $this->markConversion($scanEventId);
        }

        return [
            'lead_id' => (int) $lead->id(),
            'email' => $leadData['email'],
            'discount_code' => $lead->get('discount_code')->value ?? NULL,
        ];
    }

    // ===================================================
    // Analytics
    // ===================================================

    /**
     * Obtiene analytics de un QR.
     */
    public function getQrAnalytics(int $qrCodeId): array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrCodeId);
        if (!$qr instanceof QrCodeAgro) {
            return [];
        }

        return [
            'qr_id' => $qrCodeId,
            'label' => $qr->label(),
            'type' => $qr->getQrType(),
            'total_scans' => $qr->getScanCount(),
            'unique_scans' => (int) ($qr->get('unique_scan_count')->value ?? 0),
            'conversions' => (int) ($qr->get('conversion_count')->value ?? 0),
            'conversion_rate' => $qr->getScanCount() > 0
                ? round(((int) ($qr->get('conversion_count')->value ?? 0)) / $qr->getScanCount() * 100, 2)
                : 0,
            'is_active' => $qr->isActive(),
        ];
    }

    /**
     * Lista QR codes de un tenant.
     */
    public function getQrCodes(int $tenantId, ?string $type = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('qr_code_agro');
        $query = $storage->getQuery()
            ->condition('tenant_id', $tenantId)
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE);

        if ($type) {
            $query->condition('qr_type', $type);
        }

        $ids = $query->execute();
        if (empty($ids)) {
            return [];
        }

        $qrs = $storage->loadMultiple($ids);
        return array_values(array_map(fn($qr) => $this->getQrAnalytics((int) $qr->id()), $qrs));
    }

    // ===================================================
    // Métodos internos
    // ===================================================

    protected function createQrCode(array $data): array
    {
        $shortCode = substr(bin2hex(random_bytes(6)), 0, 12);

        $storage = $this->entityTypeManager->getStorage('qr_code_agro');
        $qr = $storage->create(array_merge($data, [
            'short_code' => $shortCode,
            'is_active' => TRUE,
            'scan_count' => 0,
            'unique_scan_count' => 0,
            'conversion_count' => 0,
            'uid' => \Drupal::currentUser()->id(),
        ]));
        $qr->save();

        return [
            'id' => (int) $qr->id(),
            'short_code' => $shortCode,
            'label' => $data['label'] ?? '',
            'qr_type' => $data['qr_type'] ?? '',
        ];
    }

    protected function isUniqueScan(int $qrCodeId, string $ip): bool
    {
        $storage = $this->entityTypeManager->getStorage('qr_scan_event');
        $existing = $storage->getQuery()
            ->condition('qr_code_id', $qrCodeId)
            ->condition('ip_address', $ip)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        return empty($existing);
    }

    protected function detectDeviceType(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mozilla') || str_contains($ua, 'chrome') || str_contains($ua, 'safari')) {
            return 'desktop';
        }
        return 'unknown';
    }

    // ===================================================
    // QR Management (Doc 81 — Sprint AC6-1)
    // ===================================================

    /**
     * Busca un QR por su short_code (para redirect público).
     */
    public function findByCode(string $code): ?QrCodeAgro
    {
        $storage = $this->entityTypeManager->getStorage('qr_code_agro');
        $ids = $storage->getQuery()
            ->condition('short_code', $code)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        $qr = $storage->load(reset($ids));
        return $qr instanceof QrCodeAgro ? $qr : NULL;
    }

    /**
     * Obtiene detalle completo de un QR code.
     */
    public function getQrDetail(int $qrId): ?array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if (!$qr instanceof QrCodeAgro) {
            return NULL;
        }

        return [
            'id' => (int) $qr->id(),
            'label' => $qr->label(),
            'short_code' => $qr->get('short_code')->value ?? '',
            'qr_type' => $qr->getQrType(),
            'target_entity_type' => $qr->get('target_entity_type')->value ?? '',
            'target_entity_id' => (int) ($qr->get('target_entity_id')->value ?? 0),
            'destination_url' => $qr->get('destination_url')->value ?? '',
            'is_active' => $qr->isActive(),
            'total_scans' => $qr->getScanCount(),
            'unique_scans' => (int) ($qr->get('unique_scan_count')->value ?? 0),
            'conversions' => (int) ($qr->get('conversion_count')->value ?? 0),
            'utm_source' => $qr->get('utm_source')->value ?? '',
            'utm_medium' => $qr->get('utm_medium')->value ?? '',
            'utm_campaign' => $qr->get('utm_campaign')->value ?? '',
            'created' => $qr->get('created')->value ?? '',
            'changed' => $qr->get('changed')->value ?? '',
        ];
    }

    /**
     * Actualiza un QR code (destino, estado, estilo).
     */
    public function updateQrCode(int $qrId, array $data): array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if (!$qr instanceof QrCodeAgro) {
            return ['error' => 'QR no encontrado.'];
        }

        $allowedFields = [
            'label',
            'destination_url',
            'is_active',
            'utm_source',
            'utm_medium',
            'utm_campaign',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $qr->set($field, $data[$field]);
            }
        }

        $qr->save();
        return $this->getQrDetail($qrId);
    }

    /**
     * Soft-delete de un QR code (desactivar).
     */
    public function deleteQrCode(int $qrId): bool
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if (!$qr instanceof QrCodeAgro) {
            return FALSE;
        }

        $qr->set('is_active', FALSE);
        $qr->save();
        return TRUE;
    }

    /**
     * Lista escaneos de un QR con paginación.
     */
    public function getQrScans(int $qrId, int $page = 0, int $limit = 50): ?array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if (!$qr instanceof QrCodeAgro) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('qr_scan_event');

        // Total.
        $total = (int) $storage->getQuery()
            ->condition('qr_code_id', $qrId)
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        // Paginado.
        $ids = $storage->getQuery()
            ->condition('qr_code_id', $qrId)
            ->sort('created', 'DESC')
            ->range($page * $limit, $limit)
            ->accessCheck(FALSE)
            ->execute();

        $items = [];
        if (!empty($ids)) {
            $scans = $storage->loadMultiple($ids);
            foreach ($scans as $scan) {
                $items[] = [
                    'id' => (int) $scan->id(),
                    'ip_address' => substr($scan->get('ip_address')->value ?? '', 0, 6) . '***',
                    'device_type' => $scan->get('device_type')->value ?? 'unknown',
                    'referrer' => $scan->get('referrer')->value ?? '',
                    'is_unique' => (bool) ($scan->get('is_unique')->value ?? FALSE),
                    'converted' => (bool) ($scan->get('converted')->value ?? FALSE),
                    'created' => $scan->get('created')->value ?? '',
                ];
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Dashboard analytics agregado del productor.
     */
    public function getProducerDashboard(int $tenantId, string $period = '30d'): array
    {
        $qrCodes = $this->getQrCodes($tenantId);

        $totalScans = 0;
        $totalConversions = 0;
        $totalUniqueScans = 0;

        foreach ($qrCodes as $qr) {
            $totalScans += $qr['total_scans'] ?? 0;
            $totalConversions += $qr['conversions'] ?? 0;
            $totalUniqueScans += $qr['unique_scans'] ?? 0;
        }

        // Leads del tenant.
        $leadResult = $this->getLeads(['tenant_id' => $tenantId], 0, 0);
        $totalLeads = $leadResult['total'];

        return [
            'period' => $period,
            'kpis' => [
                'total_qr_codes' => count($qrCodes),
                'total_scans' => $totalScans,
                'unique_scans' => $totalUniqueScans,
                'total_conversions' => $totalConversions,
                'conversion_rate' => $totalScans > 0
                    ? round($totalConversions / $totalScans * 100, 2)
                    : 0,
                'total_leads' => $totalLeads,
            ],
            'qr_codes' => $qrCodes,
        ];
    }

    /**
     * Obtiene el archivo de imagen de un QR para descarga.
     *
     * @return array|null ['content' => string, 'filename' => string] o NULL.
     */
    public function getQrFile(int $qrId, string $format): ?array
    {
        $qr = $this->entityTypeManager->getStorage('qr_code_agro')->load($qrId);
        if (!$qr instanceof QrCodeAgro) {
            return NULL;
        }

        $code = $qr->get('short_code')->value ?? 'qr';
        $filename = 'qr_' . $code . '.' . $format;

        // Placeholder: generar contenido básico.
        // En producción, endroid/qr-code genera la imagen real.
        $content = match ($format) {
            'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#fff"/><text x="50" y="50" text-anchor="middle" fill="#2E7D32">' . $code . '</text></svg>',
            'png' => '', // Placeholder — endroid genera el PNG real.
            'pdf' => '', // Placeholder — librería TCPDF genera el PDF real.
            default => NULL,
        };

        if ($content === NULL) {
            return NULL;
        }

        return ['content' => $content, 'filename' => $filename];
    }

    // ===================================================
    // Lead Management (Doc 81 — Sprint AC6-1)
    // ===================================================

    /**
     * Lista leads con filtros y paginación.
     */
    public function getLeads(array $filters = [], int $page = 0, int $limit = 50): array
    {
        $storage = $this->entityTypeManager->getStorage('qr_lead_capture');

        // Query base.
        $baseQuery = $storage->getQuery()->accessCheck(FALSE);

        if (!empty($filters['capture_type'])) {
            $baseQuery->condition('source', $filters['capture_type']);
        }
        if (!empty($filters['qr_id'])) {
            $baseQuery->condition('qr_code_id', $filters['qr_id']);
        }
        if (!empty($filters['tenant_id'])) {
            $baseQuery->condition('tenant_id', $filters['tenant_id']);
        }

        // Clonar para count.
        $countQuery = clone $baseQuery;
        $total = (int) $countQuery->count()->execute();

        if ($limit === 0) {
            return ['items' => [], 'total' => $total];
        }

        // Paginado.
        $ids = $baseQuery
            ->sort('created', 'DESC')
            ->range($page * $limit, $limit)
            ->execute();

        $items = [];
        if (!empty($ids)) {
            $leads = $storage->loadMultiple($ids);
            foreach ($leads as $lead) {
                $items[] = $this->formatLead($lead);
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Detalle de un lead.
     */
    public function getLeadDetail(int $leadId): ?array
    {
        $lead = $this->entityTypeManager->getStorage('qr_lead_capture')->load($leadId);
        if (!$lead) {
            return NULL;
        }
        return $this->formatLead($lead);
    }

    /**
     * Elimina un lead.
     */
    public function deleteLead(int $leadId): bool
    {
        $lead = $this->entityTypeManager->getStorage('qr_lead_capture')->load($leadId);
        if (!$lead) {
            return FALSE;
        }
        $lead->delete();
        return TRUE;
    }

    /**
     * Exporta leads a CSV.
     */
    public function exportLeadsCsv(array $filters = []): string
    {
        $result = $this->getLeads($filters, 0, 10000);
        $leads = $result['items'];

        $csv = "ID,Email,Nombre,Telefono,Fuente,Consentimiento,Codigo_Descuento,Fecha\n";
        foreach ($leads as $lead) {
            $csv .= implode(',', [
                $lead['id'],
                '"' . str_replace('"', '""', $lead['email']) . '"',
                '"' . str_replace('"', '""', $lead['name'] ?? '') . '"',
                '"' . ($lead['phone'] ?? '') . '"',
                '"' . ($lead['source'] ?? '') . '"',
                $lead['consent'] ? 'Si' : 'No',
                '"' . ($lead['discount_code'] ?? '') . '"',
                '"' . ($lead['created'] ?? '') . '"',
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Sincroniza un lead a CRM externo.
     */
    public function syncLeadToCrm(int $leadId): array
    {
        $lead = $this->entityTypeManager->getStorage('qr_lead_capture')->load($leadId);
        if (!$lead) {
            return ['error' => 'Lead no encontrado.'];
        }

        // Placeholder: la lógica real conecta con Mailchimp/HubSpot/etc.
        // Por ahora marcamos como sincronizado.
        $lead->set('synced_to_crm', TRUE);
        $lead->save();

        return [
            'synced' => TRUE,
            'lead_id' => $leadId,
            'message' => 'Lead sincronizado correctamente (simulado).',
        ];
    }

    /**
     * Formatea una entidad lead para respuesta JSON.
     */
    protected function formatLead($lead): array
    {
        return [
            'id' => (int) $lead->id(),
            'email' => $lead->get('email')->value ?? '',
            'name' => $lead->get('name')->value ?? '',
            'phone' => $lead->get('phone')->value ?? '',
            'source' => $lead->get('source')->value ?? '',
            'consent' => (bool) ($lead->get('consent_given')->value ?? FALSE),
            'discount_code' => $lead->get('discount_code')->value ?? NULL,
            'qr_code_id' => (int) ($lead->get('qr_code_id')->target_id ?? 0),
            'created' => $lead->get('created')->value ?? '',
        ];
    }
}
