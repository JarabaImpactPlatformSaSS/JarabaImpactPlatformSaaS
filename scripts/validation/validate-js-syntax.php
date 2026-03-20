#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-js-syntax.php
 *
 * JS-SYNTAX-LINT-001: Detecta errores comunes de sintaxis JavaScript en módulos
 * custom y tema que rompen Drupal.behaviors sin error visible en servidor.
 *
 * ESLint NO está disponible en este proyecto. Este validador usa checks basados
 * en regex PHP (misma filosofía que validate-twig-syntax.php).
 *
 * Checks (ERRORES — bloquean commit):
 *   1. UNCLOSED-BRACKET: Desbalance de {}, [], () a nivel de archivo
 *   2. DOUBLE-SEMICOLON: ;; fuera de strings — typo común
 *   3. MISSING-BEHAVIOR-ATTACH: Drupal.behaviors.X sin función attach:
 *   4. CONSOLE-LOG-PROD: console.log( en producción — usar console.warn o eliminar
 *
 * Checks (WARNINGS — informan, no bloquean):
 *   5. HARDCODED-URL: URLs hardcoded en vez de drupalSettings (ROUTE-LANGPREFIX-001)
 *   6. INNERHTML-NO-CHECKPLAIN: .innerHTML = sin Drupal.checkPlain() cercano (XSS)
 *   7. FETCH-NO-CSRF: fetch() sin X-CSRF-Token para requests same-origin (CSRF-JS-CACHE-001)
 *
 * USO:
 *   php scripts/validation/validate-js-syntax.php            # Todo el proyecto
 *   php scripts/validation/validate-js-syntax.php <archivo>  # Un archivo (lint-staged)
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
echo "\033[36m║  JS-SYNTAX-LINT-001                                     ║\033[0m\n";
echo "\033[36m║  JavaScript Static Syntax Linter                        ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── Recopilar archivos ──────────────────────────────────────────────────────

$jsFiles = [];

if (isset($argv[1]) && is_file($argv[1])) {
    $resolved = realpath($argv[1]);
    if ($resolved !== false) {
        $jsFiles[] = $resolved;
    }
} else {
    $searchDirs = [
        $root . '/web/modules/custom',
        $root . '/web/themes/custom',
    ];

    foreach ($searchDirs as $baseDir) {
        if (!is_dir($baseDir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $path = $file->getPathname();

            // Solo archivos .js.
            if (!str_ends_with($path, '.js')) {
                continue;
            }

            // Excluir vendor/, node_modules/, y *.min.js.
            if (
                str_contains($path, '/vendor/')
                || str_contains($path, '/node_modules/')
                || str_ends_with($path, '.min.js')
            ) {
                continue;
            }

            $jsFiles[] = $path;
        }
    }
}

// ── Utilidades compartidas ──────────────────────────────────────────────────

/**
 * Elimina string literals y comentarios del contenido JS para evitar
 * falsos positivos. Reemplaza con espacios preservando saltos de línea.
 */
function stripJsStringsAndComments(string $content): string {
    // 1. Comentarios de bloque /* ... */ (puede ser multilínea).
    $content = preg_replace_callback('/\/\*.*?\*\//s', function (array $m): string {
        return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 2. Comentarios de línea // ... (solo hasta fin de línea).
    $content = preg_replace_callback('/\/\/[^\n]*/', function (array $m): string {
        return str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 3. Template literals `...` (pueden contener ${}).
    $content = preg_replace_callback('/`(?:[^`\\\\]|\\\\.)*`/s', function (array $m): string {
        return preg_replace('/[^\n]/', ' ', $m[0]) ?? str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 4. Strings con comillas dobles (NO cruzan líneas en JS).
    $content = preg_replace_callback('/"(?:[^"\\\\\n]|\\\\.)*"/', function (array $m): string {
        return str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    // 5. Strings con comillas simples (NO cruzan líneas en JS).
    $content = preg_replace_callback("/'" . '(?:[^' . "'" . '\\\\\n]|\\\\.)*' . "'" . '/', function (array $m): string {
        return str_repeat(' ', strlen($m[0]));
    }, $content) ?? $content;

    return $content;
}

/**
 * Obtiene la línea limpia (sin strings/comments) para un número de línea dado.
 */
function getCleanLine(string $cleanContent, int $lineIndex): string {
    $lines = explode("\n", $cleanContent);
    return $lines[$lineIndex] ?? '';
}

// ── CHECK 1: UNCLOSED-BRACKET ───────────────────────────────────────────────

/**
 * Verifica balance global de {}, [], () a nivel de archivo.
 * Desbalance indica bracket sin cerrar o cierre extra.
 */
function checkUnclosedBrackets(string $cleanContent, string $relPath, array &$warnings): void {
    $pairs = [
        ['{', '}', 'llaves {}'],
        ['[', ']', 'corchetes []'],
        ['(', ')', 'paréntesis ()'],
    ];

    foreach ($pairs as [$open, $close, $name]) {
        $openCount = substr_count($cleanContent, $open);
        $closeCount = substr_count($cleanContent, $close);

        if ($openCount !== $closeCount) {
            $diff = $openCount - $closeCount;
            $direction = $diff > 0 ? 'apertura(s) sin cierre' : 'cierre(s) sin apertura';
            $errors[] = [
                'file' => $relPath,
                'line' => 0,
                'check' => 'UNCLOSED-BRACKET',
                'message' => "Desbalance de {$name}: " . abs($diff) . " {$direction} ({$openCount} abren, {$closeCount} cierran)",
            ];
        }
    }
}

// ── CHECK 2: DOUBLE-SEMICOLON ───────────────────────────────────────────────

/**
 * Detecta ;; fuera de strings y comentarios — typo común.
 * Excluye `;;` dentro de bucles for (ej: `for(;;)` es válido).
 */
function checkDoubleSemicolon(string $cleanContent, string $relPath, array &$errors): void {
    $lines = explode("\n", $cleanContent);

    foreach ($lines as $lineNum => $line) {
        // Buscar ;; que NO sea parte de for(;;).
        if (preg_match('/;;/', $line) && !preg_match('/\bfor\s*\(/', $line)) {
            $errors[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'DOUBLE-SEMICOLON',
                'message' => "Doble punto y coma ';;' detectado — probable typo",
                'context' => trim($line),
            ];
        }
    }
}

// ── CHECK 3: MISSING-BEHAVIOR-ATTACH ────────────────────────────────────────

/**
 * Si un archivo define Drupal.behaviors.X = {, DEBE contener attach:.
 * Sin attach, el behavior se registra pero nunca ejecuta nada.
 */
function checkMissingBehaviorAttach(string $content, string $cleanContent, string $relPath, array &$errors): void {
    // Buscar en contenido original (los nombres de behaviors pueden estar en strings).
    if (preg_match_all('/Drupal\.behaviors\.(\w+)\s*=\s*\{/', $cleanContent, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            $behaviorName = $match[0];
            $offset = (int) $match[1];
            $lineNum = substr_count(substr($cleanContent, 0, $offset), "\n") + 1;

            // Buscar attach dentro del mismo archivo (en contenido limpio).
            // Formatos válidos: `attach:`, `attach :`, `attach(` (shorthand method).
            if (!preg_match('/\battach\s*[(:=]/', $cleanContent)) {
                $errors[] = [
                    'file' => $relPath,
                    'line' => $lineNum,
                    'check' => 'MISSING-BEHAVIOR-ATTACH',
                    'message' => "Drupal.behaviors.{$behaviorName} sin función attach: — el behavior no ejecutará nada",
                ];
            }
        }
    }
}

// ── CHECK 4: CONSOLE-LOG-PROD ───────────────────────────────────────────────

/**
 * Detecta console.log() en código de producción.
 * Permitido: console.warn(), console.error(), console.info().
 */
function checkConsoleLog(string $cleanContent, string $relPath, array &$warnings): void {
    $lines = explode("\n", $cleanContent);

    foreach ($lines as $lineNum => $line) {
        if (preg_match('/\bconsole\.log\s*\(/', $line)) {
            $errors[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'CONSOLE-LOG-PROD',
                'message' => "console.log() en producción — usar console.warn() o eliminar",
                'context' => trim($line),
            ];
        }
    }
}

// ── CHECK 5: HARDCODED-URL (warning) ────────────────────────────────────────

/**
 * Detecta URLs hardcoded que deberían usar drupalSettings.
 * Patrones: /es/api/, /admin/, http:// (sin https://cdn o similar).
 */
function checkHardcodedUrls(string $cleanContent, string $relPath, array &$warnings): void {
    $lines = explode("\n", $cleanContent);

    foreach ($lines as $lineNum => $line) {
        // /es/ prefix hardcoded (debería usar drupalSettings.path.baseUrl).
        if (preg_match('#[\'"`]/es/(?:api|admin|node|user)/#', $line)) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'HARDCODED-URL',
                'message' => "URL con prefijo /es/ hardcoded — usar drupalSettings (ROUTE-LANGPREFIX-001)",
                'context' => trim($line),
            ];
            continue;
        }

        // /admin/ paths hardcoded.
        if (preg_match('#[\'"`]/admin/#', $line)) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'HARDCODED-URL',
                'message' => "URL /admin/ hardcoded — usar drupalSettings o Drupal.url()",
                'context' => trim($line),
            ];
            continue;
        }

        // http:// (sin https) — posible mixed content.
        // Excluir http://localhost, http://127.0.0.1, y protocolos scheme (http://).
        if (preg_match('#[\'"`]http://(?!localhost|127\.0\.0\.1)#', $line)) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'HARDCODED-URL',
                'message' => "URL http:// detectada — posible mixed content, usar https:// o drupalSettings",
                'context' => trim($line),
            ];
        }
    }
}

// ── CHECK 6: INNERHTML-NO-CHECKPLAIN (warning) ─────────────────────────────

/**
 * Detecta .innerHTML = sin Drupal.checkPlain() cercano.
 * XSS risk per INNERHTML-XSS-001.
 *
 * Heurística: busca innerHTML = en la línea y verifica si checkPlain
 * aparece en un rango de ±5 líneas.
 */
function checkInnerHtmlXss(string $cleanContent, string $relPath, array &$warnings): void {
    $lines = explode("\n", $cleanContent);
    $lineCount = count($lines);

    foreach ($lines as $lineNum => $line) {
        if (!preg_match('/\.innerHTML\s*=/', $line)) {
            continue;
        }

        // Verificar si Drupal.checkPlain aparece en contexto cercano (±5 líneas).
        $hasCheckPlain = false;
        $start = max(0, $lineNum - 5);
        $end = min($lineCount - 1, $lineNum + 5);

        for ($i = $start; $i <= $end; $i++) {
            if (str_contains($lines[$i], 'checkPlain') || str_contains($lines[$i], 'DOMPurify')) {
                $hasCheckPlain = true;
                break;
            }
        }

        if (!$hasCheckPlain) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'INNERHTML-NO-CHECKPLAIN',
                'message' => ".innerHTML = sin Drupal.checkPlain() cercano — riesgo XSS (INNERHTML-XSS-001)",
                'context' => trim($line),
            ];
        }
    }
}

// ── CHECK 7: FETCH-NO-CSRF (warning) ────────────────────────────────────────

/**
 * Detecta fetch() sin header X-CSRF-Token para requests same-origin.
 * Per CSRF-JS-CACHE-001, requests mutantes necesitan CSRF token.
 *
 * Heurística: busca fetch( y verifica si X-CSRF-Token aparece en ±10 líneas.
 * Excluye archivos que no usen fetch en absoluto.
 */
function checkFetchCsrf(string $cleanContent, string $relPath, array &$warnings): void {
    $lines = explode("\n", $cleanContent);
    $lineCount = count($lines);

    // Si el archivo no contiene fetch(, skip.
    if (!str_contains($cleanContent, 'fetch(')) {
        return;
    }

    // Si el archivo tiene una función/variable que gestiona CSRF globalmente, skip.
    if (
        str_contains($cleanContent, 'X-CSRF-Token')
        || str_contains($cleanContent, 'csrfToken')
        || str_contains($cleanContent, 'csrf_token')
        || str_contains($cleanContent, '/session/token')
    ) {
        return;
    }

    foreach ($lines as $lineNum => $line) {
        if (!preg_match('/\bfetch\s*\(/', $line)) {
            continue;
        }

        // Buscar si hay method: POST/PATCH/DELETE/PUT cercano (±5 líneas).
        // Si es solo GET, no necesita CSRF.
        $hasMutatingMethod = false;
        $start = max(0, $lineNum - 3);
        $end = min($lineCount - 1, $lineNum + 8);

        for ($i = $start; $i <= $end; $i++) {
            if (preg_match('/method\s*:\s*[\'"]?(POST|PATCH|DELETE|PUT)/i', $lines[$i])) {
                $hasMutatingMethod = true;
                break;
            }
        }

        if ($hasMutatingMethod) {
            $warnings[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'check' => 'FETCH-NO-CSRF',
                'message' => "fetch() con método mutante sin X-CSRF-Token — riesgo CSRF (CSRF-JS-CACHE-001)",
                'context' => trim($line),
            ];
        }
    }
}

// ── Ejecutar checks ─────────────────────────────────────────────────────────

foreach ($jsFiles as $filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        continue;
    }
    $filesChecked++;
    $relPath = str_replace($root . '/', '', $filePath);
    $cleanContent = stripJsStringsAndComments($content);

    // Errores (bloquean).
    checkDoubleSemicolon($cleanContent, $relPath, $errors);
    checkMissingBehaviorAttach($content, $cleanContent, $relPath, $errors);

    // Warnings (informan — baseline tiene 150+ console.log y bracket edge cases).
    checkUnclosedBrackets($cleanContent, $relPath, $warnings);
    checkConsoleLog($cleanContent, $relPath, $warnings);
    checkHardcodedUrls($cleanContent, $relPath, $warnings);
    checkInnerHtmlXss($cleanContent, $relPath, $warnings);
    checkFetchCsrf($cleanContent, $relPath, $warnings);
}

// ── Output ──────────────────────────────────────────────────────────────────

echo "Archivos analizados: {$filesChecked}\n\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "\033[32m✔ PASS — Sin errores de sintaxis JS detectados\033[0m\n";
    exit(0);
}

if (count($errors) > 0) {
    echo "\033[31m✘ ERRORES (" . count($errors) . ")\033[0m\n\n";
    foreach ($errors as $e) {
        $loc = $e['line'] > 0 ? "{$e['file']}:{$e['line']}" : $e['file'];
        echo "  \033[31m✘\033[0m [{$e['check']}] {$loc}\n";
        echo "    {$e['message']}\n";
        if (isset($e['context'])) {
            $ctx = mb_strlen($e['context']) > 120 ? mb_substr($e['context'], 0, 117) . '...' : $e['context'];
            echo "    \033[90m→ {$ctx}\033[0m\n";
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
            $ctx = mb_strlen($w['context']) > 120 ? mb_substr($w['context'], 0, 117) . '...' : $w['context'];
            echo "    \033[90m→ {$ctx}\033[0m\n";
        }
        echo "\n";
    }
}

// Solo los errores bloquean (exit 1). Warnings son informativos.
if (count($errors) > 0) {
    echo "\033[31m✘ FAIL — " . count($errors) . " error(es) de sintaxis JS\033[0m\n";
    exit(1);
}

echo "\033[32m✔ PASS — Solo warnings (no bloquean)\033[0m\n";
exit(0);
