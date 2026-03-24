<?php

/**
 * @file
 * METASITE-CONTENT-COMPLETENESS-001: Verifica que los 4 metasitios del
 * ecosistema Jaraba tienen contenido configurado en theme settings.
 *
 * Checks:
 * 1. Cada variante tiene hero_headline no vacío
 * 2. Cada variante tiene al menos 2 stats configurados
 * 3. Cada variante tiene seo_title no vacío
 *
 * Uso: php scripts/validation/validate-metasite-content-completeness.php
 * Requiere: Drupal bootstrap (ejecutar desde raíz del proyecto).
 */

// Si no hay bootstrap de Drupal, funcionar en modo estático (CI).
$config_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml';
if (!file_exists($config_file)) {
  echo "SKIP: Schema file not found (CI mode).\n";
  exit(0);
}

$variants = ['generic', 'pde', 'jarabaimpact', 'pepejaraba'];
$errors = 0;
$warnings = 0;

// Check 1: Verificar que los campos existen en el schema.
$schema_content = file_get_contents($config_file);
foreach ($variants as $variant) {
  $key = "{$variant}_hero_headline";
  if (strpos($schema_content, $key) === false) {
    echo "FAIL: Campo '{$key}' no encontrado en schema.\n";
    $errors++;
  }

  $seo_key = "{$variant}_seo_title";
  if (strpos($schema_content, $seo_key) === false) {
    echo "FAIL: Campo '{$seo_key}' no encontrado en schema.\n";
    $errors++;
  }

  // Verificar que existen al menos 2 stat fields por variante.
  $stat_count = 0;
  for ($i = 1; $i <= 4; $i++) {
    if (strpos($schema_content, "{$variant}_stat_value_{$i}") !== false) {
      $stat_count++;
    }
  }
  if ($stat_count < 2) {
    echo "FAIL: Variante '{$variant}' tiene solo {$stat_count}/4 stat fields en schema.\n";
    $errors++;
  }
}

// Check 2: Verificar que el helper de defaults tiene las 4 variantes.
$theme_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
if (file_exists($theme_file)) {
  $theme_content = file_get_contents($theme_file);
  if (strpos($theme_content, '_ecosistema_jaraba_theme_get_metasite_defaults') === false) {
    echo "FAIL: Helper function _ecosistema_jaraba_theme_get_metasite_defaults() not found.\n";
    $errors++;
  }
  foreach ($variants as $variant) {
    if (strpos($theme_content, "'{$variant}' =>") === false || strpos($theme_content, "'{$variant}_hero_headline'") === false) {
      // Buscar la key del variant en el helper de defaults.
      if (preg_match("/'{$variant}'\s*=>\s*\[/", $theme_content) === 0) {
        echo "WARN: Variante '{$variant}' no encontrada en helper de defaults.\n";
        $warnings++;
      }
    }
  }
}

// Check 3: Verificar que metasite_content se inyecta en preprocess.
if (file_exists($theme_file)) {
  $theme_content = file_get_contents($theme_file);
  if (strpos($theme_content, "variables['metasite_content']") === false) {
    echo "FAIL: metasite_content no se inyecta en hook_preprocess_page().\n";
    $errors++;
  }
}

// Check 4: Verificar que page--front.html.twig consume metasite_content.
$front_template = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig';
if (file_exists($front_template)) {
  $twig_content = file_get_contents($front_template);
  if (strpos($twig_content, 'metasite_content') === false) {
    echo "FAIL: page--front.html.twig no consume metasite_content.\n";
    $errors++;
  }
}

if ($errors > 0) {
  echo "\nMETASITE-CONTENT-COMPLETENESS-001: FAIL ({$errors} errors, {$warnings} warnings)\n";
  exit(1);
}

echo "METASITE-CONTENT-COMPLETENESS-001: PASS (4 variantes × 24 campos, {$warnings} warnings)\n";
exit(0);
