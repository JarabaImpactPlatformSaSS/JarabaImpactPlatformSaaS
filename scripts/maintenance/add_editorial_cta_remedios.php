<?php

/**
 * Add CTA button to Remedios' bio on /equipo page.
 *
 * Links to /editorial/equilibrio-autonomo#contenido.
 * Inserts after the book mention paragraph, before social links.
 *
 * Usage: lando drush php:script scripts/maintenance/add_editorial_cta_remedios.php
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
// ID 22 en dev (lando), 87 en produccion.
$query = $storage->getQuery()->accessCheck(FALSE)->condition('path_alias', '/equipo')->range(0, 1);
$ids = $query->execute();
$page = $ids ? $storage->load(reset($ids)) : NULL;

if (!$page) {
  echo "ERROR: page_content 87 not found.\n";
  return;
}

$canvasRaw = $page->get('canvas_data')->value;
$canvas = json_decode($canvasRaw, FALSE);
$html = $canvas->html ?? '';

// CTA button HTML — inline styles for canvas compatibility (no SCSS).
$ctaHtml = <<<'CTA'
          <div class="ped-founder__cta-group" style="margin-top: 1.25rem; margin-bottom: 0.75rem;">
            <a href="/editorial/equilibrio-autonomo#contenido"
               class="ped-founder__cta-book"
               style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #FF8C42, #e67a35); color: #fff; border-radius: 8px; font-weight: 600; font-size: 0.9375rem; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; box-shadow: 0 2px 8px rgba(255,140,66,0.25);"
               data-track-cta="equipo_remedios_libro"
               data-track-position="equipo">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
              Descubre &laquo;Equilibrio Aut&oacute;nomo&raquo;
            </a>
          </div>

CTA;

// Process both canvas_data.html AND rendered_html (dual write).
$fields = ['canvas_data', 'rendered_html'];
$modified = FALSE;

foreach ($fields as $fieldName) {
  if ($fieldName === 'canvas_data') {
    $canvas = json_decode($page->get('canvas_data')->value, FALSE);
    $fieldHtml = $canvas->html ?? '';
  }
  else {
    $fieldHtml = $page->get('rendered_html')->value ?? '';
  }

  if (empty($fieldHtml)) {
    continue;
  }

  // Idempotent: skip if CTA already present.
  if (str_contains($fieldHtml, 'editorial/equilibrio-autonomo')) {
    echo "SKIP ($fieldName): CTA already exists.\n";
    continue;
  }

  // Find the social links div in Remedios section (2nd occurrence).
  $socialDiv = '<div class="ped-founder__social">';
  $firstSocial = strpos($fieldHtml, $socialDiv);
  $remSection = strpos($fieldHtml, 'remedios-estevez');

  if ($remSection !== FALSE) {
    // Find social div AFTER Remedios section start.
    $socialPos = strpos($fieldHtml, $socialDiv, $remSection);
  }
  elseif ($firstSocial !== FALSE) {
    // Fallback: use second social div.
    $socialPos = strpos($fieldHtml, $socialDiv, $firstSocial + 1);
    if ($socialPos === FALSE) {
      $socialPos = $firstSocial;
    }
  }
  else {
    echo "WARNING ($fieldName): Could not find social links div.\n";
    continue;
  }

  $fieldHtml = substr($fieldHtml, 0, $socialPos) . $ctaHtml . substr($fieldHtml, $socialPos);

  if ($fieldName === 'canvas_data') {
    $canvas->html = $fieldHtml;
    $newJson = json_encode($canvas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (json_decode($newJson) === NULL) {
      echo "ERROR ($fieldName): Invalid JSON.\n";
      continue;
    }
    $page->set('canvas_data', $newJson);
  }
  else {
    $page->set('rendered_html', $fieldHtml);
  }
  $modified = TRUE;
  echo "OK ($fieldName): CTA inserted.\n";
}

if ($modified) {
  $page->save();
  echo "SUCCESS: CTA button added to Remedios bio on /equipo.\n";
  echo "  Link: /editorial/equilibrio-autonomo#contenido\n";
}
else {
  echo "No changes needed.\n";
}
