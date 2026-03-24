<?php

/**
 * @file
 * METASITE-VARIANT-MAP-SSOT-001: Verifica que el mapa group_id → variante
 * de metasitio NO está duplicado en el .theme file.
 *
 * El SSOT es MetaSiteResolverService::VARIANT_MAP.
 * Cualquier mapa hardcodeado [5 => 'pepejaraba'] en el .theme es violación.
 *
 * Uso: php scripts/validation/validate-metasite-variant-map-ssot.php
 */

$theme_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
if (!file_exists($theme_file)) {
  echo "SKIP: Theme file not found.\n";
  exit(0);
}

$content = file_get_contents($theme_file);
$errors = 0;

// Buscar mapas hardcodeados: [5 => 'pepejaraba' ...] que NO sean referencia a la constante.
// Patrón: array literal con group_id => variant string.
$lines = explode("\n", $content);
foreach ($lines as $num => $line) {
  $lineNum = $num + 1;
  // Detectar patrones como: [5 => 'pepejaraba', 6 => 'jarabaimpact'
  if (preg_match("/\[\s*5\s*=>\s*['\"]pepejaraba/", $line)) {
    // Excluir si es un comentario.
    $trimmed = ltrim($line);
    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#') || str_starts_with($trimmed, '*')) {
      continue;
    }
    echo "FAIL: Mapa hardcodeado en línea {$lineNum}: " . trim($line) . "\n";
    $errors++;
  }
}

// Verificar que la constante se referencia correctamente.
if (strpos($content, 'MetaSiteResolverService::VARIANT_MAP') === false) {
  echo "FAIL: No se encontró referencia a MetaSiteResolverService::VARIANT_MAP.\n";
  $errors++;
}

// Contar referencias a la constante (deberían ser 3: preprocess_html, preprocess_page, page_attachments_alter).
$references = substr_count($content, 'MetaSiteResolverService::VARIANT_MAP');
if ($references < 3) {
  echo "WARN: Solo {$references} referencias a VARIANT_MAP (esperadas >= 3).\n";
}

if ($errors > 0) {
  echo "\nMETASITE-VARIANT-MAP-SSOT-001: FAIL ({$errors} errors)\n";
  exit(1);
}

echo "METASITE-VARIANT-MAP-SSOT-001: PASS ({$references} referencias a constante SSOT)\n";
exit(0);
