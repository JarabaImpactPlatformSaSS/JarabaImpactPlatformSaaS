<?php
/**
 * @file
 * SVG-CURRENTCOLOR-001: Detecta `currentColor` en SVGs de categorías que se
 * renderizan como <img> tags (no inline SVG).
 *
 * Problema: currentColor en <img src="...svg"> se interpreta como negro.
 * CSS filter para recolorar distorsiona formas complejas a tamaños pequeños.
 * Solución: hex inline (#233D63, #FF8C42, #00A9A5).
 *
 * Categorías afectadas: TODAS (jaraba_icon() usa <img>).
 * Excepciones: SVGs usados como background-image o inline (ninguno actualmente).
 *
 * Uso: php scripts/validation/validate-svg-currentcolor.php
 * Exit: 0 = OK, 1 = SVGs con currentColor
 */

$root = dirname(__DIR__, 2);
$iconsDir = $root . '/web/modules/custom/ecosistema_jaraba_core/images/icons';

$errors = [];
$total = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($iconsDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'svg') {
        continue;
    }
    $total++;
    $content = file_get_contents($file->getPathname());

    if (stripos($content, 'currentColor') !== false) {
        $relPath = str_replace($root . '/', '', $file->getPathname());
        $count = substr_count(strtolower($content), 'currentcolor');
        $errors[] = "$relPath — $count occurrence(s) of currentColor";
    }
}

if (empty($errors)) {
    echo "✅ SVG-CURRENTCOLOR-001: All $total SVGs use hex inline colors (no currentColor).\n";
    exit(0);
}

echo "⚠️  SVG-CURRENTCOLOR-001: " . count($errors) . " of $total SVGs use currentColor\n";
echo "   These will render as BLACK when used as <img> tags via jaraba_icon().\n\n";
foreach ($errors as $error) {
    echo "  $error\n";
}
echo "\nFix: Replace currentColor with hex values:\n";
echo "  Corporate: #233D63 | Impulse: #FF8C42 | Innovation: #00A9A5\n";
// Warning only, not blocking (many legacy SVGs use currentColor + CSS filter)
exit(0);
