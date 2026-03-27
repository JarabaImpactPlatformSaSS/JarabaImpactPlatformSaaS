<?php

/**
 * @file
 * EMAIL-SES-TRANSPORT-001: Validates Amazon SES transport module integrity.
 *
 * Verifies that the jaraba_ses_transport module is correctly implemented
 * with all required components for email delivery via Amazon SES.
 *
 * Usage: php scripts/validation/validate-ses-transport-integrity.php
 * Exit code: 0 = all checks pass, 1 = failures found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$moduleBase = __DIR__ . '/../../web/modules/custom/jaraba_ses_transport';

// ─── CHECK 1: Module structure ───
$requiredFiles = [
  'jaraba_ses_transport.info.yml',
  'jaraba_ses_transport.module',
  'jaraba_ses_transport.install',
  'jaraba_ses_transport.services.yml',
  'jaraba_ses_transport.routing.yml',
  'src/Service/EmailSuppressionService.php',
  'src/Controller/SesWebhookController.php',
  'config/install/symfony_mailer.mailer_transport.ses.yml',
];
$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($moduleBase . '/' . $file)) {
        $missingFiles[] = $file;
    }
}
if (empty($missingFiles)) {
    $passed++;
} else {
    $errors[] = 'CHECK 1: Missing module files: ' . implode(', ', $missingFiles);
}

// ─── CHECK 2: Transport config uses DSN plugin ───
$transportConfig = $moduleBase . '/config/install/symfony_mailer.mailer_transport.ses.yml';
if (file_exists($transportConfig)) {
    $content = file_get_contents($transportConfig);
    if (str_contains($content, 'plugin: dsn') && str_contains($content, 'ses+api://')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: Transport config must use plugin: dsn with ses+api:// DSN';
    }
} else {
    $errors[] = 'CHECK 2: Transport config file missing';
}

// ─── CHECK 3: Webhook route exists ───
$routingFile = $moduleBase . '/jaraba_ses_transport.routing.yml';
if (file_exists($routingFile)) {
    $routingContent = file_get_contents($routingFile);
    if (str_contains($routingContent, '/api/v1/ses/webhook') && str_contains($routingContent, 'SesWebhookController')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 3: Webhook route must point to SesWebhookController at /api/v1/ses/webhook';
    }
}

// ─── CHECK 4: EmailSuppressionService has isSuppressed + suppress ───
$suppressionFile = $moduleBase . '/src/Service/EmailSuppressionService.php';
if (file_exists($suppressionFile)) {
    $content = file_get_contents($suppressionFile);
    $hasMethods = str_contains($content, 'function isSuppressed') &&
        str_contains($content, 'function suppress') &&
        str_contains($content, 'function unsuppress');
    if ($hasMethods) {
        $passed++;
    } else {
        $errors[] = 'CHECK 4: EmailSuppressionService must have isSuppressed(), suppress(), unsuppress()';
    }
}

// ─── CHECK 5: hook_mail_alter checks suppression ───
$moduleFile = $moduleBase . '/jaraba_ses_transport.module';
if (file_exists($moduleFile)) {
    $content = file_get_contents($moduleFile);
    if (str_contains($content, 'mail_alter') && str_contains($content, 'isSuppressed')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 5: hook_mail_alter must check isSuppressed() before sending';
    }
}

// ─── CHECK 6: schema.sql for email_suppression table ───
$installFile = $moduleBase . '/jaraba_ses_transport.install';
if (file_exists($installFile)) {
    $content = file_get_contents($installFile);
    if (str_contains($content, 'email_suppression') && str_contains($content, 'hook_schema')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 6: Install file must define email_suppression table in hook_schema()';
    }
}

// ─── CHECK 7: symfony/amazon-mailer in composer ───
$composerLock = __DIR__ . '/../../composer.lock';
if (file_exists($composerLock)) {
    $lockContent = file_get_contents($composerLock);
    if (str_contains($lockContent, 'symfony/amazon-mailer')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 7: symfony/amazon-mailer not in composer.lock. Run: composer require symfony/amazon-mailer';
    }
}

// ─── CHECK 8: settings.secrets.php has SES block ───
$secretsFile = __DIR__ . '/../../config/deploy/settings.secrets.php';
if (file_exists($secretsFile)) {
    $content = file_get_contents($secretsFile);
    if (str_contains($content, 'AWS_SES_ACCESS_KEY') && str_contains($content, 'ses+api://')) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 8: settings.secrets.php missing AWS_SES block. User must add manually (protected file).';
    }
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " EMAIL-SES-TRANSPORT-001: Amazon SES Transport Integrity\n";
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
