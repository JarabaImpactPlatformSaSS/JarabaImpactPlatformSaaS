<?php
/**
 * Verify Jaraba Impact meta-site elevation.
 * Run: lando ssh -c 'php scripts/verify-ji-elevation.php'
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

echo "=== Verify Jaraba Impact Elevation ===\n\n";
echo "HTTP: $httpCode | Bytes: " . strlen($html) . "\n\n";

echo "1. Theme CSS: " . (substr_count($html, 'ecosistema-jaraba-theme.css') > 0 ? 'YES' : 'NO') . "\n";

$sections = ['ji-hero', 'ji-section', 'ji-cta-block'];
echo "\n2. Sections:\n";
foreach ($sections as $s) {
  echo "   $s: " . substr_count($html, $s) . " matches\n";
}

echo "\n3. Schema.org: " . substr_count($html, 'application/ld+json') . " blocks\n";
echo "4. meta-site-tenant-6: " . (stripos($html, 'meta-site-tenant-6') !== false ? 'YES' : 'NO') . "\n";

preg_match('/<title>(.*?)<\/title>/is', $html, $t);
echo "5. Title: " . (isset($t[1]) ? $t[1] : 'N/A') . "\n";

echo "6. Contact link: " . (stripos($html, '/contacto-institucional') !== false ? 'YES' : 'NO') . "\n";
echo "7. Demo CTA: " . (stripos($html, 'Demo') !== false ? 'YES' : 'NO') . "\n";

echo "\n=== Verification Complete ===\n";
