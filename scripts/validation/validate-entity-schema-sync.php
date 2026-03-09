<?php

/**
 * @file
 * ENTITY-SCHEMA-SYNC-001: Validate entity schema synchronization.
 *
 * Static analysis checks (NO Drupal bootstrap required):
 * 1. ENTITY-COMPUTED-ORPHAN-001: Detect update hooks using getBaseFieldDefinitions()
 *    with updateFieldableEntityType() — should use getFieldStorageDefinitions() instead.
 * 2. TRANSLATABLE-FIELDS-INSTALL-001: Detect entities with translatable=TRUE that have
 *    update hooks making them translatable without installing content_translation fields.
 * 3. TWIG-URL-CONCAT-001: Detect Twig templates concatenating url() with ~ operator.
 *
 * Usage: php scripts/validation/validate-entity-schema-sync.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';
$themesDir = $projectRoot . '/web/themes/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$errors = [];
$warnings = [];

// ─────────────────────────────────────────────────────────
// CHECK 1: ENTITY-COMPUTED-ORPHAN-001
// Detect update hooks using getBaseFieldDefinitions() with
// updateFieldableEntityType(). Should use getFieldStorageDefinitions().
// ─────────────────────────────────────────────────────────

$installFiles = glob($modulesDir . '/*/*.install');
$installFiles = array_merge($installFiles, glob($modulesDir . '/*/modules/*/*.install'));

foreach ($installFiles as $installFile) {
  $content = file_get_contents($installFile);
  $relativePath = str_replace($projectRoot . '/', '', $installFile);

  // Find update hooks that use getBaseFieldDefinitions + updateFieldableEntityType.
  if (preg_match_all('/function\s+(\w+_update_\d+)\s*\(/m', $content, $hookMatches)) {
    foreach ($hookMatches[1] as $hookName) {
      // Extract the hook body (approximate: from function declaration to next function or EOF).
      $pattern = '/function\s+' . preg_quote($hookName, '/') . '\s*\([^)]*\)[^{]*\{/';
      if (!preg_match($pattern, $content, $funcMatch, PREG_OFFSET_CAPTURE)) {
        continue;
      }

      $startPos = $funcMatch[0][1];
      // Find matching closing brace (simple depth counter).
      $depth = 0;
      $inBody = false;
      $hookBody = '';
      $len = strlen($content);
      for ($i = $startPos; $i < $len; $i++) {
        $char = $content[$i];
        if ($char === '{') {
          $depth++;
          $inBody = true;
        }
        elseif ($char === '}') {
          $depth--;
          if ($inBody && $depth === 0) {
            $hookBody = substr($content, $startPos, $i - $startPos + 1);
            break;
          }
        }
      }

      if (empty($hookBody)) {
        continue;
      }

      // Check: uses getBaseFieldDefinitions AND updateFieldableEntityType.
      $usesBaseField = strpos($hookBody, 'getBaseFieldDefinitions') !== false;
      $usesUpdateFieldable = strpos($hookBody, 'updateFieldableEntityType') !== false;

      if ($usesBaseField && $usesUpdateFieldable) {
        // Check if it also uses getFieldStorageDefinitions (in which case it may be fine).
        $usesStorageDefs = strpos($hookBody, 'getFieldStorageDefinitions') !== false;
        if (!$usesStorageDefs) {
          // Check if a later update hook in the same file fixes the orphan
          // (uninstalls computed field storage). If so, demote to warning.
          $hasRemediation = strpos($content, 'uninstallFieldStorageDefinition') !== false;
          $target = $hasRemediation ? 'warnings' : 'errors';
          $suffix = $hasRemediation
            ? ' A later update hook remediates this (uninstallFieldStorageDefinition found).'
            : '';
          $$target['ENTITY-COMPUTED-ORPHAN-001'][] = sprintf(
            '%s: %s() uses getBaseFieldDefinitions() with updateFieldableEntityType(). '
            . 'This includes computed fields (metatag) that have no storage, creating orphan entries. '
            . 'Use getFieldStorageDefinitions() instead.%s',
            $relativePath,
            $hookName,
            $suffix
          );
        }
      }
    }
  }
}

// ─────────────────────────────────────────────────────────
// CHECK 2: TRANSLATABLE-FIELDS-INSTALL-001
// Detect entities declared translatable=TRUE and check that
// update hooks installing them also handle content_translation fields.
// ─────────────────────────────────────────────────────────

// Find all translatable entities.
$entityFiles = [];
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
  if ($file->isFile() && $file->getExtension() === 'php') {
    $path = $file->getPathname();
    if (strpos($path, '/src/Entity/') !== false) {
      $entityFiles[] = $path;
    }
  }
}

$translatableEntities = [];
foreach ($entityFiles as $entityFile) {
  $content = file_get_contents($entityFile);
  $relativePath = str_replace($projectRoot . '/', '', $entityFile);

  // Check annotation for translatable = TRUE.
  if (preg_match('/translatable\s*=\s*TRUE/', $content)) {
    // Extract entity id.
    if (preg_match('/id\s*=\s*"([^"]+)"/', $content, $idMatch)) {
      $translatableEntities[$idMatch[1]] = $relativePath;
    }
  }
}

// For each translatable entity, check if the module's .install has an update hook
// that references updateFieldableEntityType/updateEntityType AND also installs
// content_translation fields.
foreach ($translatableEntities as $entityId => $entityPath) {
  // Determine module name from entity path.
  $parts = explode('/', str_replace($modulesDir . '/', '', $entityPath));
  $moduleName = $parts[0];
  // Check for submodule.
  if (isset($parts[1]) && $parts[1] === 'modules' && isset($parts[2])) {
    $moduleName = $parts[2];
  }

  // Find install file.
  $installFile = null;
  $candidates = [
    $modulesDir . '/' . $parts[0] . '/' . $moduleName . '.install',
    $modulesDir . '/' . $parts[0] . '/modules/' . ($parts[2] ?? '') . '/' . $moduleName . '.install',
  ];
  foreach ($candidates as $candidate) {
    if (file_exists($candidate)) {
      $installFile = $candidate;
      break;
    }
  }

  if (!$installFile) {
    // No install file — may be handled elsewhere. Skip.
    continue;
  }

  $installContent = file_get_contents($installFile);
  $installRelative = str_replace($projectRoot . '/', '', $installFile);

  // Check if any update hook references this entity and translatable setup.
  $hasTranslatableSetup = (
    strpos($installContent, $entityId) !== false
    && (
      strpos($installContent, 'updateFieldableEntityType') !== false
      || strpos($installContent, 'setTranslatable(TRUE)') !== false
      || strpos($installContent, "'translatable' => TRUE") !== false
    )
  );

  if ($hasTranslatableSetup) {
    // Verify content_translation fields are installed somewhere in the file.
    $hasTranslationFieldInstall = (
      strpos($installContent, 'content_translation_source') !== false
      || strpos($installContent, 'content_translation_') !== false
    );

    if (!$hasTranslationFieldInstall) {
      $warnings['TRANSLATABLE-FIELDS-INSTALL-001'][] = sprintf(
        '%s: Entity "%s" (translatable=TRUE) has update hooks in %s but no installation of content_translation_* fields. '
        . 'Ensure an update hook installs the 6 content_translation tracking fields.',
        $entityPath,
        $entityId,
        $installRelative
      );
    }
  }
}

// ─────────────────────────────────────────────────────────
// CHECK 3: TWIG-URL-CONCAT-001
// Detect Twig templates that concatenate url() with ~ operator.
// ─────────────────────────────────────────────────────────

$twigDirs = [$modulesDir, $themesDir];
foreach ($twigDirs as $searchDir) {
  if (!is_dir($searchDir)) {
    continue;
  }

  $twigIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($searchDir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($twigIterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'twig') {
      $content = file_get_contents($file->getPathname());
      $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());

      // Pattern: url(...) ~ 'something' or variable ~ url(...)
      // Also: set var = url(...) followed by var ~ 'something'
      if (preg_match('/url\s*\([^)]*\)\s*~/', $content)
        || preg_match('/~\s*url\s*\(/', $content)) {
        $errors['TWIG-URL-CONCAT-001'][] = sprintf(
          '%s: Concatenating url() with ~ operator. url() returns render array in Drupal 11, '
          . 'not a string. Use url() only inside {{ }}. For concatenated paths, use '
          . "'/' ~ directory ~ '/path' or pre-compute in preprocess.",
          $relativePath
        );
      }

      // Indirect pattern: {% set var = url(...) %} then var ~ 'something'
      if (preg_match_all('/\{%\s*set\s+(\w+)\s*=\s*url\s*\(/', $content, $setMatches)) {
        foreach ($setMatches[1] as $varName) {
          // Check if this variable is later used with ~ (but not inside {{ }}).
          if (preg_match('/\b' . preg_quote($varName, '/') . '\b\s*~/', $content)
            || preg_match('/~\s*\b' . preg_quote($varName, '/') . '\b/', $content)) {
            // Verify it's inside {% set %} or similar, not {{ }}.
            // Simple heuristic: if the ~ usage is inside {% %} (not {{ }})
            if (preg_match('/\{%[^%]*\b' . preg_quote($varName, '/') . '\b\s*~/s', $content)
              || preg_match('/\{%[^%]*~\s*\b' . preg_quote($varName, '/') . '\b/s', $content)) {
              $errors['TWIG-URL-CONCAT-001'][] = sprintf(
                '%s: Variable "$%s" assigned from url() is concatenated with ~ inside {%% %%}. '
                . 'url() returns render array. Use path-based approach instead.',
                $relativePath,
                $varName
              );
            }
          }
        }
      }
    }
  }
}

// ─────────────────────────────────────────────────────────
// Report results.
// ─────────────────────────────────────────────────────────

$hasErrors = false;

if (!empty($errors)) {
  $hasErrors = true;
  echo "  ERRORS:\n";
  foreach ($errors as $ruleId => $messages) {
    echo "  [$ruleId]\n";
    foreach ($messages as $msg) {
      echo "    $msg\n";
    }
  }
  echo "\n";
}

if (!empty($warnings)) {
  echo "  WARNINGS:\n";
  foreach ($warnings as $ruleId => $messages) {
    echo "  [$ruleId]\n";
    foreach ($messages as $msg) {
      echo "    $msg\n";
    }
  }
  echo "\n";
}

if (empty($errors) && empty($warnings)) {
  echo "  OK: Entity schema sync checks passed.\n";
}

$totalEntities = count($translatableEntities);
$totalInstallFiles = count($installFiles);
echo "  Scanned: $totalInstallFiles install files, $totalEntities translatable entities.\n";

exit($hasErrors ? 1 : 0);
