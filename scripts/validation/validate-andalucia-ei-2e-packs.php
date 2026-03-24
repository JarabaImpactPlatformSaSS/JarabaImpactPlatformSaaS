<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición — 5 packs de servicios integrity.
 *
 * Verifies PackServicioEi entity has 5 pack types, 3 modalities,
 * CatalogoPacksService exists, and config/routes are wired.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-packs.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';

// CHECK 1: PackServicioEi entity class exists.
$entityFile = $moduleRoot . '/src/Entity/PackServicioEi.php';
if (file_exists($entityFile)) {
  $passes[] = "CHECK 1 PASS: PackServicioEi entity class exists";
} else {
  $errors[] = "CHECK 1 FAIL: PackServicioEi entity class not found";
}

// CHECK 2: 5 pack types defined.
$entityContent = file_exists($entityFile) ? file_get_contents($entityFile) : '';
$packTypes = ['contenido_digital', 'asistente_virtual', 'presencia_online', 'tienda_digital', 'community_manager'];
$missingPacks = [];
foreach ($packTypes as $pack) {
  if (strpos($entityContent, "'$pack'") === false) {
    $missingPacks[] = $pack;
  }
}
if (count($missingPacks) === 0) {
  $passes[] = "CHECK 2 PASS: 5/5 pack types defined in entity";
} else {
  $errors[] = "CHECK 2 FAIL: Missing pack types: " . implode(', ', $missingPacks);
}

// CHECK 3: 3 modalities defined.
$modalities = ['basico', 'estandar', 'premium'];
$missingMods = [];
foreach ($modalities as $mod) {
  if (strpos($entityContent, "'$mod'") === false) {
    $missingMods[] = $mod;
  }
}
if (count($missingMods) === 0) {
  $passes[] = "CHECK 3 PASS: 3/3 modalities defined (basico, estandar, premium)";
} else {
  $errors[] = "CHECK 3 FAIL: Missing modalities: " . implode(', ', $missingMods);
}

// CHECK 4: Stripe fields present.
if (strpos($entityContent, 'stripe_product_id') !== false && strpos($entityContent, 'stripe_price_id') !== false) {
  $passes[] = "CHECK 4 PASS: Stripe fields (product_id, price_id) defined";
} else {
  $errors[] = "CHECK 4 FAIL: Stripe fields missing in PackServicioEi";
}

// CHECK 5: CatalogoPacksService exists.
$serviceFile = $moduleRoot . '/src/Service/CatalogoPacksService.php';
if (file_exists($serviceFile)) {
  $passes[] = "CHECK 5 PASS: CatalogoPacksService exists";
} else {
  $errors[] = "CHECK 5 FAIL: CatalogoPacksService not found";
}

// CHECK 6: Service registered in services.yml.
$servicesContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.services.yml');
if (strpos($servicesContent, 'catalogo_packs') !== false) {
  $passes[] = "CHECK 6 PASS: catalogo_packs service registered";
} else {
  $errors[] = "CHECK 6 FAIL: catalogo_packs not in services.yml";
}

// CHECK 7: Catálogo público route exists.
$routingContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.routing.yml');
if (strpos($routingContent, 'catalogo_publico') !== false) {
  $passes[] = "CHECK 7 PASS: catalogo_publico route defined";
} else {
  $errors[] = "CHECK 7 FAIL: catalogo_publico route missing";
}

// CHECK 8: Catálogo template exists.
$tplFile = $moduleRoot . '/templates/catalogo-publico.html.twig';
if (file_exists($tplFile)) {
  $passes[] = "CHECK 8 PASS: catalogo-publico.html.twig template exists";
} else {
  $errors[] = "CHECK 8 FAIL: Catálogo template not found";
}

// CHECK 9: hook_theme declares catalogo_publico.
$moduleContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.module');
if (strpos($moduleContent, "'catalogo_publico'") !== false) {
  $passes[] = "CHECK 9 PASS: catalogo_publico declared in hook_theme";
} else {
  $errors[] = "CHECK 9 FAIL: catalogo_publico not in hook_theme";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2E — PACKS INTEGRITY ===\n\n";
foreach ($passes as $msg) { echo "  ✅ $msg\n"; }
foreach ($errors as $msg) { echo "  ❌ $msg\n"; }
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
