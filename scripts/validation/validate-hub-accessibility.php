<?php

/**
 * @file
 * HUB-ACCESSIBILITY-001: Validate hub accessibility sections coverage.
 *
 * Checks:
 * 1. Count UserProfileSections >= 14 (11 existing + 3 new)
 * 2. Count TenantSettingsSections >= 17 (6 existing + 11 new)
 * 3. All sections have non-empty getId, getLabel, getRoute (TenantSettings)
 *    or getId, getTitle pattern, getLinks (UserProfile)
 * 4. AB Testing section exists in UserProfile
 * 5. Privacy GDPR section exists in TenantSettings
 *
 * Usage: php scripts/validation/validate-hub-accessibility.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$coreModuleDir = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core';

$errors = [];
$warnings = [];

// --- Check 1 & 2: Count section files ---

$tenantSectionsDir = $coreModuleDir . '/src/TenantSettings/Section';
$userProfileSectionsDir = $coreModuleDir . '/src/UserProfile/Section';

if (!is_dir($tenantSectionsDir)) {
  fwrite(STDERR, "ERROR: TenantSettings/Section directory not found: $tenantSectionsDir\n");
  exit(1);
}

if (!is_dir($userProfileSectionsDir)) {
  fwrite(STDERR, "ERROR: UserProfile/Section directory not found: $userProfileSectionsDir\n");
  exit(1);
}

$tenantFiles = glob($tenantSectionsDir . '/*.php');
$profileFiles = glob($userProfileSectionsDir . '/*.php');

$tenantCount = count($tenantFiles);
$profileCount = count($profileFiles);

$minTenantSections = 17;
$minProfileSections = 14;

if ($tenantCount < $minTenantSections) {
  $errors[] = "TenantSettingsSections: found $tenantCount, expected >= $minTenantSections";
}

if ($profileCount < $minProfileSections) {
  $errors[] = "UserProfileSections: found $profileCount, expected >= $minProfileSections";
}

// --- Check 3: All TenantSettings sections have getId, getLabel, getRoute ---

foreach ($tenantFiles as $file) {
  $content = file_get_contents($file);
  $basename = basename($file);

  if (strpos($content, 'function getId()') === FALSE) {
    $errors[] = "$basename: missing getId() method";
  }
  if (strpos($content, 'function getLabel()') === FALSE) {
    $errors[] = "$basename: missing getLabel() method";
  }
  if (strpos($content, 'function getRoute()') === FALSE) {
    $errors[] = "$basename: missing getRoute() method";
  }

  // Check getId returns non-empty string.
  if (preg_match("/function getId\(\).*?return\s+'';/s", $content)) {
    $errors[] = "$basename: getId() returns empty string";
  }
}

// --- Check 3b: All UserProfile sections have getId, getTitle, getLinks ---

foreach ($profileFiles as $file) {
  $content = file_get_contents($file);
  $basename = basename($file);

  if (strpos($content, 'function getId()') === FALSE) {
    $errors[] = "$basename: missing getId() method";
  }
  if (strpos($content, 'function getTitle(') === FALSE) {
    $errors[] = "$basename: missing getTitle() method";
  }
  if (strpos($content, 'function getLinks(') === FALSE) {
    $errors[] = "$basename: missing getLinks() method";
  }
}

// --- Check 4: AB Testing section exists in UserProfile ---

$abTestingFile = $userProfileSectionsDir . '/ABTestingProfileSection.php';
if (!file_exists($abTestingFile)) {
  $errors[] = "ABTestingProfileSection.php not found in UserProfile/Section/";
}
else {
  $content = file_get_contents($abTestingFile);
  if (strpos($content, "'ab_testing'") === FALSE) {
    $errors[] = "ABTestingProfileSection: getId() should return 'ab_testing'";
  }
}

// --- Check 5: Privacy GDPR section exists in TenantSettings ---

$privacyFile = $tenantSectionsDir . '/PrivacyGdprSection.php';
if (!file_exists($privacyFile)) {
  $errors[] = "PrivacyGdprSection.php not found in TenantSettings/Section/";
}
else {
  $content = file_get_contents($privacyFile);
  if (strpos($content, "'privacy_gdpr'") === FALSE) {
    $errors[] = "PrivacyGdprSection: getId() should return 'privacy_gdpr'";
  }
}

// --- Check 6: services.yml registration ---

$servicesFile = $coreModuleDir . '/ecosistema_jaraba_core.services.yml';
if (file_exists($servicesFile)) {
  $servicesContent = file_get_contents($servicesFile);

  $requiredTenantSections = [
    'privacy_gdpr', 'billing_config', 'support_config',
    'mentoring_config', 'insights_hub', 'copilot_config',
    'page_builder_config', 'interactive_content', 'matching_config',
    'review_system', 'andalucia_ei_config',
  ];

  $requiredProfileSections = [
    'ab_testing', 'analytics_pixels', 'notification_prefs',
  ];

  foreach ($requiredTenantSections as $sectionId) {
    $serviceKey = "tenant_settings_section.$sectionId";
    if (strpos($servicesContent, $serviceKey) === FALSE) {
      $warnings[] = "TenantSettings section '$sectionId' not registered in services.yml (key: $serviceKey)";
    }
  }

  foreach ($requiredProfileSections as $sectionId) {
    $serviceKey = "user_profile_section.$sectionId";
    if (strpos($servicesContent, $serviceKey) === FALSE) {
      $warnings[] = "UserProfile section '$sectionId' not registered in services.yml (key: $serviceKey)";
    }
  }
}

// --- Output ---

echo "HUB-ACCESSIBILITY-001: Hub Accessibility Sections Validator\n";
echo str_repeat('=', 60) . "\n";
echo "TenantSettings sections: $tenantCount (min: $minTenantSections)\n";
echo "UserProfile sections:    $profileCount (min: $minProfileSections)\n";
echo str_repeat('-', 60) . "\n";

if (count($errors) === 0 && count($warnings) === 0) {
  echo "PASS: All hub accessibility checks passed.\n";
  exit(0);
}

if (count($warnings) > 0) {
  echo "\nWARNINGS (" . count($warnings) . "):\n";
  foreach ($warnings as $w) {
    echo "  WARN: $w\n";
  }
}

if (count($errors) > 0) {
  echo "\nERRORS (" . count($errors) . "):\n";
  foreach ($errors as $e) {
    echo "  FAIL: $e\n";
  }
  exit(1);
}

// Warnings only — exit 0.
exit(0);
