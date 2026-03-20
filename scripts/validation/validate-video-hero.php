<?php

declare(strict_types=1);

/**
 * @file validate-video-hero.php
 * VIDEO-HERO-001: Video Hero Asset Completeness.
 *
 * Checks that video hero assets exist and are properly sized for all verticals.
 *
 * Checks:
 * 1. Each expected MP4 file exists in the theme videos/ directory.
 * 2. File size is > 100KB (not empty/corrupt) and < 5MB (web performance).
 * 3. Each vertical that has a hero-*.mp4 also has 'video_url' in its
 *    hero config inside VerticalLandingController.php.
 *
 * EXIT CODES:
 *   0 = All checks pass
 *   1 = One or more checks fail
 */

$root = dirname(__DIR__, 2);

// ANSI colour helpers.
$green  = "\033[32m";
$red    = "\033[31m";
$yellow = "\033[33m";
$reset  = "\033[0m";
$bold   = "\033[1m";

$ok   = "{$green}✓{$reset}";
$fail = "{$red}✗{$reset}";

// ─── Header ──────────────────────────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  VIDEO-HERO-001                                         ║\n";
echo "║  Video Hero Asset Completeness                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ─── Configuration ───────────────────────────────────────────────────────────

$videosDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/videos';

/**
 * Expected video files mapped to their vertical key.
 * Key   = filename (relative to $videosDir)
 * Value = vertical identifier (used for controller check)
 */
$expectedVideos = [
    'hero-agroconecta.mp4'      => 'agroconecta',
    'hero-comercioconecta.mp4'  => 'comercioconecta',
    'hero-serviciosconecta.mp4' => 'serviciosconecta',
    'hero-empleabilidad.mp4'    => 'empleabilidad',
    'hero-emprendimiento.mp4'   => 'emprendimiento',
    'hero-jarabalex.mp4'        => 'jarabalex',
    'hero-formacion.mp4'        => 'formacion',
    'hero-andalucia-ei.mp4'     => 'andalucia-ei',
    'hero-contenthub.mp4'       => 'contenthub',
];

$minBytes = 100 * 1024;        // 100 KB
$maxBytes = 5 * 1024 * 1024;   // 5 MB

$controllerPath = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php';

// ─── Check 1 & 2: File existence and size ────────────────────────────────────
echo "  {$bold}Asset files:{$reset}\n\n";

$fileErrors  = 0;
$filePass    = 0;
$videosPassed = []; // vertical keys that passed file checks

foreach ($expectedVideos as $filename => $verticalKey) {
    $path = $videosDir . '/' . $filename;

    if (!file_exists($path)) {
        echo "  {$fail} {$filename} — {$red}FILE NOT FOUND{$reset}\n";
        $fileErrors++;
        continue;
    }

    $bytes = filesize($path);

    if ($bytes < $minBytes) {
        $kb = round($bytes / 1024, 1);
        echo "  {$fail} {$filename} — {$red}{$kb}KB (TOO SMALL — likely empty or corrupt, min 100KB){$reset}\n";
        $fileErrors++;
        continue;
    }

    if ($bytes > $maxBytes) {
        $mb = round($bytes / (1024 * 1024), 1);
        echo "  {$fail} {$filename} — {$red}{$mb}MB (TOO LARGE — max 5MB for web performance){$reset}\n";
        $fileErrors++;
        continue;
    }

    $mb = round($bytes / (1024 * 1024), 2);
    echo "  {$ok} {$filename} — {$mb}MB (OK)\n";
    $filePass++;
    $videosPassed[] = $verticalKey;
}

// ─── Check 3: Controller video_url references ────────────────────────────────
echo "\n  {$bold}Controller references:{$reset}\n\n";

$controllerErrors = 0;
$controllerPass   = 0;

if (!file_exists($controllerPath)) {
    echo "  {$fail} {$red}VerticalLandingController.php not found at:{$reset}\n";
    echo "       {$controllerPath}\n";
    $controllerErrors++;
} else {
    $source = file_get_contents($controllerPath);

    foreach ($expectedVideos as $filename => $verticalKey) {
        // The video URL stored in the controller uses the filename directly.
        $expectedUrl = 'videos/' . $filename;

        if (strpos($source, $expectedUrl) !== false) {
            echo "  {$ok} {$verticalKey} has video_url in hero config\n";
            $controllerPass++;
        } else {
            echo "  {$fail} {$verticalKey} {$red}missing video_url in hero config{$reset}\n";
            $controllerErrors++;
        }
    }
}

// ─── Summary ─────────────────────────────────────────────────────────────────
$totalChecks  = ($filePass + $fileErrors) + ($controllerPass + $controllerErrors);
$totalPass    = $filePass + $controllerPass;
$totalFail    = $fileErrors + $controllerErrors;

echo "\n";
echo str_repeat('─', 60) . "\n";

if ($totalFail === 0) {
    echo "  {$green}{$bold}RESULT: {$totalPass}/{$totalChecks} PASS{$reset}\n\n";
    exit(0);
} else {
    echo "  {$red}{$bold}RESULT: {$totalPass}/{$totalChecks} PASS — {$totalFail} failure(s){$reset}\n\n";
    exit(1);
}
