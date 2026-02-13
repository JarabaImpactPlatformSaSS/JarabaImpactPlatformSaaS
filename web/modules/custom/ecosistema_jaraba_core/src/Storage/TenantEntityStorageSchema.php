<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Storage schema with automatic tenant_id indexing.
 *
 * AUDIT-PERF-001: Adds DB indexes on tenant_id and common composite
 * fields (tenant_id+status, tenant_id+created) for ALL Content Entities
 * that define a tenant_id base field.
 *
 * Applied globally via hook_entity_type_alter() in
 * ecosistema_jaraba_core.module — zero per-entity annotation changes.
 *
 * @see ecosistema_jaraba_core_entity_type_alter()
 */
class TenantEntityStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $baseTable = $entity_type->getBaseTable();

    if (!$baseTable || empty($schema[$baseTable])) {
      return $schema;
    }

    $tableSchema = &$schema[$baseTable];
    $existingFields = $tableSchema['fields'] ?? [];

    // AUDIT-PERF-001: Index on tenant_id (single column).
    if (isset($existingFields['tenant_id'])) {
      $tableSchema['indexes']['idx_tenant_id'] = ['tenant_id'];

      // Composite: tenant_id + created (time-series queries).
      if (isset($existingFields['created'])) {
        $tableSchema['indexes']['idx_tenant_created'] = ['tenant_id', 'created'];
      }

      // Composite: tenant_id + status (workflow filtering).
      if (isset($existingFields['status'])) {
        $tableSchema['indexes']['idx_tenant_status'] = ['tenant_id', 'status'];
      }

      // Composite: tenant_id + user_id (user-scoped queries).
      if (isset($existingFields['user_id'])) {
        $tableSchema['indexes']['idx_tenant_user'] = ['tenant_id', 'user_id'];
      }
    }

    return $schema;
  }

}
