<?php

/**
 * @file
 * Fix Twig include statements missing the `only` keyword per TWIG-INCLUDE-ONLY-001.
 *
 * Rules:
 * - Only fix includes that use `with { ... }` but don't have `only` at the end.
 * - Simple includes without `with` are left alone.
 * - Handles both single-line and multi-line includes.
 * - Does NOT touch includes inside Twig comments ({# ... #}).
 *
 * Usage: php scripts/migration/fix-twig-include-only.php [--dry-run]
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$root = dirname(__DIR__, 2);

$searchDirs = [
  $root . '/web/themes/custom/',
  $root . '/web/modules/custom/',
];

$totalFixed = 0;
$totalFiles = 0;
$fileResults = [];

foreach ($searchDirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
      continue;
    }
    $path = $file->getPathname();
    $content = file_get_contents($path);
    if ($content === false) {
      continue;
    }

    $fixCount = fixFile($content, $newContent);
    if ($fixCount > 0) {
      $totalFixed += $fixCount;
      $totalFiles++;
      $relPath = str_replace($root . '/', '', $path);
      $fileResults[] = sprintf("  [%d fixes] %s", $fixCount, $relPath);
      if (!$dryRun) {
        file_put_contents($path, $newContent);
      }
    }
  }
}

echo ($dryRun ? "[DRY RUN] " : "") . "TWIG-INCLUDE-ONLY-001 Fix Results\n";
echo str_repeat('=', 60) . "\n";
echo "Files modified: $totalFiles\n";
echo "Includes fixed: $totalFixed\n";
echo str_repeat('-', 60) . "\n";
sort($fileResults);
foreach ($fileResults as $line) {
  echo $line . "\n";
}

if ($dryRun && $totalFixed > 0) {
  echo "\nRun without --dry-run to apply fixes.\n";
}

exit($totalFixed > 0 && $dryRun ? 1 : 0);

/**
 * Fix includes in file content. Returns number of fixes applied.
 */
function fixFile(string $content, ?string &$output): int {
  $fixes = 0;
  $output = $content;

  // Strategy: find all {% include ... with { ... } %} blocks (possibly multiline)
  // that don't already have `only` before the closing %}.
  //
  // We need to be careful about:
  // 1. Nested braces inside the `with { ... }` block
  // 2. Multi-line includes
  // 3. Twig comments
  //
  // Approach: walk through the content character by character tracking state.

  $len = strlen($output);
  $i = 0;
  $result = '';

  while ($i < $len) {
    // Check for Twig comment start {#
    if ($i < $len - 1 && $output[$i] === '{' && $output[$i + 1] === '#') {
      // Find end of comment #}
      $commentEnd = strpos($output, '#}', $i + 2);
      if ($commentEnd === false) {
        // Unterminated comment, copy rest
        $result .= substr($output, $i);
        break;
      }
      $result .= substr($output, $i, $commentEnd + 2 - $i);
      $i = $commentEnd + 2;
      continue;
    }

    // Check for {% include
    if ($i < $len - 1 && $output[$i] === '{' && $output[$i + 1] === '%') {
      // Find the tag content - we need to find the matching %}
      $tagStart = $i;
      $j = $i + 2;

      // Skip whitespace after {%
      while ($j < $len && ctype_space($output[$j])) {
        $j++;
      }

      // Check if this is an include tag
      $keyword = '';
      $k = $j;
      while ($k < $len && ctype_alpha($output[$k])) {
        $keyword .= $output[$k];
        $k++;
      }

      if ($keyword === 'include') {
        // This is an include tag. We need to find its closing %}
        // while tracking brace depth for the `with { ... }` part.
        $hasWith = false;
        $hasOnly = false;
        $braceDepth = 0;
        $inString = false;
        $stringChar = '';
        $closingPos = false;

        $j = $k; // After 'include'
        while ($j < $len - 1) {
          $ch = $output[$j];

          // Track strings to avoid counting braces inside strings
          if ($inString) {
            if ($ch === '\\' && $j + 1 < $len) {
              $j += 2;
              continue;
            }
            if ($ch === $stringChar) {
              $inString = false;
            }
            $j++;
            continue;
          }

          if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            $j++;
            continue;
          }

          if ($ch === '{') {
            $braceDepth++;
            $j++;
            continue;
          }

          if ($ch === '}') {
            if ($braceDepth > 0) {
              $braceDepth--;
              $j++;
              continue;
            }
          }

          // Check for closing %}
          if ($ch === '%' && $j + 1 < $len && $output[$j + 1] === '}' && $braceDepth === 0) {
            $closingPos = $j;
            break;
          }

          // Track 'with' keyword (not inside braces/strings)
          if ($braceDepth === 0 && $ch === 'w') {
            $candidate = substr($output, $j, 4);
            if ($candidate === 'with') {
              // Make sure it's a word boundary
              $before = ($j > 0) ? $output[$j - 1] : ' ';
              $after = ($j + 4 < $len) ? $output[$j + 4] : ' ';
              if (!ctype_alnum($before) && $before !== '_' && !ctype_alnum($after) && $after !== '_') {
                $hasWith = true;
              }
            }
          }

          // Track 'only' keyword
          if ($braceDepth === 0 && $ch === 'o') {
            $candidate = substr($output, $j, 4);
            if ($candidate === 'only') {
              $before = ($j > 0) ? $output[$j - 1] : ' ';
              $after = ($j + 4 < $len) ? $output[$j + 4] : ' ';
              if (!ctype_alnum($before) && $before !== '_' && !ctype_alnum($after) && $after !== '_') {
                $hasOnly = true;
              }
            }
          }

          $j++;
        }

        if ($closingPos !== false && $hasWith && !$hasOnly) {
          // Need to insert 'only ' before '%}'
          // Find the right insertion point: just before the '%}'
          // But we should add a space if needed
          $beforeClosing = $closingPos - 1;
          while ($beforeClosing >= $tagStart && ctype_space($output[$beforeClosing])) {
            $beforeClosing--;
          }

          // Insert "only " before the "%}"
          // Check what's before %}: it could be "} %}" or just "%}"
          $insertPos = $closingPos;
          // Add space before 'only' if the char before isn't already a space
          $prefix = '';
          if ($insertPos > 0 && !ctype_space($output[$insertPos - 1])) {
            $prefix = ' ';
          }

          $result .= substr($output, $tagStart, $insertPos - $tagStart) . $prefix . 'only ';
          // The %} will be added in the next iteration
          $i = $insertPos;
          $fixes++;
          continue;
        }

        // No fix needed, copy the entire tag
        if ($closingPos !== false) {
          $tagEnd = $closingPos + 2;
          $result .= substr($output, $tagStart, $tagEnd - $tagStart);
          $i = $tagEnd;
          continue;
        }
      }
    }

    $result .= $output[$i];
    $i++;
  }

  $output = $result;
  return $fixes;
}
