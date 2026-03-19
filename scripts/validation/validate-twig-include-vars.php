<?php
/**
 * @file
 * TWIG-INCLUDE-VARS-001: Verifica que {% include ... only %} pasa TODAS
 * las variables requeridas por el partial incluido.
 *
 * Problema detectado: page--demo.html.twig incluía _header.html.twig con
 * `only` pero no pasaba mega_menu_columns → mega menú vacío silenciosamente.
 *
 * Lógica:
 * 1. Encuentra todos los {% include ... only %} en templates
 * 2. Para cada include, extrae las variables pasadas
 * 3. Comprueba contra las variables REQUERIDAS del partial (definidas en este script)
 * 4. Reporta variables faltantes
 *
 * Uso: php scripts/validation/validate-twig-include-vars.php
 * Exit: 0 = OK, 1 = variables faltantes
 */

$root = dirname(__DIR__, 2);
$templatesDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';
$coreTemplates = $root . '/web/modules/custom/ecosistema_jaraba_core/templates';

// Variables REQUERIDAS por parciales críticos.
// Si un include usa `only` y no pasa estas, el partial se rompe silenciosamente.
$requiredVars = [
    '_header.html.twig' => [
        'site_name',
        'logo',
        'logged_in',
        'theme_settings',
        'mega_menu_columns',
    ],
    '_footer.html.twig' => [
        'site_name',
        'theme_settings',
        'language_prefix',
        'logged_in',
    ],
];

$errors = [];
$total = 0;

$searchDirs = [$templatesDir, $coreTemplates];

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

        // Find all {% include '...' with { ... } only %}
        // Pattern matches multi-line includes.
        if (preg_match_all('/\{%\s*include\s+[\'"]([^"\']+)[\'"]\s+with\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*only\s*%\}/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $partial = basename($match[1]);
                $varsBlock = $match[2];
                $total++;

                // Check if this partial has required vars defined.
                if (!isset($requiredVars[$partial])) {
                    continue;
                }

                // Extract variable names from the with block.
                $passedVars = [];
                if (preg_match_all('/[\'"]?(\w+)[\'"]?\s*:/', $varsBlock, $varMatches)) {
                    $passedVars = $varMatches[1];
                }

                // Check for missing required vars.
                $missing = array_diff($requiredVars[$partial], $passedVars);
                if (!empty($missing)) {
                    $lineNum = substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1;
                    $errors[] = sprintf(
                        "%s:%d — includes %s with 'only' but MISSING: %s",
                        $relPath,
                        $lineNum,
                        $partial,
                        implode(', ', $missing)
                    );
                }
            }
        }
    }
}

if (empty($errors)) {
    echo "✅ TWIG-INCLUDE-VARS-001: All {$total} 'include ... only' pass required variables.\n";
    exit(0);
}

echo "❌ TWIG-INCLUDE-VARS-001: " . count($errors) . " includes with MISSING required variables!\n\n";
foreach ($errors as $i => $error) {
    echo "  " . ($i + 1) . ". $error\n";
}
echo "\nThese variables will be NULL in the partial, causing silent failures.\n";
echo "Fix: add the missing variables to the 'with { ... }' block.\n";
exit(1);
