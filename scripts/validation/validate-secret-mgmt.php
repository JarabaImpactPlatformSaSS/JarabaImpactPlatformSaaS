<?php

/**
 * @file
 * SECRET-MGMT-001: Detects hardcoded secrets in config/sync/.
 *
 * Scans YAML config files for patterns that indicate real API keys,
 * passwords, or tokens stored directly in config (instead of using
 * getenv() via settings.secrets.php).
 *
 * Legitimate patterns (NOT flagged):
 * - Empty string values for user/pass (overridden via settings.secrets.php)
 * - Key module references (key_id, key_provider)
 * - Token placeholders (PLACEHOLDER, TODO, changeme)
 *
 * Usage: php scripts/validation/validate-secret-mgmt.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$configDir = __DIR__ . '/../../config/sync';

// Patterns that indicate a REAL secret (not a placeholder).
$secretPatterns = [
    // AWS keys.
    '/AKIA[A-Z0-9]{16}/' => 'AWS Access Key ID',
    // Stripe live keys.
    '/sk_live_[a-zA-Z0-9]{20,}/' => 'Stripe live secret key',
    '/pk_live_[a-zA-Z0-9]{20,}/' => 'Stripe live public key',
    '/whsec_[a-zA-Z0-9]{20,}/' => 'Stripe webhook secret',
    // Generic API key patterns (long base64/hex strings as values).
    '/sk-ant-api[a-zA-Z0-9_-]{30,}/' => 'Anthropic API key',
    '/sk-proj-[a-zA-Z0-9_-]{30,}/' => 'OpenAI API key',
    '/AIzaSy[a-zA-Z0-9_-]{30,}/' => 'Google API key',
    // SMTP passwords (non-empty in config).
    "/pass: '[^']{8,}'/" => 'SMTP password in config',
    "/pass: \"[^\"]{8,}\"/" => 'SMTP password in config',
];

// Files/patterns to skip (legitimate references).
$skipFiles = [
    'key.key.',           // Key module entities (store references, not values).
    'encrypt.profile.',   // Encryption profiles.
];

// ─── CHECK 1: Scan config/sync for hardcoded secrets ───
$violations = [];
$filesScanned = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($configDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'yml') {
        continue;
    }

    $relativePath = str_replace($configDir . '/', '', $file->getPathname());

    // Skip known-safe files.
    $skip = false;
    foreach ($skipFiles as $pattern) {
        if (str_contains($relativePath, $pattern)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }

    $filesScanned++;
    $content = file_get_contents($file->getPathname());

    foreach ($secretPatterns as $regex => $description) {
        if (preg_match($regex, $content, $matches)) {
            $violations[] = "{$relativePath}: {$description} detected ({$matches[0]})";
        }
    }
}

if (empty($violations)) {
    $passed++;
} else {
    foreach ($violations as $v) {
        $errors[] = "CHECK 1: Hardcoded secret — {$v}";
    }
}

// ─── CHECK 2: Verify settings.secrets.php exists and maps env vars ───
$secretsFile = __DIR__ . '/../../config/deploy/settings.secrets.php';
if (file_exists($secretsFile)) {
    $secretsContent = file_get_contents($secretsFile);
    $requiredEnvVars = ['STRIPE_SECRET_KEY', 'SMTP_USER', 'SES_SMTP_USER', 'RECAPTCHA_SECRET_KEY'];
    $missingVars = [];
    foreach ($requiredEnvVars as $var) {
        if (!str_contains($secretsContent, "getenv('{$var}')")) {
            $missingVars[] = $var;
        }
    }
    if (empty($missingVars)) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: settings.secrets.php missing getenv() for: ' . implode(', ', $missingVars);
    }
} else {
    $errors[] = 'CHECK 2: config/deploy/settings.secrets.php not found';
}

// ─── CHECK 3: Verify .env.example documents all secret env vars ───
$envExample = __DIR__ . '/../../.env.example';
if (file_exists($envExample)) {
    $envContent = file_get_contents($envExample);
    $requiredDocs = ['SES_SMTP_USER', 'STRIPE_SECRET_KEY', 'SMTP_USER'];
    $undocumented = [];
    foreach ($requiredDocs as $var) {
        if (!str_contains($envContent, $var)) {
            $undocumented[] = $var;
        }
    }
    if (empty($undocumented)) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 3: .env.example missing documentation for: ' . implode(', ', $undocumented);
        $passed++; // Warn only.
    }
} else {
    $warnings[] = 'CHECK 3: .env.example not found';
    $passed++;
}

// ─── CHECK 4: SMTP transport configs have empty credentials ───
$transports = ['smtp_ionos', 'smtp_ses'];
$transportClean = true;
foreach ($transports as $transport) {
    $transportFile = $configDir . "/symfony_mailer.mailer_transport.{$transport}.yml";
    if (file_exists($transportFile)) {
        $content = file_get_contents($transportFile);
        // user and pass should be empty strings (overridden by settings.secrets.php).
        if (preg_match("/user: '[^']+'/", $content) || preg_match("/pass: '[^']+'/", $content)) {
            $errors[] = "CHECK 4: {$transport} has non-empty credentials in config/sync (should be empty, overridden via settings.secrets.php)";
            $transportClean = false;
        }
    }
}
if ($transportClean) {
    $passed++;
}

// ─── CHECK 5: No .env files tracked in git ───
$gitTracked = [];
exec('git ls-files .env .env.local .env.production 2>/dev/null', $gitTracked);
// .env.example and .env.ci are OK to track.
$badEnvFiles = array_filter($gitTracked, fn($f) => !str_contains($f, 'example') && !str_contains($f, '.ci'));
if (empty($badEnvFiles)) {
    $passed++;
} else {
    $warnings[] = 'CHECK 5: .env files tracked in git: ' . implode(', ', $badEnvFiles);
    $passed++; // Warn only for now.
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " SECRET-MGMT-001: Secret Management Integrity\n";
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
echo " [{$filesScanned} YAML files scanned]\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
