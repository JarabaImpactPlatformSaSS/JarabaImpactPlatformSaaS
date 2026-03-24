<?php

/**
 * @file
 * THEME-SETTINGS-INTEGRITY-001: Verifica coherencia entre formulario,
 * schema y consumidores de ecosistema_jaraba_theme.settings.
 *
 * Checks:
 * 1. Campos muertos conocidos NO reaparecen en schema
 * 2. 4 PLG fields tienen schema
 * 3. Footer link colors tienen CSS var mapping
 * 4. enable_breadcrumbs tiene consumidor
 *
 * Uso: php scripts/validation/validate-theme-settings-integrity.php
 */

$schema_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml';
$theme_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';

if (!file_exists($schema_file) || !file_exists($theme_file)) {
  echo "SKIP: Required files not found.\n";
  exit(0);
}

$schema = file_get_contents($schema_file);
$theme = file_get_contents($theme_file);
$errors = 0;

// Check 1: Campos muertos eliminados NO reaparecen.
$dead_fields = [
  'product_card_layout',
  'enable_preloader',
  'plg_annual_discount_percent',
  'plg_annual_discount_message',
];
foreach ($dead_fields as $field) {
  if (preg_match("/^\s{4}{$field}:\s*$/m", $schema)) {
    echo "FAIL: Campo muerto '{$field}' reaparece en schema.\n";
    $errors++;
  }
}

// Check 2: PLG fields tienen schema.
$plg_required = [
  'plg_free_plan_note',
  'plg_cta_subtitle',
  'plg_guarantee_text',
  'plg_register_subtitle',
];
foreach ($plg_required as $field) {
  if (strpos($schema, "{$field}:") === false) {
    echo "FAIL: PLG field '{$field}' falta en schema.\n";
    $errors++;
  }
}

// Check 3: Footer link colors tienen CSS var mapping.
if (strpos($theme, '--ej-footer-link') === false) {
  echo "FAIL: --ej-footer-link no encontrado en CSS var mapping.\n";
  $errors++;
}
if (strpos($theme, '--ej-footer-link-hover') === false) {
  echo "FAIL: --ej-footer-link-hover no encontrado en CSS var mapping.\n";
  $errors++;
}

// Check 4: enable_breadcrumbs tiene consumidor en preprocess_breadcrumb.
if (strpos($theme, "enable_breadcrumbs") !== false) {
  // Verificar que se lee en preprocess_breadcrumb, no solo en el form.
  $breadcrumb_fn = strstr($theme, 'function ecosistema_jaraba_theme_preprocess_breadcrumb');
  if ($breadcrumb_fn && strpos($breadcrumb_fn, 'enable_breadcrumbs') === false) {
    echo "FAIL: enable_breadcrumbs no se consulta en preprocess_breadcrumb().\n";
    $errors++;
  }
}

// Check 5: No hay campos de tipo color en el form sin mapping CSS var.
// Extraer todos los config keys de color fields.
preg_match_all("/\\\$form\[.+?\]\[.+?\]\[.+?\]\s*=\s*\\\$fn_color\(\s*'([^']+)'/", $theme, $color_matches);
if (!empty($color_matches[1])) {
  foreach ($color_matches[1] as $color_key) {
    if (strpos($theme, "'$color_key' =>") === false && strpos($theme, "\"$color_key\" =>") === false) {
      // Verificar si tiene mapping CSS var en el array
      if (strpos($theme, "'$color_key' => '--ej-") === false) {
        // Check if it's in the mapping array at all
        echo "WARN: Color field '{$color_key}' puede no tener CSS var mapping.\n";
      }
    }
  }
}

if ($errors > 0) {
  echo "\nTHEME-SETTINGS-INTEGRITY-001: FAIL ({$errors} errors)\n";
  exit(1);
}

echo "THEME-SETTINGS-INTEGRITY-001: PASS (0 dead fields, 4 PLG schema, footer links mapped, breadcrumbs guarded)\n";
exit(0);
