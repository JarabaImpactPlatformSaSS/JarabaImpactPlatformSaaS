<?php

/**
 * @file
 * EMAIL-SES-TRANSPORT-001: Validates Amazon SES transport module integrity.
 *
 * Verifies that the jaraba_ses_transport module is correctly implemented
 * with all required components for email delivery via Amazon SES SMTP.
 *
 * Architecture: SMTP transport (NOT DSN/API). SES credentials via getenv().
 * Bounce/complaint handling via SNS webhook → EmailSuppressionService.
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
  'config/schema/jaraba_ses_transport.schema.yml',
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

// ─── CHECK 2: SMTP transport config in config/sync ───
$transportConfig = __DIR__ . '/../../config/sync/symfony_mailer.mailer_transport.smtp_ses.yml';
if (file_exists($transportConfig)) {
    $content = file_get_contents($transportConfig);
    if (str_contains($content, "id: smtp_ses") && str_contains($content, 'plugin: smtp')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: Transport config must use id: smtp_ses with plugin: smtp';
    }
} else {
    $errors[] = 'CHECK 2: Transport config symfony_mailer.mailer_transport.smtp_ses.yml missing from config/sync/';
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

// ─── CHECK 4: EmailSuppressionService has required methods ───
$suppressionFile = $moduleBase . '/src/Service/EmailSuppressionService.php';
if (file_exists($suppressionFile)) {
    $content = file_get_contents($suppressionFile);
    $hasMethods = str_contains($content, 'function isSuppressed') &&
        str_contains($content, 'function suppress') &&
        str_contains($content, 'function unsuppress') &&
        str_contains($content, 'function getStats');
    if ($hasMethods) {
        $passed++;
    } else {
        $errors[] = 'CHECK 4: EmailSuppressionService must have isSuppressed(), suppress(), unsuppress(), getStats()';
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

// ─── CHECK 7: SesWebhookController handles SNS message types ───
$controllerFile = $moduleBase . '/src/Controller/SesWebhookController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    $hasTypes = str_contains($content, 'SubscriptionConfirmation') &&
        str_contains($content, 'handleBounce') &&
        str_contains($content, 'handleComplaint') &&
        str_contains($content, 'SNS_TOPIC_PREFIX');
    if ($hasTypes) {
        $passed++;
    } else {
        $errors[] = 'CHECK 7: SesWebhookController must handle SubscriptionConfirmation, Bounce, Complaint with TopicArn validation';
    }
}

// ─── CHECK 8: settings.secrets.php has SES SMTP block ───
$secretsFile = __DIR__ . '/../../config/deploy/settings.secrets.php';
if (file_exists($secretsFile)) {
    $content = file_get_contents($secretsFile);
    if (str_contains($content, 'SES_SMTP_USER') && str_contains($content, 'smtp_ses')) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 8: settings.secrets.php missing SES_SMTP_USER/smtp_ses block (SECRET-MGMT-001).';
    }
}

// ─── CHECK 9: Module enabled in core.extension.yml ───
$extensionFile = __DIR__ . '/../../config/sync/core.extension.yml';
if (file_exists($extensionFile)) {
    $content = file_get_contents($extensionFile);
    if (str_contains($content, 'jaraba_ses_transport:')) {
        $passed++;
    } else {
        $errors[] = 'CHECK 9: jaraba_ses_transport not in core.extension.yml (module not enabled)';
    }
}

// ─── CHECK 10: .env.example documents SES variables ───
$envExample = __DIR__ . '/../../.env.example';
if (file_exists($envExample)) {
    $content = file_get_contents($envExample);
    if (str_contains($content, 'SES_SMTP_HOST') && str_contains($content, 'SES_SMTP_USER')) {
        $passed++;
    } else {
        $warnings[] = 'CHECK 10: .env.example missing SES_SMTP_* variable documentation';
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
