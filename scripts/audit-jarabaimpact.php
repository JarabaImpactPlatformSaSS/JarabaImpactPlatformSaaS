<?php
/**
 * Audit jarabaimpact.com meta-site at /es.
 * Run: lando ssh -c 'php scripts/audit-jarabaimpact.php'
 */
$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => 'https://localhost/es',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_HTTPHEADER => ['Host: jarabaimpact.jaraba-saas.lndo.site'],
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 10,
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== Audit jarabaimpact.com Meta-Site ===\n\n";
echo "HTTP: $httpCode | Bytes: " . strlen($html) . "\n\n";

// Title
preg_match('/<title>(.*?)<\/title>/is', $html, $t);
echo "TITLE: " . (isset($t[1]) ? $t[1] : 'N/A') . "\n\n";

// Meta description
preg_match('/<meta[^>]+name="description"[^>]+content="([^"]*)"/', $html, $md);
echo "META DESC: " . (isset($md[1]) ? $md[1] : 'N/A') . "\n\n";

// H1-H3
preg_match_all('/<h([1-3])[^>]*>(.*?)<\/h\1>/is', $html, $h);
echo "HEADINGS (" . count($h[0]) . "):\n";
for ($i = 0; $i < count($h[0]) && $i < 25; $i++) {
  $txt = trim(preg_replace('/\s+/', ' ', strip_tags($h[2][$i])));
  if (strlen($txt) > 100) $txt = substr($txt, 0, 100) . '...';
  echo "  H" . $h[1][$i] . ": $txt\n";
}

// Sections
preg_match_all('/<section[^>]*class="([^"]*)"/', $html, $s);
echo "\nSECTIONS (" . count($s[1]) . "):\n";
foreach ($s[1] as $c) echo "  - $c\n";

// Body class
preg_match('/<body[^>]+class="([^"]*)"/', $html, $bc);
echo "\nBODY: " . (isset($bc[1]) ? $bc[1] : 'N/A') . "\n";

// GrapesJS / Page Builder
echo "\nGrapesJS: " . (stripos($html, 'gjs') !== false ? 'YES' : 'NO') . "\n";
echo "Page Builder: " . (stripos($html, 'page-builder') !== false ? 'YES' : 'NO') . "\n";
echo "page-content entity: " . (stripos($html, 'page_content') !== false || stripos($html, 'page-content') !== false ? 'YES' : 'NO') . "\n";
echo "meta_site: " . (stripos($html, 'meta-site') !== false ? 'YES' : 'NO') . "\n";

// Schema.org
echo "Schema.org blocks: " . substr_count($html, 'application/ld+json') . "\n";

// Navigation
preg_match_all('/<nav[^>]*>(.*?)<\/nav>/is', $html, $navs);
echo "\nNAVIGATION (" . count($navs[0]) . " blocks):\n";
for ($i = 0; $i < count($navs[0]) && $i < 4; $i++) {
  preg_match_all('/<a[^>]+href="([^"]*)"[^>]*>(.*?)<\/a>/is', $navs[1][$i], $links);
  echo "  Nav $i:\n";
  for ($j = 0; $j < count($links[0]) && $j < 10; $j++) {
    $text = trim(strip_tags($links[2][$j]));
    if ($text) echo "    [$text] -> " . $links[1][$j] . "\n";
  }
}

// Main content
preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $main);
if (!empty($main[1])) {
  $mainText = strip_tags($main[1]);
  $mainText = trim(preg_replace('/\s+/', ' ', $mainText));
  echo "\nMAIN CONTENT (first 1000 chars):\n" . substr($mainText, 0, 1000) . "\n";
}
