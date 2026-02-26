<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para busqueda full-text en entidades de contenido Drupal.
 *
 * Busca por titulo y body en entidades del tipo especificado.
 * NO REQUIERE APROBACION: Es una operacion de solo lectura.
 */
class SearchContentTool extends BaseTool
{

    /**
     * Limite maximo de resultados permitido.
     */
    protected const MAX_LIMIT = 50;

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'search_content';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Buscar Contenido';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Busqueda full-text en entidades de contenido Drupal por titulo y body.';
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
                'description' => 'Texto de busqueda.',
            ],
            'entity_type' => [
                'type' => 'string',
                'required' => FALSE,
                'description' => 'Tipo de entidad a buscar (default: node).',
                'default' => 'node',
            ],
            'limit' => [
                'type' => 'int',
                'required' => FALSE,
                'description' => 'Numero maximo de resultados (default: 10, max: 50).',
                'default' => 10,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function requiresApproval(): bool
    {
        return FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $searchQuery = $params['query'];
        $entityType = $params['entity_type'] ?? 'node';
        $limit = min((int) ($params['limit'] ?? 10), self::MAX_LIMIT);

        $this->log('Searching @type for: @query (limit: @limit)', [
            '@type' => $entityType,
            '@query' => $searchQuery,
            '@limit' => $limit,
        ]);

        try {
            $storage = $this->entityTypeManager->getStorage($entityType);
            $definition = $this->entityTypeManager->getDefinition($entityType);

            // Determine the label key for this entity type.
            $labelKey = $definition->getKey('label');
            if (!$labelKey) {
                // Fallback for entity types without a label key (e.g. node uses 'title').
                $labelKey = 'title';
            }

            // Build query with OR condition group on title/body.
            $entityQuery = $storage->getQuery()
                ->accessCheck(TRUE)
                ->range(0, $limit);

            $orGroup = $entityQuery->orConditionGroup();
            $orGroup->condition($labelKey, '%' . $searchQuery . '%', 'LIKE');

            // Add body condition if the entity type has a body field.
            if ($definition->hasKey('id')) {
                $orGroup->condition('body', '%' . $searchQuery . '%', 'LIKE');
            }

            $entityQuery->condition($orGroup);

            // Filter by published status if the entity supports it.
            $statusKey = $definition->getKey('status');
            if ($statusKey) {
                $entityQuery->condition($statusKey, 1);
            }

            $ids = $entityQuery->execute();

            if (empty($ids)) {
                return $this->success([
                    'query' => $searchQuery,
                    'entity_type' => $entityType,
                    'results_count' => 0,
                    'results' => [],
                ]);
            }

            $entities = $storage->loadMultiple($ids);
            $results = [];

            foreach ($entities as $entity) {
                $result = [
                    'entity_id' => (int) $entity->id(),
                    'entity_type' => $entityType,
                    'label' => $entity->label(),
                ];

                // Include bundle if available.
                $bundleKey = $definition->getKey('bundle');
                if ($bundleKey && $entity->hasField($bundleKey)) {
                    $result['bundle'] = $entity->bundle();
                }

                // Include a snippet from body if available.
                if ($entity->hasField('body') && !$entity->get('body')->isEmpty()) {
                    $bodyValue = $entity->get('body')->value ?? '';
                    $result['snippet'] = mb_substr(strip_tags($bodyValue), 0, 200);
                }

                $results[] = $result;
            }

            return $this->success([
                'query' => $searchQuery,
                'entity_type' => $entityType,
                'results_count' => count($results),
                'results' => $results,
            ]);
        }
        catch (\Exception $e) {
            return $this->error('Content search failed: ' . $e->getMessage());
        }
    }

}
