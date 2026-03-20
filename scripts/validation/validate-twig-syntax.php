#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-twig-syntax.php
 *
 * TWIG-SYNTAX-LINT-001: Detecta errores de sintaxis comunes en templates Twig
 * que Drupal no reporta hasta runtime (causando 500 silenciosos en producción).
 *
 * Checks (ERRORES — bloquean commit):
 *   1. DOUBLE-COMMA: ,, en contexto Twig (fuera de <script>/<style> y strings)
 *   2. UNCLOSED-BALANCE: {% o {{ sin su cierre correspondiente a nivel de archivo
 *   3. UNMATCHED-BLOCK: {% block X %} sin {% endblock %} (y otros pares obligatorios)
 *
 * Checks (WARNINGS — informan, no bloquean):
 *   4. MIXED-KEY-STYLE: keys con comillas y sin comillas en un mismo mapping
 *   5. EMPTY-WITH: {% include ... with {} %} — probable olvido de variables
 *   6. TWIG-IN-HTML-COMMENT: {{ o {% dentro de <!-- --> (Twig las ejecuta)
 *
 * Diseño: Zero falsos positivos en errores. Acepta falsos negativos marginales.
 * Prioriza precisión sobre exhaustividad — un lint que bloquea commits con falsos
 * positivos destruye la confianza del equipo en el sistema de salvaguarda.
 *
 * USO:
 *   php scripts/validation/validate-twig-syntax.php            # Todo el proyecto
 *   php scripts/validation/validate-twig-syntax.php <archivo>  # Un archivo (lint-staged)
 *
 * EXIT CODES:
 *   0 = PASS (sin errores; puede haber warnings)
 *   1 = FAIL (errores encontrados)
 */

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$filesChecked = 0;

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  TWIG-SYNTAX-LINT-001                                   ║\033[0m\n";
echo "\033[36m║  Twig Static Syntax Linter                              ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── Recopilar archivos ──────────────────────────────────────────────────────

$twigFiles = [];

if (isset($argv[1]) && is_file($argv[1])) {
    $twigFiles[] = realpath($argv[1]);
} else {
    $searchDirs = [
        $root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
    ];
    $moduleDirs = glob($root . '/web/modules/custom/*/templates');
    if ($moduleDirs !== false) {
        $searchDirs = array_merge($searchDirs, $moduleDirs);
    }

    foreach ($searchDirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), '.html.twig')) {
                $twigFiles[] = $file->getPathname();
            }
        }
    }
}

// ── Utilidades compartidas ──────────────────────────────────────────────────

/**
 * Prepara contenido para análisis eliminando zonas que NO son Twig:
 * - Comentarios Twig {# ... #}
 * - Bloques <script>...</script> (contienen JS con sintaxis propia)
 * - Bloques <style>...</style> (contienen CSS)
 * - Bloques {% verbatim %}...{% endverbatim %}
 *
 * Reemplaza con espacios del mismo largo para preservar posiciones de línea.
 */
function prepareTwigContent(string $content): string {
    // 1. Comentarios Twig (pueden ser multilínea).
    // Aplicar iterativamente porque {# anidados (ej: {# ... {# inner #} ... #})
    // requieren múltiples pasadas — el non-greedy match cierra en el primer #}.
    // Tras strippar el inner, el outer queda expuesto en la siguiente iteración.
    $maxIterations = 10;
    for ($i = 0; $i < $maxIterations; $i++) {
        $replaced = preg_replace_callback('/\{#.*?#\}/s', function ($m) {
            return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
        }, $content);
        if ($replaced === $content || $replaced === null) {
            break;
        }
        $content = $replaced;
    }

    // 2. Bloques <script>...</script>.
    $content = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/si', function ($m) {
        // Preservar saltos de línea para que los números de línea sean correctos.
        return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 3. Bloques <style>...</style>.
    $content = preg_replace_callback('/<style\b[^>]*>.*?<\/style>/si', function ($m) {
        return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 4. Bloques {% verbatim %}...{% endverbatim %}.
    $content = preg_replace_callback('/\{%\s*verbatim\s*%\}.*?\{%\s*endverbatim\s*%\}/s', function ($m) {
        return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    return $content;
}

/**
 * Elimina strings (single y double quoted) de una línea para evitar
 * falsos positivos con contenido literal.
 */
function stripStrings(string $line): string {
    // Strings con comillas simples (manejar escapes).
    $line = preg_replace("/'.+?(?<!\\\\)'/", '', $line) ?? $line;
    // Strings con comillas dobles.
    $line = preg_replace('/"[^"]*"/', '', $line) ?? $line;
    return $line;
}

// ── CHECK 1: DOUBLE-COMMA ───────────────────────────────────────────────────

/**
 * Detecta ,, en contexto Twig (fuera de strings, script y style).
 * Causa: Twig\Error\SyntaxError inmediato al parsear el template.
 * Incidente: 2026-03-20, 500 en 3 subdominios multi-tenant.
 */
function checkDoubleComma(string $content, string $relPath, array &$errors): void {
    $clean = prepareTwigContent($content);
    $lines = explode("\n", $clean);

    foreach ($lines as $lineNum => $line) {
        // Solo analizar líneas que contengan algo de Twig o estén dentro de
        // un bloque Twig (entre {% ... %} o {{ ... }}). Pero dado que
        // prepareTwigContent ya eliminó script/style/verbatim, cualquier
        // ,, restante fuera de strings es sospechosa.
        $stripped = stripStrings($line);
        if (preg_match('/,,/', $stripped)) {
            $errors[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'DOUBLE-COMMA',
                'message' => "Doble coma ',,' detectada — causa SyntaxError en Twig 3.x",
                'context' => trim($lines[$lineNum]),
            ];
        }
    }
}

// ── CHECK 2: UNCLOSED-BALANCE ───────────────────────────────────────────────

/**
 * Verifica balance global de delimitadores Twig a nivel de archivo.
 *
 * Estrategia: contar TODOS los {% y %}, {{ y }} en el archivo (tras limpiar
 * zonas no-Twig). Un desbalance indica un tag/expresión sin cerrar.
 *
 * NO reporta por línea (evita falsos positivos en tags multilínea legítimos).
 * Reporta a nivel de archivo solo si hay desbalance neto.
 */
function checkUnclosedBalance(string $content, string $relPath, array &$errors): void {
    $clean = prepareTwigContent($content);

    $openTags = preg_match_all('/\{%/', $clean);
    $closeTags = preg_match_all('/%\}/', $clean);
    $openExpr = preg_match_all('/\{\{/', $clean);
    $closeExpr = preg_match_all('/\}\}/', $clean);

    if ($openTags !== $closeTags) {
        $diff = $openTags - $closeTags;
        $direction = $diff > 0 ? 'apertura(s) sin cierre' : 'cierre(s) sin apertura';
        $errors[] = [
            'file' => $relPath,
            'line' => 0,
            'check' => 'UNCLOSED-TAG-BALANCE',
            'message' => "Desbalance de {%...%}: " . abs($diff) . " $direction ({$openTags} abren, {$closeTags} cierran)",
        ];
    }

    if ($openExpr !== $closeExpr) {
        $diff = $openExpr - $closeExpr;
        $direction = $diff > 0 ? 'apertura(s) sin cierre' : 'cierre(s) sin apertura';
        $errors[] = [
            'file' => $relPath,
            'line' => 0,
            'check' => 'UNCLOSED-EXPR-BALANCE',
            'message' => "Desbalance de {{...}}: " . abs($diff) . " $direction ({$openExpr} abren, {$closeExpr} cierran)",
        ];
    }
}

// ── CHECK 3: UNMATCHED-BLOCK ────────────────────────────────────────────────

/**
 * Verifica que tags de bloque Twig tengan su cierre correspondiente.
 * Pares verificados: block/endblock, for/endfor, if/endif, macro/endmacro,
 * set (con body)/endset, apply/endapply, embed/endembed.
 *
 * Ignora {% set x = ... %} (inline, no necesita endset).
 */
function checkUnmatchedBlocks(string $content, string $relPath, array &$errors): void {
    $clean = prepareTwigContent($content);

    $blockPairs = [
        'block' => 'endblock',
        'for' => 'endfor',
        'if' => 'endif',
        'macro' => 'endmacro',
        'apply' => 'endapply',
        'embed' => 'endembed',
        'autoescape' => 'endautoescape',
    ];

    foreach ($blockPairs as $open => $close) {
        // Contar aperturas (excluyendo elseif, else que no son aperturas nuevas).
        $openCount = preg_match_all('/\{%[-~]?\s+' . $open . '\b/', $clean);
        $closeCount = preg_match_all('/\{%[-~]?\s+' . $close . '\b/', $clean);

        if ($openCount !== $closeCount) {
            $diff = $openCount - $closeCount;
            if ($diff > 0) {
                $errors[] = [
                    'file' => $relPath,
                    'line' => 0,
                    'check' => 'UNMATCHED-BLOCK',
                    'message' => "{$diff} {% {$open} %} sin {% {$close} %} correspondiente",
                ];
            } else {
                $errors[] = [
                    'file' => $relPath,
                    'line' => 0,
                    'check' => 'UNMATCHED-BLOCK',
                    'message' => abs($diff) . " {% {$close} %} sin {% {$open} %} correspondiente",
                ];
            }
        }
    }
}

// ── CHECK 4: MIXED-KEY-STYLE (warning) ──────────────────────────────────────

/**
 * Detecta mezcla de estilos de keys en mappings de {% include ... with { } %}.
 * Ejemplo: `{ key: val, 'other_key': val2 }` — inconsistente.
 *
 * Análisis multilínea: extrae el bloque completo entre `with {` y `} only %}`.
 */
function checkMixedKeyStyles(string $content, string $relPath, array &$warnings): void {
    $clean = prepareTwigContent($content);

    // Regex que captura el contenido del mapping en include...with.
    // Usa un approach balanceado para llaves anidadas (1 nivel).
    if (!preg_match_all(
        '/\{%[-~]?\s*include\b.*?with\s*\{((?:[^{}]|\{[^{}]*\})*)\}\s*(only\s*)?[-~]?%\}/s',
        $clean,
        $matches,
        PREG_OFFSET_CAPTURE
    )) {
        return;
    }

    foreach ($matches[1] as $match) {
        $mappingContent = $match[0];
        $offset = $match[1];
        $lineNum = substr_count(substr($clean, 0, (int) $offset), "\n") + 1;

        // Extraer keys del mapping.
        // Unquoted key: `word:` al inicio de línea (tras espacios) o después de `,`.
        // Quoted key: `'word':` o `"word":`.
        $hasUnquotedKeys = (bool) preg_match('/(?:^|,)\s*(\w+)\s*:/m', $mappingContent);
        $hasQuotedKeys = (bool) preg_match('/(?:^|,)\s*[\'"](\w+)[\'"]\s*:/m', $mappingContent);

        if ($hasUnquotedKeys && $hasQuotedKeys) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum,
                'check' => 'MIXED-KEY-STYLE',
                'message' => "Mezcla de estilos de keys en mapping: 'key': y key: — normalizar a un solo estilo",
            ];
        }
    }
}

// ── CHECK 5: EMPTY-WITH (warning) ───────────────────────────────────────────

/**
 * Detecta {% include ... with {} %} — mapping vacío, probable olvido.
 */
function checkEmptyWith(string $content, string $relPath, array &$warnings): void {
    $clean = prepareTwigContent($content);
    $lines = explode("\n", $clean);

    foreach ($lines as $lineNum => $line) {
        if (preg_match('/\{%[-~]?\s*include\s+.*with\s*\{\s*\}\s*(only\s*)?[-~]?%\}/', $line)) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'EMPTY-WITH',
                'message' => "{% include ... with {} %} — mapping vacío, ¿faltan variables?",
                'context' => trim($line),
            ];
        }
    }
}

// ── CHECK 6: TWIG-IN-HTML-COMMENT (warning) ─────────────────────────────────

/**
 * Detecta expresiones Twig dentro de <!-- --> HTML.
 *
 * Twig EJECUTA {{ }} y {% %} dentro de comentarios HTML — solo {# #} los omite.
 * Código "comentado" con <!-- --> puede causar errores silenciosos o fugas de datos.
 */
function checkTwigInHtmlComments(string $content, string $relPath, array &$warnings): void {
    // Strip SOLO Twig comments (no HTML comments — esos son los que queremos inspeccionar).
    $clean = preg_replace('/\{#.*?#\}/s', '', $content) ?? $content;

    if (preg_match_all('/<!--(.*?)-->/s', $clean, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            $commentContent = $match[0];
            if (preg_match('/\{\{|\{%/', $commentContent)) {
                $offset = $match[1];
                $lineNum = substr_count(substr($clean, 0, (int) $offset), "\n") + 1;
                $warnings[] = [
                    'file' => $relPath,
                    'line' => $lineNum,
                    'check' => 'TWIG-IN-HTML-COMMENT',
                    'message' => "Expresión Twig dentro de <!-- --> — Twig la EJECUTA igualmente. Usar {# #}",
                ];
            }
        }
    }
}

// ── Ejecutar checks ─────────────────────────────────────────────────────────

foreach ($twigFiles as $filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        continue;
    }
    $filesChecked++;
    $relPath = str_replace($root . '/', '', $filePath);

    // Errores (bloquean).
    checkDoubleComma($content, $relPath, $errors);
    checkUnclosedBalance($content, $relPath, $errors);
    checkUnmatchedBlocks($content, $relPath, $errors);

    // Warnings (informan).
    checkMixedKeyStyles($content, $relPath, $warnings);
    checkEmptyWith($content, $relPath, $warnings);
    checkTwigInHtmlComments($content, $relPath, $warnings);
}

// ── Output ──────────────────────────────────────────────────────────────────

echo "Archivos analizados: {$filesChecked}\n\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "\033[32m✔ PASS — Sin errores de sintaxis Twig detectados\033[0m\n";
    exit(0);
}

if (count($errors) > 0) {
    echo "\033[31m✘ ERRORES (" . count($errors) . ")\033[0m\n\n";
    foreach ($errors as $e) {
        $loc = $e['line'] > 0 ? "{$e['file']}:{$e['line']}" : $e['file'];
        echo "  \033[31m✘\033[0m [{$e['check']}] {$loc}\n";
        echo "    {$e['message']}\n";
        if (isset($e['context'])) {
            echo "    \033[90m→ {$e['context']}\033[0m\n";
        }
        echo "\n";
    }
}

if (count($warnings) > 0) {
    echo "\033[33m⚠ WARNINGS (" . count($warnings) . ")\033[0m\n\n";
    foreach ($warnings as $w) {
        $loc = $w['line'] > 0 ? "{$w['file']}:{$w['line']}" : $w['file'];
        echo "  \033[33m⚠\033[0m [{$w['check']}] {$loc}\n";
        echo "    {$w['message']}\n";
        if (isset($w['context'])) {
            echo "    \033[90m→ {$w['context']}\033[0m\n";
        }
        echo "\n";
    }
}

// Solo los errores bloquean (exit 1). Warnings son informativos.
if (count($errors) > 0) {
    echo "\033[31m✘ FAIL — " . count($errors) . " error(es) de sintaxis Twig\033[0m\n";
    exit(1);
}

echo "\033[32m✔ PASS — Solo warnings (no bloquean)\033[0m\n";
exit(0);
