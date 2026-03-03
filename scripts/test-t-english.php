<?php
/**
 * Test t() with English context.
 * Run: lando drush scr scripts/test-t-english.php
 */

echo "=== Testing t() with English ===\n";

// Get the string translation service
$translation = \Drupal::translation();

// Test with explicit English language
$en_lang = \Drupal::languageManager()->getLanguage('en');

// Method 1: Direct t() call (uses current language, which is ES in CLI)
echo "t('Soluciones') with default lang: " . t('Soluciones') . "\n";

// Method 2: Use the locale translation directly
$locale_storage = \Drupal::service('locale.storage');
try {
    $strings = $locale_storage->getTranslations([
        'language' => 'en',
        'source' => 'Soluciones',
    ]);
    echo "Locale storage for 'Soluciones' (en): ";
    if (!empty($strings)) {
        foreach ($strings as $s) {
            echo $s->getString() . "\n";
        }
    } else {
        echo "EMPTY - no translations found!\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Method 3: Check locales_target directly
$db = \Drupal::database();
$result = $db->query("
  SELECT ls.source, lt.translation, lt.language, lt.customized
  FROM {locales_source} ls
  JOIN {locales_target} lt ON ls.lid = lt.lid
  WHERE lt.language = :lang AND ls.source = :source
", [':lang' => 'en', ':source' => 'Soluciones'])->fetchObject();

echo "\nDirect DB query for 'Soluciones' (en):\n";
if ($result) {
    echo "  source: {$result->source}\n";
    echo "  translation: {$result->translation}\n";
    echo "  language: {$result->language}\n";
    echo "  customized: {$result->customized}\n";
} else {
    echo "  NOT FOUND in locales_target!\n";
}

// Method 4: Try TranslationManager directly with EN context
echo "\n=== TranslationManager test ===\n";
$manager = \Drupal::service('string_translation');
$class = get_class($manager);
echo "TranslationManager class: $class\n";

// Check if we can get translation via the manager with language override
$options = ['langcode' => 'en'];
$translated = $manager->translate('Soluciones', [], $options);
echo "translate('Soluciones', langcode=en): " . (string) $translated . "\n";

$options2 = ['langcode' => 'pt-br'];
$translated2 = $manager->translate('Soluciones', [], $options2);
echo "translate('Soluciones', langcode=pt-br): " . (string) $translated2 . "\n";
