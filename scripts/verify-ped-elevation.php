<?php
/**
 * Final verification of PED meta-site elevation.
 * Run: lando ssh -c 'php scripts/verify-ped-elevation.php'
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
curl_close($ch);

echo "=== Verification PED Meta-Site Elevation ===\n\n";
echo "HTTP: $httpCode | Bytes: " . strlen($html) . "\n\n";

// 1. SCSS loaded?
$cssCount = substr_count($html, 'ecosistema-jaraba-theme.css');
echo "1. Theme CSS loaded: " . ($cssCount > 0 ? 'YES' : 'NO') . " ($cssCount refs)\n";

// 2. PED sections exist?
$sections = ['ped-hero', 'ped-cifras', 'ped-motores', 'ped-audiencia', 'ped-partners'];
echo "\n2. PED Sections:\n";
foreach ($sections as $s) {
  $count = substr_count($html, $s);
  echo "   $s: " . ($count > 0 ? "YES ($count)" : 'MISSING');
  echo "\n";
}

// 3. Schema.org blocks
$schemaCount = substr_count($html, 'application/ld+json');
echo "\n3. Schema.org blocks: $schemaCount\n";

// 4. PED Corporation schema?
$hasCorp = stripos($html, 'Corporation') !== false;
$hasPED = stripos($html, 'Plataforma de Ecosistemas Digitales S.L.') !== false;
echo "4. PED Corporation Schema: " . ($hasCorp ? 'YES' : 'NO') . "\n";
echo "   PED legal name: " . ($hasPED ? 'YES' : 'NO') . "\n";

// 5. SoftwareApplication schema?
$hasSA = stripos($html, 'SoftwareApplication') !== false;
echo "5. SoftwareApplication Schema: " . ($hasSA ? 'YES' : 'NO') . "\n";

// 6. Title
preg_match('/<title>(.*?)<\/title>/is', $html, $t);
echo "\n6. Title: " . (isset($t[1]) ? $t[1] : 'N/A') . "\n";

// 7. meta-site class
$hasMeta = stripos($html, 'meta-site-tenant-7') !== false;
echo "7. Body class meta-site-tenant-7: " . ($hasMeta ? 'YES' : 'NO') . "\n";

// 8. CTA/conversion elements
$hasCTA = stripos($html, 'btn-gold') !== false || stripos($html, 'ped-cta-saas') !== false;
$hasContact = stripos($html, '/contacto') !== false;
$hasImpacto = stripos($html, '/impacto') !== false;
echo "\n8. Conversion elements:\n";
echo "   CTA gold button: " . ($hasCTA ? 'YES' : 'check SCSS ready') . "\n";
echo "   Contact link: " . ($hasContact ? 'YES' : 'NO') . "\n";
echo "   Impacto link: " . ($hasImpacto ? 'YES' : 'NO') . "\n";

echo "\n=== Verification Complete ===\n";
