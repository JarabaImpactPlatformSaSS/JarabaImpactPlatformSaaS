<?php
/**
 * Redis connectivity test script.
 * Run: lando ssh -c 'php /app/scripts/redis-test.php'
 */

echo "=== Redis Diagnostic ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check phpredis extension
echo "1. PhpRedis extension: ";
if (extension_loaded('redis')) {
  echo "LOADED (v" . phpversion('redis') . ")\n";
} else {
  echo "NOT LOADED - This is the problem!\n";
  exit(1);
}

// 2. DNS resolution
echo "2. DNS resolution 'redis': ";
$ip = gethostbyname('redis');
if ($ip === 'redis') {
  echo "FAILED - Cannot resolve hostname 'redis'\n";
  echo "   Hint: The redis container may not be on the same Docker network.\n";
} else {
  echo "OK → $ip\n";
}

// 3. Socket connection
echo "3. TCP connection redis:6379: ";
$timeout = 2;
$fp = @fsockopen('redis', 6379, $errno, $errstr, $timeout);
if ($fp) {
  echo "OK\n";
  fclose($fp);
} else {
  echo "FAILED ($errno: $errstr)\n";
  echo "   The Redis container is not reachable from the app container.\n";
  exit(1);
}

// 4. PhpRedis connection
echo "4. PhpRedis connect: ";
try {
  $r = new Redis();
  $r->connect('redis', 6379, 2);
  echo "OK\n";
  
  echo "5. PING: ";
  $pong = $r->ping();
  echo ($pong ? "PONG ✓" : "NO RESPONSE") . "\n";
  
  echo "6. INFO server version: ";
  $info = $r->info('server');
  echo ($info['redis_version'] ?? 'unknown') . "\n";

  echo "7. Memory used: ";
  $mem = $r->info('memory');
  echo number_format(($mem['used_memory'] ?? 0) / 1024 / 1024, 1) . " MB\n";

  echo "8. Connected clients: ";
  $clients = $r->info('clients');
  echo ($clients['connected_clients'] ?? 'unknown') . "\n";

  echo "9. Uptime: ";
  echo ($info['uptime_in_seconds'] ?? 'unknown') . " seconds\n";

  $r->close();
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}

// 5. Test Drupal cache
echo "\n10. Drupal cache test (set/get): ";
try {
  $r2 = new Redis();
  $r2->connect('redis', 6379, 2);
  $r2->set('__redis_diag_test', 'ok', 10);
  $val = $r2->get('__redis_diag_test');
  echo ($val === 'ok') ? "OK ✓\n" : "MISMATCH\n";
  $r2->del('__redis_diag_test');
  $r2->close();
} catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnosis Complete ===\n";
