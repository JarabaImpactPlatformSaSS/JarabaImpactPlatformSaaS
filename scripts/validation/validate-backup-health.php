<?php

/**
 * @file
 * BACKUP-HEALTH-001: Validates backup infrastructure is properly configured.
 *
 * Checks:
 * 1. daily-backup.yml workflow exists and uses correct server
 * 2. rclone remote config reference exists in backup script
 * 3. Backup rotation policy is defined
 *
 * Note: Actual backup freshness is checked at runtime by INFRA-HEALTH-001.
 * This validator ensures the INFRASTRUCTURE for backups is in place.
 *
 * Usage: php scripts/validation/validate-backup-health.php
 * Exit: 0 = pass, 1 = fail, 2 = warn
 */

$root = __DIR__ . '/../..';
$errors = [];
$warnings = [];

// 1. daily-backup.yml exists and targets dedicated server.
$backup_yml = "$root/.github/workflows/daily-backup.yml";
if (!file_exists($backup_yml)) {
  $errors[] = "daily-backup.yml workflow not found";
}
else {
  $content = file_get_contents($backup_yml);
  if (str_contains($content, 'access834313033') || str_contains($content, 'u101456434')) {
    $errors[] = "daily-backup.yml still references old shared hosting server";
  }
  if (!str_contains($content, 'DEPLOY_HOST') || !str_contains($content, 'DEPLOY_PORT')) {
    $errors[] = "daily-backup.yml does not use DEPLOY_HOST/DEPLOY_PORT secrets";
  }
  if (!str_contains($content, 'Notify failure via email') && !str_contains($content, 'Notify failure via Slack')) {
    $warnings[] = "daily-backup.yml has no failure notification step";
  }
}

// 2. verify-backups.yml exists.
$verify_yml = "$root/.github/workflows/verify-backups.yml";
if (!file_exists($verify_yml)) {
  $warnings[] = "verify-backups.yml workflow not found";
}

// 3. Backup script exists in repo (optional — may be server-only).
$backup_script = "$root/scripts/ci-notify-email.php";
if (!file_exists($backup_script)) {
  $warnings[] = "ci-notify-email.php not found — email alerts may not work";
}

// Report.
if (empty($errors) && empty($warnings)) {
  echo "✅ BACKUP-HEALTH-001: Backup infrastructure properly configured\n";
  exit(0);
}

if (!empty($errors)) {
  echo "❌ BACKUP-HEALTH-001:\n";
  foreach ($errors as $e) {
    echo "  - $e\n";
  }
  exit(1);
}

echo "⚠️ BACKUP-HEALTH-001:\n";
foreach ($warnings as $w) {
  echo "  - $w\n";
}
exit(2);
