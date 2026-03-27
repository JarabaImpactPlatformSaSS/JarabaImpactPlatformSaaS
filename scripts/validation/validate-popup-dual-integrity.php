<?php

/**
 * @file
 * POPUP-DUAL-SELECTOR-001: Validates popup dual integrity.
 *
 * Verifies that the dual popup (participante + negocio piloto) is
 * correctly implemented with all required paths, config keys,
 * authentication guard, and CTA tracking.
 *
 * Checks:
 * 1. JS contains both selector paths (participante + negocio)
 * 2. Config YAML has all required popup keys
 * 3. PHP hook excludes authenticated users (POPUP-ANON-ONLY-001)
 * 4. JS has data-track-cta for all CTAs (FUNNEL-COMPLETENESS-001)
 * 5. PHP injects pruebaGratuitaUrl route (POPUP-NEGOCIO-PATH-001)
 * 6. JS emits popup_impression via sendBeacon
 * 7. CSS freshness check (SCSS-COMPILE-VERIFY-001)
 * 8. All visible texts use Drupal.t() for i18n
 *
 * Usage: php scripts/validation/validate-popup-dual-integrity.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$moduleBase = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';

// ─── CHECK 1: Dual paths in JS ───
$jsFile = $moduleBase . '/js/reclutamiento-popup.js';
$jsContent = file_get_contents($jsFile);
if ($jsContent === false) {
    $errors[] = 'CHECK 1: Cannot read ' . $jsFile;
} else {
    $hasParticipante = str_contains($jsContent, 'data-popup-path="participante"');
    $hasNegocio = str_contains($jsContent, 'data-popup-path="negocio"');
    if ($hasParticipante && $hasNegocio) {
        $passed++;
    } else {
        $missing = [];
        if (!$hasParticipante) {
            $missing[] = 'participante';
        }
        if (!$hasNegocio) {
            $missing[] = 'negocio';
        }
        $errors[] = 'CHECK 1: Missing popup path(s) in JS: ' . implode(', ', $missing);
    }
}

// ─── CHECK 2: Config keys complete ───
$configFile = $moduleBase . '/config/install/jaraba_andalucia_ei.settings.yml';
$configContent = file_get_contents($configFile);
if ($configContent === false) {
    $errors[] = 'CHECK 2: Cannot read ' . $configFile;
} else {
    $requiredKeys = [
        'mostrar_popup_saas',
        'popup_campaign_utm',
        'popup_ttl_hours',
        'popup_delay_ms',
        'tasa_insercion_1e',
        'popup_negocio_enabled',
        'popup_servicios_count',
        'popup_valor_mercado_anual',
        'plazas_restantes',
        'incentivo_euros',
    ];
    $missingKeys = [];
    foreach ($requiredKeys as $key) {
        if (!str_contains($configContent, $key . ':')) {
            $missingKeys[] = $key;
        }
    }
    if (empty($missingKeys)) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: Missing config keys: ' . implode(', ', $missingKeys);
    }
}

// ─── CHECK 3: POPUP-ANON-ONLY-001 — Authenticated exclusion ───
$moduleFile = $moduleBase . '/jaraba_andalucia_ei.module';
$moduleContent = file_get_contents($moduleFile);
if ($moduleContent === false) {
    $errors[] = 'CHECK 3: Cannot read ' . $moduleFile;
} else {
    if (str_contains($moduleContent, 'isAuthenticated()') &&
        str_contains($moduleContent, '_popup_attachments')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 3: POPUP-ANON-ONLY-001 — _popup_attachments() must check isAuthenticated()';
    }
}

// ─── CHECK 4: CTA tracking completeness (FUNNEL-COMPLETENESS-001) ───
if ($jsContent !== false) {
    $requiredCtas = [
        'aei_popup_select_participante',
        'aei_popup_select_negocio',
        'aei_popup_ver_programa',
        'aei_popup_solicitar',
        'aei_popup_prueba_gratuita',
    ];
    $missingCtas = [];
    foreach ($requiredCtas as $cta) {
        if (!str_contains($jsContent, $cta)) {
            $missingCtas[] = $cta;
        }
    }
    if (empty($missingCtas)) {
        $passed++;
    } else {
        $errors[] = 'CHECK 4: Missing data-track-cta in JS: ' . implode(', ', $missingCtas);
    }
}

// ─── CHECK 5: POPUP-NEGOCIO-PATH-001 — pruebaGratuitaUrl route ───
if ($moduleContent !== false) {
    if (str_contains($moduleContent, 'pruebaGratuitaUrl') &&
        str_contains($moduleContent, 'jaraba_andalucia_ei.prueba_gratuita')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 5: POPUP-NEGOCIO-PATH-001 — Must inject pruebaGratuitaUrl from prueba_gratuita route';
    }
}

// ─── CHECK 6: sendBeacon for popup_impression ───
if ($jsContent !== false) {
    if (str_contains($jsContent, 'sendBeacon') && str_contains($jsContent, 'popup_impression')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 6: JS must emit popup_impression event via sendBeacon';
    }
}

// ─── CHECK 7: CSS freshness (SCSS-COMPILE-VERIFY-001) ───
$cssFile = $moduleBase . '/css/andalucia-ei.css';
$scssFile = $moduleBase . '/scss/_reclutamiento-popup.scss';
if (file_exists($cssFile) && file_exists($scssFile)) {
    $cssMtime = filemtime($cssFile);
    $scssMtime = filemtime($scssFile);
    if ($cssMtime >= $scssMtime) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 7: CSS timestamp (' . date('Y-m-d H:i:s', $cssMtime) .
            ') is older than SCSS (' . date('Y-m-d H:i:s', $scssMtime) .
            '). Run: npm run build in jaraba_andalucia_ei/';
    }
} else {
    $warnings[] = 'CHECK 7: Cannot compare timestamps — CSS or SCSS file missing';
}

// ─── CHECK 8: PRESAVE-RESILIENCE-001 — try-catch around Url::fromRoute() ───
if ($moduleContent !== false) {
    // The popup hook must wrap Url::fromRoute() in try-catch to prevent
    // 500 errors when routes are not in cache (deploy, cache stale).
    $hasFromRoute = str_contains($moduleContent, 'Url::fromRoute');
    $hasTryCatch = (bool) preg_match('/try\s*\{[^}]*Url::fromRoute/s', $moduleContent);
    if ($hasFromRoute && $hasTryCatch) {
        $passed++;
    } else {
        $errors[] = 'CHECK 8: PRESAVE-RESILIENCE-001 — Url::fromRoute() in _popup_attachments() must be wrapped in try-catch to prevent 500 on metasites';
    }
}

// ─── CHECK 9: i18n — Drupal.t() coverage ───
if ($jsContent !== false) {
    $t_count = substr_count($jsContent, 'Drupal.t(');
    if ($t_count >= 15) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 8: Only ' . $t_count . ' Drupal.t() calls found (expected >= 15 for full i18n coverage)';
    }
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " POPUP-DUAL-SELECTOR-001: Popup Dual Integrity\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

foreach ($errors as $error) {
    echo "  ✗ FAIL: {$error}\n";
}
foreach ($warnings as $warning) {
    echo "  ⚠ WARN: {$warning}\n";
}

echo "\n  Passed: {$passed}/{$total}";
if (!empty($warnings)) {
    echo " (+" . count($warnings) . " warnings)";
}
echo "\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
