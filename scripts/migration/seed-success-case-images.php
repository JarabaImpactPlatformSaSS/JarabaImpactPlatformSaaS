<?php

/**
 * @file
 * Seed script: Assigns theme fallback images to SuccessCase entities.
 *
 * SUCCESS-CASES-001: All case study data must come from SuccessCase entity.
 * IMG-SEED-001: Images from theme are copied to public:// and assigned to entities.
 *
 * This script is IDEMPOTENT: it only assigns images to entities that have
 * empty hero_image fields. If a SuccessCase already has a hero_image (uploaded
 * via admin UI), it is NOT overwritten.
 *
 * Run:
 *   lando drush php:script scripts/migration/seed-success-case-images.php
 *   drush php:script scripts/migration/seed-success-case-images.php (production)
 *
 * Deploy: Integrated in deploy.yml post content-seed step.
 *
 * @see SUCCESS-CASES-001
 * @see CONTENT-SEED-PIPELINE-001
 */

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

// Bootstrap Drupal if not already bootstrapped.
if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
  $autoloader = require_once 'autoload.php';
  $kernel = new DrupalKernel('prod', $autoloader);
  $request = Request::createFromGlobals();
  $kernel->boot();
  $kernel->preHandle($request);
}

$entityTypeManager = \Drupal::entityTypeManager();
$storage = $entityTypeManager->getStorage('success_case');
$fileSystem = \Drupal::service('file_system');
$themeList = \Drupal::service('extension.list.theme');

$themePath = $themeList->getPath('ecosistema_jaraba_theme');

// ============================================================================
// IMAGE MAP: vertical → directory → field → filename
// Mirrors CaseStudyLandingController::applyImageFallbacks() — SINGLE SOURCE.
// ============================================================================

$imageMap = [
  'agroconecta' => [
    'dir' => 'agroconecta-case-study',
    'hero_image' => 'antonio-olivar.webp',
    'protagonist_image' => 'antonio-olivar.webp',
    'before_after_image' => 'antes-despues-agro.webp',
    'discovery_image' => 'qr-trazabilidad.webp',
    'dashboard_image' => 'dashboard-productor.webp',
  ],
  'jarabalex' => [
    'dir' => 'jarabalex-case-study',
    'hero_image' => 'elena-despacho.webp',
    'protagonist_image' => 'elena-despacho.webp',
    'before_after_image' => 'antes-despues.webp',
    'discovery_image' => 'busqueda-ia.webp',
    'dashboard_image' => 'dashboard-legal.webp',
  ],
  'comercioconecta' => [
    'dir' => 'comercioconecta-case-study',
    'hero_image' => 'carmen-boutique.webp',
    'protagonist_image' => 'carmen-boutique.webp',
    'before_after_image' => 'antes-despues-comercio.webp',
    'discovery_image' => 'qr-escaparate.webp',
    'dashboard_image' => 'dashboard-comerciante.webp',
  ],
  'empleabilidad' => [
    'dir' => 'empleabilidad-case-study',
    'hero_image' => 'rosa-oficina.webp',
    'protagonist_image' => 'rosa-oficina.webp',
    'before_after_image' => 'antes-despues-empleo.webp',
    'discovery_image' => 'diagnostico-movil.webp',
    'dashboard_image' => 'health-score-dashboard.webp',
  ],
  'emprendimiento' => [
    'dir' => 'emprendimiento-case-study',
    'hero_image' => 'carlos-coworking.webp',
    'protagonist_image' => 'carlos-coworking.webp',
    'before_after_image' => 'antes-despues-emprendimiento.webp',
    'discovery_image' => 'canvas-ia-tablet.webp',
    'dashboard_image' => 'health-score-emprendedor.webp',
  ],
  'formacion' => [
    'dir' => 'formacion-case-study',
    'hero_image' => 'maria-coworking.webp',
    'protagonist_image' => 'maria-coworking.webp',
    'before_after_image' => 'antes-despues-formacion.webp',
    'discovery_image' => 'copilot-course-builder.webp',
    'dashboard_image' => 'lms-dashboard.webp',
  ],
  'serviciosconecta' => [
    'dir' => 'serviciosconecta-case-study',
    'hero_image' => 'carmen-consulta.webp',
    'protagonist_image' => 'carmen-consulta.webp',
    'before_after_image' => 'antes-despues-servicios.webp',
    'discovery_image' => 'qr-reservas-clinica.webp',
    'dashboard_image' => 'dashboard-servicios.webp',
  ],
  'andalucia_ei' => [
    'dir' => 'andalucia-ei-case-study',
    'hero_image' => 'ana-martinez-aedl.webp',
    'protagonist_image' => 'ana-martinez-aedl.webp',
    'before_after_image' => 'antes-despues-instituciones.webp',
    'discovery_image' => 'informe-fse-automatico.webp',
    'dashboard_image' => 'dashboard-impacto-ods.webp',
  ],
  'content_hub' => [
    'dir' => 'contenthub-case-study',
    'hero_image' => 'luis-moreno-bodega.webp',
    'protagonist_image' => 'luis-moreno-bodega.webp',
    'before_after_image' => 'antes-despues-contenido.webp',
    'discovery_image' => 'editor-ia-seo.webp',
    'dashboard_image' => 'analytics-seo-dashboard.webp',
  ],
];

// Special overrides for REAL cases (Andalucia +ei 1a Edicion) with their own photos.
// These have photos in /sites/default/files/success-cases/ already
// OR need to be assigned from specific theme images.
$realCaseOverrides = [
  'luis-miguel-criado' => [
    'hero_image_source' => 'public://success-cases/2024/luis-miguel-criado.jpg',
  ],
  'marcela-calabia-cosmopolitan-media' => [
    'hero_image_source' => 'public://success-cases/2024/marcela-calabia.jpg',
  ],
  'angel-martinez-camino-viejo' => [
    'hero_image_source' => 'public://success-cases/2024/angel-martinez.jpg',
  ],
  'maia-tolomeo' => [
    'hero_image_source' => 'public://success-cases/2024/maia-tolomeo.jpg',
  ],
  'adrian-capatina-tudor-novavid' => [
    'hero_image_source' => 'public://success-cases/photos/adrian-capatina-tudor.jpg',
  ],
  'cristina-martin-pereira-de-cris-moda' => [
    'hero_image_source' => 'public://success-cases/photos/cristina-martin-pereira.jpg',
  ],
];

// Image fields to seed.
$imageFields = ['hero_image', 'protagonist_image', 'before_after_image', 'discovery_image', 'dashboard_image'];

// Ensure destination directory exists.
$destDir = 'public://success-cases/seed';
$fileSystem->prepareDirectory($destDir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

// Load all SuccessCase entities.
$cases = $storage->loadMultiple();

if (empty($cases)) {
  echo "No SuccessCase entities found. Run seed-success-cases.php first.\n";
  exit(0);
}

$seeded = 0;
$skipped = 0;
$errors = 0;

echo "=== IMG-SEED-001: Seeding SuccessCase images ===\n";
echo "  Found " . count($cases) . " SuccessCase entities.\n";
echo "  Theme path: $themePath\n\n";

foreach ($cases as $case) {
  $vertical = $case->get('vertical')->value;
  $slug = $case->get('slug')->value;
  $name = $case->get('name')->value;

  // Check if this vertical has image mapping.
  if (!isset($imageMap[$vertical])) {
    echo "  [SKIP] $name ($slug) — vertical '$vertical' not in image map.\n";
    $skipped++;
    continue;
  }

  $verticalMap = $imageMap[$vertical];
  $imgDir = $verticalMap['dir'];
  $caseSeeded = FALSE;

  foreach ($imageFields as $field) {
    // Skip if entity already has an image in this field.
    if ($case->hasField($field) && !$case->get($field)->isEmpty()) {
      $existingFile = $case->get($field)->entity;
      if ($existingFile) {
        // Verify the physical file exists.
        $existingUri = $existingFile->getFileUri();
        $realPath = $fileSystem->realpath($existingUri);
        if ($realPath && file_exists($realPath)) {
          continue; // File exists — do not overwrite.
        }
        // File entity exists but physical file missing — reassign.
        echo "  [REPAIR] $name / $field — file entity exists but physical file missing.\n";
      }
    }

    // Determine source image.
    $sourceFile = NULL;

    // Check real case override for hero_image.
    if ($field === 'hero_image' && isset($realCaseOverrides[$slug])) {
      $overrideUri = $realCaseOverrides[$slug]['hero_image_source'];
      $overridePath = $fileSystem->realpath($overrideUri);
      if ($overridePath && file_exists($overridePath)) {
        $sourceFile = $overridePath;
      }
    }

    // Fallback to theme static image.
    if (!$sourceFile && isset($verticalMap[$field])) {
      $themeSrc = DRUPAL_ROOT . '/' . $themePath . '/images/' . $imgDir . '/' . $verticalMap[$field];
      if (file_exists($themeSrc)) {
        $sourceFile = $themeSrc;
      }
    }

    if (!$sourceFile) {
      continue; // No source available for this field.
    }

    // Copy file to public:// and create File entity.
    $filename = basename($sourceFile);
    $destUri = $destDir . '/' . $slug . '--' . $field . '--' . $filename;

    try {
      // Copy if destination doesn't exist yet.
      $destPath = $fileSystem->realpath($destUri);
      if (!$destPath || !file_exists($destPath)) {
        $copiedUri = $fileSystem->copy($sourceFile, $destUri, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
      }
      else {
        $copiedUri = $destUri;
      }

      // Check if File entity already exists for this URI.
      $existingFiles = $entityTypeManager->getStorage('file')
        ->loadByProperties(['uri' => $copiedUri]);

      if (!empty($existingFiles)) {
        $fileEntity = reset($existingFiles);
      }
      else {
        // Create File entity.
        $fileEntity = File::create([
          'uri' => $copiedUri,
          'filename' => $filename,
          'filemime' => \Drupal::service('file.mime_type.guesser')->guessMimeType($copiedUri),
          'status' => 1,
          'uid' => 1,
        ]);
        $fileEntity->save();
      }

      // Assign to entity field.
      $case->set($field, [
        'target_id' => $fileEntity->id(),
        'alt' => $name,
        'title' => $name,
      ]);
      $caseSeeded = TRUE;

    }
    catch (\Throwable $e) {
      echo "  [ERROR] $name / $field — " . $e->getMessage() . "\n";
      $errors++;
    }
  }

  if ($caseSeeded) {
    try {
      $case->save();
      echo "  [OK] $name ($slug) — images seeded.\n";
      $seeded++;
    }
    catch (\Throwable $e) {
      echo "  [ERROR] $name save failed — " . $e->getMessage() . "\n";
      $errors++;
    }
  }
  else {
    echo "  [SKIP] $name ($slug) — all images already present.\n";
    $skipped++;
  }
}

echo "\n=== Results ===\n";
echo "  Seeded: $seeded | Skipped: $skipped | Errors: $errors\n";
echo "  Total: " . count($cases) . " entities processed.\n";

if ($errors > 0) {
  exit(1);
}
exit(0);
