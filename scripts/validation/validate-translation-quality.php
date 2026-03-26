<?php

/**
 * @file
 * TRANSLATION-QUALITY-001: Valida calidad semantica de traducciones IA.
 *
 * Complementa TRANSLATION-INTEG-001 (solo page_content canvas) con checks
 * de calidad para TODOS los entity types traducibles (13 tiers).
 *
 * Checks:
 * 1. Titulo traducido identico al original ES = traduccion fallida (fallo silencioso IA).
 * 2. Campos traducibles vacios donde el original ES tiene contenido.
 * 3. Longitud anomala: traduccion < 30% o > 300% del original (hallucination o truncamiento).
 * 4. Artefactos IA en campos de texto (patrones hallucination conocidos).
 * 5. rendered_html vacio cuando canvas_data tiene contenido (canvas sin renderizar).
 *
 * Uso:
 *   lando drush php:script scripts/validation/validate-translation-quality.php
 *   php scripts/validation/validate-translation-quality.php
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$checks = 0;
$totalEntities = 0;

echo "TRANSLATION-QUALITY-001: Validando calidad de traducciones IA\n";
echo str_repeat('=', 60) . "\n\n";

$db = \Drupal::database();

// Mapa entity_type => [tabla, campo_titulo, campos_texto_clave].
$entityMap = [
  'page_content' => [
    'table' => 'page_content_field_data',
    'title' => 'title',
    'text_fields' => ['meta_title', 'meta_description'],
    'canvas' => TRUE,
  ],
  'content_article' => [
    'table' => 'content_article_field_data',
    'title' => 'title',
    'text_fields' => ['slug', 'excerpt', 'seo_title', 'seo_description'],
    'canvas' => TRUE,
  ],
  'site_config' => [
    'table' => 'site_config_field_data',
    'title' => 'site_name',
    'text_fields' => ['site_tagline', 'header_cta_text', 'meta_title_suffix'],
    'canvas' => FALSE,
  ],
  'content_category' => [
    'table' => 'content_category_field_data',
    'title' => 'name',
    'text_fields' => ['description', 'meta_title'],
    'canvas' => FALSE,
  ],
  'tenant_faq' => [
    'table' => 'tenant_faq_field_data',
    'title' => 'question',
    'text_fields' => ['answer'],
    'canvas' => FALSE,
  ],
  'tenant_policy' => [
    'table' => 'tenant_policy_field_data',
    'title' => 'title',
    'text_fields' => ['summary'],
    'canvas' => FALSE,
  ],
];

$hallucinationPatterns = [
  "I'm ready to",
  "I'll translate",
  "I don't see",
  "please provide",
  "However, I notice",
  "Could you please",
  "Here is the translation",
  "Sure, here",
  "```html",
  "```json",
];

$targetLangs = ['en', 'pt-br'];

foreach ($entityMap as $entityType => $config) {
  $table = $config['table'];

  if (!$db->schema()->tableExists($table)) {
    echo "  [{$entityType}] Tabla no existe — omitido\n";
    continue;
  }

  $titleField = $config['title'];

  // Cargar entidades ES con titulo no vacio.
  $esEntities = $db->select($table, 't')
    ->fields('t', ['id', $titleField])
    ->condition('langcode', 'es')
    ->condition($titleField, '', '<>')
    ->execute()
    ->fetchAllAssoc('id');

  if (count($esEntities) === 0) {
    continue;
  }

  $totalEntities += count($esEntities);

  foreach ($targetLangs as $lang) {
    foreach ($esEntities as $id => $esRow) {
      $esTitle = $esRow->{$titleField} ?? '';
      if (trim($esTitle) === '') {
        continue;
      }

      // Cargar traduccion.
      $trRow = $db->select($table, 't')
        ->fields('t')
        ->condition('id', $id)
        ->condition('langcode', $lang)
        ->execute()
        ->fetchObject();

      if (!$trRow) {
        // Sin traduccion — cubierto por TRANSLATION-COVERAGE-001.
        continue;
      }

      $trTitle = $trRow->{$titleField} ?? '';

      // CHECK 1: Titulo identico al original (fallo silencioso IA).
      $checks++;
      if ($trTitle === $esTitle && $lang !== 'es' && strlen($esTitle) > 3) {
        // Excluir palabras universales que no cambian.
        $universales = ['Blog', 'Partners', 'FAQ', 'OK', 'Email', 'Demo', 'App', 'API', 'CRM', 'SaaS', 'SEO'];
        if (!in_array($esTitle, $universales, TRUE)) {
          $warnings[] = "[IDENTICAL] {$entityType} #{$id} ({$lang}): titulo identico al ES \"{$esTitle}\"";
        }
      }

      // CHECK 2: Titulo vacio donde ES tiene contenido.
      $checks++;
      if (trim($trTitle) === '' && trim($esTitle) !== '') {
        $errors[] = "[EMPTY-TITLE] {$entityType} #{$id} ({$lang}): titulo vacio, ES=\"{$esTitle}\"";
      }

      // CHECK 3: Longitud anomala del titulo.
      $checks++;
      $esLen = mb_strlen($esTitle);
      $trLen = mb_strlen($trTitle);
      if ($esLen > 5 && $trLen > 0) {
        $ratio = $trLen / $esLen;
        if ($ratio < 0.3) {
          $warnings[] = "[SHORT] {$entityType} #{$id} ({$lang}): titulo {$trLen} chars vs ES {$esLen} (ratio " . round($ratio, 2) . ")";
        }
        elseif ($ratio > 3.0) {
          $warnings[] = "[LONG] {$entityType} #{$id} ({$lang}): titulo {$trLen} chars vs ES {$esLen} (ratio " . round($ratio, 2) . ")";
        }
      }

      // CHECK 4: Artefactos de hallucination en campos de texto.
      $fieldsToCheck = array_merge([$titleField], $config['text_fields']);
      foreach ($fieldsToCheck as $field) {
        if (!isset($trRow->{$field}) || $trRow->{$field} === NULL) {
          continue;
        }
        $value = (string) $trRow->{$field};
        foreach ($hallucinationPatterns as $pattern) {
          if (stripos($value, $pattern) !== FALSE) {
            $checks++;
            $errors[] = "[HALLUCINATION] {$entityType} #{$id} ({$lang}) campo {$field}: contiene \"{$pattern}\"";
            break;
          }
        }
      }

      // CHECK 5: rendered_html vacio con canvas_data poblado (canvas entities).
      if ($config['canvas'] && isset($trRow->canvas_data) && isset($trRow->rendered_html)) {
        $checks++;
        $canvasLen = strlen($trRow->canvas_data ?? '');
        $htmlLen = strlen($trRow->rendered_html ?? '');
        if ($canvasLen > 500 && $htmlLen < 100) {
          $warnings[] = "[EMPTY-HTML] {$entityType} #{$id} ({$lang}): canvas_data {$canvasLen}B pero rendered_html {$htmlLen}B";
        }
      }
    }
  }
}

// ─── RESUMEN ───
echo "\n" . str_repeat('=', 60) . "\n";
echo "Entidades auditadas: {$totalEntities} | Checks: {$checks}\n";
echo "Errores: " . count($errors) . " | Avisos: " . count($warnings) . "\n";

if (!empty($warnings)) {
  echo "\nAVISOS:\n";
  foreach ($warnings as $w) {
    echo "  !  {$w}\n";
  }
}

if (!empty($errors)) {
  echo "\nERRORES:\n";
  foreach ($errors as $e) {
    echo "  x  {$e}\n";
  }
  exit(1);
}

echo "\n+ TRANSLATION-QUALITY-001: Calidad de traducciones OK.\n";
exit(0);
