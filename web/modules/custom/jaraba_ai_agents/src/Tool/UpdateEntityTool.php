<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para actualizar campos en entidades Drupal existentes.
 *
 * REQUIERE APROBACION: Si, porque modifica datos persistentes.
 *
 * Valida que la entidad exista antes de intentar la actualizacion.
 * Soporta cualquier tipo de entidad gestionado por EntityTypeManager.
 */
class UpdateEntityTool extends BaseTool
{

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
        return 'update_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Actualizar Entidad';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Actualiza campos en una entidad Drupal existente.';
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
                'description' => 'Tipo de entidad (node, user, taxonomy_term, etc.).',
            ],
            'entity_id' => [
                'type' => 'int',
                'required' => TRUE,
                'description' => 'ID de la entidad a actualizar.',
            ],
            'fields' => [
                'type' => 'array',
                'required' => TRUE,
                'description' => 'Array asociativo de campos y sus nuevos valores.',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function requiresApproval(): bool
    {
        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $params): array
    {
        $errors = parent::validate($params);

        if (isset($params['fields']) && is_array($params['fields']) && empty($params['fields'])) {
            $errors[] = "Parameter 'fields' must not be empty.";
        }

        if (isset($params['entity_id']) && (int) $params['entity_id'] <= 0) {
            $errors[] = "Parameter 'entity_id' must be a positive integer.";
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $entityType = $params['entity_type'];
        $entityId = (int) $params['entity_id'];
        $fields = $params['fields'];

        $this->log('Updating @type entity @id', [
            '@type' => $entityType,
            '@id' => $entityId,
        ]);

        try {
            $storage = $this->entityTypeManager->getStorage($entityType);
            $entity = $storage->load($entityId);

            if (!$entity) {
                return $this->error("Entity '{$entityType}' with ID {$entityId} not found.");
            }

            $updatedFields = [];
            foreach ($fields as $fieldName => $value) {
                if (!$entity->hasField($fieldName)) {
                    $this->log('Skipping non-existent field @field on @type @id', [
                        '@field' => $fieldName,
                        '@type' => $entityType,
                        '@id' => $entityId,
                    ]);
                    continue;
                }

                $entity->set($fieldName, $value);
                $updatedFields[] = $fieldName;
            }

            if (empty($updatedFields)) {
                return $this->error('No valid fields to update on the entity.');
            }

            $entity->save();

            return $this->success([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'updated_fields' => $updatedFields,
                'label' => $entity->label(),
            ]);
        }
        catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}
