#!/usr/bin/env php
<?php

/**
 * @file
 * Fix remaining world-class gaps on /sobre-nosotros (83) and /equipo (87).
 *
 * Fixes:
 * 1. Meta descriptions: "30 anos" → "50 anos de experiencia combinada"
 * 2. Timeline: "2019" → "2020" for PED founding
 * 3. Aria-labels: unified to "Perfil de LinkedIn de {nombre}"
 * 4. CTA secundario in sobre-nosotros
 * 5. Person schema in content_data for og:image fallback
 *
 * Usage: lando drush scr scripts/maintenance/fix-cofounders-world-class.php
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// ============================================================================
// 1. FIX /sobre-nosotros (Entity 83)
// ============================================================================

$entity83 = $storage->load(83);
if (!$entity83) {
  echo "ERROR: Entity 83 not found.\n";
  return;
}

// Fix meta_description.
$entity83->set('meta_description', 'Conoce la mision, vision y valores de PED S.L. Mas de 50 anos de experiencia combinada de sus cofundadores en transformacion digital del mundo rural.');

// Fix canvas_data: aria-labels + CTA secundario.
$canvas = json_decode($entity83->get('canvas_data')->value, TRUE);
$html = $canvas['html'] ?? '';

// Unify aria-labels.
$html = str_replace(
  'aria-label="LinkedIn de Pepe Jaraba"',
  'aria-label="Perfil de LinkedIn de Jose Jaraba"',
  $html
);
$html = str_replace(
  'aria-label="LinkedIn de Remedios Estevez"',
  'aria-label="Perfil de LinkedIn de Remedios Estevez"',
  $html
);

// Add CTA secundario (Ver el equipo) next to Solicitar demo.
$html = str_replace(
  '<a href="/contacto" class="btn-gold">Solicitar demo</a>',
  '<a href="/contacto" class="btn-gold">Solicitar demo</a>' . "\n" .
  '        <a href="/equipo" class="btn-ghost">Conocer al equipo</a>',
  $html
);

$canvas['html'] = $html;
$entity83->set('canvas_data', json_encode($canvas, JSON_UNESCAPED_UNICODE));

// Set content_data with og_image for cofounders.
$contentData83 = json_decode($entity83->get('content_data')->value ?? '{}', TRUE) ?: [];
$contentData83['og_image'] = '/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp';
$entity83->set('content_data', json_encode($contentData83, JSON_UNESCAPED_UNICODE));

$entity83->save();
echo "Fixed /sobre-nosotros (ID 83): meta_description, aria-labels, CTA secundario, og_image.\n";

// ============================================================================
// 2. FIX /equipo (Entity 87)
// ============================================================================

$entity87 = $storage->load(87);
if (!$entity87) {
  echo "ERROR: Entity 87 not found.\n";
  return;
}

// Fix meta_description.
$entity87->set('meta_description', 'Conoce a los cofundadores de PED S.L.: Jose Jaraba Munoz (CEO) y Remedios Estevez Palomino (COO). Mas de 50 anos de experiencia combinada en transformacion territorial y fondos europeos.');

// Fix canvas_data: timeline 2019→2020.
$canvas87 = json_decode($entity87->get('canvas_data')->value, TRUE);
$html87 = $canvas87['html'] ?? '';

// Fix timeline year.
$html87 = str_replace(
  '2019 &ndash; Presente',
  '2020 &ndash; Presente',
  $html87
);
$html87 = str_replace(
  '2019 – Presente',
  '2020 – Presente',
  $html87
);

// Unify aria-labels.
$html87 = str_replace(
  'aria-label="LinkedIn de Pepe Jaraba"',
  'aria-label="Perfil de LinkedIn de Jose Jaraba"',
  $html87
);

$canvas87['html'] = $html87;
$entity87->set('canvas_data', json_encode($canvas87, JSON_UNESCAPED_UNICODE));

// Set content_data with og_image.
$contentData87 = json_decode($entity87->get('content_data')->value ?? '{}', TRUE) ?: [];
$contentData87['og_image'] = '/themes/custom/ecosistema_jaraba_theme/images/equipo-pepe-jaraba.webp';
$entity87->set('content_data', json_encode($contentData87, JSON_UNESCAPED_UNICODE));

$entity87->save();
echo "Fixed /equipo (ID 87): meta_description, timeline 2019→2020, aria-labels, og_image.\n";

echo "\nDone. Clear cache with: drush cr\n";
