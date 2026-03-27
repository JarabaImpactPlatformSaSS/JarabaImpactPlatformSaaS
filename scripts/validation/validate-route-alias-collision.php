<?php

/**
 * @file
 * Detecta colisiones entre rutas de routing.yml y path aliases de page_content.
 *
 * ROUTE-ALIAS-COLLISION-001: Previene que un path alias de page_content
 * capture una ruta definida en routing.yml (como ocurrió con /metodo y
 * /certificacion donde page_content de meta-sitios capturaba la ruta
 * del controller del SaaS principal).
 *
 * Uso: php scripts/validation/validate-route-alias-collision.php
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

echo "\n\033[1m[ROUTE-ALIAS-COLLISION-001]\033[0m Route vs path alias collision detection\n\n";

// Extraer rutas de todos los routing.yml de módulos custom.
$routePaths = [];
$routingFiles = glob("$basePath/web/modules/custom/*/*.routing.yml");
foreach ($routingFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match_all('/^\s*path:\s*[\'"]?([^\'"#\n]+)/m', $content, $matches)) {
    foreach ($matches[1] as $path) {
      $path = trim($path);
      // Ignorar rutas con parámetros dinámicos {param}.
      if (str_contains($path, '{')) continue;
      // Ignorar rutas /admin/*.
      if (str_starts_with($path, '/admin')) continue;
      // Ignorar rutas /api/*.
      if (str_starts_with($path, '/api')) continue;
      $routePaths[$path] = basename($file);
    }
  }
}

// Extraer path aliases de page_content en content seed JSON files.
$aliases = [];
$seedFiles = glob("$basePath/scripts/content-seed/data/metasite-*.json");
foreach ($seedFiles as $file) {
  $data = json_decode(file_get_contents($file), true);
  $domain = $data['_metadata']['tenant_domain'] ?? basename($file);
  foreach ($data['page_content'] ?? [] as $page) {
    $alias = $page['path_alias'] ?? '';
    if ($alias !== '') {
      $aliases[$alias][] = $domain;
    }
  }
}

echo "  Routes scanned: " . count($routePaths) . "\n";
echo "  Aliases scanned: " . count($aliases) . "\n\n";

// Detectar colisiones.
$collisions = 0;
foreach ($routePaths as $path => $routingFile) {
  if (isset($aliases[$path])) {
    $domains = implode(', ', $aliases[$path]);
    check(
      "Route $path ($routingFile) collides with page_content alias",
      false,
      "Alias exists in: $domains. El alias captura la ruta del controller.",
      true // warn, no fail — puede ser intencional
    );
    $collisions++;
  }
}

if ($collisions === 0) {
  check('No route/alias collisions detected', true);
}

// Verificar rutas conocidas problemáticas (aprendizaje del proyecto).
$knownCollisions = ['/metodo', '/certificacion'];
foreach ($knownCollisions as $known) {
  $hasRoute = isset($routePaths[$known]);
  check(
    "Known collision $known NOT in routing.yml",
    !$hasRoute,
    $hasRoute ? "Route $known still defined — use alternative path" : ''
  );
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
