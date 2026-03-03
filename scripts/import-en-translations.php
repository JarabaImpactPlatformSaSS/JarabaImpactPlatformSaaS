<?php
/**
 * @file import-en-translations.php
 * Import English translations directly into Drupal locale database,
 * bypassing the drush locale:import "not translatable" restriction.
 * 
 * Run via: lando drush scr scripts/import-en-translations.php
 */

use Drupal\locale\Gettext;

$file = new \stdClass();
$file->uri = DRUPAL_ROOT . '/themes/custom/ecosistema_jaraba_theme/translations/ecosistema_jaraba_theme.en.po';
$file->langcode = 'en';
$file->filename = 'ecosistema_jaraba_theme.en.po';

if (!file_exists($file->uri)) {
    echo "ERROR: File not found: " . $file->uri . "\n";
    return;
}

$options = [
    'customized' => LOCALE_CUSTOMIZED,
    'overwrite_options' => [
        'customized' => TRUE,
        'not_customized' => TRUE,
    ],
];

echo "Importing EN translations from: " . $file->uri . "\n";
$report = Gettext::fileToDatabase($file, $options);

if (!empty($report)) {
    echo "Import report:\n";
    foreach ($report as $key => $value) {
        echo "  $key: $value\n";
    }
} else {
    echo "Import completed (no report returned).\n";
}

// Clear caches
\Drupal::cache('locale')->deleteAll();
echo "Locale cache cleared.\n";
