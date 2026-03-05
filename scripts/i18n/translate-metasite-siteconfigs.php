#!/usr/bin/env php
<?php
/**
 * @file translate-metasite-siteconfigs.php
 *
 * Traduce los campos texto de SiteConfig de los 3 meta-sitios a EN (y PT-BR
 * para jarabaimpact.com) usando AITranslationService.
 *
 * PREREQUISITOS:
 *   - jaraba_site_builder_update_10004() ejecutado (site_config translatable)
 *   - Idiomas en/pt-br configurados
 *
 * USO:
 *   lando drush scr scripts/i18n/translate-metasite-siteconfigs.php
 */

declare(strict_types=1);

// =========================================================================
// CONFIGURACION
// =========================================================================

/** @var array<int, array{name: string, languages: string[]}> */
$tenants = [
  5 => ['name' => 'pepejaraba.com', 'languages' => ['en']],
  6 => ['name' => 'jarabaimpact.com', 'languages' => ['en', 'pt-br']],
  7 => ['name' => 'plataformadeecosistemas.es', 'languages' => ['en']],
];

$translatableFields = [
  'site_name',
  'site_tagline',
  'header_cta_text',
  'footer_copyright',
  'footer_col1_title',
  'footer_col2_title',
  'footer_col3_title',
  'meta_title_suffix',
];

// =========================================================================
// SERVICES
// =========================================================================

$entityTypeManager = \Drupal::entityTypeManager();
$languageManager = \Drupal::languageManager();

// Verificar idiomas.
$configuredLangs = array_keys($languageManager->getLanguages());
foreach (['en', 'pt-br'] as $lang) {
  if (!in_array($lang, $configuredLangs)) {
    echo "ERROR: Idioma '$lang' no configurado. Ejecuta: lando drush language:add $lang\n";
    return;
  }
}

// Verificar AITranslationService.
if (!\Drupal::hasService('jaraba_i18n.ai_translation')) {
  echo "ERROR: AITranslationService no disponible. Asegura que jaraba_i18n esta habilitado.\n";
  return;
}
/** @var \Drupal\jaraba_i18n\Service\AITranslationService $aiTranslation */
$aiTranslation = \Drupal::service('jaraba_i18n.ai_translation');
echo "OK: AITranslationService disponible\n\n";

$storage = $entityTypeManager->getStorage('site_config');
$totalTranslated = 0;
$totalSkipped = 0;
$totalErrors = 0;

// =========================================================================
// TRANSLATION LOOP
// =========================================================================

foreach ($tenants as $tenantId => $config) {
  echo str_repeat('─', 60) . "\n";
  echo "Tenant $tenantId: {$config['name']}\n";
  echo str_repeat('─', 60) . "\n";

  // Cargar SiteConfig del tenant.
  $siteConfigs = $storage->loadByProperties(['tenant_id' => $tenantId]);
  if (empty($siteConfigs)) {
    echo "  Sin SiteConfig — saltando\n\n";
    continue;
  }

  $siteConfig = reset($siteConfigs);
  $siteName = $siteConfig->get('site_name')->value ?? 'Sin nombre';
  echo "  SiteConfig #{$siteConfig->id()}: \"$siteName\"\n";

  if (!$siteConfig->isTranslatable()) {
    echo "  ERROR: SiteConfig no es translatable. Ejecuta: lando drush updatedb\n\n";
    continue;
  }

  foreach ($config['languages'] as $targetLang) {
    echo "  -> $targetLang: ";

    // Recopilar textos a traducir del original.
    $original = $siteConfig->getUntranslated();
    $texts = [];
    foreach ($translatableFields as $fieldName) {
      $value = $original->get($fieldName)->value ?? '';
      if (!empty(trim($value))) {
        $texts[$fieldName] = $value;
      }
    }

    if (empty($texts)) {
      echo "sin textos traducibles (skip)\n";
      $totalSkipped++;
      continue;
    }

    try {
      // Traducir en batch.
      $translated = $aiTranslation->translateBatch($texts, 'es', $targetLang);

      // Crear o actualizar traduccion.
      if ($original->hasTranslation($targetLang)) {
        $translation = $original->getTranslation($targetLang);
      }
      else {
        $translation = $original->addTranslation($targetLang);
      }

      foreach ($translated as $fieldName => $value) {
        $translation->set($fieldName, $value);
      }

      $siteConfig->setSyncing(TRUE);
      $siteConfig->save();
      $siteConfig->setSyncing(FALSE);

      echo count($translated) . " campos traducidos\n";
      $totalTranslated++;
    }
    catch (\Throwable $e) {
      echo "ERROR: " . $e->getMessage() . "\n";
      $totalErrors++;
    }
  }

  echo "\n";
}

// =========================================================================
// RESUMEN
// =========================================================================

echo str_repeat('=', 60) . "\n";
echo "RESUMEN\n";
echo str_repeat('=', 60) . "\n";
echo "  Traducciones completadas: $totalTranslated\n";
echo "  Saltadas:                 $totalSkipped\n";
echo "  Errores:                  $totalErrors\n";
echo str_repeat('=', 60) . "\n";
