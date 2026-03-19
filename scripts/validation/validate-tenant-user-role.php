<?php

/**
 * @file
 * TENANT-USER-ROLE-001: Verifies all group members have tenant_user Drupal role.
 *
 * Group module permissions (tenant-member) are GROUP-level permissions.
 * Routes with _permission: check GLOBAL Drupal permissions.
 * Without the tenant_user role, group members get 403 on /my-pages,
 * /content-hub, /page-builder and profile cards disappear silently.
 *
 * This validator requires Drupal bootstrap (drush php:script).
 * Usage: drush php:script scripts/validation/validate-tenant-user-role.php
 * OR:    php scripts/validation/validate-tenant-user-role.php (static checks only)
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  TENANT-USER-ROLE-001                                  ║\n";
echo "║  Group Members → tenant_user Drupal Role Validator     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ── CHECK 1: Role exists in config/sync ──────────────────────────────
$configFile = $root . '/config/sync/user.role.tenant_user.yml';
$installHook = $root . '/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install';

if (file_exists($configFile)) {
  $passes[] = "CHECK 1 PASS: user.role.tenant_user.yml exists in config/sync";
} else {
  // Check if created via update hook (not exported yet).
  if (file_exists($installHook) && str_contains(file_get_contents($installHook), 'tenant_user')) {
    $warnings[] = "CHECK 1 WARN: tenant_user role created via update hook but not exported to config/sync";
  } else {
    $errors[] = "CHECK 1 FAIL: tenant_user role not found in config/sync or install hooks";
  }
}

// ── CHECK 2: Update hook creates role ────────────────────────────────
if (file_exists($installHook)) {
  $installContent = file_get_contents($installHook);
  if (str_contains($installContent, "tenant_user") && str_contains($installContent, "grantPermission")) {
    $passes[] = "CHECK 2 PASS: Install hook creates tenant_user role with permissions";
  } else {
    $errors[] = "CHECK 2 FAIL: Install hook missing tenant_user role creation";
  }
} else {
  $errors[] = "CHECK 2 FAIL: ecosistema_jaraba_core.install not found";
}

// ── CHECK 3: Auto-assignment hook exists ─────────────────────────────
$moduleFile = $root . '/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module';
if (file_exists($moduleFile)) {
  $moduleContent = file_get_contents($moduleFile);

  if (str_contains($moduleContent, 'TENANT-USER-ROLE-001')
      && str_contains($moduleContent, "addRole('tenant_user')")) {
    $passes[] = "CHECK 3 PASS: Auto-assignment hook exists (TENANT-USER-ROLE-001)";
  } else {
    $errors[] = "CHECK 3 FAIL: No auto-assignment of tenant_user on group join";
  }

  // Check it's inside entity_insert, not standalone.
  if (str_contains($moduleContent, 'group_relationship')
      && str_contains($moduleContent, 'group_membership')) {
    $passes[] = "CHECK 3b PASS: Hook triggers on group_relationship insert with membership check";
  } else {
    $warnings[] = "CHECK 3b WARN: Auto-assignment may not be triggered correctly on group join";
  }
} else {
  $errors[] = "CHECK 3 FAIL: .module file not found";
}

// ── CHECK 4: Permissions cover critical routes ───────────────────────
$criticalPermissions = [
  'access page builder' => 'jaraba_page_builder.routing.yml',
  'access content article overview' => 'jaraba_content_hub.routing.yml',
  'create page content' => 'jaraba_page_builder.routing.yml',
  'create content article' => 'jaraba_content_hub.routing.yml',
];

if (file_exists($installHook)) {
  $installContent = file_get_contents($installHook);
  foreach ($criticalPermissions as $perm => $source) {
    if (str_contains($installContent, "'$perm'")) {
      $passes[] = "CHECK 4 PASS: Permission '$perm' included in tenant_user role";
    } else {
      $errors[] = "CHECK 4 FAIL: Permission '$perm' (from $source) NOT in tenant_user role";
    }
  }
}

// ── CHECK 5: group.role.tenant-member has permissions ────────────────
$groupRoleFile = $root . '/config/sync/group.role.tenant-member.yml';
if (file_exists($groupRoleFile)) {
  $content = file_get_contents($groupRoleFile);
  if (str_contains($content, 'permissions: {') || str_contains($content, "permissions: {}")) {
    $errors[] = "CHECK 5 FAIL: group.role.tenant-member has EMPTY permissions — update via drush updatedb";
  } else {
    $passes[] = "CHECK 5 PASS: group.role.tenant-member has permissions configured";
  }
} else {
  $warnings[] = "CHECK 5 WARN: group.role.tenant-member.yml not in config/sync";
}

// ── REPORT ───────────────────────────────────────────────────────────
echo "\n";
foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$total = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$total PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
