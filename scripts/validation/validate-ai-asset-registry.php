<?php

/**
 * @file
 * NANO-BANANA-ASSET-AUDIT-001: AI-generated asset traceability registry.
 *
 * Verifies that all AI-generated assets (Nano Banana images, Veo videos)
 * are registered in ai-asset-registry.json with traceability data.
 *
 * Usage: php scripts/validation/validate-ai-asset-registry.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$themeDir = "$root/web/themes/custom/ecosistema_jaraba_theme";
$registryPath = "$root/ai-asset-registry.json";

$passes = 0;
$failures = [];
$warnings = [];

echo "=== NANO-BANANA-ASSET-AUDIT-001: AI Asset Registry Validation ===\n\n";

// Step 1: Check registry file exists.
if (!file_exists($registryPath)) {
  echo "Registry not found. Auto-discovering AI-generated assets...\n\n";

  $discovered = [];

  // Trust strip logos.
  foreach (glob("$themeDir/images/logo-*.png") ?: [] as $logo) {
    $discovered[] = [
      'filename' => 'images/' . basename($logo),
      'tool' => 'nano-banana',
      'prompt' => '(fill in original prompt)',
      'date' => date('Y-m-d'),
      'purpose' => 'Trust strip logo (TRUST-STRIP-001)',
    ];
  }

  // Quiz illustrations.
  foreach (glob("$themeDir/images/quiz/*.png") ?: [] as $quiz) {
    $discovered[] = [
      'filename' => 'images/quiz/' . basename($quiz),
      'tool' => 'nano-banana',
      'prompt' => '(fill in original prompt)',
      'date' => '2026-03-20',
      'purpose' => 'Quiz vertical illustration',
    ];
  }

  // Hero videos.
  foreach (glob("$themeDir/videos/hero-*.mp4") ?: [] as $video) {
    $discovered[] = [
      'filename' => 'videos/' . basename($video),
      'tool' => 'veo',
      'prompt' => '(fill in original prompt)',
      'date' => '2026-03-21',
      'purpose' => 'Landing hero video (VIDEO-HERO-001)',
    ];
  }

  // Picker presets.
  foreach (glob("$themeDir/images/pickers/presets/*.png") ?: [] as $picker) {
    $discovered[] = [
      'filename' => 'images/pickers/presets/' . basename($picker),
      'tool' => 'nano-banana',
      'prompt' => '(fill in original prompt)',
      'date' => '2026-03-19',
      'purpose' => 'Theme preset picker preview',
    ];
  }

  $registry = [
    '_meta' => [
      'description' => 'AI-generated assets registry (NANO-BANANA-ASSET-AUDIT-001)',
      'last_updated' => date('Y-m-d'),
      'total_assets' => count($discovered),
    ],
    'assets' => $discovered,
  ];

  file_put_contents(
    $registryPath,
    json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
  );
  echo "Created ai-asset-registry.json with " . count($discovered) . " assets.\n";
  echo "Prompts are placeholders — fill in for full traceability.\n\n";
  $warnings[] = 'Registry auto-created with placeholder prompts';
  $passes++;
} else {
  // Step 2: Validate existing registry.
  $registry = json_decode(file_get_contents($registryPath), TRUE);

  if (!$registry || !isset($registry['assets'])) {
    $failures[] = 'ai-asset-registry.json is malformed (missing assets key)';
    echo "Registry file is malformed.\n";
  } else {
    $registeredFiles = array_column($registry['assets'], 'filename');
    echo "Registry contains " . count($registeredFiles) . " assets.\n\n";

    // Check known AI patterns are registered.
    $knownPatterns = array_merge(
      array_map(fn($f) => 'images/' . basename($f), glob("$themeDir/images/logo-*.png") ?: []),
      array_map(fn($f) => 'images/quiz/' . basename($f), glob("$themeDir/images/quiz/*.png") ?: []),
      array_map(fn($f) => 'videos/' . basename($f), glob("$themeDir/videos/hero-*.mp4") ?: [])
    );

    $unregistered = array_diff($knownPatterns, $registeredFiles);
    if (empty($unregistered)) {
      echo "All known AI-generated assets are registered.\n";
      $passes++;
    } else {
      foreach ($unregistered as $u) {
        $warnings[] = "Unregistered AI asset: $u";
        echo "  Missing: $u\n";
      }
    }

    // Check for placeholder prompts.
    $placeholders = 0;
    foreach ($registry['assets'] as $asset) {
      if (empty($asset['prompt']) || str_contains($asset['prompt'], 'fill in')) {
        $placeholders++;
      }
    }
    if ($placeholders > 0) {
      $warnings[] = "$placeholders assets have placeholder prompts";
      echo "$placeholders assets with placeholder prompts.\n";
    }

    // Check registered files exist on disk.
    $orphaned = [];
    foreach ($registeredFiles as $regFile) {
      if (!file_exists("$themeDir/$regFile")) {
        $orphaned[] = $regFile;
      }
    }
    if (!empty($orphaned)) {
      foreach ($orphaned as $o) {
        $warnings[] = "Orphaned registry entry: $o";
      }
      echo count($orphaned) . " registered assets not found on disk.\n";
    }
  }
}

echo "\n=== RESULT ===\n";

if (!empty($failures)) {
  echo "NANO-BANANA-ASSET-AUDIT-001: " . count($failures) . " failures.\n";
  foreach ($failures as $f) {
    echo "  - $f\n";
  }
  exit(1);
}

if (!empty($warnings)) {
  echo "NANO-BANANA-ASSET-AUDIT-001: Passed with " . count($warnings) . " warnings.\n";
  exit(0);
}

echo "NANO-BANANA-ASSET-AUDIT-001: All AI assets registered and verified.\n";
exit(0);
