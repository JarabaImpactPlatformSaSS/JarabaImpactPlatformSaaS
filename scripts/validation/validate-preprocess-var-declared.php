<?php

/**
 * @file
 * PREPROCESS-VAR-DECLARED-001: Detect preprocess variables not declared in hook_theme().
 *
 * Drupal silently discards template variables that are not declared in
 * hook_theme() 'variables' array. This causes invisible bugs where a
 * preprocess function sets $variables['key'] = value but the Twig template
 * never receives it.
 *
 * Checks:
 * 1. Parses hook_theme() in each .module to extract template => declared vars.
 * 2. Parses preprocess functions to extract $variables['key'] = ... assignments.
 * 3. Reports assignments to undeclared variables.
 *
 * Excludes:
 * - Templates declared with 'render element' (entity render, vars are dynamic).
 * - Standard Drupal variables always available (attributes, title_prefix, etc.).
 * - Render array properties ($variables['#attached'], $variables['#cache']).
 * - Generic preprocess hooks (preprocess_html, preprocess_page, preprocess_node).
 * - Test files and scripts/.
 *
 * Usage: php scripts/validation/validate-preprocess-var-declared.php
 * Exit:  0 = pass (warn_check), 1 = violations found (informational)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  PREPROCESS-VAR-DECLARED-001                            ║\033[0m\n";
echo "\033[36m║  Preprocess Variables Declared in hook_theme() Validator ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Standard Drupal variables always available without declaration.
const STANDARD_VARS = [
  'attributes',
  'title_attributes',
  'content_attributes',
  'title_prefix',
  'title_suffix',
  'directory',
  'theme_hook_original',
  'theme_hook_suggestions',
  'content',
  'elements',
  'entity',
  'label',
  'url',
  'view_mode',
  'page',
];

// Generic preprocess hooks that apply to core templates, not module-defined ones.
const GENERIC_HOOKS = [
  'html',
  'page',
  'node',
  'block',
  'region',
  'field',
  'form',
  'form_element',
  'input',
  'select',
  'textarea',
  'details',
  'fieldset',
  'table',
  'views_view',
  'views_view_field',
  'views_view_fields',
  'views_view_table',
  'views_view_unformatted',
  'views_view_list',
  'views_view_grid',
  'image',
  'links',
  'item_list',
  'breadcrumb',
  'menu',
  'menu_local_task',
  'menu_local_tasks',
  'pager',
  'status_messages',
  'user',
  'taxonomy_term',
  'comment',
  'toolbar',
];

$violations = [];
$modulesChecked = 0;
$preprocessChecked = 0;

// Collect all .module files.
$moduleFiles = glob($modulesDir . '/*/*.module');
if ($moduleFiles === false) {
  $moduleFiles = [];
}

foreach ($moduleFiles as $moduleFile) {
  $content = file_get_contents($moduleFile);
  if ($content === false) {
    continue;
  }

  $moduleName = basename(dirname($moduleFile));
  $relPath = str_replace($projectRoot . '/', '', $moduleFile);

  // 1. Extract hook_theme() declared variables.
  $themeVars = parseHookTheme($content, $moduleName);
  if ($themeVars === []) {
    continue;
  }

  $modulesChecked++;

  // 2. Find preprocess functions and their variable assignments.
  $preprocessAssignments = parsePreprocessFunctions($content, $moduleName);

  foreach ($preprocessAssignments as $templateName => $assignedVars) {
    $preprocessChecked++;

    // Skip generic hooks (html, page, node, etc.).
    if (in_array($templateName, GENERIC_HOOKS, true)) {
      continue;
    }

    // Check if this template is declared in hook_theme().
    if (!isset($themeVars[$templateName])) {
      // Template not in this module's hook_theme() — might be from another
      // module or core. Skip.
      continue;
    }

    // If template uses 'render element', skip (vars are dynamic).
    if ($themeVars[$templateName] === null) {
      continue;
    }

    $declaredVars = $themeVars[$templateName];

    foreach ($assignedVars as $varName) {
      // Skip render array properties.
      if (str_starts_with($varName, '#')) {
        continue;
      }

      // Skip standard Drupal vars.
      if (in_array($varName, STANDARD_VARS, true)) {
        continue;
      }

      // Check if declared.
      if (!in_array($varName, $declaredVars, true)) {
        $violations[] = [
          'module' => $moduleName,
          'file' => $relPath,
          'template' => $templateName,
          'variable' => $varName,
        ];
      }
    }
  }
}

// Output results.
$violationCount = count($violations);

if ($violationCount > 0) {
  // Group by module for readability.
  $byModule = [];
  foreach ($violations as $v) {
    $byModule[$v['module']][] = $v;
  }

  echo "\033[33m⚠  PREPROCESS-VAR-DECLARED-001: {$violationCount} undeclared variable(s) found\033[0m\n\n";

  foreach ($byModule as $module => $moduleViolations) {
    echo "  \033[1m{$module}\033[0m\n";
    foreach ($moduleViolations as $v) {
      echo "    template: {$v['template']} — \$variables['{$v['variable']}'] not in hook_theme()\n";
    }
    echo "\n";
  }

  echo sprintf(
    "Checked %d modules, %d preprocess functions, found %d undeclared variables.\n",
    $modulesChecked,
    $preprocessChecked,
    $violationCount
  );
  echo "\033[33mThis is a warn_check — violations are reported for awareness.\033[0m\n";
  // warn_check: exit 0 even with violations.
  exit(0);
}

echo "\033[32m✔  PREPROCESS-VAR-DECLARED-001: PASS\033[0m";
echo sprintf(" — %d modules, %d preprocess functions checked, 0 undeclared variables.\n", $modulesChecked, $preprocessChecked);
exit(0);

// ============================================================================
// Helper functions.
// ============================================================================

/**
 * Parse hook_theme() from module content to extract template => declared vars.
 *
 * Returns array where:
 * - key = template name (underscores)
 * - value = array of declared variable names, or NULL if 'render element'.
 *
 * @return array<string, list<string>|null>
 */
function parseHookTheme(string $content, string $moduleName): array {
  $funcName = $moduleName . '_theme';

  // Find the hook_theme function.
  $pattern = '/function\s+' . preg_quote($funcName, '/') . '\s*\([^)]*\)\s*(?::\s*array\s*)?\{/s';
  if (!preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
    return [];
  }

  $startPos = (int) $match[0][1];

  // Extract function body by tracking braces.
  $body = extractFunctionBody($content, $startPos);
  if ($body === null) {
    return [];
  }

  $result = [];

  // Find template entries: 'template_name' => [ ... ]
  // Match each top-level key in the return array.
  // Pattern: 'template_name' => [
  $entryPattern = "/['\"]([a-z][a-z0-9_]*)['\"]\\s*=>\\s*\\[/";
  preg_match_all($entryPattern, $body, $entries, PREG_OFFSET_CAPTURE);

  foreach ($entries[1] as $idx => $entry) {
    $templateName = $entry[0];
    $entryStart = (int) $entries[0][$idx][1];

    // Extract the array content for this entry.
    $arrayBody = extractBracketContent($body, $entryStart + strlen($entries[0][$idx][0]) - 1);
    if ($arrayBody === null) {
      continue;
    }

    // Check if it's a render element.
    if (preg_match("/['\"]render\\s+element['\"]\\s*=>/", $arrayBody)) {
      $result[$templateName] = null;
      continue;
    }

    // Extract variables array.
    if (preg_match("/['\"]variables['\"]\\s*=>\\s*\\[/", $arrayBody, $varMatch, PREG_OFFSET_CAPTURE)) {
      $varStart = (int) $varMatch[0][1] + strlen($varMatch[0][0]) - 1;
      $varArrayBody = extractBracketContent($arrayBody, $varStart);
      if ($varArrayBody !== null) {
        // Extract variable names: 'var_name' => ...
        preg_match_all("/['\"]([a-z][a-z0-9_]*)['\"]\\s*=>/", $varArrayBody, $varNames);
        $result[$templateName] = $varNames[1] ?? [];
      }
      else {
        $result[$templateName] = [];
      }
    }
  }

  return $result;
}

/**
 * Parse preprocess functions from module content.
 *
 * Finds both:
 * - {module}_preprocess_{template}(array &$variables)
 * - template_preprocess_{template}(array &$variables)
 *
 * Returns array where key = template name, value = list of assigned var names.
 *
 * @return array<string, list<string>>
 */
function parsePreprocessFunctions(string $content, string $moduleName): array {
  $result = [];

  // Match both naming conventions.
  $patterns = [
    '/function\s+' . preg_quote($moduleName, '/') . '_preprocess_([a-z][a-z0-9_]*)\s*\(\s*array\s*&\s*\$variables/s',
    '/function\s+template_preprocess_([a-z][a-z0-9_]*)\s*\(\s*array\s*&\s*\$variables/s',
  ];

  foreach ($patterns as $pattern) {
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

    foreach ($matches[1] as $idx => $match) {
      $templateName = $match[0];
      $funcStart = (int) $matches[0][$idx][1];

      // Extract function body.
      $body = extractFunctionBody($content, $funcStart);
      if ($body === null) {
        continue;
      }

      // Find all $variables['key'] = ... assignments.
      // Also matches $variables['key'][] = ... and $variables['key']['sub'] = ...
      preg_match_all('/\$variables\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]\s*(?:\[[^\]]*\]\s*)*=/', $body, $varMatches);

      $assignedVars = array_unique($varMatches[1] ?? []);

      if (isset($result[$templateName])) {
        $result[$templateName] = array_unique(array_merge($result[$templateName], $assignedVars));
      }
      else {
        $result[$templateName] = $assignedVars;
      }
    }
  }

  return $result;
}

/**
 * Extract function body starting from a position that contains 'function'.
 *
 * Tracks brace depth to find the complete function body.
 */
function extractFunctionBody(string $content, int $startPos): ?string {
  // Find the opening brace.
  $bracePos = strpos($content, '{', $startPos);
  if ($bracePos === false) {
    return null;
  }

  $depth = 0;
  $len = strlen($content);
  $bodyStart = $bracePos + 1;

  for ($i = $bracePos; $i < $len; $i++) {
    $char = $content[$i];
    if ($char === '{') {
      $depth++;
    }
    elseif ($char === '}') {
      $depth--;
      if ($depth === 0) {
        return substr($content, $bodyStart, $i - $bodyStart);
      }
    }
  }

  return null;
}

/**
 * Extract content inside square brackets starting at given position.
 *
 * $pos must point to the opening '['.
 */
function extractBracketContent(string $content, int $pos): ?string {
  if (!isset($content[$pos]) || $content[$pos] !== '[') {
    return null;
  }

  $depth = 0;
  $len = strlen($content);
  $bodyStart = $pos + 1;

  for ($i = $pos; $i < $len; $i++) {
    $char = $content[$i];
    if ($char === '[') {
      $depth++;
    }
    elseif ($char === ']') {
      $depth--;
      if ($depth === 0) {
        return substr($content, $bodyStart, $i - $bodyStart);
      }
    }
  }

  return null;
}
