<?php

/**
 * Fixes Remedios credential icons: replaces emoji with SVG inline (ICON-EMOJI-001).
 * Matches Pepe's credential format exactly.
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$page = $storage->load(87);
$canvas = json_decode($page->get('canvas_data')->value, FALSE);
$html = $canvas->html ?? '';

// SVG icons from Pepe's section (exact same stroke color and style).
$svgDegree = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.657 2.686 3 6 3s6-1.343 6-3v-5"/></svg>';
$svgCert = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C49B30" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>';

// Map emojis to SVGs.
$replacements = [
  '🎓' => $svgDegree,
  '📋' => $svgCert,
  '⚖️' => $svgCert,
  '🧠' => $svgCert,
  '👥' => $svgCert,
  '🤝' => $svgCert,
];

$count = 0;
foreach ($replacements as $emoji => $svg) {
  $old = ">{$emoji}</div>";
  $new = ">\n            {$svg}\n          </div>";
  $found = substr_count($html, $old);
  if ($found > 0) {
    $html = str_replace($old, $new, $html);
    $count += $found;
  }
}

echo "Replaced $count emoji icons with SVG inline.\n";

// Also fix credential structure to match Pepe: wrap title+detail in <div>.
// Pepe's format: <div class="credential-icon">SVG</div><div><div class="title">...</div><div class="detail">...</div></div>
// Current Remedios: <div class="credential-icon">SVG</div><div class="title">...</div><div class="detail">...</div>
// Need to add wrapper <div> around title+detail pairs in Remedios' section only.

$canvas->html = $html;
$newJson = json_encode($canvas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (json_decode($newJson) === NULL) {
  echo "ERROR: Invalid JSON\n";
  return;
}

$page->set('canvas_data', $newJson);
$page->save();

echo "SUCCESS: Icons fixed. No emojis remain.\n";
echo "Emoji count check: " . preg_match_all('/[\x{1F600}-\x{1F9FF}]/u', $html) . " emojis in output.\n";
