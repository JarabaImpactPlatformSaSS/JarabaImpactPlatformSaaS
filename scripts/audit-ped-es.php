<?php
/**
 * Audit PED meta-site at /es path.
 * Run: lando ssh -c 'php scripts/audit-ped-es.php'
 */
$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => 'https://localhost/es',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_HTTPHEADER => ['Host: plataformadeecosistemas.jaraba-saas.lndo.site'],
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 10,
]);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "HTTP: $httpCode | Final: $finalUrl | Bytes: " . strlen($html) . "\n\n";

// Title
preg_match('/<title>(.*?)<\/title>/is', $html, $t);
echo "TITLE: " . (isset($t[1]) ? $t[1] : 'N/A') . "\n\n";

// H1-H3
preg_match_all('/<h([1-3])[^>]*>(.*?)<\/h\1>/is', $html, $h);
echo "HEADINGS:\n";
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

// GrapesJS check
echo "\nGrapesJS ref: " . (stripos($html, 'gjs') !== false ? 'YES' : 'NO') . "\n";
echo "Page Builder: " . (stripos($html, 'page-builder') !== false ? 'YES' : 'NO') . "\n";
echo "page-content entity: " . (stripos($html, 'page_content') !== false || stripos($html, 'page-content') !== false ? 'YES' : 'NO') . "\n";

// Main content (text only, first 800 chars)
preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $main);
if (!empty($main[1])) {
  $mainText = strip_tags($main[1]);
  $mainText = trim(preg_replace('/\s+/', ' ', $mainText));
  echo "\nMAIN CONTENT (first 800 chars):\n" . substr($mainText, 0, 800) . "\n";
}

// Navigation
preg_match_all('/<nav[^>]*>(.*?)<\/nav>/is', $html, $navs);
echo "\nNAVIGATION (" . count($navs[0]) . " blocks):\n";
for ($i = 0; $i < count($navs[0]) && $i < 6; $i++) {
  preg_match_all('/<a[^>]+href="([^"]*)"[^>]*>(.*?)<\/a>/is', $navs[1][$i], $links);
  echo "  Nav $i:\n";
  for ($j = 0; $j < count($links[0]) && $j < 8; $j++) {
    $text = trim(strip_tags($links[2][$j]));
    if ($text) echo "    [$text] -> " . $links[1][$j] . "\n";
  }
}

// Also check the /es comparison: same as / or different?
$ch2 = curl_init();
curl_setopt_array($ch2, [
  CURLOPT_URL => 'https://localhost/',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_HTTPHEADER => ['Host: plataformadeecosistemas.jaraba-saas.lndo.site'],
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 10,
]);
$htmlRoot = curl_exec($ch2);
curl_close($ch2);

echo "\nCOMPARISON: / (" . strlen($htmlRoot) . " bytes) vs /es (" . strlen($html) . " bytes) => " .
  (strlen($htmlRoot) === strlen($html) ? "SAME SIZE" : "DIFFERENT") . "\n";

preg_match('/<title>(.*?)<\/title>/is', $htmlRoot, $rootTitle);
echo "  / title: " . (isset($rootTitle[1]) ? $rootTitle[1] : 'N/A') . "\n";
echo "  /es title: " . (isset($t[1]) ? $t[1] : 'N/A') . "\n";
