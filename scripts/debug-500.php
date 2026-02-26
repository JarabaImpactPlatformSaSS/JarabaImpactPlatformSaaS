<?php
/**
 * Debug HTTP 500 on PED meta-site.
 * Run: lando ssh -c 'php scripts/debug-500.php'
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

echo "HTTP: $httpCode | Bytes: " . strlen($html) . "\n";

if ($httpCode >= 400) {
  // Show the error content
  echo "\nERROR CONTENT:\n";
  $text = strip_tags($html);
  $text = trim(preg_replace('/\s+/', ' ', $text));
  echo substr($text, 0, 1000) . "\n";
  
  // Check for specific error patterns
  if (stripos($html, 'Twig') !== false) echo ">>> TWIG ERROR\n";
  if (stripos($html, 'ServiceNotFoundException') !== false) echo ">>> SERVICE NOT FOUND\n";
  if (stripos($html, 'ParseError') !== false) echo ">>> PHP PARSE ERROR\n";
  if (preg_match('/Exception.*?in\s+(\S+)\s+line\s+(\d+)/s', $html, $m)) {
    echo ">>> Exception in: " . $m[1] . " line " . $m[2] . "\n";
  }
  
  // Also check the default site  
  $ch2 = curl_init();
  curl_setopt_array($ch2, [
    CURLOPT_URL => 'https://localhost/es',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['Host: jaraba-saas.lndo.site'],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
  ]);
  $html2 = curl_exec($ch2);
  $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
  curl_close($ch2);
  echo "\nDefault site: HTTP $httpCode2 | " . strlen($html2) . " bytes\n";
}
