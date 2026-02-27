<?php

/**
 * @file
 * Delete the page_content entity at /casos-de-exito.
 *
 * Run: drush scr web/modules/custom/jaraba_success_cases/scripts/delete_pagebuilder_page.php
 */

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// Load all page_content entities and find the one with /casos-de-exito
$ids = $storage->getQuery()->accessCheck(FALSE)->execute();
$entities = $storage->loadMultiple($ids);

echo "Total page_content entities: " . count($entities) . "\n\n";

$deleted = 0;
foreach ($entities as $entity) {
    $slug = '';
    $label = $entity->label() ?? '(no label)';

    // Check slug field
    if ($entity->hasField('slug') && !$entity->get('slug')->isEmpty()) {
        $slug = $entity->get('slug')->value;
    }
    // Check path field
    if ($entity->hasField('path') && !$entity->get('path')->isEmpty()) {
        $slug = $entity->get('path')->value;
    }

    if (str_contains($slug, 'casos-de-exito') || str_contains(strtolower($label), 'caso')) {
        echo "MATCH: ID={$entity->id()} | Label={$label} | Slug={$slug}\n";
        $entity->delete();
        echo "  -> DELETED\n";
        $deleted++;
    }
}

if ($deleted === 0) {
    echo "No matching page_content found by slug/label. Listing all:\n";
    foreach ($entities as $entity) {
        $slug = '';
        if ($entity->hasField('slug') && !$entity->get('slug')->isEmpty()) {
            $slug = $entity->get('slug')->value;
        }
        if ($entity->hasField('path') && !$entity->get('path')->isEmpty()) {
            $slug .= ' | path:' . $entity->get('path')->value;
        }
        echo "  [{$entity->id()}] {$entity->label()} (slug: {$slug})\n";
    }
}

echo "\nDone. If deleted, run: drush cr\n";
