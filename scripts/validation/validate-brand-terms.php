<?php
/**
 * @file
 * BRAND-TERM-VALIDATOR-001: Detecta terminos de marca obsoletos en el frontend.
 *
 * Escanea web/ y config/sync/ buscando terminos de marca que han sido
 * reemplazados por nuevos terminos de posicionamiento. Permite excepciones
 * para nombres propios (titulos academicos, organizaciones, programas EU).
 *
 * Historial de cambios de marca:
 * - 2026-03-27: "Desarrollo Rural" → "Desarrollo Local"
 *   (Excepciones: titulos academicos UCO, nombre GDR Campina Sur, LEADER EU)
 *
 * Uso: php scripts/validation/validate-brand-terms.php
 * Exit: 0 = OK, 1 = terminos obsoletos detectados en frontend
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);

// ─── Directorios a escanear (frontend + config) ───
$scanDirs = [
    $root . '/web/modules/custom',
    $root . '/web/themes/custom',
    $root . '/config/sync',
];

// ─── Extensiones a revisar ───
$extensions = ['php', 'twig', 'yml', 'yaml', 'po', 'js', 'html', 'json'];

// ─── Terminos obsoletos: patron simple + filtro de contexto ───
// Match simple, luego se filtra con $protectedPatterns.
$obsoleteTerms = [
    [
        '/[Dd]esarrollo\s+[Rr]ural/',
        'BRAND-RURAL-001',
        '"Desarrollo Rural" obsoleto → usar "Desarrollo Local"',
    ],
];

// ─── Exclusiones de ficheros (nombres propios, snapshots, docs) ───
$excludePatterns = [
    '/canvas-snapshots\//',
    '/vendor\//',
    '/node_modules\//',
    '/\.git\//',
];

$errors = [];
$filesScanned = 0;

foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $ext = strtolower($file->getExtension());

        // Filtrar por extension.
        if (!in_array($ext, $extensions, true)) {
            continue;
        }

        // Exclusiones.
        $skip = false;
        foreach ($excludePatterns as $excludePattern) {
            if (preg_match($excludePattern, $path)) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            continue;
        }

        $filesScanned++;
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            foreach ($obsoleteTerms as [$pattern, $ruleId, $message]) {
                if (preg_match($pattern, $line, $matches)) {
                    // Verificar que no es un nombre propio protegido
                    // (contexto expandido para evitar falsos positivos).
                    $context = $line;

                    // Proteger nombres propios conocidos.
                    $protectedPatterns = [
                        '/Master\s+(en\s+|Cientifico\s+en\s+)?(Gestion\s+del\s+)?Desarrollo\s+Rural/i',
                        '/Grupo\s+de\s+Desarrollo\s+Rural\s+Camp/i',
                        '/programas\s+de\s+Desarrollo\s+Rural\s+Territorial/i',
                        '/Universidad\s+de\s+C[oó]rdoba[^\.]{0,40}Desarrollo\s+Rural/i',
                        '/Liaison\s+Entre\s+Actions.*Rural/i',
                        '/Économie\s+Rurale/i',
                    ];

                    $isProtected = false;
                    foreach ($protectedPatterns as $pp) {
                        if (preg_match($pp, $context)) {
                            $isProtected = true;
                            break;
                        }
                    }

                    if (!$isProtected) {
                        $relPath = str_replace($root . '/', '', $path);
                        $errors[] = [
                            'file' => $relPath,
                            'line' => $lineNum + 1,
                            'rule' => $ruleId,
                            'message' => $message,
                            'match' => trim(substr($line, 0, 120)),
                        ];
                    }
                }
            }
        }
    }
}

// ─── Output ───
echo "\n";
echo "BRAND-TERM-VALIDATOR-001: Terminos de marca obsoletos\n";
echo str_repeat('=', 60) . "\n";
echo "Ficheros escaneados: $filesScanned\n";

if (empty($errors)) {
    echo "\n✅ BRAND-TERM-VALIDATOR-001: 0 terminos obsoletos detectados\n";
    exit(0);
}

echo "\n❌ " . count($errors) . " TERMINOS OBSOLETOS detectados:\n\n";
foreach ($errors as $e) {
    echo "  {$e['file']}:{$e['line']}\n";
    echo "    [{$e['rule']}] {$e['message']}\n";
    echo "    > " . substr($e['match'], 0, 100) . "\n\n";
}

exit(1);
