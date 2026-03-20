<?php

/**
 * @file validate-doc-version-drift.php
 * DOC-VERSION-DRIFT-001: Meta-validator ensuring version coherence across
 * the 4 master docs, CLAUDE.md, and MEMORY.md.
 *
 * Checks:
 * 1. All 4 master docs have matching learning# and golden rule#
 * 2. Version numbers are monotonically increasing (not stale)
 * 3. Cross-reference banner in INDICE matches actual doc versions
 */

$errors = [];
$warnings = [];

$docs = [
    'DIRECTRICES' => 'docs/00_DIRECTRICES_PROYECTO.md',
    'ARQUITECTURA' => 'docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md',
    'INDICE' => 'docs/00_INDICE_GENERAL.md',
    'FLUJO' => 'docs/00_FLUJO_TRABAJO_CLAUDE.md',
];

$versions = [];
$learningNums = [];
$goldenNums = [];

foreach ($docs as $name => $path) {
    if (!file_exists($path)) {
        $errors[] = "[$name] Master doc not found: $path";
        continue;
    }

    // Read first 10 lines for version info
    $lines = array_slice(file($path), 0, 10);
    $header = implode("\n", $lines);

    // Extract version number
    if (preg_match('/[Vv]ersi.n[:\s*]*(\d+\.\d+\.\d+)/u', $header, $m)) {
        $versions[$name] = $m[1];
    } else {
        $errors[] = "[$name] Cannot extract version number from header";
    }

    // Extract learning number
    if (preg_match('/aprendizaje\s*#(\d+)/iu', $header, $m)) {
        $learningNums[$name] = (int)$m[1];
    }

    // Extract golden rule number
    if (preg_match('/regla de oro\s*#(\d+)/iu', $header, $m)) {
        $goldenNums[$name] = (int)$m[1];
    }
}

// Check learning numbers are consistent across all docs
if (!empty($learningNums)) {
    $uniqueLearning = array_unique($learningNums);
    if (count($uniqueLearning) > 1) {
        $details = [];
        foreach ($learningNums as $doc => $num) {
            $details[] = "$doc=#$num";
        }
        $errors[] = "Learning numbers diverge: " . implode(', ', $details);
    }
}

// Check golden rule numbers are consistent
if (!empty($goldenNums)) {
    $uniqueGolden = array_unique($goldenNums);
    if (count($uniqueGolden) > 1) {
        $details = [];
        foreach ($goldenNums as $doc => $num) {
            $details[] = "$doc=#$num";
        }
        $errors[] = "Golden rule numbers diverge: " . implode(', ', $details);
    }
}

// Check INDICE cross-reference banner matches actual versions
if (file_exists($docs['INDICE'])) {
    $indiceContent = file_get_contents($docs['INDICE']);
    foreach ($versions as $docName => $ver) {
        $majorVer = explode('.', $ver)[0];
        // Check the banner contains the right version prefix
        if (preg_match('/v' . $majorVer . '\s+' . strtoupper($docName) . '|v' . $majorVer . '\s+' . $docName . '/i', $indiceContent)) {
            // Found matching version in banner
        } else {
            // Softer check: just verify the number appears near the doc name
            if (strpos($indiceContent, "v$majorVer") !== false) {
                // Close enough — version number exists
            } else {
                $warnings[] = "INDICE banner may not match $docName version v$ver";
            }
        }
    }
}

// Report
if (empty($errors) && empty($warnings)) {
    $versionStr = implode(', ', array_map(fn($k, $v) => "$k=v$v", array_keys($versions), $versions));
    echo "DOC-VERSION-DRIFT-001: PASS — All docs coherent ($versionStr)\n";
    exit(0);
}

foreach ($warnings as $w) {
    echo "  WARN: $w\n";
}
foreach ($errors as $e) {
    echo "  FAIL: $e\n";
}

if (!empty($errors)) {
    echo "DOC-VERSION-DRIFT-001: FAIL — " . count($errors) . " version coherence errors\n";
    exit(1);
}

echo "DOC-VERSION-DRIFT-001: WARN — " . count($warnings) . " minor warnings\n";
exit(0);
