<?php

/**
 * @file
 * Traduce todas las páginas de page_content a los idiomas configurados.
 *
 * Usa el AI Provider framework de Drupal directamente (sin orquestador)
 * para traducir canvas_data, rendered_html, content_data, title y meta.
 *
 * Usage:
 *   lando drush php:script scripts/maintenance/translate-all-pages.php
 *   lando drush php:script scripts/maintenance/translate-all-pages.php -- --lang=pt-br
 *   lando drush php:script scripts/maintenance/translate-all-pages.php -- --pages=57,58,59
 *   lando drush php:script scripts/maintenance/translate-all-pages.php -- --resync
 *   lando drush php:script scripts/maintenance/translate-all-pages.php -- --dry-run
 */

declare(strict_types=1);

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;

// Parse arguments.
$args = $extra ?? $argv ?? [];
$dry_run = in_array('--dry-run', $args, TRUE);
$resync = in_array('--resync', $args, TRUE);

$target_lang = NULL;
$page_ids = NULL;

foreach ($args as $arg) {
  if (str_starts_with((string) $arg, '--lang=')) {
    $target_lang = substr((string) $arg, 7);
  }
  if (str_starts_with((string) $arg, '--pages=')) {
    $page_ids = array_map('intval', explode(',', substr((string) $arg, 8)));
  }
}

// Skip template/demo pages (no user-facing content).
$skip_ids = [1, 6, 17, 42, 44, 45, 46, 47];

$lang_names = [
  'es' => 'español',
  'en' => 'inglés',
  'pt-br' => 'portugués brasileño',
  'pt' => 'portugués',
  'fr' => 'francés',
  'de' => 'alemán',
  'ca' => 'catalán',
];

echo "=============================================================\n";
echo "  JARABA IMPACT PLATFORM — Page Translation (AI-powered)\n";
echo "  Mode: " . ($dry_run ? 'DRY RUN' : 'LIVE') . "\n";
echo "  Target: " . ($target_lang ?: 'all missing languages') . "\n";
echo "  Resync: " . ($resync ? 'YES (re-translate existing)' : 'NO (only missing)') . "\n";
echo "=============================================================\n\n";

// ─────────────────────────────────────────────────────────
// AI Provider setup — direct API call, no orchestrator.
// ─────────────────────────────────────────────────────────
$provider_manager = \Drupal::service('ai.provider');
$provider = $provider_manager->createInstance('anthropic');
$model_id = 'claude-haiku-4-5-20251001'; // Fast tier for translations.

echo "AI Provider: anthropic / $model_id\n";

$languageManager = \Drupal::languageManager();
$configuredLangs = array_keys($languageManager->getLanguages());
echo "Configured languages: " . implode(', ', $configuredLangs) . "\n\n";

// ─────────────────────────────────────────────────────────
// Helper: translate text via AI Provider directly.
// ─────────────────────────────────────────────────────────
function ai_translate(string $text, string $sourceLang, string $targetLang, $provider, string $model_id, array $lang_names): string {
  if (empty(trim($text)) || $sourceLang === $targetLang) {
    return $text;
  }

  $srcName = $lang_names[$sourceLang] ?? $sourceLang;
  $tgtName = $lang_names[$targetLang] ?? $targetLang;

  $systemPrompt = <<<PROMPT
Eres un traductor profesional especializado en contenido web corporativo.
Traduce de {$srcName} a {$tgtName}.
Reglas:
1. Preserva EXACTAMENTE todas las etiquetas HTML, atributos, clases CSS y URLs.
2. No traduzcas nombres propios, marcas, URLs, direcciones de email ni códigos.
3. Adapta expresiones idiomáticas al idioma destino.
4. Mantén longitud similar (±20%).
5. Tono profesional pero accesible.
6. Responde ÚNICAMENTE con el texto traducido, sin explicaciones ni comentarios.
PROMPT;

  $chatInput = new ChatInput([
    new ChatMessage('system', $systemPrompt),
    new ChatMessage('user', $text),
  ]);

  $response = $provider->chat($chatInput, $model_id, [
    'temperature' => 0.3,
  ]);

  $result = trim($response->getNormalized()->getText());

  // Clean AI artifacts: markdown code fences, explanatory text.
  $result = preg_replace('/^```(?:html|json)?\s*\n?/i', '', $result);
  $result = preg_replace('/\n?```\s*$/i', '', $result);
  // Remove leading # (markdown heading artifacts).
  if (str_starts_with($result, '# ') && !str_contains($text, '# ')) {
    $result = substr($result, 2);
  }
  // Remove \n in single-line fields.
  if (!str_contains($text, "\n") && str_contains($result, "\n")) {
    $result = str_replace(["\n\n", "\n"], [' ', ' '], $result);
  }

  return trim($result);
}

// ─────────────────────────────────────────────────────────
// Helper: translate HTML canvas content preserving structure.
// ─────────────────────────────────────────────────────────
function translate_canvas_html(string $html, string $sourceLang, string $targetLang, $provider, string $model_id, array $lang_names): string {
  if (empty(trim($html))) {
    return $html;
  }

  // For large HTML (>4000 chars), split into sections to avoid token limits.
  if (mb_strlen($html) > 4000) {
    // Split by top-level sections.
    $sections = preg_split('/(<section[^>]*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (count($sections) > 2) {
      $result = '';
      $current = '';
      foreach ($sections as $i => $section) {
        $current .= $section;
        // When we hit a closing section or end, translate the chunk.
        if (mb_strlen($current) > 3000 || $i === count($sections) - 1) {
          if (!empty(trim(strip_tags($current)))) {
            $result .= ai_translate($current, $sourceLang, $targetLang, $provider, $model_id, $lang_names);
          }
          else {
            $result .= $current;
          }
          $current = '';
        }
      }
      return $result;
    }
  }

  // Small enough to translate in one call.
  return ai_translate($html, $sourceLang, $targetLang, $provider, $model_id, $lang_names);
}

// ─────────────────────────────────────────────────────────
// Helper: translate canvas_data JSON.
// ─────────────────────────────────────────────────────────
function translate_canvas_data(string $canvasJson, string $sourceLang, string $targetLang, $provider, string $model_id, array $lang_names): string {
  $data = json_decode($canvasJson, TRUE);
  if (!is_array($data)) {
    return $canvasJson;
  }

  foreach (['html', 'gjs-html'] as $key) {
    if (!empty($data[$key]) && is_string($data[$key])) {
      $data[$key] = translate_canvas_html($data[$key], $sourceLang, $targetLang, $provider, $model_id, $lang_names);
    }
  }

  return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// ─────────────────────────────────────────────────────────
// Load pages.
// ─────────────────────────────────────────────────────────
$storage = \Drupal::entityTypeManager()->getStorage('page_content');

$query = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('langcode', 'es')
  ->condition('canvas_data', '', '<>')
  ->sort('id');

if ($page_ids) {
  $query->condition('id', $page_ids, 'IN');
}

$ids = $query->execute();

if (empty($ids)) {
  echo "No pages found.\n";
  return;
}

echo "Found " . count($ids) . " ES pages with canvas_data.\n\n";

$translated_count = 0;
$skipped = 0;
$errors = 0;

foreach ($ids as $id) {
  if (!$page_ids && in_array((int) $id, $skip_ids, TRUE)) {
    echo "  [SKIP] Page $id — template/demo page\n";
    $skipped++;
    continue;
  }

  $page = $storage->load($id);
  if (!$page) {
    echo "  [ERROR] Page $id — failed to load\n";
    $errors++;
    continue;
  }

  $original = $page->getUntranslated();
  $title = $original->label() ?? '(sin título)';

  // Determine which languages need translation.
  $langsNeeded = [];
  foreach ($configuredLangs as $lang) {
    if ($lang === 'es') {
      continue;
    }
    if ($target_lang && $lang !== $target_lang) {
      continue;
    }
    if (!$resync && $original->hasTranslation($lang)) {
      continue;
    }
    $langsNeeded[] = $lang;
  }

  if (empty($langsNeeded)) {
    echo "  [OK] Page $id — \"$title\" — all translations exist\n";
    $skipped++;
    continue;
  }

  foreach ($langsNeeded as $lang) {
    $action = $original->hasTranslation($lang) ? 'RESYNC' : 'NEW';
    echo "  [$action] Page $id → $lang — \"$title\" ... ";

    if ($dry_run) {
      echo "(dry run)\n";
      $translated_count++;
      continue;
    }

    try {
      // Create or get translation.
      if ($original->hasTranslation($lang)) {
        $translation = $original->getTranslation($lang);
      }
      else {
        $translation = $original->addTranslation($lang);
        if ($original->hasField('status')) {
          $translation->set('status', $original->get('status')->value);
        }
        if ($original->hasField('template_id')) {
          $translation->set('template_id', $original->get('template_id')->value);
        }
      }

      // Column length limits.
      $field_limits = ['title' => 255, 'meta_title' => 70, 'meta_description' => 60000, 'path_alias' => 255];

      // 1. Translate title.
      $esTitle = $original->label() ?? '';
      if (!empty($esTitle)) {
        $translatedTitle = mb_substr(ai_translate($esTitle, 'es', $lang, $provider, $model_id, $lang_names), 0, $field_limits['title']);
        $translation->set('title', $translatedTitle);
        echo "T";
      }

      // 2. Translate meta_title + meta_description.
      foreach (['meta_title', 'meta_description'] as $metaField) {
        if ($original->hasField($metaField)) {
          $metaValue = $original->get($metaField)->value ?? '';
          if (!empty($metaValue)) {
            $translatedMeta = mb_substr(ai_translate($metaValue, 'es', $lang, $provider, $model_id, $lang_names), 0, $field_limits[$metaField] ?? 255);
            $translation->set($metaField, $translatedMeta);
            echo "M";
          }
        }
      }

      // 3. Translate path_alias.
      if ($original->hasField('path_alias')) {
        $alias = $original->get('path_alias')->value ?? '';
        if (!empty($alias)) {
          $aliasText = str_replace(['/', '-'], [' ', ' '], trim($alias, '/'));
          if (!empty($aliasText)) {
            $translatedAlias = ai_translate($aliasText, 'es', $lang, $provider, $model_id, $lang_names);
            // SAFEGUARD-ALIAS-001: Slugify, enforce single leading slash, max 80 chars.
            $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($translatedAlias));
            $slug = trim($slug, '-');
            // Detect AI hallucinations (slug too long or contains sentence patterns).
            if (strlen($slug) > 80 || str_contains($slug, 'translate') || str_contains($slug, 'ready-to')) {
              // Fallback: slugify from the translated title.
              $titleForSlug = $translation->get('title')->value ?? $aliasText;
              $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($titleForSlug));
              $slug = trim($slug, '-');
              echo "!";
            }
            $translatedAlias = '/' . mb_substr($slug, 0, 80);
            $translation->set('path_alias', $translatedAlias);
            echo "A";
          }
        }
      }

      // 4. Translate canvas_data (the big one).
      if ($original->hasField('canvas_data')) {
        $canvasData = $original->get('canvas_data')->value ?? '{}';
        if ($canvasData !== '{}' && !empty($canvasData) && strlen($canvasData) > 100) {
          $translatedCanvas = translate_canvas_data($canvasData, 'es', $lang, $provider, $model_id, $lang_names);
          // Validate JSON before saving.
          if (json_decode($translatedCanvas) !== NULL) {
            $translation->set('canvas_data', $translatedCanvas);
            echo "C";
          }
          else {
            echo "!C(invalid JSON)";
          }
        }
      }

      // 5. Translate rendered_html.
      if ($original->hasField('rendered_html')) {
        $html = $original->get('rendered_html')->value ?? '';
        if (!empty($html) && strlen($html) > 50) {
          $translatedHtml = translate_canvas_html($html, 'es', $lang, $provider, $model_id, $lang_names);
          $translation->set('rendered_html', $translatedHtml);
          echo "H";
        }
      }

      // 6. Translate content_data.
      if ($original->hasField('content_data')) {
        $contentData = $original->get('content_data')->value ?? '{}';
        if ($contentData !== '{}' && !empty($contentData) && strlen($contentData) > 50) {
          // content_data has structured JSON — translate values.
          $cdObj = json_decode($contentData, TRUE);
          if (is_array($cdObj)) {
            $cdObj = translate_content_data_recursive($cdObj, 'es', $lang, $provider, $model_id, $lang_names);
            $translatedCd = json_encode($cdObj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (json_decode($translatedCd) !== NULL) {
              $translation->set('content_data', $translatedCd);
              echo "D";
            }
          }
        }
      }

      // SAFEGUARD: Verify translation canvas hasn't been corrupted (cross-page swap).
      // The translated canvas should be roughly the same size as the original (±50%).
      $esCanvas = $original->get('canvas_data')->value ?? '{}';
      $trCanvas = $translation->get('canvas_data')->value ?? '{}';
      $esLen = strlen($esCanvas);
      $trLen = strlen($trCanvas);
      if ($esLen > 500 && ($trLen < $esLen * 0.4 || $trLen > $esLen * 1.6)) {
        echo " ABORT (canvas size mismatch: ES=$esLen, $lang=$trLen — possible corruption)\n";
        $errors++;
        $storage->resetCache([$id]);
        continue;
      }

      // Save.
      $page->setSyncing(TRUE);
      $page->save();
      $page->setSyncing(FALSE);

      echo " OK\n";
      $translated_count++;

      // CRITICAL: Reload entity after save to avoid cross-language contamination.
      // Without this, the next language iteration reuses a stale entity object
      // that may carry over modifications from the previous translation save.
      $storage->resetCache([$id]);
      $page = $storage->load($id);
      $original = $page ? $page->getUntranslated() : NULL;
      if (!$page || !$original) {
        echo "  [ERROR] Page $id — failed to reload after save\n";
        $errors++;
        break;
      }

    }
    catch (\Throwable $e) {
      echo " ERROR: " . $e->getMessage() . "\n";
      $errors++;
      // Reset entity to avoid partial saves in next iteration.
      $storage->resetCache([$id]);
    }
  }
}

echo "\n=============================================================\n";
echo "  Results: $translated_count translated, $skipped skipped, $errors errors\n";
echo "=============================================================\n";

if ($translated_count > 0 && !$dry_run) {
  echo "\nRun `lando drush cr` to clear caches.\n";
}

// ─────────────────────────────────────────────────────────
// Helper: recursively translate content_data JSON values.
// ─────────────────────────────────────────────────────────
function translate_content_data_recursive(array $data, string $sourceLang, string $targetLang, $provider, string $model_id, array $lang_names): array {
  $skip_keys = ['id', 'uuid', 'type', 'template_id', 'image', 'icon', 'color',
    'bg_color', 'url', 'href', 'src', 'class', 'style', 'css'];

  foreach ($data as $key => &$value) {
    if (is_array($value)) {
      $value = translate_content_data_recursive($value, $sourceLang, $targetLang, $provider, $model_id, $lang_names);
      continue;
    }

    if (!is_string($value) || mb_strlen($value) < 4) {
      continue;
    }

    // Skip non-translatable keys.
    $keyLower = strtolower((string) $key);
    if (in_array($keyLower, $skip_keys, TRUE)
      || str_ends_with($keyLower, '_id')
      || str_ends_with($keyLower, '_url')
      || str_ends_with($keyLower, '_image')
      || str_ends_with($keyLower, '_icon')
    ) {
      continue;
    }

    // Skip URLs, hex colors, JSON.
    if (preg_match('/^(#[0-9a-f]{3,8}|https?:\/\/|\{|\}|\[|\]|\/)/i', $value)) {
      continue;
    }

    try {
      $value = ai_translate($value, $sourceLang, $targetLang, $provider, $model_id, $lang_names);
    }
    catch (\Throwable $e) {
      // Keep original on failure.
    }
  }

  return $data;
}
