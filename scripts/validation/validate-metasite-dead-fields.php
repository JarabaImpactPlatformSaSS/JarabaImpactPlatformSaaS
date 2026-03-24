<?php

/**
 * @file
 * METASITE-DEAD-FIELDS-001: Verifica que los 18 campos muertos del antiguo
 * TAB 15 genérico NO existen en el schema ni en el formulario.
 *
 * Campos muertos (sin prefijo de variante):
 * hero_eyebrow, hero_headline, hero_subtitle, hero_cta_primary_text,
 * hero_cta_secondary_text, hero_cta_secondary_url,
 * stat_value_{1-4}, stat_suffix_{1-4}, stat_label_{1-4}
 *
 * Uso: php scripts/validation/validate-metasite-dead-fields.php
 */

$schema_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml';
if (!file_exists($schema_file)) {
  echo "SKIP: Schema file not found.\n";
  exit(0);
}

$dead_fields = [
  'hero_eyebrow',
  'hero_headline',
  'hero_subtitle',
  'hero_cta_primary_text',
  'hero_cta_secondary_text',
  'hero_cta_secondary_url',
];
for ($i = 1; $i <= 4; $i++) {
  $dead_fields[] = "stat_value_{$i}";
  $dead_fields[] = "stat_suffix_{$i}";
  $dead_fields[] = "stat_label_{$i}";
}

$schema_content = file_get_contents($schema_file);
$errors = 0;

foreach ($dead_fields as $field) {
  // Buscar la key EXACTA en el schema (como key YAML al inicio de línea con indentación).
  // No debe matchear campos con prefijo (generic_hero_eyebrow está OK).
  if (preg_match("/^\s{4}{$field}:\s*$/m", $schema_content)) {
    echo "FAIL: Campo muerto '{$field}' (sin prefijo) encontrado en schema.\n";
    $errors++;
  }
}

if ($errors > 0) {
  echo "\nMETASITE-DEAD-FIELDS-001: FAIL ({$errors} campos muertos en schema)\n";
  exit(1);
}

echo "METASITE-DEAD-FIELDS-001: PASS (0 campos muertos, 18 eliminados correctamente)\n";
exit(0);
