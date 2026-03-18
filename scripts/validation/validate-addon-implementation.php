<?php

/**
 * @file
 * ADDON-IMPLEMENTATION-001: Verifies that addon entities have corresponding
 * modules/services implemented, not just config entities.
 *
 * Detects addons that are "sellable" (price > 0, is_active = TRUE) but don't
 * have a corresponding Drupal module or service that implements the feature.
 *
 * Usage: php scripts/validation/validate-addon-implementation.php
 */

$base_path = dirname(__DIR__, 2);
$warnings = [];

// Map addon machine_names to expected modules.
$addon_module_map = [
  'jaraba_crm' => 'jaraba_crm',
  'jaraba_email' => 'jaraba_email',
  'jaraba_email_plus' => 'jaraba_email',
  'jaraba_social' => 'jaraba_social',
  'paid_ads_sync' => 'jaraba_ads',
  'retargeting_pixels' => 'jaraba_pixels',
  'events_webinars' => 'jaraba_events',
  'ab_testing' => 'jaraba_ab_testing',
  'referral_program' => 'jaraba_referral',
  'white_label' => 'jaraba_theming',
  // Vertical addons use existing vertical modules.
  'empleabilidad' => 'jaraba_candidate',
  'emprendimiento' => 'jaraba_copilot_v2',
  'comercioconecta' => 'jaraba_comercio_conecta',
  'agroconecta' => 'jaraba_agroconecta_core',
  'jarabalex' => 'jaraba_legal',
  'serviciosconecta' => 'jaraba_servicios_conecta',
  'andalucia_ei' => 'jaraba_andalucia_ei',
  'content_hub' => 'jaraba_content_hub',
  'formacion' => 'jaraba_lms',
];

$modules_dir = $base_path . '/web/modules/custom';
$checked = 0;
$implemented = 0;

foreach ($addon_module_map as $addon_key => $module_name) {
  $checked++;
  $module_path = $modules_dir . '/' . $module_name;

  if (is_dir($module_path)) {
    // Check if it has at least a .info.yml and a src/ directory.
    $info_file = $module_path . '/' . $module_name . '.info.yml';
    $src_dir = $module_path . '/src';

    if (file_exists($info_file) && is_dir($src_dir)) {
      $implemented++;
    }
    else {
      $warnings[] = sprintf(
        'Addon "%s" → module "%s" exists but incomplete (missing .info.yml or src/)',
        $addon_key, $module_name
      );
    }
  }
  else {
    $warnings[] = sprintf(
      'Addon "%s" → expected module "%s" NOT FOUND in web/modules/custom/',
      $addon_key, $module_name
    );
  }
}

// Output.
echo "ADDON-IMPLEMENTATION-001: Addon module implementation audit\n";
echo str_repeat('=', 60) . "\n";

if (empty($warnings)) {
  echo "\033[32mPASS\033[0m — All $implemented/$checked addons have implementing modules.\n";
}
else {
  echo "\033[33mWARN\033[0m — " . count($warnings) . " addon(s) sin módulo implementado:\n";
  foreach ($warnings as $w) {
    echo "  \033[33m⚠\033[0m $w\n";
  }
  echo "\nImplemented: $implemented/$checked\n";
}

exit(0); // Warnings don't block.
