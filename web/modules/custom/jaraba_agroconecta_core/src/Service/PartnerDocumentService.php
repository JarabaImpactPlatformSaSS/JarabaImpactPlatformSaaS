<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio principal del Hub Documental B2B.
 *
 * Gestiona relaciones productor-partner, documentos compartidos,
 * control de acceso por niveles, descarga con auditoría, y analytics.
 */
class PartnerDocumentService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mapa de jerarquía de niveles de acceso.
     *
     * @var array<string, int>
     */
    protected const ACCESS_LEVELS = [
        'basico' => 1,
        'verificado' => 2,
        'premium' => 3,
    ];

    /**
     * Constructor.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * Crea una nueva relación con un partner.
     *
     * Genera token de acceso seguro y establece estado 'pending'.
     *
     * @param int $producer_id
     *   ID del productor.
     * @param string $partner_email
     *   Email del partner.
     * @param string $partner_name
     *   Nombre/empresa del partner.
     * @param string $partner_type
     *   Tipo de partner (distribuidor, exportador, etc.).
     * @param string $access_level
     *   Nivel de acceso (basico, verificado, premium).
     * @param int $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Datos de la relación creada.
     */
    public function createRelationship(
        int $producer_id,
        string $partner_email,
        string $partner_name,
        string $partner_type,
        string $access_level,
        int $tenant_id
    ): array {
        $storage = $this->entityTypeManager->getStorage('partner_relationship');

        // Verificar que no existe relación activa duplicada.
        $existing = $storage->loadByProperties([
            'producer_id' => $producer_id,
            'partner_email' => $partner_email,
            'status' => 'active',
        ]);

        if (!empty($existing)) {
            return ['error' => 'relationship_exists', 'message' => 'Ya existe una relación activa con este partner.'];
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $entity = $storage->create([
            'tenant_id' => $tenant_id,
            'producer_id' => $producer_id,
            'partner_email' => $partner_email,
            'partner_name' => $partner_name,
            'partner_type' => $partner_type,
            'access_level' => $access_level,
            'access_token' => bin2hex(random_bytes(32)),
            'status' => 'pending',
        ]);
        $entity->save();

        return [
            'id' => (int) $entity->id(),
            'uuid' => $entity->uuid(),
            'partner_name' => $entity->getPartnerName(),
            'partner_email' => $entity->getPartnerEmail(),
            'access_level' => $entity->getAccessLevel(),
            'status' => $entity->getStatus(),
            'access_token' => $entity->getAccessToken(),
        ];
    }

    /**
     * Activa una relación por token de acceso (magic link).
     *
     * @param string $token
     *   Token de acceso único.
     *
     * @return array|null
     *   Datos de la relación activada o null si token inválido.
     */
    public function activateByToken(string $token): ?array
    {
        $storage = $this->entityTypeManager->getStorage('partner_relationship');
        $entities = $storage->loadByProperties(['access_token' => $token]);

        if (empty($entities)) {
            return NULL;
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $entity = reset($entities);
        $entity->set('status', 'active');
        $entity->set('last_access_at', \Drupal::time()->getRequestTime());
        $entity->save();

        return [
            'id' => (int) $entity->id(),
            'uuid' => $entity->uuid(),
            'partner_name' => $entity->getPartnerName(),
            'access_level' => $entity->getAccessLevel(),
            'status' => 'active',
        ];
    }

    /**
     * Revoca el acceso de un partner.
     *
     * @param string $uuid
     *   UUID de la relación.
     *
     * @return bool
     *   TRUE si se revocó correctamente.
     */
    public function revokeRelationship(string $uuid): bool
    {
        $storage = $this->entityTypeManager->getStorage('partner_relationship');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        if (empty($entities)) {
            return FALSE;
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
        $entity = reset($entities);
        $entity->set('status', 'revoked');
        $entity->save();

        return TRUE;
    }

    /**
     * Lista partners de un productor con paginación.
     *
     * @param int $producer_id
     *   ID del productor.
     * @param int $page
     *   Página actual (0-indexed).
     * @param int $limit
     *   Resultados por página.
     *
     * @return array
     *   Lista paginada de partners.
     */
    public function getPartnersByProducer(int $producer_id, int $page = 0, int $limit = 20): array
    {
        $storage = $this->entityTypeManager->getStorage('partner_relationship');

        $query = $storage->getQuery()
            ->condition('producer_id', $producer_id)
            ->sort('created', 'DESC')
            ->range($page * $limit, $limit)
            ->accessCheck(FALSE);

        $ids = $query->execute();
        $entities = $storage->loadMultiple($ids);

        $items = [];
        foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $entity */
            $items[] = [
                'id' => (int) $entity->id(),
                'uuid' => $entity->uuid(),
                'partner_name' => $entity->getPartnerName(),
                'partner_email' => $entity->getPartnerEmail(),
                'partner_type' => $entity->getPartnerType(),
                'access_level' => $entity->getAccessLevel(),
                'status' => $entity->getStatus(),
                'last_access_at' => $entity->get('last_access_at')->value,
                'created' => $entity->get('created')->value,
            ];
        }

        // Contar total.
        $total = (int) $storage->getQuery()
            ->condition('producer_id', $producer_id)
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Obtiene documentos accesibles para un partner según su relación.
     *
     * Filtra por: nivel de acceso, tipo de partner, productos/categorías permitidos.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $relationship
     *   Relación partner activa.
     *
     * @return array
     *   Lista de documentos accesibles.
     */
    public function getAccessibleDocuments($relationship): array
    {
        $storage = $this->entityTypeManager->getStorage('product_document');
        $partnerLevel = self::ACCESS_LEVELS[$relationship->getAccessLevel()] ?? 1;
        $partnerType = $relationship->getPartnerType();
        $allowedProducts = $relationship->getAllowedProducts();

        $query = $storage->getQuery()
            ->condition('producer_id', $relationship->getProducerId())
            ->condition('is_active', TRUE)
            ->sort('document_type')
            ->sort('title')
            ->accessCheck(FALSE);

        $ids = $query->execute();
        $entities = $storage->loadMultiple($ids);

        $documents = [];
        foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */

            // Filtro por nivel de acceso.
            $requiredLevel = self::ACCESS_LEVELS[$entity->getMinAccessLevel()] ?? 1;
            if ($partnerLevel < $requiredLevel) {
                continue;
            }

            // Filtro por tipo de partner permitido.
            $allowedTypes = $entity->getAllowedPartnerTypes();
            if ($allowedTypes !== NULL && !in_array($partnerType, $allowedTypes, TRUE)) {
                continue;
            }

            // Filtro por producto permitido.
            $productId = $entity->getProductId();
            if ($productId && $allowedProducts !== NULL && !in_array($productId, $allowedProducts, TRUE)) {
                continue;
            }

            $documents[] = [
                'id' => (int) $entity->id(),
                'uuid' => $entity->uuid(),
                'title' => $entity->getTitle(),
                'document_type' => $entity->getDocumentType(),
                'version' => $entity->getVersion(),
                'product_id' => $productId,
                'min_access_level' => $entity->getMinAccessLevel(),
                'language' => $entity->get('language_code')->value ?? 'es',
                'download_count' => $entity->getDownloadCount(),
                'valid_until' => $entity->get('valid_until')->value,
                'created' => $entity->get('created')->value,
            ];
        }

        return $documents;
    }

    /**
     * Sube un nuevo documento.
     *
     * @param array $data
     *   Datos del documento.
     * @param int $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Datos del documento creado.
     */
    public function uploadDocument(array $data, int $tenant_id): array
    {
        $storage = $this->entityTypeManager->getStorage('product_document');

        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
        $entity = $storage->create([
            'tenant_id' => $tenant_id,
            'producer_id' => $data['producer_id'],
            'product_id' => $data['product_id'] ?? NULL,
            'title' => $data['title'],
            'document_type' => $data['document_type'],
            'file_id' => $data['file_id'],
            'min_access_level' => $data['min_access_level'] ?? 'basico',
            'allowed_partner_types' => isset($data['allowed_partner_types']) ? json_encode($data['allowed_partner_types']) : NULL,
            'version' => $data['version'] ?? '1.0',
            'valid_from' => $data['valid_from'] ?? NULL,
            'valid_until' => $data['valid_until'] ?? NULL,
            'language_code' => $data['language'] ?? 'es',
            'is_auto_generated' => $data['is_auto_generated'] ?? FALSE,
            'is_active' => TRUE,
        ]);
        $entity->save();

        return [
            'id' => (int) $entity->id(),
            'uuid' => $entity->uuid(),
            'title' => $entity->getTitle(),
            'document_type' => $entity->getDocumentType(),
            'version' => $entity->getVersion(),
        ];
    }

    /**
     * Actualiza permisos/metadatos de un documento.
     *
     * @param string $uuid
     *   UUID del documento.
     * @param array $data
     *   Campos a actualizar.
     *
     * @return array|null
     *   Datos actualizados o null si no se encontró.
     */
    public function updateDocument(string $uuid, array $data): ?array
    {
        $storage = $this->entityTypeManager->getStorage('product_document');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        if (empty($entities)) {
            return NULL;
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
        $entity = reset($entities);

        $updatable = ['title', 'min_access_level', 'version', 'valid_from', 'valid_until', 'language_code', 'is_active'];
        foreach ($updatable as $field) {
            if (array_key_exists($field, $data)) {
                $entity->set($field, $data[$field]);
            }
        }
        if (isset($data['allowed_partner_types'])) {
            $entity->set('allowed_partner_types', json_encode($data['allowed_partner_types']));
        }
        $entity->save();

        return [
            'uuid' => $entity->uuid(),
            'title' => $entity->getTitle(),
            'updated' => TRUE,
        ];
    }

    /**
     * Desactiva un documento (soft delete).
     *
     * @param string $uuid
     *   UUID del documento.
     *
     * @return bool
     *   TRUE si se desactivó correctamente.
     */
    public function deleteDocument(string $uuid): bool
    {
        $storage = $this->entityTypeManager->getStorage('product_document');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        if (empty($entities)) {
            return FALSE;
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
        $entity = reset($entities);
        $entity->set('is_active', FALSE);
        $entity->save();

        return TRUE;
    }

    /**
     * Lista documentos de un productor.
     *
     * @param int $producer_id
     *   ID del productor.
     * @param int $page
     *   Página actual.
     * @param int $limit
     *   Resultados por página.
     *
     * @return array
     *   Lista paginada de documentos.
     */
    public function getDocumentsByProducer(int $producer_id, int $page = 0, int $limit = 20): array
    {
        $storage = $this->entityTypeManager->getStorage('product_document');

        $query = $storage->getQuery()
            ->condition('producer_id', $producer_id)
            ->condition('is_active', TRUE)
            ->sort('created', 'DESC')
            ->range($page * $limit, $limit)
            ->accessCheck(FALSE);

        $ids = $query->execute();
        $entities = $storage->loadMultiple($ids);

        $items = [];
        foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $entity */
            $items[] = [
                'id' => (int) $entity->id(),
                'uuid' => $entity->uuid(),
                'title' => $entity->getTitle(),
                'document_type' => $entity->getDocumentType(),
                'product_id' => $entity->getProductId(),
                'min_access_level' => $entity->getMinAccessLevel(),
                'version' => $entity->getVersion(),
                'download_count' => $entity->getDownloadCount(),
                'valid_until' => $entity->get('valid_until')->value,
                'is_auto_generated' => $entity->isAutoGenerated(),
                'created' => $entity->get('created')->value,
            ];
        }

        $total = (int) $storage->getQuery()
            ->condition('producer_id', $producer_id)
            ->condition('is_active', TRUE)
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Registra una descarga en el audit log.
     *
     * @param int $document_id
     *   ID del documento descargado.
     * @param int $relationship_id
     *   ID de la relación partner.
     * @param int $tenant_id
     *   ID del tenant.
     * @param string|null $ip_address
     *   Dirección IP del partner.
     * @param string|null $user_agent
     *   User agent del navegador.
     */
    public function logDownload(
        int $document_id,
        int $relationship_id,
        int $tenant_id,
        ?string $ip_address = NULL,
        ?string $user_agent = NULL
    ): void {
        // Registrar en audit log.
        $logStorage = $this->entityTypeManager->getStorage('document_download_log');
        $log = $logStorage->create([
            'tenant_id' => $tenant_id,
            'document_id' => $document_id,
            'relationship_id' => $relationship_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent ? mb_substr($user_agent, 0, 255) : NULL,
        ]);
        $log->save();

        // Incrementar contador de descargas del documento.
        $docStorage = $this->entityTypeManager->getStorage('product_document');
        $document = $docStorage->load($document_id);
        if ($document) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\ProductDocument $document */
            $currentCount = $document->getDownloadCount();
            $document->set('download_count', $currentCount + 1);
            $document->save();
        }
    }

    /**
     * Genera analytics delHub para un productor.
     *
     * @param int $producer_id
     *   ID del productor.
     *
     * @return array
     *   Dashboard de métricas.
     */
    public function getProducerAnalytics(int $producer_id): array
    {
        $partnerStorage = $this->entityTypeManager->getStorage('partner_relationship');
        $docStorage = $this->entityTypeManager->getStorage('product_document');
        $logStorage = $this->entityTypeManager->getStorage('document_download_log');

        // Partners activos.
        $activePartners = (int) $partnerStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->condition('status', 'active')
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        // Total partners.
        $totalPartners = (int) $partnerStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        // Total documentos activos.
        $totalDocs = (int) $docStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->condition('is_active', TRUE)
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        // Total descargas.
        $docIds = $docStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->accessCheck(FALSE)
            ->execute();

        $totalDownloads = 0;
        if (!empty($docIds)) {
            $totalDownloads = (int) $logStorage->getQuery()
                ->condition('document_id', $docIds, 'IN')
                ->count()
                ->accessCheck(FALSE)
                ->execute();
        }

        // Descargas última semana.
        $weekAgo = \Drupal::time()->getRequestTime() - (7 * 24 * 3600);
        $weekDownloads = 0;
        if (!empty($docIds)) {
            $weekDownloads = (int) $logStorage->getQuery()
                ->condition('document_id', $docIds, 'IN')
                ->condition('downloaded_at', $weekAgo, '>=')
                ->count()
                ->accessCheck(FALSE)
                ->execute();
        }

        // Certificaciones por caducar (30 días).
        $thirtyDaysAhead = date('Y-m-d', \Drupal::time()->getRequestTime() + (30 * 24 * 3600));
        $expiringCerts = (int) $docStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->condition('document_type', 'certificacion')
            ->condition('is_active', TRUE)
            ->condition('valid_until', $thirtyDaysAhead, '<=')
            ->condition('valid_until', date('Y-m-d'), '>=')
            ->count()
            ->accessCheck(FALSE)
            ->execute();

        return [
            'active_partners' => $activePartners,
            'total_partners' => $totalPartners,
            'total_documents' => $totalDocs,
            'total_downloads' => $totalDownloads,
            'week_downloads' => $weekDownloads,
            'expiring_certifications' => $expiringCerts,
        ];
    }

    /**
     * Exporta el log de descargas como CSV.
     *
     * @param int $producer_id
     *   ID del productor.
     *
     * @return string
     *   Contenido CSV.
     */
    public function exportDownloadsCsv(int $producer_id): string
    {
        $docStorage = $this->entityTypeManager->getStorage('product_document');
        $logStorage = $this->entityTypeManager->getStorage('document_download_log');
        $partnerStorage = $this->entityTypeManager->getStorage('partner_relationship');

        $docIds = $docStorage->getQuery()
            ->condition('producer_id', $producer_id)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($docIds)) {
            return "documento,partner,fecha,ip\n";
        }

        $logIds = $logStorage->getQuery()
            ->condition('document_id', $docIds, 'IN')
            ->sort('downloaded_at', 'DESC')
            ->accessCheck(FALSE)
            ->execute();

        $logs = $logStorage->loadMultiple($logIds);
        $csv = "documento,partner,fecha,ip\n";

        foreach ($logs as $log) {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\DocumentDownloadLog $log */
            $doc = $docStorage->load($log->getDocumentId());
            $partner = $partnerStorage->load($log->getRelationshipId());

            $docTitle = $doc ? $doc->label() : '-';
            $partnerName = $partner ? $partner->label() : '-';
            $date = $log->get('downloaded_at')->value
                ? date('Y-m-d H:i:s', (int) $log->get('downloaded_at')->value)
                : '-';
            $ip = $log->get('ip_address')->value ?? '-';

            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $docTitle),
                str_replace('"', '""', $partnerName),
                $date,
                $ip
            );
        }

        return $csv;
    }

    /**
     * Busca una relación partner por token.
     *
     * @param string $token
     *   Token de acceso.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship|null
     *   La entidad relación o null.
     */
    public function findByToken(string $token)
    {
        $storage = $this->entityTypeManager->getStorage('partner_relationship');
        $entities = $storage->loadByProperties([
            'access_token' => $token,
            'status' => 'active',
        ]);

        return !empty($entities) ? reset($entities) : NULL;
    }

    /**
     * Obtiene productos accesibles para un partner.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\PartnerRelationship $relationship
     *   Relación partner.
     *
     * @return array
     *   Lista de productos con sus documentos.
     */
    public function getAccessibleProducts($relationship): array
    {
        $productStorage = $this->entityTypeManager->getStorage('product_agro');
        $allowedProducts = $relationship->getAllowedProducts();

        $query = $productStorage->getQuery()
            ->condition('tenant_id', $relationship->get('tenant_id')->target_id)
            ->sort('label')
            ->accessCheck(FALSE);

        if ($allowedProducts !== NULL) {
            $query->condition('id', $allowedProducts, 'IN');
        }

        $ids = $query->execute();
        $products = $productStorage->loadMultiple($ids);

        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'id' => (int) $product->id(),
                'title' => $product->label(),
            ];
        }

        return $items;
    }

}
