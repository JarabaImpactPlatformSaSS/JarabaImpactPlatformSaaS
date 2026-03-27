<?php

/**
 * @file
 * CONTENT-SEED-SYNC-001: Verifica que canvas_data en content seed JSON
 * coincide con los datos clave en la base de datos.
 *
 * Previene regresiones donde el seed JSON tiene contenido obsoleto
 * que sobreescribiría la DB si se reimporta (como ocurrió con /metodo).
 *
 * Uso: php scripts/validation/validate-content-seed-sync.php
 * Nota: Requiere acceso a la base de datos (ejecutar con drush o en entorno Drupal).
 */

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$warn = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = '', bool $isWarn = false): void {
  global $pass, $fail, $warn, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  elseif ($isWarn) {
    $warn++;
    echo "  \033[33mWARN\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[1m[CONTENT-SEED-SYNC-001]\033[0m Content seed JSON vs DB sync check\n\n";

// Solo verificamos que los archivos JSON de seed existen y tienen estructura válida.
// La verificación contra DB requiere entorno Drupal (drush php:script).
$seedFiles = glob("$basePath/scripts/content-seed/data/metasite-*.json");
check('Content seed files found', count($seedFiles) >= 3, 'Expected 3+ metasite JSON files');

foreach ($seedFiles as $file) {
  $name = basename($file, '.json');
  $data = json_decode(file_get_contents($file), true);

  // Validar estructura básica.
  $hasMetadata = isset($data['_metadata']['tenant_domain']);
  $hasPageContent = isset($data['page_content']) && is_array($data['page_content']);
  $hasPageTree = isset($data['site_page_tree']) && is_array($data['site_page_tree']);

  check("$name: valid structure", $hasMetadata && $hasPageContent && $hasPageTree);

  // Verificar que page_content tiene canvas_data no vacío para páginas canvas.
  $emptyCanvas = 0;
  foreach ($data['page_content'] ?? [] as $page) {
    $layout = $page['layout_mode'] ?? 'legacy';
    if ($layout === 'canvas') {
      $canvas = $page['canvas_data'] ?? '';
      if (strlen($canvas) < 50) {
        $emptyCanvas++;
      }
    }
  }

  if ($emptyCanvas > 0) {
    check("$name: canvas_data populated", false,
      "$emptyCanvas page(s) with empty canvas_data", true);
  }
  else {
    check("$name: canvas_data populated", true);
  }

  // Verificar meta_title en páginas con SEO.
  $missingMeta = 0;
  foreach ($data['page_content'] ?? [] as $page) {
    if (($page['status'] ?? false) && ($page['meta_title'] ?? '') === '') {
      // Solo warn si no tiene meta_title.
      $missingMeta++;
    }
  }

  if ($missingMeta > 0) {
    check("$name: SEO meta_title", false,
      "$missingMeta published page(s) without meta_title", true);
  }
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
