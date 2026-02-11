<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\jaraba_tenant_knowledge\Service\KnowledgeIndexerService;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para buscar en el conocimiento del tenant.
 *
 * Utiliza RAG para encontrar información relevante.
 */
class SearchKnowledgeTool extends BaseTool
{

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected KnowledgeIndexerService $knowledgeIndexer,
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'search_knowledge';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Buscar Conocimiento';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Busca información relevante en la base de conocimiento del tenant usando RAG.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Pregunta o consulta para buscar.',
            ],
            'limit' => [
                'type' => 'int',
                'required' => FALSE,
                'description' => 'Número máximo de resultados (default: 5).',
                'default' => 5,
            ],
            'tenant_id' => [
                'type' => 'int',
                'required' => FALSE,
                'description' => 'ID del tenant (usa contexto si no se especifica).',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $query = $params['query'];
        $limit = $params['limit'] ?? 5;
        $tenantId = $params['tenant_id'] ?? $context['tenant_id'] ?? NULL;

        $this->log('Searching knowledge for: @query', ['@query' => $query]);

        try {
            $results = $this->knowledgeIndexer->searchKnowledge($query, (int) $tenantId, ['limit' => $limit]);

            return $this->success([
                'query' => $query,
                'results_count' => count($results),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}
