<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Agent Long-Term Memory Service (FIX-039).
 *
 * Provides persistent cross-session memory for agents backed by
 * database (structured facts) and optionally Qdrant (semantic recall).
 *
 * Memory types: fact, preference, interaction_summary, correction.
 */
class AgentLongTermMemoryService
{

    /**
     * Valid memory types.
     */
    protected const MEMORY_TYPES = ['fact', 'preference', 'interaction_summary', 'correction'];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ?object $qdrantClient = NULL,
    ) {
    }

    /**
     * Stores a memory for an agent/tenant combination.
     *
     * @param string $agentId
     *   The agent ID.
     * @param string $tenantId
     *   The tenant ID.
     * @param string $type
     *   Memory type: fact, preference, interaction_summary, correction.
     * @param string $content
     *   The memory content text.
     * @param array $metadata
     *   Additional metadata.
     *
     * @return array
     *   Result with success and memory_id.
     */
    public function remember(string $agentId, string $tenantId, string $type, string $content, array $metadata = []): array
    {
        if (!in_array($type, self::MEMORY_TYPES, TRUE)) {
            return ['success' => FALSE, 'error' => "Invalid memory type: {$type}"];
        }

        try {
            // Store in database via shared_memory entity if available.
            if ($this->entityTypeManager->hasDefinition('shared_memory')) {
                $storage = $this->entityTypeManager->getStorage('shared_memory');
                $memory = $storage->create([
                    'agent_id' => $agentId,
                    'tenant_id' => $tenantId,
                    'memory_type' => $type,
                    'content' => $content,
                    'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'created' => time(),
                ]);
                $memory->save();

                $memoryId = $memory->id();
            }
            else {
                // Fallback: use state API for simple key-value storage.
                $key = "agent_memory:{$agentId}:{$tenantId}:" . time();
                \Drupal::state()->set($key, [
                    'type' => $type,
                    'content' => $content,
                    'metadata' => $metadata,
                    'created' => time(),
                ]);
                $memoryId = $key;
            }

            // Optionally index in Qdrant for semantic recall.
            if ($this->qdrantClient && method_exists($this->qdrantClient, 'upsert')) {
                try {
                    $this->indexInQdrant($agentId, $tenantId, $content, $metadata);
                } catch (\Exception $e) {
                    $this->logger->warning('Qdrant indexing failed for memory: @msg', ['@msg' => $e->getMessage()]);
                }
            }

            $this->logger->info('Memory stored: agent=@agent, tenant=@tenant, type=@type', [
                '@agent' => $agentId,
                '@tenant' => $tenantId,
                '@type' => $type,
            ]);

            return [
                'success' => TRUE,
                'memory_id' => $memoryId,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to store memory: @msg', ['@msg' => $e->getMessage()]);
            return ['success' => FALSE, 'error' => $e->getMessage()];
        }
    }

    /**
     * Recalls relevant memories for an agent/tenant.
     *
     * @param string $agentId
     *   The agent ID.
     * @param string $tenantId
     *   The tenant ID.
     * @param string|null $query
     *   Optional query for semantic search.
     * @param int $limit
     *   Maximum memories to return.
     *
     * @return array
     *   Array of memory items.
     */
    public function recall(string $agentId, string $tenantId, ?string $query = NULL, int $limit = 10): array
    {
        $memories = [];

        try {
            if ($this->entityTypeManager->hasDefinition('shared_memory')) {
                $storage = $this->entityTypeManager->getStorage('shared_memory');
                $ids = $storage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('agent_id', $agentId)
                    ->condition('tenant_id', $tenantId)
                    ->sort('created', 'DESC')
                    ->range(0, $limit)
                    ->execute();

                if (!empty($ids)) {
                    $entities = $storage->loadMultiple($ids);
                    foreach ($entities as $entity) {
                        $memories[] = [
                            'id' => $entity->id(),
                            'type' => $entity->get('memory_type')->value ?? 'fact',
                            'content' => $entity->get('content')->value ?? '',
                            'metadata' => json_decode($entity->get('metadata')->value ?? '{}', TRUE),
                            'created' => $entity->get('created')->value ?? 0,
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->warning('Memory recall failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return $memories;
    }

    /**
     * Builds a prompt section with agent memories.
     *
     * @param string $agentId
     *   The agent ID.
     * @param string $tenantId
     *   The tenant ID.
     *
     * @return string
     *   XML memory section for system prompt injection.
     */
    public function buildMemoryPrompt(string $agentId, string $tenantId): string
    {
        $memories = $this->recall($agentId, $tenantId, NULL, 5);

        if (empty($memories)) {
            return '';
        }

        $output = "<agent_memory>\n";
        foreach ($memories as $memory) {
            $output .= "  <memory type=\"{$memory['type']}\">{$memory['content']}</memory>\n";
        }
        $output .= "</agent_memory>";

        return $output;
    }

    /**
     * Indexes a memory in Qdrant for semantic recall.
     */
    protected function indexInQdrant(string $agentId, string $tenantId, string $content, array $metadata): void
    {
        // Qdrant integration: would generate embedding and upsert.
        // Deferred until Qdrant client is configured for this collection.
        $this->logger->debug('Qdrant memory indexing deferred for agent @agent.', ['@agent' => $agentId]);
    }

}
