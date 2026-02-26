<?php
/**
 * Audit script for plataformadeecosistemas.es meta-site.
 * Run: lando ssh -c 'php scripts/audit-ped-metasite.php'
 */

echo "=== Auditoría Meta-Sitio plataformadeecosistemas.es ===\n\n";

// 1. Fetch homepage with Host header
$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => 'https://localhost/',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_HTTPHEADER => ['Host: plataformadeecosistemas.jaraba-saas.lndo.site'],
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 10,
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "1. HTTP Status: $httpCode\n";
echo "   Redirect URL: " . ($redirectUrl ?: 'none') . "\n";
echo "   Body length: " . strlen($html) . " bytes\n\n";

if (empty($html)) {
  echo "No HTML returned. Trying without HTTPS...\n";
  $ch2 = curl_init();
  curl_setopt_array($ch2, [
    CURLOPT_URL => 'http://localhost/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Host: plataformadeecosistemas.jaraba-saas.lndo.site'],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
  ]);
  $html = curl_exec($ch2);
  $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
  curl_close($ch2);
  echo "   HTTP (no SSL) Status: $httpCode\n";
  echo "   Body length: " . strlen($html) . " bytes\n\n";
}

if (empty($html)) {
  echo "ERROR: No HTML returned. Site may not respond to this Host header.\n";
  exit(1);
}

// 2. Page title
preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatch);
echo "2. Page Title: " . ($titleMatch[1] ?? 'NOT FOUND') . "\n\n";

// 3. Meta description
preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/', $html, $metaDesc);
echo "3. Meta Description: " . ($metaDesc[1] ?? 'NOT FOUND') . "\n\n";

// 4. Headings
preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $headings);
echo "4. Headings (" . count($headings[0]) . " found):\n";
foreach ($headings[0] as $i => $h) {
  $level = $headings[1][$i];
  $text = strip_tags($headings[2][$i]);
  $text = trim(preg_replace('/\s+/', ' ', $text));
  if (strlen($text) > 80) $text = substr($text, 0, 80) . '...';
  echo "   H$level: $text\n";
  if ($i >= 15) { echo "   ... (" . (count($headings[0]) - 16) . " more)\n"; break; }
}

// 5. Sections/main areas
preg_match_all('/<section[^>]*class=["\']([^"\']*)["\']/', $html, $sections);
echo "\n5. Sections (" . count($sections[0]) . " found):\n";
foreach ($sections[1] as $cls) {
  echo "   - $cls\n";
}

// 6. Navigation items
preg_match_all('/<nav[^>]*>(.*?)<\/nav>/is', $html, $navs);
echo "\n6. Navigation blocks: " . count($navs[0]) . "\n";
foreach ($navs[0] as $i => $nav) {
  preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $nav, $links);
  echo "   Nav $i (" . count($links[0]) . " links):\n";
  foreach ($links[0] as $j => $link) {
    $href = $links[1][$j];
    $text = trim(strip_tags($links[2][$j]));
    if ($text && $j < 10) echo "     - [$text] → $href\n";
  }
}

// 7. Schema.org
$schemaCount = substr_count($html, 'application/ld+json');
echo "\n7. Schema.org blocks: $schemaCount\n";

// 8. CSS files
preg_match_all('/href=["\']([^"\']*\.css[^"\']*)["\']/', $html, $css);
echo "\n8. CSS files: " . count($css[1]) . "\n";
foreach ($css[1] as $f) { echo "   - " . basename(parse_url($f, PHP_URL_PATH)) . "\n"; }

// 9. JS files
preg_match_all('/src=["\']([^"\']*\.js[^"\']*)["\']/', $html, $js);
echo "\n9. JS files: " . count($js[1]) . "\n";

// 10. Forms
preg_match_all('/<form[^>]*id=["\']([^"\']*)["\']/', $html, $forms);
echo "\n10. Forms: " . count($forms[0]) . "\n";
foreach ($forms[1] as $fid) { echo "    - #$fid\n"; }

// 11. Images
preg_match_all('/<img[^>]+src=["\']([^"\']*)["\']/', $html, $imgs);
echo "\n11. Images: " . count($imgs[0]) . "\n";

// 12. Key classes / body classes
preg_match('/<body[^>]+class=["\']([^"\']*)["\']/', $html, $bodyClass);
echo "\n12. Body classes: " . ($bodyClass[1] ?? 'NOT FOUND') . "\n";

echo "\n=== Auditoría Completa ===\n";
