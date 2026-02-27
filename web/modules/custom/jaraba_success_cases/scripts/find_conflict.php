<?php

/**
 * @file
 * Comprehensive lookup for the /casos-de-exito page conflict.
 * 
 * Run: drush scr web/modules/custom/jaraba_success_cases/scripts/find_conflict.php
 */

use Drupal\Core\Url;

// 1. Check ALL path aliases containing 'casos'
echo "=== PATH ALIASES containing 'casos' ===\n";
$connection = \Drupal::database();
try {
    $results = $connection->select('path_alias', 'pa')
        ->fields('pa', ['id', 'path', 'alias', 'langcode'])
        ->condition('alias', '%casos%', 'LIKE')
        ->execute();
    foreach ($results as $row) {
        echo "  ID:{$row->id} | {$row->path} -> {$row->alias} (lang: {$row->langcode})\n";
    }
} catch (\Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
    // Try with path_alias entity storage
    $alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
    $all = $alias_storage->loadMultiple();
    foreach ($all as $alias) {
        if (str_contains($alias->getAlias(), 'casos')) {
            echo "  ID:{$alias->id()} | {$alias->getPath()} -> {$alias->getAlias()} (lang: {$alias->get('langcode')->value})\n";
        }
    }
}

// 2. Check router table for route matching
echo "\n=== ROUTE MATCH for /casos-de-exito ===\n";
try {
    $router = \Drupal::service('router.no_access_checks');
    $result = $router->match('/casos-de-exito');
    echo "  Route: " . ($result['_route'] ?? 'unknown') . "\n";
    echo "  Controller: " . ($result['_controller'] ?? 'unknown') . "\n";
    if (isset($result['node'])) {
        $node = $result['node'];
        echo "  Node ID: {$node->id()}\n";
        echo "  Node Title: {$node->label()}\n";
        echo "  Node Type: {$node->bundle()}\n";
    }
} catch (\Exception $e) {
    echo "  Route not found or error: " . $e->getMessage() . "\n";
}

// 3. Check for /es/casos-de-exito specifically
echo "\n=== ROUTE MATCH for /es/casos-de-exito ===\n";
try {
    $router = \Drupal::service('router.no_access_checks');
    $result = $router->match('/es/casos-de-exito');
    echo "  Route: " . ($result['_route'] ?? 'unknown') . "\n";
    echo "  Controller: " . ($result['_controller'] ?? 'unknown') . "\n";
    if (isset($result['node'])) {
        $node = $result['node'];
        echo "  Node ID: {$node->id()}\n";
        echo "  Node Title: {$node->label()}\n";
        echo "  Node Type: {$node->bundle()}\n";
    }
} catch (\Exception $e) {
    echo "  Not found: " . $e->getMessage() . "\n";
}

// 4. List all nodes with 'caso' in title
echo "\n=== NODES with 'caso' in title ===\n";
$nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->accessCheck(FALSE)
    ->condition('title', '%caso%', 'LIKE')
    ->execute();
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
foreach ($nodes as $node) {
    echo "  NID:{$node->id()} | {$node->label()} (type: {$node->bundle()}, lang: {$node->language()->getId()})\n";
}

echo "\nDone.\n";
