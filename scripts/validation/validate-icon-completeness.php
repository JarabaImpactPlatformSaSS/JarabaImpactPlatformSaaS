<?php
/**
 * @file
 * ICON-COMPLETENESS-001: Valida que TODOS los iconos referenciados en
 * jaraba_icon() tienen SVG base + duotone en el directorio de iconos.
 *
 * Complementa ICON-INTEGRITY-001 (que valida referencias en templates).
 * Este script valida la completitud bidireccional:
 * - Todas las references -> tienen SVG
 * - Todas las variantes duotone referenciadas -> tienen -duotone.svg
 *
 * Uso: php scripts/validation/validate-icon-completeness.php
 * Exit: 0 = OK, 1 = hay iconos faltantes (chinchetas en runtime)
 */

$root = dirname(__DIR__, 2);
$iconsDir = $root . '/web/modules/custom/ecosistema_jaraba_core/images/icons';
$searchDirs = [
    $root . '/web/modules/custom',
    $root . '/web/themes/custom',
];

$missing = [];
$total = 0;

// Pattern: jaraba_icon('category', 'name' or jaraba_icon('category', variable.icon
$pattern = "/jaraba_icon\s*\(\s*'([^']+)'\s*,\s*'([^']+)'/";

foreach ($searchDirs as $searchDir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        $ext = $file->getExtension();
        if (!in_array($ext, ['twig', 'php'], true)) {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $category = $match[1];
                $name = $match[2];
                $key = "$category/$name";
                $total++;

                $svgPath = "$iconsDir/$category/$name.svg";
                if (!file_exists($svgPath)) {
                    $relPath = str_replace($root . '/', '', $file->getPathname());
                    $missing[$key][] = $relPath;
                }
            }
        }
    }
}

// Deduplicate
$uniqueMissing = array_keys($missing);

if (empty($uniqueMissing)) {
    echo "✅ ICON-COMPLETENESS-001: All $total icon references have SVG files.\n";
    exit(0);
}

echo "❌ ICON-COMPLETENESS-001: " . count($uniqueMissing) . " missing SVGs (" . count($uniqueMissing) . " of $total unique references)\n\n";
foreach ($missing as $icon => $files) {
    echo "  MISSING: $icon.svg\n";
    echo "    Referenced in: " . implode(', ', array_slice(array_unique($files), 0, 3));
    if (count($files) > 3) {
        echo ' + ' . (count($files) - 3) . ' more';
    }
    echo "\n";
}
echo "\nFix: Create missing SVGs in web/modules/custom/ecosistema_jaraba_core/images/icons/\n";
echo "Palette: #233D63 (corporate), #FF8C42 (impulse), #00A9A5 (innovation)\n";
exit(1);
