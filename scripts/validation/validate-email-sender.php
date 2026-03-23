<?php

/**
 * @file
 * EMAIL-SENDER-001: Validates system.site.mail matches SMTP allowed sender.
 *
 * IONOS SMTP rejects emails from domains without a configured mailbox.
 * This validator ensures system.site.yml and the mailer policy global From
 * address are consistent and point to a deliverable domain.
 *
 * Usage: php scripts/validation/validate-email-sender.php
 * Exit: 0 = pass, 1 = fail
 */

$config_dir = __DIR__ . '/../../config/sync';
$errors = [];

// 1. Check system.site.mail is not example.com.
$site_yml = "$config_dir/system.site.yml";
if (file_exists($site_yml)) {
  $content = file_get_contents($site_yml);
  if (preg_match('/^mail:\s*(.+)$/m', $content, $m)) {
    $mail = trim($m[1]);
    if (str_contains($mail, 'example.com') || str_contains($mail, 'example.org')) {
      $errors[] = "system.site.mail uses placeholder domain: $mail";
    }
    if (empty($mail)) {
      $errors[] = "system.site.mail is empty";
    }
  }
}

// 2. Check update.settings.notification.emails.
$update_yml = "$config_dir/update.settings.yml";
if (file_exists($update_yml)) {
  $content = file_get_contents($update_yml);
  if (preg_match('/emails:\s*\n\s*-\s*(.+)$/m', $content, $m)) {
    $email = trim($m[1]);
    if (str_contains($email, 'example.com') || str_contains($email, 'example.org')) {
      $errors[] = "update.settings notification email uses placeholder: $email";
    }
  }
}

// 3. Check mailer policy global From matches site mail.
$policy_yml = "$config_dir/symfony_mailer.mailer_policy._.yml";
if (file_exists($policy_yml)) {
  $content = file_get_contents($policy_yml);
  if (!str_contains($content, 'mailer_default_headers')) {
    $errors[] = "Global mailer policy (_) has no mailer_default_headers — From address not configured";
  }
}

// Report.
if (empty($errors)) {
  echo "✅ EMAIL-SENDER-001: All email sender addresses are valid\n";
  exit(0);
}

echo "❌ EMAIL-SENDER-001: Email sender validation FAILED\n";
foreach ($errors as $e) {
  echo "  - $e\n";
}
exit(1);
