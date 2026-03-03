<?php
/**
 * Check language configuration status.
 * Run via: lando drush scr scripts/check-lang-status.php
 */

echo "=== LANGUAGE STATUS ===\n";
$lm = \Drupal::languageManager();
foreach ($lm->getLanguages() as $lang) {
    echo $lang->getId() . " | " . $lang->getName() . " | default=" . ($lang->isDefault() ? "yes" : "no") . " | locked=" . ($lang->isLocked() ? "yes" : "no") . "\n";
}

echo "\n=== LANGUAGE NEGOTIATION URL PREFIXES ===\n";
$config = \Drupal::config('language.negotiation');
$prefixes = $config->get('url.prefixes');
print_r($prefixes);

echo "\n=== TRANSLATE ENGLISH ===\n";
$locale_config = \Drupal::config('locale.settings');
echo "translate_english (root): " . var_export($locale_config->get('translate_english'), true) . "\n";
echo "translate_english (nested): " . var_export($locale_config->get('translation.translate_english'), true) . "\n";

echo "\n=== LOCALE DB COUNTS ===\n";
$db = \Drupal::database();
$en_count = $db->query("SELECT COUNT(*) FROM {locales_target} WHERE language = :lang", [':lang' => 'en'])->fetchField();
$pt_count = $db->query("SELECT COUNT(*) FROM {locales_target} WHERE language = :lang", [':lang' => 'pt-br'])->fetchField();
echo "EN entries: $en_count\n";
echo "PT-BR entries: $pt_count\n";

echo "\n=== SAMPLE EN TRANSLATION ===\n";
$sample = $db->query("SELECT ls.source, lt.translation FROM {locales_source} ls JOIN {locales_target} lt ON ls.lid = lt.lid WHERE lt.language = :lang AND ls.source = :src", [':lang' => 'en', ':src' => 'Soluciones'])->fetchObject();
if ($sample) {
    echo "Source: " . $sample->source . " -> Translation: " . $sample->translation . "\n";
} else {
    echo "No EN translation found for 'Soluciones'\n";
}

echo "\n=== CONTAINER PARAMETER ===\n";
$container = \Drupal::getContainer();
if ($container->hasParameter('language.translate_english')) {
    echo "language.translate_english = " . var_export($container->getParameter('language.translate_english'), true) . "\n";
} else {
    echo "language.translate_english parameter NOT SET in container\n";
}
