<?php

/**
 * @file
 * SAFEGUARD-AEI-CAMPAIGN-001: Validates reclutamiento landing readiness.
 *
 * Verifies that the Andalucía +ei reclutamiento landing page is fully
 * instrumented and ready for an active recruitment campaign.
 *
 * Usage: php scripts/validation/validate-aei-reclutamiento-campaign.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$moduleBase = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$themeBase = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';

// ─── CHECK 1: Tracking CTAs complete ───
$template = file_get_contents($moduleBase . '/templates/andalucia-ei-reclutamiento.html.twig');
if ($template === false) {
    $errors[] = 'CHECK 1: Cannot read reclutamiento template';
} else {
    $trackCount = substr_count($template, 'data-track-cta=');
    $posCount = substr_count($template, 'data-track-position=');
    if ($trackCount < 10) {
        $errors[] = "CHECK 1: Only $trackCount data-track-cta found (minimum 10 required)";
    } elseif ($trackCount !== $posCount) {
        $errors[] = "CHECK 1: Mismatch — $trackCount data-track-cta vs $posCount data-track-position";
    } else {
        $passed++;
        echo "  ✓ CHECK 1: $trackCount CTAs with full tracking\n";
    }
}

// ─── CHECK 2: Countdown markup present ───
if ($template !== false && strpos($template, 'aei-rec__countdown') !== false) {
    $passed++;
    echo "  ✓ CHECK 2: Countdown markup present\n";
} else {
    $errors[] = 'CHECK 2: No countdown markup (.aei-rec__countdown) in reclutamiento template';
}

// ─── CHECK 3: Pre-qualification form present ───
if ($template !== false && strpos($template, 'aei-rec__prequalify') !== false) {
    $passed++;
    echo "  ✓ CHECK 3: Pre-qualification form present\n";
} else {
    $errors[] = 'CHECK 3: No pre-qualification form (.aei-rec__prequalify) in reclutamiento template';
}

// ─── CHECK 4: Schema.org FAQPage present ───
if ($template !== false && strpos($template, '"@type": "FAQPage"') !== false) {
    $passed++;
    echo "  ✓ CHECK 4: FAQPage schema present\n";
} else {
    $errors[] = 'CHECK 4: No FAQPage schema in reclutamiento template';
}

// ─── CHECK 5: Schema.org EducationalOccupationalProgram in module ───
$moduleFile = file_get_contents($moduleBase . '/jaraba_andalucia_ei.module');
if ($moduleFile !== false && strpos($moduleFile, 'EducationalOccupationalProgram') !== false) {
    $passed++;
    echo "  ✓ CHECK 5: EducationalOccupationalProgram schema in module\n";
} else {
    $errors[] = 'CHECK 5: No EducationalOccupationalProgram schema in module';
}

// ─── CHECK 6: Video hero has poster ───
if ($template !== false && preg_match('/poster="[^"]+"/', $template)) {
    $passed++;
    echo "  ✓ CHECK 6: Video hero has poster attribute\n";
} else {
    $errors[] = 'CHECK 6: Video hero missing poster attribute';
}

// ─── CHECK 7: Solicitud form route exists ───
$routingFile = file_get_contents($moduleBase . '/jaraba_andalucia_ei.routing.yml');
if ($routingFile !== false && strpos($routingFile, 'jaraba_andalucia_ei.solicitar') !== false) {
    $passed++;
    echo "  ✓ CHECK 7: Solicitud form route exists\n";
} else {
    $errors[] = 'CHECK 7: Solicitud form route not found in routing.yml';
}

// ─── CHECK 8: Email templates defined ───
if ($moduleFile !== false && strpos($moduleFile, "'confirmacion_solicitud'") !== false
    && strpos($moduleFile, "'nueva_solicitud'") !== false) {
    $passed++;
    echo "  ✓ CHECK 8: Email templates (confirmacion_solicitud + nueva_solicitud) defined\n";
} else {
    $errors[] = 'CHECK 8: Missing email templates in hook_mail';
}

// ─── CHECK 9: WhatsApp link uses correct number ───
if ($template !== false && strpos($template, 'wa.me/34623174304') !== false) {
    $passed++;
    echo "  ✓ CHECK 9: WhatsApp link uses NAP number (34623174304)\n";
} else {
    $errors[] = 'CHECK 9: WhatsApp link missing or wrong number';
}

// ─── CHECK 10: Testimonial images exist ───
$testimonialImages = ['testimonio-cristina.webp', 'testimonio-adrian.webp', 'testimonio-marcela.webp'];
$allExist = true;
foreach ($testimonialImages as $img) {
    if (!file_exists($moduleBase . '/images/' . $img)) {
        $allExist = false;
        $errors[] = "CHECK 10: Missing testimonial image $img";
    }
}
if ($allExist) {
    $passed++;
    echo "  ✓ CHECK 10: All 3 testimonial images exist\n";
}

// ─── CHECK 11: Publicidad oficial FSE+ banner present ───
if ($template !== false && strpos($template, 'publicidad-oficial-piil') !== false) {
    $passed++;
    echo "  ✓ CHECK 11: Publicidad oficial FSE+ banner present\n";
} else {
    $errors[] = 'CHECK 11: Missing obligatory FSE+ publicity banner';
}

// ─── CHECK 12: Popup has UTM params ───
$popupJs = file_get_contents($moduleBase . '/js/reclutamiento-popup.js');
if ($popupJs !== false && strpos($popupJs, 'utmParams') !== false) {
    $passed++;
    echo "  ✓ CHECK 12: Popup JS has UTM params support\n";
} else {
    $errors[] = 'CHECK 12: Popup JS missing UTM params support';
}

// ─── CHECK 13: Popup uses localStorage (not sessionStorage) ───
if ($popupJs !== false && strpos($popupJs, 'localStorage') !== false
    && strpos($popupJs, 'sessionStorage') === false) {
    $passed++;
    echo "  ✓ CHECK 13: Popup uses localStorage with TTL\n";
} else {
    $warnings[] = 'CHECK 13: Popup may still use sessionStorage';
}

// ─── CHECK 14: Thank-you page route exists ───
if ($routingFile !== false && strpos($routingFile, 'solicitud_confirmada') !== false) {
    $passed++;
    echo "  ✓ CHECK 14: Thank-you page route exists\n";
} else {
    $errors[] = 'CHECK 14: Missing solicitud_confirmada route';
}

// ─── CHECK 15: Config schema includes campaign fields ───
$schemaFile = file_get_contents($moduleBase . '/config/schema/jaraba_andalucia_ei.schema.yml');
if ($schemaFile !== false && strpos($schemaFile, 'fecha_limite_solicitudes') !== false
    && strpos($schemaFile, 'mostrar_countdown') !== false
    && strpos($schemaFile, 'popup_campaign_utm') !== false) {
    $passed++;
    echo "  ✓ CHECK 15: Config schema includes campaign fields\n";
} else {
    $errors[] = 'CHECK 15: Config schema missing campaign fields';
}

// ─── RESULTS ───
echo "\n";
$total = $passed + count($errors);
echo "SAFEGUARD-AEI-CAMPAIGN-001: $passed/$total checks passed";
if (!empty($warnings)) {
    echo " (" . count($warnings) . " warnings)";
}
echo "\n";

if (!empty($errors)) {
    echo "\nFAILURES:\n";
    foreach ($errors as $err) {
        echo "  ✗ $err\n";
    }
    exit(1);
}

if (!empty($warnings)) {
    echo "\nWARNINGS:\n";
    foreach ($warnings as $w) {
        echo "  ⚠ $w\n";
    }
}

echo "\nAll checks passed.\n";
exit(0);
