#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-include-only-var-pass.php
 *
 * INCLUDE-ONLY-VAR-PASS-001: Detecta variables usadas en parciales Twig
 * incluidos con `{% include ... with { ... } only %}` que NO se pasan
 * explícitamente en el bloque `with`.
 *
 * Con `only`, el parcial NO hereda variables del padre. Si el parcial usa
 * una variable que no se pasa, será `null` en runtime — bug silencioso.
 *
 * Checks:
 *   1. Variables usadas en el parcial no presentes en el bloque `with`
 *
 * Exclusiones (reducción de falsos positivos):
 *   - Twig built-ins: loop, _self, _context, _key
 *   - Variables definidas con {% set %} dentro del parcial
 *   - Variables de bucle {% for x in ... %} (x es local)
 *   - Funciones Twig: jaraba_icon(), path(), url(), t(), range(), etc.
 *   - Includes con path dinámico (variable en el path)
 *   - Variables accedidas como propiedades de variables pasadas (var.prop)
 *
 * USO:
 *   php scripts/validation/validate-include-only-var-pass.php
 *
 * EXIT CODES:
 *   0 = siempre (warn_check)
 */

$root = dirname(__DIR__, 2);
$warnings = [];
$filesChecked = 0;
$includesChecked = 0;

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  INCLUDE-ONLY-VAR-PASS-001                              ║\033[0m\n";
echo "\033[36m║  Twig include-only variable completeness                ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── Twig built-in variables to exclude ─────────────────────────────────────

$BUILTIN_VARS = [
    'loop', '_self', '_context', '_key', '_charset',
    'true', 'false', 'null', 'none',
    'app', 'directory', 'active_theme_path', 'active_theme',
    'base_path', 'front_page', 'language', 'theme',
    'is_front', 'is_admin', 'logged_in', 'user',
    'db_is_active', 'attributes', 'context',
    // Common Drupal preprocess variables available globally.
    'content', 'page', 'node', 'label', 'title_prefix', 'title_suffix',
    'title_attributes', 'content_attributes',
];

// ── Twig functions to exclude (not variables) ──────────────────────────────

$TWIG_FUNCTIONS = [
    'jaraba_icon', 'path', 'url', 't', 'range', 'cycle', 'constant',
    'random', 'date', 'dump', 'max', 'min', 'source', 'block',
    'parent', 'include', 'create_attribute', 'attach_library',
    'active_theme_path', 'file_url', 'link', 'render_var',
];

// ── Collect Twig files ─────────────────────────────────────────────────────

$searchDirs = [
    $root . '/web/themes/custom',
    $root . '/web/modules/custom',
];

$twigFiles = [];
foreach ($searchDirs as $baseDir) {
    if (!is_dir($baseDir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (str_ends_with($file->getFilename(), '.html.twig')) {
            $twigFiles[$file->getPathname()] = true;
        }
    }
}

// ── Namespace resolution ───────────────────────────────────────────────────

/**
 * Resolve @namespace/path to filesystem path.
 *
 * Convention: @module_name → web/modules/custom/module_name/templates/
 *             @ecosistema_jaraba_theme → web/themes/custom/ecosistema_jaraba_theme/templates/
 */
function resolveTemplatePath(string $includePath, string $parentFile, string $root): ?string {
    $includePath = trim($includePath, " \t\n\r\"'");

    // Dynamic path (contains Twig variable) — skip.
    if (str_contains($includePath, '{{') || str_contains($includePath, '~')) {
        return null;
    }

    if (str_starts_with($includePath, '@')) {
        // Extract namespace and relative path.
        if (!preg_match('#^@([a-zA-Z0-9_]+)/(.+)$#', $includePath, $m)) {
            return null;
        }
        $namespace = $m[1];
        $relPath = $m[2];

        // Theme namespace.
        if ($namespace === 'ecosistema_jaraba_theme') {
            $resolved = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates/' . $relPath;
            if (is_file($resolved)) {
                return $resolved;
            }
        }

        // Module namespace: try templates/ subdirectory.
        $modulePath = $root . '/web/modules/custom/' . $namespace . '/templates/' . $relPath;
        if (is_file($modulePath)) {
            return $modulePath;
        }

        return null;
    }

    // Relative path — resolve from parent directory.
    $parentDir = dirname($parentFile);
    $resolved = $parentDir . '/' . $includePath;
    if (is_file($resolved)) {
        return realpath($resolved);
    }

    return null;
}

// ── Strip Twig comments and verbatim blocks ────────────────────────────────

function stripTwigComments(string $content): string {
    // Remove {# ... #} comments (may be multiline).
    $content = preg_replace('/\{#.*?#\}/s', '', $content) ?? $content;
    // Remove {% verbatim %}...{% endverbatim %}.
    $content = preg_replace('/\{%[-~]?\s*verbatim\s*[-~]?%\}.*?\{%[-~]?\s*endverbatim\s*[-~]?%\}/s', '', $content) ?? $content;
    return $content;
}

// ── Extract variables passed in `with { ... }` block ───────────────────────

function extractWithVars(string $withBlock): array {
    $vars = [];
    // Match keys in the mapping: `key:` or `'key':` or `"key":`.
    if (preg_match_all('/(?:^|[,{])\s*[\'"]?(\w+)[\'"]?\s*:/m', $withBlock, $matches)) {
        foreach ($matches[1] as $var) {
            $vars[] = $var;
        }
    }
    return $vars;
}

// ── Extract variables used in a partial ────────────────────────────────────

function extractUsedVars(string $content): array {
    $content = stripTwigComments($content);
    $vars = [];

    // 1. {{ var }}, {{ var.prop }}, {{ var|filter }}, {{ var.prop|filter }}
    //    Also inside {% if var %}, {% for x in var %}, {% set x = var %}, etc.
    //    Strategy: find all word tokens that appear after {{ or {% or inside Twig expressions.

    // Extract all Twig expression/tag content.
    $twigBlocks = [];
    if (preg_match_all('/\{\{(.+?)\}\}|\{%(.+?)%\}/s', $content, $matches)) {
        foreach ($matches[1] as $expr) {
            if (trim($expr) !== '') {
                $twigBlocks[] = $expr;
            }
        }
        foreach ($matches[2] as $tag) {
            if (trim($tag) !== '') {
                $twigBlocks[] = $tag;
            }
        }
    }

    foreach ($twigBlocks as $block) {
        // Remove string literals to avoid false positives.
        $block = preg_replace("/'[^']*'/", ' ', $block) ?? $block;
        $block = preg_replace('/"[^"]*"/', ' ', $block) ?? $block;

        // Remove hash literal keys (words before `:` inside `{ ... }`).
        // This prevents `{ variant: 'duotone', color: 'azul' }` from
        // registering `variant` and `color` as used variables.
        $block = preg_replace('/\b(\w+)\s*:(?!=)/', ' ', $block) ?? $block;

        // Remove function call contents (jaraba_icon(...), path(...), etc.)
        // to avoid treating named arguments as variables.
        // Simple 1-level nesting removal.
        $block = preg_replace('/\w+\s*\((?:[^()]*|\([^()]*\))*\)/', ' ', $block) ?? $block;

        // Only capture ROOT variable names:
        // - Must NOT be preceded by a dot (property access like obj.prop)
        // - Must NOT be followed by `(` (function call like path())
        // This regex captures words that start a dotted chain or stand alone.
        if (preg_match_all('/(?<!\.)(?<!\w)\b([a-zA-Z_]\w*)\b(?!\s*\()/', $block, $wordMatches)) {
            foreach ($wordMatches[1] as $word) {
                $vars[$word] = true;
            }
        }
    }

    return array_keys($vars);
}

// ── Extract locally defined variables ({% set %}, {% for x in %}) ──────────

function extractLocalVars(string $content): array {
    $content = stripTwigComments($content);
    $locals = [];

    // {% set var = ... %} or {% set var %}...{% endset %}
    if (preg_match_all('/\{%[-~]?\s*set\s+(\w+)/', $content, $matches)) {
        foreach ($matches[1] as $var) {
            $locals[] = $var;
        }
    }

    // {% for key, value in ... %} or {% for value in ... %}
    if (preg_match_all('/\{%[-~]?\s*for\s+(\w+)\s*,?\s*(\w*)\s+in\b/', $content, $matches)) {
        foreach ($matches[1] as $var) {
            if ($var !== '') {
                $locals[] = $var;
            }
        }
        foreach ($matches[2] as $var) {
            if ($var !== '') {
                $locals[] = $var;
            }
        }
    }

    // {% macro name(arg1, arg2) %}
    if (preg_match_all('/\{%[-~]?\s*macro\s+\w+\s*\(([^)]*)\)/', $content, $matches)) {
        foreach ($matches[1] as $argList) {
            if (preg_match_all('/(\w+)/', $argList, $argMatches)) {
                foreach ($argMatches[1] as $arg) {
                    $locals[] = $arg;
                }
            }
        }
    }

    // {% with { var: ... } %}
    if (preg_match_all('/\{%[-~]?\s*with\s*\{((?:[^{}]|\{[^{}]*\})*)\}/', $content, $matches)) {
        foreach ($matches[1] as $block) {
            if (preg_match_all('/[\'"]?(\w+)[\'"]?\s*:/', $block, $keyMatches)) {
                foreach ($keyMatches[1] as $var) {
                    $locals[] = $var;
                }
            }
        }
    }

    return $locals;
}

// ── Twig keywords to exclude ───────────────────────────────────────────────

$TWIG_KEYWORDS = [
    'if', 'else', 'elseif', 'endif', 'for', 'endfor', 'in', 'not',
    'set', 'endset', 'block', 'endblock', 'extends', 'include', 'with',
    'only', 'macro', 'endmacro', 'import', 'from', 'as', 'is', 'and',
    'or', 'trans', 'endtrans', 'plural', 'apply', 'endapply', 'do',
    'flush', 'verbatim', 'endverbatim', 'embed', 'endembed', 'use',
    'sandbox', 'endsandbox', 'autoescape', 'endautoescape', 'deprecated',
    'even', 'odd', 'defined', 'empty', 'iterable', 'sameas', 'same',
    'divisibleby', 'divisible', 'starts', 'ends', 'matches', 'has',
    'some', 'every', 'b', 'by',
    // Common Twig test names.
    'null', 'none', 'true', 'false',
    // Common Twig filter names (appear as words after |).
    'default', 'escape', 'e', 'raw', 'length', 'upper', 'lower',
    'trim', 'striptags', 'title', 'capitalize', 'first', 'last',
    'join', 'split', 'sort', 'reverse', 'keys', 'values', 'merge',
    'batch', 'column', 'filter', 'map', 'reduce', 'slice', 'abs',
    'round', 'number_format', 'date', 'date_modify', 'format',
    'replace', 'nl2br', 'url_encode', 'json_encode', 'convert_encoding',
    'without', 'clean_class', 'clean_id', 'safe_join', 'render',
    'placeholder', 'trans', 'format_date', 'add_class', 'set_attribute',
    'number', 'clean', 'class',
];

// ── Build global exclusion set ─────────────────────────────────────────────

$globalExclusions = array_flip(array_merge(
    $BUILTIN_VARS,
    $TWIG_FUNCTIONS,
    $TWIG_KEYWORDS
));

// ── Find and analyze include...only directives ─────────────────────────────

foreach ($twigFiles as $filePath => $_) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        continue;
    }
    $filesChecked++;

    $relPath = str_replace($root . '/', '', $filePath);
    $cleanContent = stripTwigComments($content);

    // Find all {% include 'path' with { ... } only %} patterns.
    // Support multiline with blocks (up to 1 level of nested braces).
    $pattern = '/\{%[-~]?\s*include\s+[\'"]([^"\']+)[\'"]\s+with\s*\{((?:[^{}]|\{[^{}]*\})*)\}\s*only\s*[-~]?%\}/s';

    if (!preg_match_all($pattern, $cleanContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        // Also check bare `only` without `with` block.
        $barePattern = '/\{%[-~]?\s*include\s+[\'"]([^"\']+)[\'"]\s+only\s*[-~]?%\}/s';
        if (preg_match_all($barePattern, $cleanContent, $bareMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($bareMatches as $match) {
                $includePath = $match[1][0];
                $offset = $match[0][1];
                $lineNum = substr_count(substr($cleanContent, 0, (int) $offset), "\n") + 1;

                $resolvedPath = resolveTemplatePath($includePath, $filePath, $root);
                if ($resolvedPath === null || !is_file($resolvedPath)) {
                    continue;
                }

                $includesChecked++;
                $partialContent = file_get_contents($resolvedPath);
                if ($partialContent === false) {
                    continue;
                }

                $usedVars = extractUsedVars($partialContent);
                $localVars = extractLocalVars($partialContent);
                $localSet = array_flip($localVars);

                $missing = [];
                foreach ($usedVars as $var) {
                    if (isset($globalExclusions[$var])) {
                        continue;
                    }
                    if (isset($localSet[$var])) {
                        continue;
                    }
                    $missing[] = $var;
                }

                if (!empty($missing)) {
                    sort($missing);
                    $partialRel = str_replace($root . '/', '', $resolvedPath);
                    $warnings[] = [
                        'file' => $relPath,
                        'line' => $lineNum,
                        'check' => 'MISSING-VAR-BARE-ONLY',
                        'message' => "include con 'only' sin 'with' — parcial usa: " . implode(', ', array_slice($missing, 0, 10))
                            . (count($missing) > 10 ? ' (+' . (count($missing) - 10) . ' más)' : ''),
                        'context' => "→ {$partialRel}",
                    ];
                }
            }
        }
        continue;
    }

    foreach ($matches as $match) {
        $includePath = $match[1][0];
        $withBlock = $match[2][0];
        $offset = $match[0][1];
        $lineNum = substr_count(substr($cleanContent, 0, (int) $offset), "\n") + 1;

        // Resolve the partial path.
        $resolvedPath = resolveTemplatePath($includePath, $filePath, $root);
        if ($resolvedPath === null || !is_file($resolvedPath)) {
            continue;
        }

        $includesChecked++;

        // Extract variables passed via `with`.
        $passedVars = extractWithVars($withBlock);
        $passedSet = array_flip($passedVars);

        // Read and analyze the partial.
        $partialContent = file_get_contents($resolvedPath);
        if ($partialContent === false) {
            continue;
        }

        $usedVars = extractUsedVars($partialContent);
        $localVars = extractLocalVars($partialContent);
        $localSet = array_flip($localVars);

        // Determine missing variables.
        $missing = [];
        foreach ($usedVars as $var) {
            // Skip globals/builtins/keywords/functions.
            if (isset($globalExclusions[$var])) {
                continue;
            }
            // Skip locally defined variables.
            if (isset($localSet[$var])) {
                continue;
            }
            // Skip variables that were passed.
            if (isset($passedSet[$var])) {
                continue;
            }
            // Skip if the var is a sub-property of a passed variable.
            // e.g., if `document` is passed, `document.format` uses `document`.
            // The var we see might be a property name that only appears as `passed.var`.
            $isProperty = false;
            foreach ($passedVars as $pv) {
                // Check if this var only appears as `passed_var.var` in the partial.
                if (preg_match('/\b' . preg_quote($pv, '/') . '\.' . preg_quote($var, '/') . '\b/', $partialContent)) {
                    // Also check it doesn't appear standalone.
                    // Pattern: var preceded by {{ or space/operator (not dot).
                    $standalonePattern = '/(?<!\.)(?<!\w)\b' . preg_quote($var, '/') . '\b(?!\s*\()/';
                    $partialClean = stripTwigComments($partialContent);
                    // Remove all `passed.var` occurrences, then check if var still appears.
                    $stripped = preg_replace('/\b' . preg_quote($pv, '/') . '\.' . preg_quote($var, '/') . '\b/', '', $partialClean) ?? $partialClean;
                    if (!preg_match($standalonePattern, $stripped)) {
                        $isProperty = true;
                        break;
                    }
                }
            }
            if ($isProperty) {
                continue;
            }

            $missing[] = $var;
        }

        if (!empty($missing)) {
            sort($missing);
            $partialRel = str_replace($root . '/', '', $resolvedPath);
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum,
                'check' => 'MISSING-VAR-IN-WITH',
                'message' => "Parcial usa variables no pasadas en 'with': " . implode(', ', array_slice($missing, 0, 10))
                    . (count($missing) > 10 ? ' (+' . (count($missing) - 10) . ' más)' : ''),
                'context' => "→ {$partialRel} | pasadas: " . implode(', ', $passedVars),
            ];
        }
    }
}

// ── Output ─────────────────────────────────────────────────────────────────

echo "Archivos analizados: {$filesChecked}\n";
echo "Includes con 'only' analizados: {$includesChecked}\n\n";

if (count($warnings) === 0) {
    echo "\033[32m✔ PASS — Todos los includes con 'only' pasan las variables necesarias\033[0m\n";
    exit(0);
}

echo "\033[33m⚠ WARNINGS (" . count($warnings) . ")\033[0m\n\n";
foreach ($warnings as $w) {
    $loc = $w['line'] > 0 ? "{$w['file']}:{$w['line']}" : $w['file'];
    echo "  \033[33m⚠\033[0m [{$w['check']}] {$loc}\n";
    echo "    {$w['message']}\n";
    if (isset($w['context'])) {
        echo "    \033[90m{$w['context']}\033[0m\n";
    }
    echo "\n";
}

echo "\033[32m✔ PASS — Solo warnings (no bloquean)\033[0m\n";
exit(0);
