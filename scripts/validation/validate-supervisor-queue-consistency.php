<?php

/**
 * @file
 * SUPERVISOR-QUEUE-SYNC-001: Ensure Supervisor workers and Redis queue routing are in sync.
 *
 * Every queue in supervisor-ai-workers.conf should have a matching entry in
 * settings.ai-queues.php (otherwise the worker runs but uses database instead
 * of Redis). Conversely, queues in settings.ai-queues.php without a Supervisor
 * worker rely on cron only (warning).
 *
 * Usage: php scripts/validation/validate-supervisor-queue-consistency.php
 * Exit:  0 = clean, 1 = mismatches found (Supervisor without Redis routing)
 */

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$warn = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = '', bool $isWarn = false): void {
  global $pass, $fail, $warn, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  elseif ($isWarn) {
    $warn++;
    echo "  \033[33mWARN\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[1m[SUPERVISOR-QUEUE-SYNC-001]\033[0m Supervisor ↔ Redis queue routing consistency\n\n";

$supervisorFile = "$basePath/config/deploy/supervisor-ai-workers.conf";
$queuesFile = "$basePath/config/deploy/settings.ai-queues.php";

// --- Check files exist ---
check('supervisor-ai-workers.conf exists', file_exists($supervisorFile));
check('settings.ai-queues.php exists', file_exists($queuesFile));

if (!file_exists($supervisorFile) || !file_exists($queuesFile)) {
  echo "\n============================================================\n";
  echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
  echo "============================================================\n";
  exit(1);
}

// --- Parse Supervisor queues ---
// Patterns: `drush queue:run QUEUE_NAME` or queue-worker.sh QUEUE_NAME
$supervisorContent = file_get_contents($supervisorFile);
$supervisorQueues = [];

// Match: drush queue:run QUEUE_NAME
if (preg_match_all('/drush\s+queue:run\s+([a-zA-Z0-9_]+)/', $supervisorContent, $matches)) {
  foreach ($matches[1] as $queue) {
    $supervisorQueues[$queue] = true;
  }
}

// Match: queue-worker.sh QUEUE_NAME
if (preg_match_all('/queue-worker\.sh\s+([a-zA-Z0-9_]+)/', $supervisorContent, $matches)) {
  foreach ($matches[1] as $queue) {
    $supervisorQueues[$queue] = true;
  }
}

// --- Parse settings.ai-queues.php ---
// Pattern: $settings['queue_service_QUEUE_NAME'] = 'queue.redis_reliable';
$queuesContent = file_get_contents($queuesFile);
$redisQueues = [];

if (preg_match_all('/\$settings\s*\[\s*[\'"]queue_service_([a-zA-Z0-9_]+)[\'"]\s*\]/', $queuesContent, $matches)) {
  foreach ($matches[1] as $queue) {
    $redisQueues[$queue] = true;
  }
}

echo "  Supervisor queues: " . count($supervisorQueues) . " (" . implode(', ', array_keys($supervisorQueues)) . ")\n";
echo "  Redis queues: " . count($redisQueues) . " (" . implode(', ', array_keys($redisQueues)) . ")\n\n";

// --- Check: Supervisor queues must have Redis routing ---
foreach ($supervisorQueues as $queue => $_) {
  check(
    "Supervisor queue '$queue' has Redis routing",
    isset($redisQueues[$queue]),
    "Worker processes this queue but it uses database backend (no \$settings['queue_service_$queue'] in settings.ai-queues.php)"
  );
}

// --- Check: Redis queues without Supervisor worker (warn only) ---
foreach ($redisQueues as $queue => $_) {
  if (!isset($supervisorQueues[$queue])) {
    check(
      "Redis queue '$queue' has Supervisor worker",
      false,
      "Queue routed to Redis but no dedicated worker — cron only processing",
      true
    );
  }
  else {
    check("Redis queue '$queue' has Supervisor worker", true);
  }
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
