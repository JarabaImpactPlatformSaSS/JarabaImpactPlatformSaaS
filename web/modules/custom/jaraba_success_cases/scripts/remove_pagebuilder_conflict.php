<?php

/**
 * @file
 * Find and delete the Page Builder page at /casos-de-exito.
 *
 * Run: drush scr web/modules/custom/jaraba_success_cases/scripts/remove_pagebuilder_conflict.php
 */

// 1. Check for path aliases pointing to /casos-de-exito
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$aliases = $alias_storage->loadByProperties(['alias' => '/casos-de-exito']);

if (!empty($aliases)) {
    foreach ($aliases as $alias) {
        $source = $alias->getPath();
        echo "Found path alias: {$source} -> /casos-de-exito (ID: {$alias->id()})\n";

        // Try to load and delete the source entity
        if (preg_match('#^/node/(\d+)$#', $source, $m)) {
            $node = \Drupal::entityTypeManager()->getStorage('node')->load($m[1]);
            if ($node) {
                echo "  -> This is node '{$node->label()}' (type: {$node->bundle()})\n";
                $node->delete();
                echo "  -> DELETED node {$m[1]}\n";
            }
        }

        // Delete the alias itself
        $alias->delete();
        echo "  -> DELETED path alias\n";
    }
} else {
    echo "No path alias found for /casos-de-exito\n";
}

// Also check /es/casos-de-exito
$aliases_es = $alias_storage->loadByProperties(['alias' => '/es/casos-de-exito']);
if (!empty($aliases_es)) {
    foreach ($aliases_es as $alias) {
        echo "Found /es/casos-de-exito alias, deleting...\n";
        $alias->delete();
        echo "  -> DELETED\n";
    }
}

// 2. Check for page_builder_page entities
$entity_types = \Drupal::entityTypeManager()->getDefinitions();
foreach (['page_builder_page', 'grapesjs_page', 'landing_page'] as $type) {
    if (isset($entity_types[$type])) {
        echo "\nChecking entity type: {$type}\n";
        $storage = \Drupal::entityTypeManager()->getStorage($type);
        $ids = $storage->getQuery()->accessCheck(FALSE)->execute();
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
            $path = '';
            if ($entity->hasField('path') && !$entity->get('path')->isEmpty()) {
                $path = $entity->get('path')->value;
            } elseif ($entity->hasField('slug') && !$entity->get('slug')->isEmpty()) {
                $path = $entity->get('slug')->value;
            }
            $label = $entity->label() ?? '(no label)';
            echo "  [{$entity->id()}] {$label} (path: {$path})\n";

            if (str_contains($path, 'casos-de-exito') || str_contains($label, 'caso')) {
                echo "  -> MATCH! Deleting...\n";
                $entity->delete();
                echo "  -> DELETED\n";
            }
        }
    }
}

// 3. Check route match
echo "\nDone. Clear cache with: drush cr\n";
