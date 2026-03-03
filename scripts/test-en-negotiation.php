<?php
/**
 * Test language negotiation from /en URL.
 * Checks if /en path is matched as an alias or as a language prefix.
 * Run: lando drush scr scripts/test-en-negotiation.php
 */

// Check if /en matches any path alias
$alias_manager = \Drupal::service('path_alias.manager');
try {
    $alias_result = $alias_manager->getPathByAlias('/en', 'es');
    echo "Path alias for /en: $alias_result\n";
} catch (\Exception $e) {
    echo "No path alias found for /en\n";
}

// Check the language negotiation method
echo "\n=== NEGOTIATION METHODS (language_interface) ===\n";
$config = \Drupal::config('language.types');
$methods = $config->get('negotiation.language_interface.enabled');
print_r($methods);

echo "\n=== URL-BASED TEST ===\n";
// Simulate URL detection
$negotiation_config = \Drupal::config('language.negotiation');
$prefixes = $negotiation_config->get('url.prefixes');
echo "URL prefix for 'en': " . ($prefixes['en'] ?? 'NOT SET') . "\n";

// Check if path /en is being resolved as a route
try {
    $url = \Drupal\Core\Url::fromUri('internal:/en');
    echo "Route for /en: " . $url->getRouteName() . "\n";
    echo "Params: " . json_encode($url->getRouteParameters()) . "\n";
} catch (\Exception $e) {
    echo "No route for /en: " . $e->getMessage() . "\n";
}

// Check for path alias conflicts
$db = \Drupal::database();
$aliases = $db->query("SELECT * FROM {path_alias} WHERE alias LIKE :alias", [':alias' => '/en%'])->fetchAll();
echo "\n=== PATH ALIAS TABLE ===\n";
foreach ($aliases as $alias) {
    echo "  path={$alias->path} alias={$alias->alias} langcode={$alias->langcode}\n";
}
if (empty($aliases)) {
    echo "  No aliases starting with /en found.\n";
}

echo "\n=== LANGUAGE DETECTION ORDER ===\n";
$all_types = $config->get('negotiation');
foreach ($all_types as $type => $info) {
    echo "$type: " . json_encode($info['enabled']) . "\n";
}
