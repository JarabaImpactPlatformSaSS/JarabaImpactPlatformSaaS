#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-lead-magnet-crm.php
 *
 * LEAD-MAGNET-CRM-001: Verifica la integridad del pipeline Lead Magnet → CRM.
 *
 * Checks:
 *   1. PublicSubscribeController importa ContactService
 *   2. PublicSubscribeController importa OpportunityService
 *   3. PublicSubscribeController tiene $contactService y $opportunityService
 *   4. Lógica str_starts_with($source, 'lead_magnet_') + createCrmLead present
 *   5. jaraba_crm.allowed_values.yml tiene 'lead_magnet' en contact_source
 *   6. VerticalLandingController tiene datos 'lead_magnet' en ≥8 verticales
 *
 * USO:
 *   php scripts/validation/validate-lead-magnet-crm.php
 *
 * EXIT CODES:
 *   0 = PASS (todos los checks pasan)
 *   1 = FAIL (uno o más checks fallaron)
 */

$root = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$results = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  LEAD-MAGNET-CRM-001                                    ║\033[0m\n";
echo "\033[36m║  Lead Magnet → CRM Pipeline Integrity                   ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── Helpers ──────────────────────────────────────────────────────────────────

function check_pass(string $msg): void {
    global $pass, $results;
    $pass++;
    $results[] = ['ok' => true, 'msg' => $msg];
}

function check_fail(string $msg): void {
    global $fail, $results;
    $fail++;
    $results[] = ['ok' => false, 'msg' => $msg];
}

// ── CHECK 1 & 2: PublicSubscribeController — imports ─────────────────────────

$subscribePath = $root . '/web/modules/custom/jaraba_email/src/Controller/PublicSubscribeController.php';

if (!is_file($subscribePath)) {
    check_fail('PublicSubscribeController.php no encontrado en jaraba_email');
    check_fail('PublicSubscribeController no puede verificarse (archivo ausente)');
    check_fail('$contactService/$opportunityService no pueden verificarse (archivo ausente)');
    check_fail('str_starts_with lead_magnet_ / createCrmLead no puede verificarse (archivo ausente)');
} else {
    $subscribeContent = file_get_contents($subscribePath);
    if ($subscribeContent === false) {
        check_fail('No se pudo leer PublicSubscribeController.php');
        check_fail('PublicSubscribeController no puede verificarse (lectura fallida)');
        check_fail('$contactService/$opportunityService no pueden verificarse (lectura fallida)');
        check_fail('str_starts_with lead_magnet_ / createCrmLead no puede verificarse (lectura fallida)');
    } else {
        // CHECK 1: import ContactService
        if (preg_match('/^\s*use\s+[^\n]*ContactService\s*;/m', $subscribeContent)) {
            check_pass('PublicSubscribeController importa ContactService');
        } else {
            check_fail('PublicSubscribeController NO importa ContactService (falta use statement)');
        }

        // CHECK 2: import OpportunityService
        if (preg_match('/^\s*use\s+[^\n]*OpportunityService\s*;/m', $subscribeContent)) {
            check_pass('PublicSubscribeController importa OpportunityService');
        } else {
            check_fail('PublicSubscribeController NO importa OpportunityService (falta use statement)');
        }

        // CHECK 3: $contactService + $opportunityService property/param
        $hasContactProp = (bool) preg_match('/\$contactService\b/', $subscribeContent);
        $hasOpportunityProp = (bool) preg_match('/\$opportunityService\b/', $subscribeContent);

        if ($hasContactProp && $hasOpportunityProp) {
            check_pass('PublicSubscribeController declara $contactService y $opportunityService');
        } elseif (!$hasContactProp && !$hasOpportunityProp) {
            check_fail('PublicSubscribeController NO declara $contactService ni $opportunityService');
        } elseif (!$hasContactProp) {
            check_fail('PublicSubscribeController NO declara $contactService');
        } else {
            check_fail('PublicSubscribeController NO declara $opportunityService');
        }

        // CHECK 4: str_starts_with lead_magnet_ + createCrmLead
        $hasStrStarts = (bool) preg_match("/str_starts_with\s*\(\s*\\\$\w+\s*,\s*'lead_magnet_'\s*\)/", $subscribeContent);
        $hasCreateCrmLead = (bool) preg_match('/createCrmLead\b/', $subscribeContent);

        if ($hasStrStarts && $hasCreateCrmLead) {
            check_pass("CRM lead creation para fuentes lead_magnet_* (str_starts_with + createCrmLead)");
        } elseif (!$hasStrStarts && !$hasCreateCrmLead) {
            check_fail("Falta str_starts_with(\$source, 'lead_magnet_') Y método createCrmLead");
        } elseif (!$hasStrStarts) {
            check_fail("Falta str_starts_with(\$source, 'lead_magnet_') en PublicSubscribeController");
        } else {
            check_fail("Falta método createCrmLead en PublicSubscribeController");
        }
    }
}

// ── CHECK 5: jaraba_crm.allowed_values.yml — contact_source.lead_magnet ──────

$allowedValuesPath = $root . '/web/modules/custom/jaraba_crm/jaraba_crm.allowed_values.yml';

if (!is_file($allowedValuesPath)) {
    check_fail('jaraba_crm.allowed_values.yml no encontrado');
} else {
    $allowedContent = file_get_contents($allowedValuesPath);
    if ($allowedContent === false) {
        check_fail('No se pudo leer jaraba_crm.allowed_values.yml');
    } else {
        // Look for contact_source section containing lead_magnet key.
        // Pattern: after "contact_source:" block, find "lead_magnet:" key.
        if (preg_match('/contact_source\s*:/i', $allowedContent)) {
            // Extract the contact_source block (lines after the key until next top-level key or EOF).
            if (preg_match('/contact_source\s*:.*?(?=\n\S|\z)/s', $allowedContent, $blockMatch)) {
                $block = $blockMatch[0];
                if (preg_match('/\blead_magnet\s*:/i', $block)) {
                    check_pass("contact_source incluye el valor 'lead_magnet'");
                } else {
                    check_fail("contact_source en allowed_values.yml NO contiene clave 'lead_magnet'");
                }
            } else {
                // Fallback: simpler search anywhere after contact_source declaration.
                $posSection = strpos($allowedContent, 'contact_source');
                $snippet = substr($allowedContent, (int) $posSection, 500);
                if (preg_match('/\blead_magnet\s*:/i', $snippet)) {
                    check_pass("contact_source incluye el valor 'lead_magnet'");
                } else {
                    check_fail("contact_source en allowed_values.yml NO contiene clave 'lead_magnet'");
                }
            }
        } else {
            check_fail("Sección 'contact_source' no encontrada en jaraba_crm.allowed_values.yml");
        }
    }
}

// ── CHECK 6: VerticalLandingController — lead_magnet data por vertical ────────

$landingPath = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php';

if (!is_file($landingPath)) {
    check_fail('VerticalLandingController.php no encontrado en ecosistema_jaraba_core');
} else {
    $landingContent = file_get_contents($landingPath);
    if ($landingContent === false) {
        check_fail('No se pudo leer VerticalLandingController.php');
    } else {
        // Count occurrences of 'lead_magnet' => data definitions.
        // Each vertical defines an array with 'lead_magnet' => [...].
        $count = preg_match_all("/['\"]lead_magnet['\"]\s*=>/", $landingContent);
        $count = $count === false ? 0 : $count;

        $required = 8;
        $total = 9; // 9 verticales comerciales (Demo excluido de requisito).

        if ($count >= $required) {
            check_pass("{$count}/{$total} verticales tienen datos de lead_magnet definidos");
        } else {
            check_fail("Solo {$count}/{$total} verticales tienen datos lead_magnet (mínimo {$required} requeridos)");
        }
    }
}

// ── Output final ──────────────────────────────────────────────────────────────

$total = $pass + $fail;

foreach ($results as $r) {
    if ($r['ok']) {
        echo "  \033[32m✓\033[0m {$r['msg']}\n";
    } else {
        echo "  \033[31m✘\033[0m {$r['msg']}\n";
    }
}

echo "\n";

if ($fail === 0) {
    echo "  \033[32mRESULT: {$pass}/{$total} PASS\033[0m\n\n";
    exit(0);
} else {
    echo "  \033[31mRESULT: {$pass}/{$total} PASS — {$fail} check(s) fallaron\033[0m\n\n";
    exit(1);
}
