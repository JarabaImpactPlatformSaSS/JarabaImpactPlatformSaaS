<?php

/**
 * @file
 * TESTIMONIAL-VERIFICATION-001: Validates SuccessCase data quality.
 *
 * Verifies that published SuccessCase entities have minimum data quality
 * for credible, verifiable testimonials.
 *
 * Usage: php scripts/validation/validate-testimonial-verification.php
 * Type: warn_check (does not block CI, alerts quality gaps)
 */

$base_path = dirname(__DIR__, 2);

// Paths to check.
$entity_file = $base_path . '/web/modules/custom/jaraba_success_cases/src/Entity/SuccessCase.php';
$seed_file = $base_path . '/scripts/migration/seed-success-cases.php';
$complete_file = $base_path . '/scripts/migration/complete-success-cases-data.php';
$assets_dir = $base_path . '/docs/assets/casos-de-exito';

$checks_passed = 0;
$checks_failed = 0;
$checks_warned = 0;
$total_checks = 5;

echo "TESTIMONIAL-VERIFICATION-001: Validating SuccessCase data quality...\n\n";

// CHECK 1: SuccessCase entity has required fields for verification.
$entity_content = file_get_contents($entity_file);
$required_fields = ['program_name', 'program_funder', 'program_year', 'hero_image', 'quote_short', 'quote_long'];
$missing_fields = [];
foreach ($required_fields as $field) {
  if (strpos($entity_content, "'$field'") === FALSE) {
    $missing_fields[] = $field;
  }
}
if (empty($missing_fields)) {
  echo "  [PASS] CHECK 1: SuccessCase entity has all verification fields\n";
  $checks_passed++;
} else {
  echo "  [FAIL] CHECK 1: SuccessCase entity missing fields: " . implode(', ', $missing_fields) . "\n";
  $checks_failed++;
}

// CHECK 2: Seed script contains real case data (program_name with real program).
$seed_content = file_get_contents($seed_file);
$real_programs = ['Andalucía +ei', 'PIIL'];
$has_real = FALSE;
foreach ($real_programs as $program) {
  if (strpos($seed_content, $program) !== FALSE) {
    $has_real = TRUE;
    break;
  }
}
if ($has_real) {
  echo "  [PASS] CHECK 2: Seed script references real program data\n";
  $checks_passed++;
} else {
  echo "  [WARN] CHECK 2: Seed script does not reference any real program (Andalucía +ei, PIIL)\n";
  $checks_warned++;
}

// CHECK 3: Asset briefs exist for documented participants.
$brief_dirs = glob($assets_dir . '/*/brief.md');
$brief_count = count($brief_dirs);
if ($brief_count >= 3) {
  echo "  [PASS] CHECK 3: $brief_count participant briefs found in docs/assets/casos-de-exito/\n";
  $checks_passed++;
} else {
  echo "  [WARN] CHECK 3: Only $brief_count briefs found (expected >= 3)\n";
  $checks_warned++;
}

// CHECK 4: No hardcoded case study controllers with inline data remain.
$legacy_pattern = '*CaseStudyController.php';
$modules_dir = $base_path . '/web/modules/custom';
$legacy_controllers = [];

$vertical_modules = [
  'jaraba_agroconecta_core', 'jaraba_andalucia_ei', 'jaraba_business_tools',
  'jaraba_candidate', 'jaraba_comercio_conecta', 'jaraba_content_hub',
  'jaraba_lms', 'jaraba_servicios_conecta',
];

foreach ($vertical_modules as $module) {
  $controller_dir = $modules_dir . '/' . $module . '/src/Controller';
  if (is_dir($controller_dir)) {
    $files = glob($controller_dir . '/*CaseStudyController.php');
    foreach ($files as $file) {
      $content = file_get_contents($file);
      // Check if it contains hardcoded case data (arrays with testimonials/metrics).
      if (preg_match('/[\'"]challenge_before[\'"]\s*=>/', $content) ||
          preg_match('/[\'"]quote_short[\'"]\s*=>/', $content)) {
        $legacy_controllers[] = basename(dirname(dirname(dirname($file)))) . '/' . basename($file);
      }
    }
  }
}

if (empty($legacy_controllers)) {
  echo "  [PASS] CHECK 4: No legacy controllers with hardcoded case data\n";
  $checks_passed++;
} else {
  echo "  [FAIL] CHECK 4: Legacy controllers with hardcoded data: " . implode(', ', $legacy_controllers) . "\n";
  $checks_failed++;
}

// CHECK 5: Complete data script has real narrative data (not just generic placeholders).
if (file_exists($complete_file)) {
  $complete_content = file_get_contents($complete_file);
  $has_real_narrative = strpos($complete_content, 'Luis Miguel') !== FALSE
    || strpos($complete_content, 'Marcela Calabia') !== FALSE
    || strpos($complete_content, 'Ángel Martínez') !== FALSE;

  if ($has_real_narrative) {
    echo "  [PASS] CHECK 5: Complete data script contains real participant narratives\n";
    $checks_passed++;
  } else {
    echo "  [WARN] CHECK 5: Complete data script has no real participant names in narratives\n";
    $checks_warned++;
  }
} else {
  echo "  [WARN] CHECK 5: Complete data script not found at $complete_file\n";
  $checks_warned++;
}

// Summary.
echo "\nTESTIMONIAL-VERIFICATION-001: $checks_passed/$total_checks passed";
if ($checks_warned > 0) {
  echo " ($checks_warned warnings)";
}
if ($checks_failed > 0) {
  echo " ($checks_failed failures)";
}
echo "\n";

if ($checks_failed > 0) {
  echo "RESULT: FAIL\n";
  exit(1);
} elseif ($checks_warned > 0) {
  echo "RESULT: WARN — Data quality can be improved\n";
  exit(0);
} else {
  echo "RESULT: PASS\n";
  exit(0);
}
