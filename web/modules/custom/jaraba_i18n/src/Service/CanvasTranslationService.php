<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Traduce canvas_data, rendered_html y content_data de PageContent entities.
 *
 * Encapsula la logica de traduccion de GrapesJS canvas como servicio
 * reutilizable para hooks automaticos y scripts one-time.
 */
class CanvasTranslationService {

  /**
   * Campos de texto simple que se traducen con IA.
   */
  private const TEXT_FIELDS = [
    'title',
    'meta_title',
    'meta_description',
  ];

  /**
   * Campos que contienen canvas/HTML y requieren traduccion especial.
   */
  private const CANVAS_FIELDS = [
    'canvas_data',
    'rendered_html',
    'content_data',
  ];

  /**
   * Patrones de keys JSON que NO se deben traducir.
   */
  private const SKIP_PATTERNS = [
    '/^(id|uuid|type|template_id|image|icon|color|bg_color|url|href|src|class)$/i',
    '/_id$/i',
    '/_url$/i',
    '/_image$/i',
    '/_icon$/i',
  ];

  public function __construct(
    protected AITranslationService $aiTranslation,
    protected TranslationManagerService $translationManager,
    protected LanguageManagerInterface $languageManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Traduce todos los campos canvas de una pagina a un idioma destino.
   *
   * Si la traduccion no existe la crea; si existe la actualiza.
   */
  public function translatePageCanvas(ContentEntityInterface $page, string $targetLang, string $sourceLang = 'es'): void {
    if ($sourceLang === $targetLang) {
      return;
    }

    $original = $page->getUntranslated();

    // Crear o obtener traduccion.
    if ($original->hasTranslation($targetLang)) {
      $translation = $original->getTranslation($targetLang);
    }
    else {
      $translation = $original->addTranslation($targetLang);
      // Copiar campos no traducibles del original.
      if ($original->hasField('status')) {
        $translation->set('status', $original->get('status')->value);
      }
      if ($original->hasField('template_id')) {
        $translation->set('template_id', $original->get('template_id')->value);
      }
    }

    // Traducir campos de texto simples.
    $textsToTranslate = [];
    foreach (self::TEXT_FIELDS as $fieldName) {
      if (!$original->hasField($fieldName)) {
        continue;
      }
      $value = $original->get($fieldName)->value ?? '';
      if (!empty(trim($value))) {
        $textsToTranslate[$fieldName] = $value;
      }
    }

    if (!empty($textsToTranslate)) {
      try {
        $translated = $this->aiTranslation->translateBatch($textsToTranslate, $sourceLang, $targetLang);
        foreach ($translated as $fieldName => $translatedValue) {
          $translation->set($fieldName, $translatedValue);
        }
      }
      catch (\Throwable $e) {
        $this->logger->warning('Failed to translate text fields for page @id to @lang: @msg', [
          '@id' => $original->id(),
          '@lang' => $targetLang,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    // Traducir path_alias.
    if ($original->hasField('path_alias')) {
      $originalAlias = $original->get('path_alias')->value ?? '';
      if ($originalAlias !== '') {
        try {
          $aliasText = str_replace(['/', '-'], [' ', ' '], trim($originalAlias, '/'));
          if ($aliasText !== '') {
            $translatedAlias = $this->aiTranslation->translate($aliasText, $sourceLang, $targetLang);
            // Transliterar acentos (á→a, ñ→n, ç→c, etc.) antes de slugificar.
            if (function_exists('transliterator_transliterate')) {
              $translatedAlias = transliterator_transliterate('Any-Latin; Latin-ASCII', $translatedAlias);
            }
            $translatedAlias = mb_strtolower($translatedAlias);
            $translatedAlias = preg_replace('/[^a-z0-9]+/', '-', $translatedAlias) ?? $translatedAlias;
            $translatedAlias = '/' . trim($translatedAlias, '-');
            // Truncar a 255 chars (limite columna DB).
            if (mb_strlen($translatedAlias) > 255) {
              $translatedAlias = mb_substr($translatedAlias, 0, 255);
              $translatedAlias = rtrim($translatedAlias, '-');
            }
            $translation->set('path_alias', $translatedAlias);
          }
        }
        catch (\Throwable $e) {
          // Keep original alias on failure.
        }
      }
    }

    // Traducir canvas_data.
    if ($original->hasField('canvas_data')) {
      $canvasData = $original->get('canvas_data')->value ?? '{}';
      if ($canvasData !== '{}' && !empty($canvasData)) {
        try {
          $translated = $this->translateCanvasData($canvasData, $sourceLang, $targetLang);
          $translation->set('canvas_data', $translated);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Failed to translate canvas_data for page @id: @msg', [
            '@id' => $original->id(),
            '@msg' => $e->getMessage(),
          ]);
        }
      }
    }

    // Traducir rendered_html.
    if ($original->hasField('rendered_html')) {
      $html = $original->get('rendered_html')->value ?? '';
      if (!empty($html)) {
        try {
          $translated = $this->translateHtmlContent($html, $sourceLang, $targetLang);
          $translation->set('rendered_html', $translated);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Failed to translate rendered_html for page @id: @msg', [
            '@id' => $original->id(),
            '@msg' => $e->getMessage(),
          ]);
        }
      }
    }

    // Traducir content_data.
    if ($original->hasField('content_data')) {
      $contentData = $original->get('content_data')->value ?? '{}';
      if ($contentData !== '{}' && !empty($contentData)) {
        try {
          $translated = $this->translateContentData($contentData, $sourceLang, $targetLang);
          $translation->set('content_data', $translated);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Failed to translate content_data for page @id: @msg', [
            '@id' => $original->id(),
            '@msg' => $e->getMessage(),
          ]);
        }
      }
    }

    // Guardar con syncing flag para evitar loop.
    $page->setSyncing(TRUE);
    $page->save();
    $page->setSyncing(FALSE);

    $this->logger->info('Translated page @id canvas to @lang', [
      '@id' => $original->id(),
      '@lang' => $targetLang,
    ]);
  }

  /**
   * Sincroniza traducciones de una pagina a todos los idiomas configurados.
   */
  public function syncAllTranslations(ContentEntityInterface $page): void {
    $original = $page->getUntranslated();
    $sourceLang = $original->language()->getId();
    $configuredLangs = array_keys($this->languageManager->getLanguages());

    foreach ($configuredLangs as $langcode) {
      if ($langcode === $sourceLang) {
        continue;
      }
      try {
        $this->translatePageCanvas($page, $langcode, $sourceLang);
      }
      catch (\Throwable $e) {
        $this->logger->error('Failed to sync translation for page @id to @lang: @msg', [
          '@id' => $original->id(),
          '@lang' => $langcode,
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Traduce canvas_data JSON de GrapesJS.
   */
  public function translateCanvasData(string $canvasJson, string $source, string $target): string {
    $data = json_decode($canvasJson, TRUE);
    if (!is_array($data)) {
      return $canvasJson;
    }

    // GrapesJS almacena HTML en data['html'] o data['gjs-html'].
    foreach (['html', 'gjs-html'] as $key) {
      if (!empty($data[$key]) && is_string($data[$key])) {
        $data[$key] = $this->translateHtmlContent($data[$key], $source, $target);
      }
    }

    // Traducir componentes si estan presentes.
    if (!empty($data['components']) && is_array($data['components'])) {
      $data['components'] = $this->translateGrapesJsComponents($data['components'], $source, $target);
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  /**
   * Traduce contenido HTML extrayendo textos en batch.
   */
  public function translateHtmlContent(string $html, string $source, string $target): string {
    if (empty(trim($html))) {
      return $html;
    }

    // Extraer bloques de texto significativos del HTML.
    $texts = [];
    $index = 0;

    $result = preg_replace_callback(
      '/>((?:(?!<).)+)</u',
      function ($matches) use (&$texts, &$index) {
        $text = trim($matches[1]);
        if (empty($text) || mb_strlen($text) < 3) {
          return $matches[0];
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
      $translatedTexts = $this->aiTranslation->translateBatch($texts, $source, $target);
    }
    catch (\Throwable $e) {
      return $html;
    }

    // Reemplazar placeholders con traducciones.
    foreach ($translatedTexts as $placeholder => $translatedText) {
      $result = str_replace($placeholder, $translatedText, $result);
    }

    // Limpiar placeholders no traducidos.
    foreach ($texts as $placeholder => $originalText) {
      if (str_contains($result, $placeholder)) {
        $result = str_replace($placeholder, $originalText, $result);
      }
    }

    return $result;
  }

  /**
   * Traduce recursivamente componentes GrapesJS.
   */
  public function translateGrapesJsComponents(array $components, string $source, string $target): array {
    foreach ($components as &$component) {
      // Traducir el contenido de texto del componente.
      if (!empty($component['content']) && is_string($component['content'])) {
        $text = strip_tags($component['content']);
        if (!empty(trim($text)) && mb_strlen($text) > 2) {
          try {
            $component['content'] = $this->aiTranslation->translate($component['content'], $source, $target);
          }
          catch (\Throwable $e) {
            // Mantener original si falla.
          }
        }
      }

      // Recursia: traducir componentes hijos.
      if (!empty($component['components']) && is_array($component['components'])) {
        $component['components'] = $this->translateGrapesJsComponents($component['components'], $source, $target);
      }

      // Traducir atributos de texto (alt, title, placeholder).
      if (!empty($component['attributes']) && is_array($component['attributes'])) {
        foreach (['alt', 'title', 'placeholder', 'aria-label'] as $attr) {
          if (!empty($component['attributes'][$attr]) && is_string($component['attributes'][$attr])) {
            try {
              $component['attributes'][$attr] = $this->aiTranslation->translate(
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
   * Traduce content_data JSON recursivamente.
   */
  public function translateContentData(string $contentJson, string $source, string $target): string {
    $data = json_decode($contentJson, TRUE);
    if (!is_array($data)) {
      return $contentJson;
    }

    // Recopilar todos los textos traducibles en un batch.
    $textsToTranslate = [];
    $this->collectTranslatableTexts($data, '', $textsToTranslate);

    if (empty($textsToTranslate)) {
      return $contentJson;
    }

    try {
      $translated = $this->aiTranslation->translateBatch($textsToTranslate, $source, $target);
    }
    catch (\Throwable $e) {
      return $contentJson;
    }

    $this->applyTranslatedTexts($data, '', $translated);

    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }

  /**
   * Recopila textos traducibles de un array recursivamente.
   */
  private function collectTranslatableTexts(array $data, string $prefix, array &$collected): void {
    foreach ($data as $key => $value) {
      $path = $prefix ? "{$prefix}.{$key}" : (string) $key;

      if (is_array($value)) {
        $this->collectTranslatableTexts($value, $path, $collected);
      }
      elseif (is_string($value) && mb_strlen($value) > 3) {
        $skip = FALSE;
        foreach (self::SKIP_PATTERNS as $pattern) {
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
  private function applyTranslatedTexts(array &$data, string $prefix, array $translated): void {
    foreach ($data as $key => &$value) {
      $path = $prefix ? "{$prefix}.{$key}" : (string) $key;

      if (is_array($value)) {
        $this->applyTranslatedTexts($value, $path, $translated);
      }
      elseif (isset($translated[$path])) {
        $value = $translated[$path];
      }
    }
  }

}
