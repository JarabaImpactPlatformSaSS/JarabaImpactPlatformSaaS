<?php

/**
 * @file
 * HOOK-THEME-COMPLETENESS-001: Validates hook_theme() variable completeness.
 *
 * Checks that ALL variables used in Twig templates are declared in hook_theme().
 * Focuses on dashboard theme hooks where undeclared variables are silently discarded.
 *
 * Usage: php scripts/validation/validate-hook-theme-completeness.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  HOOK-THEME-COMPLETENESS-001                            ║\033[0m\n";
echo "\033[36m║  hook_theme() Variable Completeness Validator            ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Drupal core variables to skip (always available without declaration).
$core_variables = [
  'attributes', 'title_prefix', 'title_suffix', 'directory',
  'theme_hook_original', 'theme_hook_suggestions', 'content',
  'page', 'node', 'user', 'logged_in', 'is_admin', 'is_front',
  'language', 'base_path', 'db_is_active', 'url',
  'clean_content', 'clean_messages',
];

// Twig built-in variables and loop variables to skip.
$twig_builtins = [
  'loop', 'item', 'key', 'value', '_key', '_context',
  'true', 'false', 'null', 'none',
];

// Priority theme hooks to check (dashboards are most important).
$priority_hooks = [
  'page_builder_dashboard',
  'content_hub_dashboard_frontend',
  'demo_dashboard',
  'demo_dashboard_view',
  'candidate_dashboard',
  'experiment_dashboard',
  'analytics_dashboard',
  'demo_landing',
  'demo_ai_storytelling',
];

$module_files = glob($root . '/web/modules/custom/*/*.module');
if ($module_files === false) {
  $module_files = [];
}

$total_checked = 0;
$total_hooks_found = 0;

foreach ($module_files as $module_file) {
  $module_content = file_get_contents($module_file);
  if ($module_content === false) {
    continue;
  }

  // Find hook_theme() function.
  if (!preg_match('/function\s+\w+_theme\s*\(\s*\)/', $module_content)) {
    continue;
  }

  $module_name = basename(dirname($module_file));
  $module_dir = dirname($module_file);

  // Extract theme hook declarations with their variables and template names.
  // Match patterns like: 'hook_name' => [ ... 'variables' => [...], 'template' => '...' ... ]
  // We use a simpler approach: find each hook key and extract its block.
  if (!preg_match('/function\s+\w+_theme\s*\(\s*\)\s*\{(.*?)^\}/ms', $module_content, $theme_func_match)) {
    continue;
  }
  $theme_func_body = $theme_func_match[1];

  // Find each hook declaration: 'hook_name' => [
  if (!preg_match_all("/['\"]([a-z_]+)['\"]\s*=>\s*\[/", $theme_func_body, $hook_matches, PREG_OFFSET_CAPTURE)) {
    continue;
  }

  foreach ($hook_matches[1] as $idx => $hook_data) {
    $hook_name = $hook_data[0];
    $hook_offset = $hook_data[1];

    // Only check priority hooks if specified.
    if (!empty($priority_hooks) && !in_array($hook_name, $priority_hooks, true)) {
      continue;
    }

    $total_hooks_found++;

    // Extract the block for this hook (find matching bracket).
    $block_start = $hook_matches[0][$idx][1];
    $block = substr($theme_func_body, $block_start);

    // Extract declared variables from 'variables' => [...]
    $declared_vars = [];
    if (preg_match("/'variables'\s*=>\s*\[(.*?)\]/s", $block, $vars_match)) {
      // Match 'var_name' => ... patterns.
      if (preg_match_all("/['\"]([a-z_]+)['\"]\s*=>/", $vars_match[1], $var_names)) {
        $declared_vars = $var_names[1];
      }
    }

    // Extract template name.
    $template_name = str_replace('_', '-', $hook_name);
    if (preg_match("/'template'\s*=>\s*['\"]([^'\"]+)['\"]/", $block, $tpl_match)) {
      $template_name = $tpl_match[1];
    }

    // Find the template file.
    $template_file = $module_dir . '/templates/' . $template_name . '.html.twig';
    if (!file_exists($template_file)) {
      // Try theme directory.
      $template_file = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates/' . $template_name . '.html.twig';
    }
    if (!file_exists($template_file)) {
      $warnings[] = "[$module_name] $hook_name: template '$template_name.html.twig' not found";
      continue;
    }

    $total_checked++;
    $template_content = file_get_contents($template_file);
    if ($template_content === false) {
      continue;
    }

    // Extract top-level variable references from template.
    // Match {{ variable_name }} and {% if variable_name %} patterns.
    // Exclude function calls like path(), jaraba_icon(), t(), url().
    $used_vars = [];

    // Match {{ var }}, {{ var|filter }}, {{ var.sub }}.
    if (preg_match_all('/\{\{[^}]*?\b([a-z_][a-z_0-9]*)\b/', $template_content, $expr_matches)) {
      $used_vars = array_merge($used_vars, $expr_matches[1]);
    }

    // Match {% if var %}, {% for x in var %}, {% set x = var %}.
    if (preg_match_all('/\{%[^%]*?\b(?:if|for\s+\w+\s+in|set\s+\w+\s*=)\s+([a-z_][a-z_0-9]*)/', $template_content, $ctrl_matches)) {
      $used_vars = array_merge($used_vars, $ctrl_matches[1]);
    }

    // Also match variables used after 'with' in include statements.
    // e.g., {% include '_partial.html.twig' with { setup_wizard: setup_wizard } %}
    if (preg_match_all('/with\s*\{[^}]*?:\s*([a-z_][a-z_0-9]*)/', $template_content, $with_matches)) {
      $used_vars = array_merge($used_vars, $with_matches[1]);
    }

    $used_vars = array_unique($used_vars);

    // Filter out known exclusions.
    $skip_all = array_merge($core_variables, $twig_builtins);
    $missing = [];
    $ok_count = 0;

    foreach ($used_vars as $var) {
      // Skip core/builtin variables.
      if (in_array($var, $skip_all, true)) {
        continue;
      }
      // Skip single-character vars (loop vars like i, x, etc.).
      if (strlen($var) <= 1) {
        continue;
      }
      // Skip Twig functions and Drupal Twig extensions.
      if (in_array($var, ['range', 'cycle', 'date', 'dump', 'max', 'min', 'random', 'include', 'block', 'parent', 'source', 'jaraba_icon', 'path', 'url', 'file_url', 'attach_library', 'active_theme_path', 'active_theme', 'create_attribute', 'render_var', 'link', 'trans', 'endtrans', 'placeholder', 'clean_class', 'clean_id', 'safe_join', 'without', 'format_date'], true)) {
        continue;
      }
      // Skip for-loop iteration variables (defined inline).
      if (preg_match('/\{%\s*for\s+' . preg_quote($var, '/') . '\s+in\b/', $template_content)) {
        continue;
      }
      // Skip set variables (defined inline).
      if (preg_match('/\{%\s*set\s+' . preg_quote($var, '/') . '\s*=/', $template_content)) {
        continue;
      }

      if (in_array($var, $declared_vars, true)) {
        $ok_count++;
      } else {
        $missing[] = $var;
      }
    }

    if (empty($missing)) {
      $passes[] = "[$module_name] $hook_name: All template variables declared in hook_theme() ($ok_count vars)";
    } else {
      foreach ($missing as $m) {
        $errors[] = "[$module_name] $hook_name: Variable '$m' used in template but NOT declared in hook_theme()";
      }
      if ($ok_count > 0) {
        $passes[] = "[$module_name] $hook_name: $ok_count variables correctly declared";
      }
    }
  }
}

// ── REPORT ────────────────────────────────────────────────────────────
echo "Scanned: " . count($module_files) . " module files, found $total_hooks_found priority hooks, checked $total_checked templates\n\n";

foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$total = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$total PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
