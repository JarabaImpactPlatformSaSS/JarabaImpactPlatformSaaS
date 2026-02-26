<?php
/**
 * Verify Sprint 9: tracking JS + domain records.
 * Run: lando ssh -c 'php scripts/verify-sprint9.php'
 */
$metasites = [
  'plataformadeecosistemas.jaraba-saas.lndo.site' => 'PED S.L.',
  'jarabaimpact.jaraba-saas.lndo.site' => 'Jaraba Impact',
];

echo "=== Sprint 9 Verification ===\n\n";

foreach ($metasites as $host => $label) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => 'https://localhost/es',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ["Host: $host"],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 10,
  ]);
  $html = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  echo "$label ($host):\n";
  echo "  HTTP: $httpCode | " . strlen($html) . " bytes\n";
  echo "  Tracking JS: " . (stripos($html, 'metasite-tracking') !== false ? 'LOADED' : 'NOT FOUND') . "\n";
  echo "  dataLayer ref: " . (stripos($html, 'dataLayer') !== false ? 'YES' : 'NO') . "\n";
  echo "  Schema.org: " . substr_count($html, 'application/ld+json') . " blocks\n";
  echo "  SCSS theme: " . (substr_count($html, 'ecosistema-jaraba-theme.css') > 0 ? 'YES' : 'NO') . "\n\n";
}

// Check domain records exist in config
$configDir = '/app/config/sync';
$domainRecords = glob($configDir . '/domain.record.*.yml');
echo "DOMAIN RECORDS in config/sync:\n";
foreach ($domainRecords as $dr) {
  echo "  - " . basename($dr) . "\n";
}

echo "\n=== Verification Complete ===\n";
