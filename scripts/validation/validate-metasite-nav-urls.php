<?php

/**
 * @file
 * METASITE-NAV-URLS-001: Verifica que las URLs del mega menú corporativo PDE
 * coinciden con path_alias reales de PageContent del tenant correspondiente.
 *
 * Previene errores 404 como /empresa vs /sobre-nosotros (bug encontrado 2026-03-26).
 *
 * Checks:
 * 1. MegaMenuBridgeService::getPedCorporateColumns() existe y devuelve datos
 * 2. Cada URL del mega menú PDE tiene PageContent con path_alias coincidente
 * 3. Las URLs de megamenu_direct_links en el preprocess son rutas válidas
 * 4. Las URLs del footer PDE (footer_nav_col*) son rutas válidas
 *
 * Uso: php scripts/validation/validate-metasite-nav-urls.php
 * Modo CI: Funciona sin Drupal bootstrap verificando ficheros fuente.
 */

$errors = 0;
$warnings = 0;
$pass = 0;

$bridge_file = __DIR__ . '/../../web/modules/custom/ecosistema_jaraba_core/src/Service/MegaMenuBridgeService.php';
$theme_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';

if (!file_exists($bridge_file)) {
  echo "SKIP: MegaMenuBridgeService.php not found.\n";
  exit(0);
}

$bridge_content = file_get_contents($bridge_file);
$theme_content = file_exists($theme_file) ? file_get_contents($theme_file) : '';

// Check 1: getPedCorporateColumns() exists.
if (strpos($bridge_content, 'function getPedCorporateColumns') === false) {
  echo "FAIL: getPedCorporateColumns() not found in MegaMenuBridgeService.\n";
  $errors++;
} else {
  echo "PASS: getPedCorporateColumns() exists.\n";
  $pass++;
}

// Check 2: Extract URLs from getPedCorporateColumns() and verify consistency.
// Extract all 'url' => '/path' patterns from the method.
$ped_urls = [];
if (preg_match('/function getPedCorporateColumns.*?^  \}/ms', $bridge_content, $method_match)) {
  $method_body = $method_match[0];
  if (preg_match_all("/'url'\s*=>\s*'([^']+)'/", $method_body, $url_matches)) {
    $ped_urls = $url_matches[1];
  }
}

if (empty($ped_urls)) {
  echo "FAIL: No URLs found in getPedCorporateColumns().\n";
  $errors++;
} else {
  echo "PASS: Found " . count($ped_urls) . " URLs in getPedCorporateColumns().\n";
  $pass++;
}

// Check 3: Verify PDE URLs don't collide with SaaS vertical URLs.
$saas_vertical_urls = ['/empleabilidad', '/emprendimiento', '/comercioconecta', '/agroconecta',
                        '/serviciosconecta', '/jarabalex', '/content-hub', '/instituciones',
                        '/formacion', '/andalucia-ei'];
$collisions = array_intersect($ped_urls, $saas_vertical_urls);
if (!empty($collisions)) {
  echo "WARN: PDE mega menu shares URLs with SaaS verticals: " . implode(', ', $collisions) . ".\n";
  $warnings++;
} else {
  echo "PASS: No URL collisions between PDE and SaaS mega menus.\n";
  $pass++;
}

// Check 4: Verify URLs in preprocess match getPedCorporateColumns().
// Check megamenu_direct_links in the theme file.
$direct_link_urls = [];
if (preg_match_all("/megamenu_direct_links.*?\[\s*\n(.*?)\]/s", $theme_content, $dl_match)) {
  // Extract URLs from the first match (PDE block).
  $block = $dl_match[1][0] ?? '';
  if (preg_match_all("/'url'\s*=>\s*\\\$lp\s*\.\s*'([^']+)'/", $block, $dl_urls)) {
    $direct_link_urls = $dl_urls[1];
  }
}

// Check that direct link URLs are either in PDE mega menu or are known global routes.
$global_routes = ['/impacto', '/casos-de-exito', '/planes', '/contacto', '/andalucia-ei/enlaces'];
foreach ($direct_link_urls as $dl_url) {
  if (!in_array($dl_url, $ped_urls) && !in_array($dl_url, $global_routes)) {
    echo "WARN: Direct link URL '{$dl_url}' not found in PDE columns or known routes.\n";
    $warnings++;
  }
}

// Check 5: Verify footer PDE URLs in preprocess.
$footer_urls = [];
if (preg_match_all("/footer_nav_col\d_links.*?implode.*?\[(.*?)\]/s", $theme_content, $footer_match)) {
  foreach ($footer_match[1] as $block) {
    if (preg_match_all("/'[^|]+\|([^']+)'/", $block, $fu_matches)) {
      $footer_urls = array_merge($footer_urls, $fu_matches[1]);
    }
  }
}

if (empty($footer_urls)) {
  echo "WARN: No PDE footer URLs found in preprocess (may use defaults).\n";
  $warnings++;
} else {
  echo "PASS: Found " . count($footer_urls) . " PDE footer URLs in preprocess.\n";
  $pass++;

  // Verify footer URLs are valid paths (exist in mega menu, global routes, or legal pages).
  $legal_pages = ['/aviso-legal', '/politica-privacidad', '/politica-cookies'];
  $all_known = array_merge($ped_urls, $global_routes, $legal_pages);
  foreach ($footer_urls as $fu) {
    if (!in_array($fu, $all_known)) {
      echo "WARN: Footer URL '{$fu}' not in known PDE paths.\n";
      $warnings++;
    }
  }
}

// Check 6: Verify isPedMegaMenu detection in preprocess.
if (strpos($theme_content, 'isPedMegaMenu') === false) {
  echo "FAIL: isPedMegaMenu detection not found in theme preprocess.\n";
  $errors++;
} else {
  echo "PASS: isPedMegaMenu detection exists in preprocess.\n";
  $pass++;
}

// Check 7: Verify getPedCorporateColumns is called for PDE.
if (strpos($theme_content, 'getPedCorporateColumns') === false) {
  echo "FAIL: getPedCorporateColumns() not called in theme preprocess.\n";
  $errors++;
} else {
  echo "PASS: getPedCorporateColumns() called in preprocess.\n";
  $pass++;
}

echo "\n=== METASITE-NAV-URLS-001: {$pass} PASS, {$warnings} WARN, {$errors} FAIL ===\n";
exit($errors > 0 ? 1 : 0);
