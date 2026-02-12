<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Entity\AgroBatch;
use Drupal\jaraba_agroconecta_core\Entity\IntegrityProofAgro;
use Drupal\jaraba_agroconecta_core\Entity\TraceEventAgro;

/**
 * Servicio de trazabilidad para AgroConecta.
 *
 * RESPONSABILIDADES:
 * - Gestión de lotes de producción.
 * - Registro inmutable de eventos con hash encadenado (blockchain-like).
 * - Generación de pruebas de integridad.
 * - Verificación de cadena de eventos.
 * - Serialización para API pública y landing pages.
 */
class TraceabilityService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Registra un nuevo evento de trazabilidad en la cadena del lote.
     *
     * Calcula el hash del evento y lo enlaza al hash anterior,
     * actualizando el chain_hash del lote.
     */
    public function addTraceEvent(int $batchId, string $eventType, array $eventData): ?array
    {
        $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);
        if (!$batch instanceof AgroBatch || $batch->isSealed()) {
            return NULL;
        }

        // Obtener último evento de la cadena.
        $lastEvent = $this->getLastEvent($batchId);
        $previousHash = $lastEvent ? $lastEvent->getEventHash() : '0000000000000000000000000000000000000000000000000000000000000000';
        $sequence = $lastEvent ? $lastEvent->getSequence() + 1 : 1;

        // Calcular hash del evento.
        $eventHash = $this->calculateEventHash($batchId, $eventType, $eventData, $previousHash, $sequence);

        // Crear evento.
        $storage = $this->entityTypeManager->getStorage('trace_event_agro');
        /** @var TraceEventAgro $event */
        $event = $storage->create([
            'batch_id' => $batchId,
            'event_type' => $eventType,
            'description' => $eventData['description'] ?? '',
            'location' => $eventData['location'] ?? '',
            'event_timestamp' => $eventData['event_timestamp'] ?? date('Y-m-d\TH:i:s'),
            'actor' => $eventData['actor'] ?? '',
            'metadata' => !empty($eventData['metadata']) ? json_encode($eventData['metadata']) : NULL,
            'evidence_url' => $eventData['evidence_url'] ?? '',
            'event_hash' => $eventHash,
            'previous_hash' => $previousHash,
            'sequence' => $sequence,
            'uid' => $eventData['uid'] ?? \Drupal::currentUser()->id(),
        ]);
        $event->save();

        // Actualizar chain_hash del lote.
        $batch->set('chain_hash', $eventHash);
        $batch->save();

        return $this->serializeEvent($event);
    }

    /**
     * Verifica la integridad de la cadena de un lote.
     *
     * Recalcula todos los hashes de la cadena y verifica que
     * coincidan con los almacenados.
     */
    public function verifyChainIntegrity(int $batchId): array
    {
        $events = $this->getChainEvents($batchId);
        $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);

        if (empty($events) || !$batch instanceof AgroBatch) {
            return ['valid' => TRUE, 'events_checked' => 0, 'errors' => []];
        }

        $errors = [];
        $previousHash = '0000000000000000000000000000000000000000000000000000000000000000';

        foreach ($events as $event) {
            // Verificar que el previous_hash coincida.
            if ($event->getPreviousHash() !== $previousHash) {
                $errors[] = [
                    'event_id' => (int) $event->id(),
                    'sequence' => $event->getSequence(),
                    'type' => 'broken_chain',
                    'message' => 'Hash anterior no coincide.',
                ];
            }

            // Recalcular hash y verificar.
            $recalculated = $this->calculateEventHash(
                $batchId,
                $event->getEventType(),
                [
                    'description' => $event->get('description')->value,
                    'location' => $event->get('location')->value,
                    'event_timestamp' => $event->get('event_timestamp')->value,
                    'actor' => $event->get('actor')->value,
                ],
                $event->getPreviousHash(),
                $event->getSequence()
            );

            if ($recalculated !== $event->getEventHash()) {
                $errors[] = [
                    'event_id' => (int) $event->id(),
                    'sequence' => $event->getSequence(),
                    'type' => 'hash_mismatch',
                    'message' => 'Hash del evento no coincide con los datos almacenados.',
                ];
            }

            $previousHash = $event->getEventHash();
        }

        // Verificar chain_hash del lote.
        $lastEvent = end($events);
        if ($lastEvent && $batch->getChainHash() !== $lastEvent->getEventHash()) {
            $errors[] = [
                'type' => 'batch_hash_mismatch',
                'message' => 'Hash del lote no coincide con el último evento.',
            ];
        }

        return [
            'valid' => empty($errors),
            'events_checked' => count($events),
            'errors' => $errors,
            'chain_hash' => $batch->getChainHash(),
        ];
    }

    /**
     * Crea una prueba de integridad para anclar el estado actual de la cadena.
     */
    public function createIntegrityProof(int $batchId, string $anchorType = 'internal'): ?array
    {
        $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);
        if (!$batch instanceof AgroBatch || empty($batch->getChainHash())) {
            return NULL;
        }

        $events = $this->getChainEvents($batchId);

        $proofStorage = $this->entityTypeManager->getStorage('integrity_proof_agro');
        /** @var IntegrityProofAgro $proof */
        $proof = $proofStorage->create([
            'batch_id' => $batchId,
            'proof_hash' => $batch->getChainHash(),
            'anchor_type' => $anchorType,
            'event_count' => count($events),
            'proof_timestamp' => date('Y-m-d\TH:i:s'),
            'verification_status' => ($anchorType === 'internal') ? 'verified' : 'pending',
            'uid' => \Drupal::currentUser()->id(),
        ]);
        $proof->save();

        return [
            'id' => (int) $proof->id(),
            'batch_id' => $batchId,
            'batch_code' => $batch->getBatchCode(),
            'proof_hash' => $proof->getProofHash(),
            'anchor_type' => $proof->getAnchorType(),
            'event_count' => count($events),
            'verification_status' => $proof->get('verification_status')->value,
        ];
    }

    /**
     * Obtiene la historia completa de un lote para la landing pública.
     */
    public function getBatchTraceability(int $batchId): ?array
    {
        $batch = $this->entityTypeManager->getStorage('agro_batch')->load($batchId);
        if (!$batch instanceof AgroBatch) {
            return NULL;
        }

        $events = $this->getChainEvents($batchId);
        $integrity = $this->verifyChainIntegrity($batchId);

        // Cargar producto y productor.
        $product = $batch->get('product_id')->entity;
        $producer = $batch->get('producer_id')->entity;

        return [
            'batch' => [
                'id' => (int) $batch->id(),
                'code' => $batch->getBatchCode(),
                'origin' => $batch->get('origin')->value ?? '',
                'variety' => $batch->get('variety')->value ?? '',
                'harvest_date' => $batch->get('harvest_date')->value ?? '',
                'quantity' => $batch->get('quantity')->value ?? '',
                'unit' => $batch->get('unit')->value ?? 'kg',
                'status' => $batch->getStatus(),
            ],
            'product' => $product ? [
                'id' => (int) $product->id(),
                'name' => $product->label(),
            ] : NULL,
            'producer' => $producer ? [
                'id' => (int) $producer->id(),
                'name' => $producer->label(),
            ] : NULL,
            'events' => array_map([$this, 'serializeEvent'], $events),
            'integrity' => $integrity,
            'total_events' => count($events),
        ];
    }

    /**
     * Busca un lote por código (para URL pública y API).
     */
    public function findBatchByCode(string $code): ?AgroBatch
    {
        $storage = $this->entityTypeManager->getStorage('agro_batch');
        $ids = $storage->getQuery()
            ->condition('batch_code', $code)
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        $batch = $storage->load(reset($ids));
        return $batch instanceof AgroBatch ? $batch : NULL;
    }

    // ===================================================
    // Métodos internos
    // ===================================================

    protected function calculateEventHash(int $batchId, string $type, array $data, string $previousHash, int $sequence): string
    {
        $payload = implode('|', [
            $batchId,
            $type,
            $data['description'] ?? '',
            $data['location'] ?? '',
            $data['event_timestamp'] ?? '',
            $data['actor'] ?? '',
            $previousHash,
            $sequence,
        ]);
        return hash('sha256', $payload);
    }

    protected function getLastEvent(int $batchId): ?TraceEventAgro
    {
        $storage = $this->entityTypeManager->getStorage('trace_event_agro');
        $ids = $storage->getQuery()
            ->condition('batch_id', $batchId)
            ->sort('sequence', 'DESC')
            ->range(0, 1)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        $event = $storage->load(reset($ids));
        return $event instanceof TraceEventAgro ? $event : NULL;
    }

    /**
     * @return TraceEventAgro[]
     */
    protected function getChainEvents(int $batchId): array
    {
        $storage = $this->entityTypeManager->getStorage('trace_event_agro');
        $ids = $storage->getQuery()
            ->condition('batch_id', $batchId)
            ->sort('sequence', 'ASC')
            ->accessCheck(FALSE)
            ->execute();

        if (empty($ids)) {
            return [];
        }

        return array_values(array_filter(
            $storage->loadMultiple($ids),
            fn($e) => $e instanceof TraceEventAgro
        ));
    }

    protected function serializeEvent(TraceEventAgro $event): array
    {
        return [
            'id' => (int) $event->id(),
            'type' => $event->getEventType(),
            'description' => $event->get('description')->value ?? '',
            'location' => $event->get('location')->value ?? '',
            'timestamp' => $event->get('event_timestamp')->value ?? '',
            'actor' => $event->get('actor')->value ?? '',
            'evidence_url' => $event->get('evidence_url')->value ?? '',
            'sequence' => $event->getSequence(),
            'hash' => $event->getEventHash(),
        ];
    }
}
