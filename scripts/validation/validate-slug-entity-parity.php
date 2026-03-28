<?php

declare(strict_types=1);

/**
 * @file
 * SLUG-ENTITY-PARITY-001: Verifica que los slugs de SuccessCase referenciados
 * en PHP (controllers, preprocess) existen en el seed script.
 *
 * Detecta:
 * - caseStudyUrl('vertical', 'slug') en controllers
 * - Url::fromRoute('jaraba_success_cases.detail', ['slug' => 'value'])
 * - Hardcoded paths /caso-de-exito/slug en PHP strings
 *
 * Uso: php scripts/validation/validate-slug-entity-parity.php
 * Exit: 0 = pass, 1 = violations
 */

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = ''): void {
  global $pass, $fail, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[36m╔══════════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║\033[0m  \033[1mSLUG-ENTITY-PARITY-001\033[0m — SuccessCase slug parity check     \033[36m║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════════╝\033[0m\n\n";

// === Step 1: Extract slugs from seed script ===
$seedFile = "$basePath/scripts/migration/seed-success-cases.php";
$seedSlugs = [];

if (!file_exists($seedFile)) {
  echo "  \033[31mFAIL\033[0m Seed script not found: scripts/migration/seed-success-cases.php\n";
  exit(1);
}

$seedContent = file_get_contents($seedFile);
if (preg_match_all("/['\"]slug['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $seedContent, $matches)) {
  $seedSlugs = array_unique($matches[1]);
}

$checks++;
if (count($seedSlugs) > 0) {
  $pass++;
  echo "  \033[32mPASS\033[0m Seed script: " . count($seedSlugs) . " slugs found\n";
}
else {
  $fail++;
  echo "  \033[31mFAIL\033[0m Seed script: no slugs found\n";
  exit(1);
}

// === Step 2: Scan PHP files for slug references ===
$scanDir = "$basePath/web/modules/custom";
$violations = [];
$referencedSlugs = [];

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }

  // Skip the seed script itself and test files.
  $filePath = $file->getPathname();
  $relPath = str_replace($basePath . '/', '', $filePath);

  if (str_contains($relPath, '/tests/')) {
    continue;
  }

  $content = file_get_contents($filePath);

  // Pattern 1: caseStudyUrl('vertical', 'slug')
  if (preg_match_all("/caseStudyUrl\s*\(\s*['\"][^'\"]*['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
    foreach ($matches[1] as $idx => $slug) {
      $lineNum = substr_count(substr($content, 0, strpos($content, $matches[0][$idx])), "\n") + 1;
      $referencedSlugs[$slug][] = "$relPath:$lineNum (caseStudyUrl)";
    }
  }

  // Pattern 2: Url::fromRoute('jaraba_success_cases.detail', ['slug' => 'value'])
  if (preg_match_all("/fromRoute\s*\(\s*['\"]jaraba_success_cases\.detail['\"]\s*,\s*\[[^\]]*['\"]slug['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
    foreach ($matches[1] as $idx => $slug) {
      $lineNum = substr_count(substr($content, 0, strpos($content, $matches[0][$idx])), "\n") + 1;
      $referencedSlugs[$slug][] = "$relPath:$lineNum (fromRoute)";
    }
  }

  // Pattern 3: Hardcoded paths like '/caso-de-exito/slug' or '/vertical/caso-de-exito/slug'
  if (preg_match_all("#['\"]/?[a-z-]*/caso-de-exito/([a-z][a-z0-9-]+)['\"]#", $content, $matches)) {
    foreach ($matches[1] as $idx => $slug) {
      $lineNum = substr_count(substr($content, 0, strpos($content, $matches[0][$idx])), "\n") + 1;
      $referencedSlugs[$slug][] = "$relPath:$lineNum (hardcoded path)";
    }
  }
}

// === Step 3: Check parity ===
$orphanSlugs = [];
foreach ($referencedSlugs as $slug => $locations) {
  if (!in_array($slug, $seedSlugs, true)) {
    $orphanSlugs[$slug] = $locations;
  }
}

// Check: all referenced slugs exist in seed.
check(
  'All referenced slugs exist in seed (' . count($referencedSlugs) . ' references, ' . count(array_unique(array_merge(...array_values($referencedSlugs)))) . ' locations)',
  empty($orphanSlugs)
);

if (!empty($orphanSlugs)) {
  echo "\n  \033[31mOrphan slugs (referenced in code but missing from seed):\033[0m\n";
  foreach ($orphanSlugs as $slug => $locations) {
    echo "    \033[33m• \"$slug\"\033[0m\n";
    foreach ($locations as $loc) {
      echo "      → $loc\n";
    }
  }
  echo "\n";
}

// Check: seed slugs referenced at least once (informational, not a failure).
$unusedSeedSlugs = array_diff($seedSlugs, array_keys($referencedSlugs));
check(
  'Seed slugs referenced in code (' . (count($seedSlugs) - count($unusedSeedSlugs)) . '/' . count($seedSlugs) . ')',
  true
);

if (!empty($unusedSeedSlugs)) {
  echo "    \033[33mInfo: " . count($unusedSeedSlugs) . " seed slugs not directly referenced in PHP (may be used dynamically):\033[0m\n";
  foreach ($unusedSeedSlugs as $slug) {
    echo "      · $slug\n";
  }
  echo "\n";
}

// Summary.
echo "\n\033[36m══════════════════════════════════════════════════════════════\033[0m\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed (of $checks)\n";
echo "\033[36m══════════════════════════════════════════════════════════════\033[0m\n";

exit($fail > 0 ? 1 : 0);
