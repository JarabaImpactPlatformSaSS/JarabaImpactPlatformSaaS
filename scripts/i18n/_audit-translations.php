#!/usr/bin/env php
<?php
/**
 * Audit translation quality across all tenants.
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// All tenants with translations.
$tenants = [
  5 => ['name' => 'pepejaraba.com', 'langs' => ['en']],
  6 => ['name' => 'jarabaimpact.com', 'langs' => ['en', 'pt-br']],
  7 => ['name' => 'plataformadeecosistemas.es', 'langs' => ['en']],
];

// Also check tenant-less (SaaS main) and other tenants.
$allPages = $storage->loadMultiple();
$byTenant = [];
foreach ($allPages as $p) {
  $tid = $p->get('tenant_id')->target_id ?? '0';
  $byTenant[$tid][] = $p;
}

echo "=== PAGE DISTRIBUTION ===\n";
foreach ($byTenant as $tid => $pages) {
  $name = $tenants[(int)$tid]['name'] ?? ($tid === '0' ? 'SaaS main (no tenant)' : "tenant $tid");
  echo "  Tenant $tid ($name): " . count($pages) . " pages\n";
}
echo "\n";

// Audit meta-site tenants.
foreach ($tenants as $tid => $config) {
  echo str_repeat('=', 60) . "\n";
  echo "TENANT $tid: {$config['name']}\n";
  echo str_repeat('=', 60) . "\n";

  $pages = $storage->loadByProperties(['tenant_id' => $tid]);
  foreach ($pages as $page) {
    $id = $page->id();
    $esTitle = $page->get('title')->value ?? '';
    $es = $page->getUntranslated();
    $esHtml = $es->get('rendered_html')->value ?? '';

    // Get ES text count.
    preg_match_all('/>((?:(?!<).){5,})</us', $esHtml, $m);
    $esTexts = array_values(array_filter(array_map('trim', $m[1])));
    $esCount = count($esTexts);

    foreach ($config['langs'] as $lang) {
      if (!$page->hasTranslation($lang)) {
        echo "  #$id \"$esTitle\" -> $lang: NO TRANSLATION\n";
        continue;
      }

      $tr = $page->getTranslation($lang);
      $trTitle = $tr->get('title')->value ?: '(empty)';
      $trHtml = $tr->get('rendered_html')->value ?? '';

      // Count texts in translation.
      preg_match_all('/>((?:(?!<).){5,})</us', $trHtml, $m2);
      $trTexts = array_values(array_filter(array_map('trim', $m2[1])));
      $trCount = count($trTexts);

      // Count how many are still identical to ES (untranslated).
      $identical = 0;
      foreach ($trTexts as $i => $txt) {
        if (isset($esTexts[$i]) && $txt === $esTexts[$i]) {
          $identical++;
        }
      }

      $translated = $trCount - $identical;
      $pct = $trCount > 0 ? round(($translated / $trCount) * 100) : 0;

      $status = $pct >= 80 ? 'OK' : ($pct >= 50 ? 'PARTIAL' : 'POOR');
      echo "  #$id \"$esTitle\" -> $lang: title=\"$trTitle\" | $translated/$trCount translated ($pct%) [$status]\n";

      // Show untranslated samples if POOR.
      if ($status === 'POOR' && $identical > 0) {
        $shown = 0;
        foreach ($trTexts as $i => $txt) {
          if (isset($esTexts[$i]) && $txt === $esTexts[$i] && $shown < 3) {
            echo "    UNTRANSLATED: " . mb_substr($txt, 0, 80) . "\n";
            $shown++;
          }
        }
      }
    }
  }
  echo "\n";
}

// Check SaaS main pages (no tenant).
echo str_repeat('=', 60) . "\n";
echo "SAAS MAIN (no tenant) — " . count($byTenant['0'] ?? []) . " pages\n";
echo str_repeat('=', 60) . "\n";
$noTenantPages = $byTenant['0'] ?? [];
$hasEn = 0;
$noEn = 0;
foreach ($noTenantPages as $page) {
  if ($page->hasTranslation('en')) {
    $hasEn++;
  } else {
    $noEn++;
  }
}
echo "  With EN: $hasEn | Without EN: $noEn\n";
// Show sample titles.
foreach (array_slice($noTenantPages, 0, 10) as $page) {
  $title = $page->get('title')->value ?? '(no title)';
  $en = $page->hasTranslation('en') ? 'EN' : '--';
  echo "  #" . $page->id() . " \"$title\" [$en]\n";
}
if (count($noTenantPages) > 10) {
  echo "  ... and " . (count($noTenantPages) - 10) . " more\n";
}
