<?php

/**
 * @file
 * EMAIL-REPUTATION-SAFEGUARDS-001: Validates email reputation safeguard system.
 *
 * Verifies the 3 email safeguards are correctly implemented:
 * - EMAIL-REPUTATION-MONITOR-001: Cron-based reputation monitoring
 * - EMAIL-FAILOVER-001: Circuit breaker SES→IONOS fallback
 * - EMAIL-SUPPRESSION-AUDIT-001: Weekly suppression audit
 *
 * Usage: php scripts/validation/validate-email-reputation-safeguards.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$moduleBase = __DIR__ . '/../../web/modules/custom/jaraba_ses_transport';

// ─── CHECK 1: EmailReputationMonitorService exists ───
$monitorFile = $moduleBase . '/src/Service/EmailReputationMonitorService.php';
if (file_exists($monitorFile)) {
    $content = file_get_contents($monitorFile);
    if (str_contains($content, 'function checkReputation') &&
        str_contains($content, 'BOUNCE_RATE_WARNING') &&
        str_contains($content, 'COMPLAINT_RATE_ERROR') &&
        str_contains($content, 'getCachedReputation')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 1: EmailReputationMonitorService missing required methods (checkReputation, getCachedReputation) or constants';
    }
} else {
    $errors[] = 'CHECK 1: EmailReputationMonitorService.php not found';
}

// ─── CHECK 2: EmailFailoverService exists with circuit breaker ───
$failoverFile = $moduleBase . '/src/Service/EmailFailoverService.php';
if (file_exists($failoverFile)) {
    $content = file_get_contents($failoverFile);
    if (str_contains($content, 'function getActiveTransport') &&
        str_contains($content, 'function recordSuccess') &&
        str_contains($content, 'function recordFailure') &&
        str_contains($content, 'FAILURE_THRESHOLD') &&
        str_contains($content, 'COOLDOWN_SECONDS')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: EmailFailoverService missing circuit breaker methods (getActiveTransport, recordSuccess, recordFailure)';
    }
} else {
    $errors[] = 'CHECK 2: EmailFailoverService.php not found';
}

// ─── CHECK 3: Services registered in services.yml ───
$servicesFile = $moduleBase . '/jaraba_ses_transport.services.yml';
if (file_exists($servicesFile)) {
    $content = file_get_contents($servicesFile);
    $hasReputation = str_contains($content, 'reputation_monitor');
    $hasFailover = str_contains($content, 'failover');
    if ($hasReputation && $hasFailover) {
        $passed++;
    } else {
        $missing = [];
        if (!$hasReputation) $missing[] = 'reputation_monitor';
        if (!$hasFailover) $missing[] = 'failover';
        $errors[] = 'CHECK 3: Services not registered in services.yml: ' . implode(', ', $missing);
    }
}

// ─── CHECK 4: hook_cron exists with reputation check ───
$moduleFile = $moduleBase . '/jaraba_ses_transport.module';
if (file_exists($moduleFile)) {
    $content = file_get_contents($moduleFile);
    if (str_contains($content, 'function jaraba_ses_transport_cron') &&
        str_contains($content, 'checkReputation')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 4: hook_cron missing or does not call checkReputation()';
    }
}

// ─── CHECK 5: hook_requirements exists with reputation + failover ───
if (file_exists($moduleFile)) {
    $content = file_get_contents($moduleFile);
    if (str_contains($content, 'function jaraba_ses_transport_requirements') &&
        str_contains($content, 'jaraba_ses_reputation') &&
        str_contains($content, 'jaraba_ses_failover') &&
        str_contains($content, 'jaraba_ses_credentials')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 5: hook_requirements missing reputation, failover, or credentials status checks';
    }
}

// ─── CHECK 6: Suppression audit in cron (weekly) ───
if (file_exists($moduleFile)) {
    $content = file_get_contents($moduleFile);
    if (str_contains($content, 'last_suppression_audit') &&
        str_contains($content, 'getStats') &&
        str_contains($content, '604800')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 6: Weekly suppression audit not found in hook_cron (must check last_suppression_audit + getStats + 604800s interval)';
    }
}

// ─── CHECK 7: Circuit breaker has both transport IDs ───
if (file_exists($failoverFile)) {
    $content = file_get_contents($failoverFile);
    if (str_contains($content, "TRANSPORT_SES = 'smtp_ses'") &&
        str_contains($content, "TRANSPORT_IONOS = 'smtp_ionos'")) {
        $passed++;
    } else {
        $errors[] = 'CHECK 7: EmailFailoverService must reference both transport IDs (smtp_ses, smtp_ionos)';
    }
}

// ─── CHECK 8: DI uses StateInterface (not \Drupal::state() in services) ───
if (file_exists($monitorFile) && file_exists($failoverFile)) {
    $monitor = file_get_contents($monitorFile);
    $failover = file_get_contents($failoverFile);
    if (str_contains($monitor, 'StateInterface') && str_contains($failover, 'StateInterface')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 8: Services must use StateInterface DI, not \Drupal::state()';
    }
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " EMAIL-REPUTATION-SAFEGUARDS-001: Reputation Safeguards\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

foreach ($errors as $error) {
    echo "  ✗ FAIL: {$error}\n";
}
foreach ($warnings as $warning) {
    echo "  ⚠ WARN: {$warning}\n";
}

echo "\n  Passed: {$passed}/{$total}";
if (!empty($warnings)) {
    echo " (+" . count($warnings) . " warnings)";
}
echo "\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
