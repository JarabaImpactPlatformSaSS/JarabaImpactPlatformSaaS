<?php

/**
 * @file
 * EMAIL-NOEXPOSE-001: Detects exposed email addresses in frontend-facing files.
 *
 * Scans Twig templates, JS files, and controllers for hardcoded emails
 * that could be scraped by bots. Whitelists legal/technical exceptions.
 *
 * Rules enforced:
 * - EMAIL-NOEXPOSE-001: NEVER render emails as plain text or mailto: in frontend
 * - CONTACT-NOHARD-001: NEVER use |default('email@domain.com') for contact emails
 * - CONTACT-SSOT-001: Contact data from theme_settings or getenv()
 *
 * Usage: php scripts/validation/validate-email-exposure.php
 * Exit: 0 = pass, 1 = fail
 */

$base_dir = realpath(__DIR__ . '/../../web');
$errors = [];
$warnings = [];
$checks = 0;

// Whitelist: emails that are allowed (legal/technical requirements).
$whitelist_patterns = [
  // VAPID subject (RFC 8292 requirement, not user-visible).
  'mailto:admin@jaraba.es',
  // DPO email in privacy policy generator (GDPR legal requirement).
  'dpo@jarabaimpact.com',
  // Example/placeholder emails clearly marked as such.
  'email@example.com',
  'noreply@example.com',
  // Test files.
  'test@example.com',
  'test@test.com',
];

// Whitelist paths: files that are allowed to contain emails.
$whitelist_paths = [
  '/tests/',
  '/test_',
  'Test.php',
  'phpunit',
  // Config sync (not frontend-facing).
  '/config/sync/',
  // Docs.
  '/docs/',
  // This validator itself.
  'validate-email-exposure.php',
];

// ── Check 1: mailto: links in Twig templates ──
$checks++;
$twig_files = [];
exec("find $base_dir -name '*.html.twig' -not -path '*/node_modules/*' -not -path '*/tests/*' 2>/dev/null", $twig_files);
$mailto_count = 0;
foreach ($twig_files as $file) {
  $content = file_get_contents($file);
  if (preg_match_all('/mailto:([^\s"\']+)/i', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
      $email = $match[1];
      // Skip Twig variables (mailto:{{ var }}) — these are tenant/user data, not hardcoded.
      if (str_starts_with($email, '{{') || str_starts_with($email, '{%')) {
        continue;
      }
      // Skip mailto:?subject= patterns (sharing links).
      if (str_starts_with($email, '?')) {
        continue;
      }
      if (in_array($email, $whitelist_patterns, true)) {
        continue;
      }
      $rel = str_replace($base_dir, '', $file);
      $mailto_count++;
      $errors[] = "mailto: link found in $rel: $email";
    }
  }
}
if ($mailto_count === 0) {
  echo "  [PASS] No mailto: links in Twig templates\n";
} else {
  echo "  [FAIL] $mailto_count mailto: links found in Twig templates\n";
}

// ── Check 2: Hardcoded real emails in Twig templates ──
$checks++;
$real_email_pattern = '/[a-zA-Z0-9._%+-]+@(plataformadeecosistemas|jarabaimpact|jaraba|ecosistemajaraba)\.(com|es|io)/';
$real_email_count = 0;
foreach ($twig_files as $file) {
  // Skip whitelisted paths.
  $skip = false;
  foreach ($whitelist_paths as $wp) {
    if (str_contains($file, $wp)) {
      $skip = true;
      break;
    }
  }
  if ($skip) continue;

  $content = file_get_contents($file);
  // Skip comments.
  $content_no_comments = preg_replace('/\{#.*?#\}/s', '', $content);
  if (preg_match_all($real_email_pattern, $content_no_comments, $matches)) {
    foreach ($matches[0] as $email) {
      if (in_array($email, $whitelist_patterns, true)) continue;
      $rel = str_replace($base_dir, '', $file);
      $real_email_count++;
      $errors[] = "Hardcoded email in template $rel: $email";
    }
  }
}
if ($real_email_count === 0) {
  echo "  [PASS] No hardcoded Jaraba emails in Twig templates\n";
} else {
  echo "  [FAIL] $real_email_count hardcoded emails in templates\n";
}

// ── Check 3: |default('...@...') pattern for contact emails in Twig ──
$checks++;
$default_email_count = 0;
foreach ($twig_files as $file) {
  $skip = false;
  foreach ($whitelist_paths as $wp) {
    if (str_contains($file, $wp)) { $skip = true; break; }
  }
  if ($skip) continue;

  $content = file_get_contents($file);
  if (preg_match_all("/\|default\(['\"]([^'\"]*@[^'\"]+)['\"]\\)/", $content, $matches)) {
    foreach ($matches[1] as $email) {
      // Allow empty string defaults and example.com.
      if (str_contains($email, 'example.com') || $email === '') continue;
      if (in_array($email, $whitelist_patterns, true)) continue;
      $rel = str_replace($base_dir, '', $file);
      $default_email_count++;
      $errors[] = "CONTACT-NOHARD-001: |default('$email') in $rel";
    }
  }
}
if ($default_email_count === 0) {
  echo "  [PASS] No |default('email@...') patterns in templates\n";
} else {
  echo "  [FAIL] $default_email_count |default('email') patterns found\n";
}

// ── Check 4: Hardcoded emails in JS files ──
$checks++;
$js_files = [];
exec("find $base_dir -name '*.js' -not -path '*/node_modules/*' -not -path '*/vendor/*' -not -path '*/tests/*' -not -name '*.min.js' 2>/dev/null", $js_files);
$js_email_count = 0;
foreach ($js_files as $file) {
  $content = file_get_contents($file);
  if (preg_match_all($real_email_pattern, $content, $matches)) {
    foreach ($matches[0] as $email) {
      if (in_array($email, $whitelist_patterns, true)) continue;
      $rel = str_replace($base_dir, '', $file);
      $js_email_count++;
      $errors[] = "Email in JS file $rel: $email";
    }
  }
}
if ($js_email_count === 0) {
  echo "  [PASS] No hardcoded Jaraba emails in JS files\n";
} else {
  echo "  [FAIL] $js_email_count emails in JS files\n";
}

// ── Check 5: WhatsApp theme settings configured ──
$checks++;
$schema_file = "$base_dir/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml";
if (file_exists($schema_file)) {
  $schema = file_get_contents($schema_file);
  $required_keys = ['whatsapp_enabled', 'whatsapp_number', 'whatsapp_display', 'whatsapp_msg_default', 'contact_schema_email'];
  $missing = [];
  foreach ($required_keys as $key) {
    if (!str_contains($schema, $key . ':')) {
      $missing[] = $key;
    }
  }
  if (empty($missing)) {
    echo "  [PASS] All WhatsApp theme settings defined in schema\n";
  } else {
    $errors[] = "Missing WhatsApp keys in schema.yml: " . implode(', ', $missing);
    echo "  [FAIL] Missing keys: " . implode(', ', $missing) . "\n";
  }
} else {
  $errors[] = "Theme schema file not found";
  echo "  [FAIL] Schema file not found\n";
}

// ── Check 6: WhatsApp FAB library registered ──
$checks++;
$lib_file = "$base_dir/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml";
if (file_exists($lib_file)) {
  $lib = file_get_contents($lib_file);
  if (str_contains($lib, 'whatsapp-fab-contextual:')) {
    echo "  [PASS] WhatsApp FAB library registered\n";
  } else {
    $errors[] = "whatsapp-fab-contextual library not in libraries.yml";
    echo "  [FAIL] WhatsApp FAB library not registered\n";
  }
} else {
  $errors[] = "Libraries file not found";
  echo "  [FAIL] Libraries file not found\n";
}

// ── Check 7: Schema.org templates use contact_schema_email (not contact_email) ──
$checks++;
$schema_templates = [
  "$base_dir/themes/custom/ecosistema_jaraba_theme/templates/partials/_seo-schema.html.twig",
  "$base_dir/themes/custom/ecosistema_jaraba_theme/templates/partials/_ped-schema.html.twig",
  "$base_dir/themes/custom/ecosistema_jaraba_theme/templates/partials/_vertical-schema.html.twig",
];
$schema_issues = 0;
foreach ($schema_templates as $sf) {
  if (!file_exists($sf)) continue;
  $content = file_get_contents($sf);
  // Check for hardcoded emails (not via variables).
  if (preg_match_all('/"email":\s*"([^{][^"]*)"/', $content, $m)) {
    foreach ($m[1] as $email) {
      $rel = str_replace($base_dir, '', $sf);
      $schema_issues++;
      $errors[] = "Hardcoded email in Schema.org template $rel: $email";
    }
  }
}
if ($schema_issues === 0) {
  echo "  [PASS] Schema.org templates use dynamic email variables\n";
} else {
  echo "  [FAIL] $schema_issues hardcoded emails in Schema.org templates\n";
}

// ── Check 8: llms.txt has no emails ──
$checks++;
$llms_controller = "$base_dir/modules/custom/ecosistema_jaraba_core/src/Controller/LlmsTxtController.php";
if (file_exists($llms_controller)) {
  $content = file_get_contents($llms_controller);
  if (preg_match_all($real_email_pattern, $content, $matches)) {
    foreach ($matches[0] as $email) {
      $errors[] = "Email in LlmsTxtController.php: $email";
    }
    echo "  [FAIL] Emails found in llms.txt controller\n";
  } else {
    echo "  [PASS] No emails in llms.txt controller\n";
  }
} else {
  echo "  [WARN] LlmsTxtController not found\n";
}

// ── Check 9: Hardcoded platform phone in Twig templates (CONTACT-NOHARD-001) ──
$checks++;
$phone_pattern = '/34623174304|\+34\s*623\s*174\s*304/';
$phone_count = 0;
foreach ($twig_files as $file) {
  $skip = false;
  foreach ($whitelist_paths as $wp) {
    if (str_contains($file, $wp)) { $skip = true; break; }
  }
  if ($skip) continue;

  $content = file_get_contents($file);
  // Skip comments.
  $content_no_comments = preg_replace('/\{#.*?#\}/s', '', $content);
  if (preg_match_all($phone_pattern, $content_no_comments, $matches)) {
    $rel = str_replace($base_dir, '', $file);
    $phone_count += count($matches[0]);
    $errors[] = "CONTACT-NOHARD-001: Hardcoded phone in $rel (" . count($matches[0]) . " occurrences)";
  }
}
if ($phone_count === 0) {
  echo "  [PASS] No hardcoded platform phone in Twig templates\n";
} else {
  echo "  [FAIL] $phone_count hardcoded phone occurrences in templates\n";
}

// ── Check 10: |default('34623174304') pattern in Twig templates ──
$checks++;
$default_phone_count = 0;
foreach ($twig_files as $file) {
  $skip = false;
  foreach ($whitelist_paths as $wp) {
    if (str_contains($file, $wp)) { $skip = true; break; }
  }
  if ($skip) continue;

  $content = file_get_contents($file);
  if (preg_match_all("/\|default\(['\"]34623174304['\"]\)/", $content, $matches)) {
    $rel = str_replace($base_dir, '', $file);
    $default_phone_count += count($matches[0]);
    $errors[] = "CONTACT-NOHARD-001: |default('34623174304') in $rel";
  }
}
if ($default_phone_count === 0) {
  echo "  [PASS] No |default('34623174304') patterns in templates\n";
} else {
  echo "  [FAIL] $default_phone_count |default phone patterns found\n";
}

// ── Check 11: Schema.org templates — no hardcoded telephone ──
$checks++;
$schema_phone_issues = 0;
foreach ($schema_templates as $sf) {
  if (!file_exists($sf)) continue;
  $content = file_get_contents($sf);
  if (preg_match_all('/"telephone":\s*"\+?34/', $content, $m)) {
    $rel = str_replace($base_dir, '', $sf);
    $schema_phone_issues += count($m[0]);
    $errors[] = "Hardcoded telephone in Schema.org template $rel";
  }
}
if ($schema_phone_issues === 0) {
  echo "  [PASS] Schema.org templates use dynamic telephone variables\n";
} else {
  echo "  [FAIL] $schema_phone_issues hardcoded telephones in Schema.org\n";
}

// ── Summary ──
echo "\n";
echo "validate-email-exposure: $checks checks";
if (count($errors) > 0) {
  echo ", " . count($errors) . " ERRORS:\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  exit(1);
} else {
  echo " — ALL PASS\n";
  exit(0);
}
