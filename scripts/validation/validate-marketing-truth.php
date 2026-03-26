<?php
/**
 * @file
 * MARKETING-TRUTH-001: Verifica que las claims de marketing en templates
 * coinciden con la realidad del sistema de billing/suscripciones.
 *
 * MODELO DE BILLING VERIFICADO (2026-03-19):
 * - TODOS los planes incluyen 14 días de prueba gratis (Stripe trial_period_days=14).
 * - Los precios varían por vertical (Starter desde 29€, Professional desde 99€).
 * - "Sin permanencia, cancela cuando quieras" = VERDAD.
 * - "Todos los planes incluyen 14 días de prueba gratuita" = VERDAD (select-plan.html.twig L34).
 * - El registro es gratuito (sin pago). El pago se configura al elegir plan.
 * - NO existe un plan gratuito para siempre (Starter tiene precio real).
 * - Config: ecosistema_jaraba_core.stripe.yml trial.days=14, require_payment_method=false.
 *
 * REGLAS:
 * 1. NUNCA "gratis para siempre" — no hay plan 0€ permanente
 * 2. "14 días gratis" = CORRECTO — pero solo como prueba del plan
 * 3. "Sin tarjeta" solo al REGISTRAR (no al suscribir plan de pago)
 * 4. NUNCA prometer precios específicos hardcodeados (NO-HARDCODE-PRICE-001)
 * 5. "Cancela cuando quieras" = CORRECTO
 *
 * Uso: php scripts/validation/validate-marketing-truth.php
 * Exit: 0 = OK, 1 = claims falsas detectadas
 */

$root = dirname(__DIR__, 2);
$searchDirs = [
    $root . '/web/modules/custom/ecosistema_jaraba_core/templates',
    $root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
    // MARKETING-TRUTH-001: All module templates with public-facing marketing copy.
    $root . '/web/modules/custom/jaraba_andalucia_ei/templates',
    $root . '/web/modules/custom/jaraba_page_builder/templates',
    $root . '/web/modules/custom/jaraba_success_cases/templates',
];

$errors = [];
$warnings = [];

// === RULE 1: "Gratis para siempre" — FALSO, no hay plan 0€ permanente ===
$falsePatterns = [
    '/[Gg]ratis\s+para\s+siempre/' => 'FREE-FOREVER: "Gratis para siempre" es falso. Todos los planes tienen precio mensual. Los 14 días de prueba son temporales.',
    '/[Pp]lan\s+(?:Starter|básico)\s+(?:es\s+)?(?:100%\s+)?gratuit/' => 'STARTER-FREE: "Plan Starter gratuito" es falso. Starter tiene precio (ej: 29€/mes Empleabilidad, 39€/mes Emprendimiento).',
    '/[Ss]in\s+tarjeta[^\.]{0,40}(?:para\s+siempre|permanente|ilimitad)/' => 'CARD-PERMANENT: "Sin tarjeta permanentemente" es falso. La tarjeta es requerida al activar plan de pago (Stripe checkout).',
];

// === RULE 2: Precios hardcodeados (NO-HARDCODE-PRICE-001) ===
$hardcodedPrices = [
    '/(?<!\d)[23]\d€\s*\/\s*mes/' => 'HARDCODED-PRICE: Precio EUR hardcodeado en template. Usar MetaSitePricingService (NO-HARDCODE-PRICE-001).',
];

// === RULE 3: Claims que DEBEN existir en CTAs de conversión ===
// (No son errores, son verificaciones de que las promesas correctas están presentes)

foreach ($searchDirs as $searchDir) {
    if (!is_dir($searchDir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'twig') {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        $relPath = str_replace($root . '/', '', $file->getPathname());

        // Skip comment blocks (Twig {# ... #} and docblocks).
        $contentNoComments = preg_replace('/\{#.*?#\}/s', '', $content);

        foreach ($falsePatterns as $pattern => $message) {
            if (preg_match($pattern, $contentNoComments, $match)) {
                $lineNum = substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1;
                $errors[] = "$relPath:$lineNum — $message\n    Found: \"{$match[0]}\"";
            }
        }

        foreach ($hardcodedPrices as $pattern => $message) {
            if (preg_match($pattern, $contentNoComments, $match)) {
                // Skip if inside a Twig variable reference ({{ pricing.* }}).
                $context = substr($contentNoComments, max(0, strpos($contentNoComments, $match[0]) - 20), 50);
                if (strpos($context, 'pricing') === false && strpos($context, 'price') === false) {
                    $lineNum = substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1;
                    $warnings[] = "$relPath:$lineNum — $message\n    Found: \"{$match[0]}\"";
                }
            }
        }
    }
}

// Report.
if (empty($errors) && empty($warnings)) {
    echo "✅ MARKETING-TRUTH-001: All marketing claims match billing reality.\n";
    echo "   Model: 14-day free trial on ALL plans · Prices vary per vertical · Cancel anytime\n";
    exit(0);
}

if (!empty($errors)) {
    echo "❌ MARKETING-TRUTH-001: " . count($errors) . " FALSE MARKETING CLAIMS detected!\n\n";
    foreach ($errors as $i => $error) {
        echo "  ERROR " . ($i + 1) . ": $error\n\n";
    }
}

if (!empty($warnings)) {
    echo "⚠️  " . count($warnings) . " WARNINGS (review manually):\n\n";
    foreach ($warnings as $i => $warning) {
        echo "  WARNING " . ($i + 1) . ": $warning\n\n";
    }
}

echo "\nBilling Truth (verified 2026-03-19):\n";
echo "  - ALL plans: 14-day free trial (Stripe trial_period_days=14)\n";
echo "  - Prices per vertical: Starter from 29€, Professional from 99€\n";
echo "  - Registration: free (no payment). Payment at plan selection.\n";
echo "  - Cancel anytime, no lock-in: TRUE\n";
echo "  - Source: select-plan.html.twig L34 + CheckoutSessionService::DEFAULT_TRIAL_DAYS\n";

exit(!empty($errors) ? 1 : 0);
