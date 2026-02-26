#!/usr/bin/env php
<?php
/**
 * @file translate-metasite-pages.php
 *
 * Script para crear traducciones EN (y PT-BR para jarabaimpact.com)
 * de todas las páginas de los 3 meta-sitios.
 *
 * PREREQUISITOS (ejecutar antes):
 *   lando drush en language content_translation locale
 *   lando drush language:add en
 *   lando drush language:add pt-br
 *   lando drush cr
 *
 * USO:
 *   lando drush scr scripts/i18n/translate-metasite-pages.php
 *
 * El script usa AITranslationService para generar traducciones con brand voice.
 * Si el servicio no está disponible, crea traducciones vacías para rellenar manualmente.
 */

use Drupal\Core\Entity\ContentEntityInterface;

// =========================================================================
// CONFIGURACIÓN
// =========================================================================

/** @var array<int, array{name: string, languages: string[]}> */
$tenants = [
  5 => ['name' => 'pepejaraba.com', 'languages' => ['en']],
  6 => ['name' => 'jarabaimpact.com', 'languages' => ['en', 'pt-br']],
  7 => ['name' => 'plataformadeecosistemas.es', 'languages' => ['en']],
];

// Campos de texto que se traducen con IA.
$translatableTextFields = [
  'title',
  'meta_title',
  'meta_description',
];

// =========================================================================
// SERVICES
// =========================================================================

$entityTypeManager = \Drupal::entityTypeManager();
$languageManager = \Drupal::languageManager();

// Verificar que los idiomas están configurados.
$configuredLangs = array_keys($languageManager->getLanguages());
$requiredLangs = ['en', 'pt-br'];
foreach ($requiredLangs as $lang) {
  if (!in_array($lang, $configuredLangs)) {
    echo "⚠️  Idioma '$lang' no configurado. Ejecuta: lando drush language:add $lang\n";
    echo "   Luego: lando drush cr\n";
    return;
  }
}
echo "✅ Idiomas configurados: " . implode(', ', $configuredLangs) . "\n\n";

// Intentar cargar servicios de traducción IA (opcionales).
$aiTranslation = NULL;
$translationManager = NULL;
if (\Drupal::hasService('jaraba_i18n.ai_translation')) {
  $aiTranslation = \Drupal::service('jaraba_i18n.ai_translation');
  echo "✅ AITranslationService disponible — traducciones con IA\n";
} else {
  echo "⚠️  AITranslationService no disponible — creando traducciones vacías\n";
}
if (\Drupal::hasService('jaraba_i18n.translation_manager')) {
  $translationManager = \Drupal::service('jaraba_i18n.translation_manager');
}

$storage = $entityTypeManager->getStorage('page_content');

// =========================================================================
// TRANSLATION LOOP
// =========================================================================

$totalCreated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($tenants as $tenantId => $config) {
  echo "\n" . str_repeat('─', 60) . "\n";
  echo "📦 Tenant $tenantId: {$config['name']}\n";
  echo str_repeat('─', 60) . "\n";

  // Cargar todas las páginas del tenant.
  $pages = $storage->loadByProperties(['tenant_id' => $tenantId]);

  if (empty($pages)) {
    echo "  ⚠️  Sin páginas — saltando\n";
    continue;
  }

  echo "  📄 " . count($pages) . " páginas encontradas\n";

  foreach ($pages as $page) {
    /** @var ContentEntityInterface $page */
    $pageTitle = $page->get('title')->value ?? 'Sin título';
    $pageId = $page->id();

    foreach ($config['languages'] as $targetLang) {
      echo "\n  ▸ Page #$pageId \"$pageTitle\" → $targetLang: ";

      // Verificar si ya existe la traducción.
      if ($page->hasTranslation($targetLang)) {
        echo "ya existe (skip)";
        $totalSkipped++;
        continue;
      }

      try {
        // Crear la traducción.
        $translation = $page->addTranslation($targetLang);

        // Copiar campos no traducibles del original.
        $translation->set('status', $page->get('status')->value);
        $translation->set('template_id', $page->get('template_id')->value);

        // Traducir campos de texto simples con IA.
        if ($aiTranslation) {
          $textsToTranslate = [];
          foreach ($translatableTextFields as $fieldName) {
            $value = $page->get($fieldName)->value ?? '';
            if (!empty(trim($value))) {
              $textsToTranslate[$fieldName] = $value;
            }
          }

          if (!empty($textsToTranslate)) {
            $translated = $aiTranslation->translateBatch(
              $textsToTranslate,
              'es',
              $targetLang
            );

            foreach ($translated as $fieldName => $translatedValue) {
              $translation->set($fieldName, $translatedValue);
            }
          }

          // Traducir path_alias: generar alias en idioma destino.
          $originalAlias = $page->get('path_alias')->value ?? '';
          if (!empty($originalAlias)) {
            // Para EN/PT: traducir el alias si es significativo.
            $aliasText = str_replace(['/', '-'], [' ', ' '], trim($originalAlias, '/'));
            if (!empty($aliasText)) {
              $translatedAlias = $aiTranslation->translate($aliasText, 'es', $targetLang);
              $translatedAlias = '/' . preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($translatedAlias));
              $translatedAlias = trim($translatedAlias, '-');
              $translatedAlias = '/' . $translatedAlias;
              $translation->set('path_alias', $translatedAlias);
            }
          }

          // Traducir canvas_data (JSON con HTML).
          $canvasData = $page->get('canvas_data')->value ?? '{}';
          if ($canvasData !== '{}' && !empty($canvasData)) {
            $translatedCanvas = translateCanvasData($canvasData, 'es', $targetLang, $aiTranslation);
            $translation->set('canvas_data', $translatedCanvas);
          }

          // Traducir rendered_html.
          $renderedHtml = $page->get('rendered_html')->value ?? '';
          if (!empty($renderedHtml)) {
            $translatedHtml = translateHtmlContent($renderedHtml, 'es', $targetLang, $aiTranslation);
            $translation->set('rendered_html', $translatedHtml);
          }

          // Traducir content_data (JSON con textos).
          $contentData = $page->get('content_data')->value ?? '{}';
          if ($contentData !== '{}' && !empty($contentData)) {
            $translatedContent = translateContentData($contentData, 'es', $targetLang, $aiTranslation);
            $translation->set('content_data', $translatedContent);
          }
        }

        $page->save();
        echo "✅ creada";
        $totalCreated++;
      }
      catch (\Throwable $e) {
        echo "❌ error: " . $e->getMessage();
        $totalErrors++;
      }
    }
  }
}

// =========================================================================
// RESUMEN
// =========================================================================

echo "\n\n" . str_repeat('═', 60) . "\n";
echo "📊 RESUMEN\n";
echo str_repeat('═', 60) . "\n";
echo "  ✅ Traducciones creadas: $totalCreated\n";
echo "  ⏭️  Existentes (skip):   $totalSkipped\n";
echo "  ❌ Errores:              $totalErrors\n";
echo str_repeat('═', 60) . "\n\n";

// =========================================================================
// HELPER FUNCTIONS
// =========================================================================

/**
 * Traduce canvas_data (JSON de GrapesJS con HTML embebido).
 *
 * Extrae el campo 'html' del JSON, traduce sus textos y reconstruye.
 */
function translateCanvasData(string $canvasJson, string $source, string $target, $aiTranslation): string {
  $data = json_decode($canvasJson, TRUE);
  if (!is_array($data)) {
    return $canvasJson;
  }

  // GrapesJS almacena HTML en data['html'] o data['gjs-html'].
  $htmlKeys = ['html', 'gjs-html'];
  foreach ($htmlKeys as $key) {
    if (!empty($data[$key]) && is_string($data[$key])) {
      $data[$key] = translateHtmlContent($data[$key], $source, $target, $aiTranslation);
    }
  }

  // También traducir componentes si están presentes.
  if (!empty($data['components']) && is_array($data['components'])) {
    $data['components'] = translateGrapesJsComponents($data['components'], $source, $target, $aiTranslation);
  }

  return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Traduce recursivamente componentes GrapesJS.
 *
 * Cada componente puede tener 'content' (texto) y 'components' (hijos).
 */
function translateGrapesJsComponents(array $components, string $source, string $target, $aiTranslation): array {
  foreach ($components as &$component) {
    // Traducir el contenido de texto del componente.
    if (!empty($component['content']) && is_string($component['content'])) {
      $text = strip_tags($component['content']);
      if (!empty(trim($text)) && mb_strlen($text) > 2) {
        try {
          $component['content'] = $aiTranslation->translate($component['content'], $source, $target);
        }
        catch (\Throwable $e) {
          // Mantener original si falla.
        }
      }
    }

    // Recursia: traducir componentes hijos.
    if (!empty($component['components']) && is_array($component['components'])) {
      $component['components'] = translateGrapesJsComponents($component['components'], $source, $target, $aiTranslation);
    }

    // Traducir atributos de texto (alt, title, placeholder).
    if (!empty($component['attributes']) && is_array($component['attributes'])) {
      foreach (['alt', 'title', 'placeholder', 'aria-label'] as $attr) {
        if (!empty($component['attributes'][$attr]) && is_string($component['attributes'][$attr])) {
          try {
            $component['attributes'][$attr] = $aiTranslation->translate(
              $component['attributes'][$attr], $source, $target
            );
          }
          catch (\Throwable $e) {
            // Mantener original.
          }
        }
      }
    }
  }

  return $components;
}

/**
 * Traduce contenido HTML extrayendo nodos de texto.
 *
 * Usa un enfoque batch: extrae todos los textos, los traduce en lote,
 * y reinserta en el HTML.
 */
function translateHtmlContent(string $html, string $source, string $target, $aiTranslation): string {
  if (empty(trim($html))) {
    return $html;
  }

  // Extraer bloques de texto significativos del HTML.
  // Buscamos contenido entre tags (no attrs, no tags).
  $texts = [];
  $index = 0;

  $translated = preg_replace_callback(
    '/>((?:(?!<).)+)</u',
    function ($matches) use (&$texts, &$index) {
      $text = trim($matches[1]);
      if (empty($text) || mb_strlen($text) < 3) {
        return $matches[0]; // Mantener textos muy cortos.
      }
      $placeholder = "___TRANSLATE_{$index}___";
      $texts[$placeholder] = $text;
      $index++;
      return ">{$placeholder}<";
    },
    $html
  );

  if (empty($texts)) {
    return $html;
  }

  // Traducir todos los textos en batch.
  try {
    $translatedTexts = $aiTranslation->translateBatch($texts, $source, $target);
  }
  catch (\Throwable $e) {
    return $html; // Fallback: mantener original.
  }

  // Reemplazar placeholders con traducciones.
  foreach ($translatedTexts as $placeholder => $translatedText) {
    $translated = str_replace($placeholder, $translatedText, $translated);
  }

  // Limpiar placeholders no traducidos.
  foreach ($texts as $placeholder => $originalText) {
    if (str_contains($translated, $placeholder)) {
      $translated = str_replace($placeholder, $originalText, $translated);
    }
  }

  return $translated;
}

/**
 * Traduce content_data (JSON con values de texto).
 *
 * Recorre recursivamente el array JSON y traduce valores string significativos.
 */
function translateContentData(string $contentJson, string $source, string $target, $aiTranslation): string {
  $data = json_decode($contentJson, TRUE);
  if (!is_array($data)) {
    return $contentJson;
  }

  // Recopilar todos los textos traducibles en un batch.
  $textsToTranslate = [];
  collectTranslatableTexts($data, '', $textsToTranslate);

  if (empty($textsToTranslate)) {
    return $contentJson;
  }

  // Traducir en batch.
  try {
    $translated = $aiTranslation->translateBatch($textsToTranslate, $source, $target);
  }
  catch (\Throwable $e) {
    return $contentJson;
  }

  // Aplicar traducciones al array.
  applyTranslatedTexts($data, '', $translated);

  return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Recopila textos traducibles de un array recursivamente.
 */
function collectTranslatableTexts(array $data, string $prefix, array &$collected): void {
  // Campos que NO se deben traducir (IDs, URLs, colores, configs).
  $skipPatterns = [
    '/^(id|uuid|type|template_id|image|icon|color|bg_color|url|href|src|class)$/i',
    '/_id$/i',
    '/_url$/i',
    '/_image$/i',
    '/_icon$/i',
  ];

  foreach ($data as $key => $value) {
    $path = $prefix ? "{$prefix}.{$key}" : (string) $key;

    if (is_array($value)) {
      collectTranslatableTexts($value, $path, $collected);
    }
    elseif (is_string($value) && mb_strlen($value) > 3) {
      // Verificar si este campo debe saltarse.
      $skip = FALSE;
      foreach ($skipPatterns as $pattern) {
        if (preg_match($pattern, (string) $key)) {
          $skip = TRUE;
          break;
        }
      }

      // Saltar URLs, colores hex, JSON.
      if (!$skip && !preg_match('/^(#[0-9a-f]{3,8}|https?:\/\/|{|}|\[|\])/i', $value)) {
        $collected[$path] = $value;
      }
    }
  }
}

/**
 * Aplica textos traducidos a un array recursivamente.
 */
function applyTranslatedTexts(array &$data, string $prefix, array $translated): void {
  foreach ($data as $key => &$value) {
    $path = $prefix ? "{$prefix}.{$key}" : (string) $key;

    if (is_array($value)) {
      applyTranslatedTexts($value, $path, $translated);
    }
    elseif (isset($translated[$path])) {
      $value = $translated[$path];
    }
  }
}
