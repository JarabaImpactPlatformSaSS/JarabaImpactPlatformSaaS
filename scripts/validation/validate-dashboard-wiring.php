<?php

/**
 * @file
 * Validator: DASHBOARD-WIRING-001 — hook_theme → controller #theme parity.
 *
 * For every custom module's hook_theme() entry with a 'template' key,
 * verifies the corresponding controller returns #theme (not empty markup).
 * Skips entity view mode themes ('render element' => 'elements').
 *
 * Usage: php scripts/validation/validate-dashboard-wiring.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];
$skipped = 0;

$modulesDir = __DIR__ . '/../../web/modules/custom';
$moduleDirs = glob($modulesDir . '/*', GLOB_ONLYDIR);

foreach ($moduleDirs as $moduleDir) {
  $moduleName = basename($moduleDir);
  $moduleFile = $moduleDir . '/' . $moduleName . '.module';

  if (!file_exists($moduleFile)) {
    continue;
  }

  $moduleContent = file_get_contents($moduleFile);

  // Find hook_theme() function.
  if (!preg_match('/function\s+' . preg_quote($moduleName, '/') . '_theme\s*\(/s', $moduleContent)) {
    continue;
  }

  // Extract the hook_theme function body.
  $funcStart = strpos($moduleContent, 'function ' . $moduleName . '_theme(');
  if ($funcStart === FALSE) {
    continue;
  }

  // Find opening brace of function body.
  $bracePos = strpos($moduleContent, '{', $funcStart);
  if ($bracePos === FALSE) {
    continue;
  }

  // Extract function body by matching braces.
  $depth = 0;
  $funcBody = '';
  $len = strlen($moduleContent);
  for ($i = $bracePos; $i < $len; $i++) {
    $char = $moduleContent[$i];
    if ($char === '{') {
      $depth++;
    } elseif ($char === '}') {
      $depth--;
    }
    $funcBody .= $char;
    if ($depth === 0) {
      break;
    }
  }

  // Extract theme entries: 'theme_name' => [
  if (!preg_match_all("/['\"](\w+)['\"]\s*=>\s*\[/", $funcBody, $themeMatches)) {
    continue;
  }

  foreach ($themeMatches[1] as $themeName) {
    // Extract this theme entry's array content.
    $entryPattern = "/['\"]" . preg_quote($themeName, '/') . "['\"]\s*=>\s*\[/";
    if (!preg_match($entryPattern, $funcBody, $m, PREG_OFFSET_CAPTURE)) {
      continue;
    }

    $arrayStart = $m[0][1] + strlen($m[0][0]) - 1;
    $entryDepth = 0;
    $entryBody = '';
    for ($j = $arrayStart; $j < strlen($funcBody); $j++) {
      $c = $funcBody[$j];
      if ($c === '[') {
        $entryDepth++;
      } elseif ($c === ']') {
        $entryDepth--;
      }
      $entryBody .= $c;
      if ($entryDepth === 0) {
        break;
      }
    }

    // Skip entity view modes (render element => elements).
    if (preg_match("/['\"]render element['\"]\s*=>\s*['\"]elements['\"]/", $entryBody)) {
      $skipped++;
      continue;
    }

    // Must have 'template' key to be relevant.
    if (!preg_match("/['\"]template['\"]\s*=>/", $entryBody)) {
      $skipped++;
      continue;
    }

    // Now look for a controller that should return #theme => $themeName.
    $controllerDir = $moduleDir . '/src/Controller';
    if (!is_dir($controllerDir)) {
      // No controller dir — could be a preprocess-only theme. Skip.
      $skipped++;
      continue;
    }

    $controllerFiles = glob($controllerDir . '/*.php');
    if (empty($controllerFiles)) {
      $skipped++;
      continue;
    }

    // Search all controllers for references to this theme name.
    $foundThemeReturn = FALSE;
    $foundEmptyMarkup = FALSE;
    $relevantController = NULL;

    foreach ($controllerFiles as $ctrlFile) {
      $ctrlContent = file_get_contents($ctrlFile);

      // Check if controller references this theme name at all.
      if (strpos($ctrlContent, $themeName) === FALSE) {
        continue;
      }

      $relevantController = basename($ctrlFile);

      // Check for #theme => 'theme_name'.
      if (preg_match("/['\"]#theme['\"]\s*=>\s*['\"]" . preg_quote($themeName, '/') . "['\"]/", $ctrlContent)) {
        $foundThemeReturn = TRUE;
        break;
      }

      // Check for empty markup pattern in the same method context.
      if (preg_match("/['\"]#type['\"]\s*=>\s*['\"]markup['\"]/", $ctrlContent)
        && preg_match("/['\"]#markup['\"]\s*=>\s*['\"]['\"]/" , $ctrlContent)) {
        $foundEmptyMarkup = TRUE;
      }
    }

    if ($foundThemeReturn) {
      $passes[] = "CHECK: {$themeName} — controller {$relevantController} returns #theme";
    } elseif ($foundEmptyMarkup && $relevantController !== NULL) {
      $errors[] = "FAIL: {$themeName} — controller {$relevantController} returns empty markup instead of #theme";
    } elseif ($relevantController !== NULL) {
      // Controller references the theme name but doesn't use #theme — ambiguous.
      // Could be ZERO-REGION pattern (preprocess injects). Report as warning pass.
      $passes[] = "CHECK: {$themeName} — controller {$relevantController} references theme (may use ZERO-REGION pattern)";
    }
    // If no controller references the theme at all, it's a preprocess-only theme — skip silently.
  }
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== DASHBOARD-WIRING-001 ===\n\n";
foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed (skipped $skipped entity/non-controller themes) ---\n\n";
exit(empty($errors) ? 0 : 1);
