<?php

/**
 * @file
 * TRANSLATION-INTEG-001: Validates page_content translation integrity.
 *
 * Checks:
 * 1. No two different pages share identical canvas_data (cross-page corruption).
 * 2. Translations have non-NULL title when ES original has one.
 * 3. Translated canvas_data is proportional to ES (not empty/truncated).
 * 4. No markdown fences (```html) in canvas_data (AI artifact).
 *
 * Usage:
 *   php scripts/validation/validate-translation-integrity.php
 *
 * Part of Safeguard Layer 1 (scripts/validation/).
 */

declare(strict_types=1);

$database = \Drupal::database();
$violations = [];

// Skip template pages.
$skip_ids = [1, 6, 17, 42, 44, 45, 46, 47];
$skip_placeholders = implode(',', $skip_ids);

// CHECK 1: Cross-page canvas duplication.
$dupes = $database->query("
  SELECT a.id AS page_a, b.id AS page_b, a.langcode, a.title
  FROM {page_content_field_data} a
  JOIN {page_content_field_data} b
    ON a.canvas_data = b.canvas_data AND a.id < b.id AND a.langcode = b.langcode
  WHERE LENGTH(a.canvas_data) > 200
")->fetchAll();

foreach ($dupes as $d) {
  $violations[] = "[CROSS-PAGE-DUP] Pages {$d->page_a} and {$d->page_b} ({$d->langcode}) share identical canvas_data — \"{$d->title}\"";
}

// CHECK 2: NULL title in translations where ES has a title.
$nullTitles = $database->query("
  SELECT p.id, p.langcode, es.title AS es_title
  FROM {page_content_field_data} p
  JOIN {page_content_field_data} es ON es.id = p.id AND es.langcode = 'es'
  WHERE p.langcode IN ('en', 'pt-br') AND p.title IS NULL AND es.title IS NOT NULL
    AND p.id NOT IN ($skip_placeholders)
")->fetchAll();

foreach ($nullTitles as $n) {
  $violations[] = "[NULL-TITLE] Page {$n->id} ({$n->langcode}) has NULL title — ES: \"{$n->es_title}\"";
}

// CHECK 3: Translated canvas significantly smaller than ES (>50% shrink).
$small = $database->query("
  SELECT p.id, p.langcode, LENGTH(es.canvas_data) AS es_len, LENGTH(p.canvas_data) AS tr_len, es.title
  FROM {page_content_field_data} p
  JOIN {page_content_field_data} es ON es.id = p.id AND es.langcode = 'es'
  WHERE p.langcode IN ('en', 'pt-br')
    AND LENGTH(es.canvas_data) > 500
    AND LENGTH(p.canvas_data) < LENGTH(es.canvas_data) * 0.5
    AND p.id NOT IN ($skip_placeholders)
")->fetchAll();

foreach ($small as $s) {
  $violations[] = "[SMALL-CANVAS] Page {$s->id} ({$s->langcode}) canvas is {$s->tr_len} bytes vs ES {$s->es_len} — \"{$s->title}\"";
}

// CHECK 4: Markdown fences in canvas_data (AI artifact).
$fences = $database->query("
  SELECT id, langcode, title
  FROM {page_content_field_data}
  WHERE canvas_data LIKE '%\`\`\`%'
    AND LENGTH(canvas_data) > 100
    AND id NOT IN ($skip_placeholders)
")->fetchAll();

foreach ($fences as $f) {
  $violations[] = "[MD-FENCE] Page {$f->id} ({$f->langcode}) has markdown fences in canvas_data — \"{$f->title}\"";
}

// ─── REPORT ───
$total_pages = $database->query("
  SELECT COUNT(DISTINCT id) FROM {page_content_field_data}
  WHERE canvas_data IS NOT NULL AND LENGTH(canvas_data) > 100
    AND id NOT IN ($skip_placeholders)
")->fetchField();

if (empty($violations)) {
  echo "TRANSLATION-INTEG-001: PASS — No issues in $total_pages pages.\n";
  return;
}

echo "TRANSLATION-INTEG-001: FAIL — " . count($violations) . " issue(s) found.\n\n";
foreach ($violations as $v) {
  echo "  - $v\n";
}
echo "\n";

throw new \RuntimeException('TRANSLATION-INTEG-001: FAIL');
