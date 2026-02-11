<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para crear entidades Drupal.
 *
 * Permite a los agentes crear entidades de cualquier tipo soportado.
 */
class CreateEntityTool extends BaseTool
{

    /**
     * Tipos de entidad permitidos.
     *
     * @var array
     */
    protected array $allowedTypes = [
        'node',
        'taxonomy_term',
        'tenant_knowledge',
        'ai_skill',
    ];

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
        return 'create_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Crear Entidad';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Crea una nueva entidad en el sistema (node, taxonomy, etc.).';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'entity_type' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Tipo de entidad (node, taxonomy_term, etc.).',
            ],
            'bundle' => [
                'type' => 'string',
                'required' => FALSE,
                'description' => 'Bundle/tipo de contenido (para node, término, etc.).',
            ],
            'values' => [
                'type' => 'array',
                'required' => TRUE,
                'description' => 'Valores de campos de la entidad.',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $params): array
    {
        $errors = parent::validate($params);

        if (isset($params['entity_type']) && !in_array($params['entity_type'], $this->allowedTypes)) {
            $errors[] = "Entity type '{$params['entity_type']}' is not allowed.";
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $entityType = $params['entity_type'];
        $bundle = $params['bundle'] ?? NULL;
        $values = $params['values'] ?? [];

        $this->log('Creating @type entity', ['@type' => $entityType]);

        try {
            $storage = $this->entityTypeManager->getStorage($entityType);

            // Añadir bundle si aplica.
            if ($bundle) {
                $bundleKey = $this->entityTypeManager
                    ->getDefinition($entityType)
                    ->getKey('bundle');
                if ($bundleKey) {
                    $values[$bundleKey] = $bundle;
                }
            }

            // Añadir tenant_id del contexto si la entidad lo soporta.
            if (!empty($context['tenant_id']) && !isset($values['tenant_id'])) {
                $values['tenant_id'] = $context['tenant_id'];
            }

            $entity = $storage->create($values);
            $entity->save();

            return $this->success([
                'entity_id' => $entity->id(),
                'entity_type' => $entityType,
                'bundle' => $bundle,
                'label' => $entity->label(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}
