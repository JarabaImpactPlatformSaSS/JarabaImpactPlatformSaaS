<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Servicio de colaboración tenant-to-tenant.
 *
 * PROPÓSITO:
 * Facilita la colaboración entre tenants del ecosistema:
 * - Solicitudes de colaboración (bundles, cross-promotions)
 * - Mensajería entre tenants
 * - Acuerdos de comisiones compartidas
 *
 * PHASE 13: Marketplace & Network Effects
 */
class TenantCollaborationService
{

    /**
     * Estados de colaboración.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Tipos de colaboración.
     */
    public const TYPE_BUNDLE = 'bundle';
    public const TYPE_CROSS_PROMOTION = 'cross_promotion';
    public const TYPE_REFERRAL = 'referral';
    public const TYPE_PARTNERSHIP = 'partnership';

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Envía una solicitud de colaboración a otro tenant.
     *
     * @param string $fromTenantId
     *   ID del tenant que envía.
     * @param string $toTenantId
     *   ID del tenant destinatario.
     * @param string $type
     *   Tipo de colaboración.
     * @param array $details
     *   Detalles de la propuesta.
     *
     * @return string
     *   ID de la solicitud creada.
     */
    public function sendCollaborationRequest(
        string $fromTenantId,
        string $toTenantId,
        string $type,
        array $details = []
    ): string {
        $requestId = Crypt::randomBytesBase64(12);

        $this->database->insert('tenant_collaboration_requests')
            ->fields([
                    'id' => $requestId,
                    'from_tenant_id' => $fromTenantId,
                    'to_tenant_id' => $toTenantId,
                    'type' => $type,
                    'status' => self::STATUS_PENDING,
                    'details' => json_encode($details),
                    'created' => time(),
                    'updated' => time(),
                ])
            ->execute();

        return $requestId;
    }

    /**
     * Acepta una solicitud de colaboración.
     */
    public function acceptRequest(string $requestId, array $terms = []): bool
    {
        $updated = $this->database->update('tenant_collaboration_requests')
            ->fields([
                    'status' => self::STATUS_ACCEPTED,
                    'terms' => json_encode($terms),
                    'accepted_at' => time(),
                    'updated' => time(),
                ])
            ->condition('id', $requestId)
            ->condition('status', self::STATUS_PENDING)
            ->execute();

        if ($updated) {
            $this->createPartnership($requestId);
        }

        return $updated > 0;
    }

    /**
     * Rechaza una solicitud de colaboración.
     */
    public function declineRequest(string $requestId, ?string $reason = NULL): bool
    {
        $updated = $this->database->update('tenant_collaboration_requests')
            ->fields([
                    'status' => self::STATUS_DECLINED,
                    'decline_reason' => $reason,
                    'updated' => time(),
                ])
            ->condition('id', $requestId)
            ->condition('status', self::STATUS_PENDING)
            ->execute();

        return $updated > 0;
    }

    /**
     * Obtiene solicitudes pendientes para un tenant.
     */
    public function getPendingRequests(string $tenantId): array
    {
        $results = $this->database->select('tenant_collaboration_requests', 'tcr')
            ->fields('tcr')
            ->condition('to_tenant_id', $tenantId)
            ->condition('status', self::STATUS_PENDING)
            ->orderBy('created', 'DESC')
            ->execute()
            ->fetchAll();

        return array_map(fn($row) => (array) $row, $results);
    }

    /**
     * Obtiene colaboraciones activas de un tenant.
     */
    public function getActivePartnerships(string $tenantId): array
    {
        $results = $this->database->select('tenant_partnerships', 'tp')
            ->fields('tp')
            ->condition(
                $this->database->condition('OR')
                    ->condition('tenant_a_id', $tenantId)
                    ->condition('tenant_b_id', $tenantId)
            )
            ->condition('status', 'active')
            ->execute()
            ->fetchAll();

        return array_map(fn($row) => (array) $row, $results);
    }

    /**
     * Crea una partnership activa tras aceptar solicitud.
     */
    protected function createPartnership(string $requestId): void
    {
        $request = $this->database->select('tenant_collaboration_requests', 'tcr')
            ->fields('tcr')
            ->condition('id', $requestId)
            ->execute()
            ->fetchObject();

        if (!$request) {
            return;
        }

        $this->database->insert('tenant_partnerships')
            ->fields([
                    'id' => Crypt::randomBytesBase64(12),
                    'request_id' => $requestId,
                    'tenant_a_id' => $request->from_tenant_id,
                    'tenant_b_id' => $request->to_tenant_id,
                    'type' => $request->type,
                    'status' => 'active',
                    'terms' => $request->terms ?? '{}',
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Envía un mensaje entre tenants.
     */
    public function sendMessage(
        string $fromTenantId,
        string $toTenantId,
        string $subject,
        string $message
    ): string {
        $messageId = Crypt::randomBytesBase64(12);

        $this->database->insert('tenant_messages')
            ->fields([
                    'id' => $messageId,
                    'from_tenant_id' => $fromTenantId,
                    'to_tenant_id' => $toTenantId,
                    'subject' => $subject,
                    'message' => $message,
                    'is_read' => 0,
                    'created' => time(),
                ])
            ->execute();

        return $messageId;
    }

    /**
     * Obtiene mensajes no leídos de un tenant.
     */
    public function getUnreadMessages(string $tenantId): array
    {
        $results = $this->database->select('tenant_messages', 'tm')
            ->fields('tm')
            ->condition('to_tenant_id', $tenantId)
            ->condition('is_read', 0)
            ->orderBy('created', 'DESC')
            ->execute()
            ->fetchAll();

        return array_map(fn($row) => (array) $row, $results);
    }

    /**
     * Marca mensaje como leído.
     */
    public function markAsRead(string $messageId): void
    {
        $this->database->update('tenant_messages')
            ->fields(['is_read' => 1, 'read_at' => time()])
            ->condition('id', $messageId)
            ->execute();
    }

    /**
     * Crea un bundle colaborativo entre productos de distintos tenants.
     */
    public function createCollaborativeBundle(
        string $name,
        array $products,
        float $discountPercent = 10.0
    ): string {
        $bundleId = Crypt::randomBytesBase64(12);

        $this->database->insert('collaborative_bundles')
            ->fields([
                    'id' => $bundleId,
                    'name' => $name,
                    'products' => json_encode($products),
                    'discount_percent' => $discountPercent,
                    'status' => 'active',
                    'created' => time(),
                ])
            ->execute();

        return $bundleId;
    }

    /**
     * Obtiene bundles colaborativos activos.
     */
    public function getActiveBundles(int $limit = 10): array
    {
        // Demo data - En producción vendría de la base de datos.
        return [
            [
                'id' => 'bundle1',
                'name' => 'Pack Mediterráneo',
                'products' => [
                    ['title' => 'Aceite de Oliva', 'tenant' => 'Finca Olivares'],
                    ['title' => 'Queso Manchego', 'tenant' => 'Quesería López'],
                    ['title' => 'Vino Tinto', 'tenant' => 'Bodega del Valle'],
                ],
                'original_price' => '€65.00',
                'bundle_price' => '€55.00',
                'discount' => '15%',
            ],
            [
                'id' => 'bundle2',
                'name' => 'Desayuno Gourmet',
                'products' => [
                    ['title' => 'Miel Ecológica', 'tenant' => 'ApiJaraba'],
                    ['title' => 'Pan Artesano', 'tenant' => 'Panadería Rural'],
                    ['title' => 'Mermelada Casera', 'tenant' => 'Dulces del Campo'],
                ],
                'original_price' => '€28.00',
                'bundle_price' => '€24.00',
                'discount' => '14%',
            ],
        ];
    }

}
