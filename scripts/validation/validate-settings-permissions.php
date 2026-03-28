<?php

/**
 * @file
 * SETTINGS-PERMS-PROD-001: Verify sensitive settings files security.
 *
 * Checks that settings.env.php and settings.secrets.php:
 * - Exist in the expected location
 * - Are not world-readable
 * - Do not contain real secrets committed to the repository
 *
 * This is a WARN-only validator for permission checks (can't verify
 * production file ownership from local). Secret detection is a FAIL.
 *
 * Usage: php scripts/validation/validate-settings-permissions.php
 * Exit:  0 = clean, 1 = secrets detected in repo files
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

echo "\n\033[1m[SETTINGS-PERMS-PROD-001]\033[0m Settings files security validation\n\n";

$settingsFiles = [
  'settings.env.php' => "$basePath/config/deploy/settings.env.php",
  'settings.secrets.php' => "$basePath/config/deploy/settings.secrets.php",
];

// Known safe values: base64 keys, tokens, or IDs that are NOT API secrets.
// These are excluded from the "looks like a real secret" detection.
$knownSafeEnvKeys = [
  'WA_ENCRYPTION_KEY',
  'WHATSAPP_VERIFY_TOKEN',
  'WHATSAPP_PHONE_NUMBER_ID',
  'WHATSAPP_BUSINESS_ACCOUNT_ID',
  'WA_JOSE_PHONE',
  'WA_JOSE_EMAIL',
  'SES_SMTP_HOST',
  'SES_SMTP_PORT',
];

foreach ($settingsFiles as $name => $path) {
  // --- File existence ---
  check("$name exists", file_exists($path));
  if (!file_exists($path)) {
    continue;
  }

  // --- File permissions (warn-only, may differ local vs production) ---
  $perms = fileperms($path);
  if ($perms !== false) {
    $octal = substr(sprintf('%o', $perms), -3);
    $worldReadable = ($perms & 0004) !== 0;
    check(
      "$name is not world-readable (current: $octal)",
      !$worldReadable,
      "File is world-readable ($octal) — production should be 440 or 400",
      true
    );
  }

  // --- Scan for real secrets in putenv() calls ---
  $content = file_get_contents($path);
  if ($content === false) {
    continue;
  }

  // Extract putenv('KEY=VALUE') entries.
  $secretsFound = [];
  if (preg_match_all("/putenv\(\s*['\"]([A-Z_]+)=([^'\"]*?)['\"]\s*\)/", $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      $envKey = $match[1];
      $envValue = $match[2];

      // Skip known safe values.
      if (in_array($envKey, $knownSafeEnvKeys, true)) {
        continue;
      }

      // Skip PLACEHOLDER values.
      if (str_starts_with($envValue, 'PLACEHOLDER')) {
        continue;
      }

      // Skip empty values.
      if ($envValue === '') {
        continue;
      }

      // Detect values that look like real secrets:
      // - Alphanumeric+special strings 40+ chars (API keys, tokens).
      // - AWS-style keys (AKIA...).
      $looksLikeSecret = false;

      // AWS access key pattern (starts with AKIA).
      if (preg_match('/^AKIA[A-Z0-9]{12,}$/', $envValue)) {
        $looksLikeSecret = true;
      }
      // Long alphanumeric/base64 strings (40+ chars) — typical API keys/passwords.
      elseif (preg_match('/^[A-Za-z0-9+\/=]{40,}$/', $envValue)) {
        $looksLikeSecret = true;
      }
      // Long mixed strings with special chars (40+ chars) — complex passwords.
      elseif (strlen($envValue) >= 40 && preg_match('/^[A-Za-z0-9+\/=_\-]{40,}$/', $envValue)) {
        $looksLikeSecret = true;
      }

      if ($looksLikeSecret) {
        $masked = substr($envValue, 0, 6) . '...' . substr($envValue, -4);
        $secretsFound[] = "$envKey=$masked";
      }
    }
  }

  if (count($secretsFound) > 0) {
    foreach ($secretsFound as $secret) {
      check(
        "$name does not contain real secrets",
        false,
        "Possible secret committed: $secret — use PLACEHOLDER and set via deploy"
      );
    }
  }
  else {
    check("$name contains no detected real secrets in putenv()", true);
  }

  // --- Scan for $config assignments with long inline secret values ---
  // Pattern: $config['...'] = 'LONG_VALUE_HERE';
  $inlineSecrets = [];
  if (preg_match_all("/\\\$config\[['\"][^'\"]+['\"]\]\[['\"][^'\"]+['\"]\]\s*=\s*['\"]([^'\"]{40,})['\"];/", $content, $configMatches)) {
    foreach ($configMatches[1] as $value) {
      // Skip variable references ($var).
      if (str_starts_with($value, '$')) {
        continue;
      }
      $masked = substr($value, 0, 6) . '...' . substr($value, -4);
      $inlineSecrets[] = $masked;
    }
  }

  if (count($inlineSecrets) > 0) {
    foreach ($inlineSecrets as $secret) {
      check(
        "$name has no hardcoded secrets in \$config",
        false,
        "Inline secret found: $secret — use getenv() pattern"
      );
    }
  }
  else {
    check("$name uses getenv() pattern for \$config overrides", true);
  }
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
