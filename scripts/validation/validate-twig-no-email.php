<?php

/**
 * @file
 * EMAIL-NOEXPOSE-001: Pre-commit check for hardcoded emails in Twig/JS files.
 *
 * Lightweight validator for lint-staged. Checks ONLY staged files passed as
 * arguments. Detects:
 * - mailto: links with literal emails (not Twig variables)
 * - Hardcoded Jaraba domain emails in text
 *
 * Usage: php scripts/validation/validate-twig-no-email.php [file1] [file2] ...
 * If no arguments, scans all staged Twig files.
 * Exit: 0 = pass, 1 = fail
 */

$files = $argv;
array_shift($files); // Remove script name.

if (empty($files)) {
  // Fallback: get staged .html.twig and .js files.
  exec('git diff --cached --name-only --diff-filter=ACMR -- "*.html.twig" "*.js"', $files);
}

if (empty($files)) {
  exit(0); // Nothing to check.
}

$errors = [];

// Pattern: real Jaraba domain emails (not in Twig comments).
$email_pattern = '/[a-zA-Z0-9._%+-]+@(plataformadeecosistemas|jarabaimpact|jaraba|ecosistemajaraba)\.(com|es|io)/';

// Whitelist paths.
$whitelist_paths = ['/tests/', '/docs/', 'validate-'];

// Whitelist emails (legal/technical).
$whitelist_emails = ['dpo@jarabaimpact.com', 'admin@jaraba.es'];

foreach ($files as $file) {
  if (!file_exists($file)) {
    continue;
  }

  // Skip whitelisted paths.
  $skip = false;
  foreach ($whitelist_paths as $wp) {
    if (str_contains($file, $wp)) {
      $skip = true;
      break;
    }
  }
  if ($skip) {
    continue;
  }

  $content = file_get_contents($file);
  $lines = explode("\n", $content);

  foreach ($lines as $num => $line) {
    $lineNo = $num + 1;

    // Skip Twig comments.
    if (str_contains($line, '{#') || str_contains($line, '#}')) {
      continue;
    }
    // Skip PHP/JS comments.
    if (preg_match('#^\s*(//|/\*|\*)#', $line)) {
      continue;
    }

    // Check 1: mailto: with literal email (not {{ variable }}).
    if (preg_match('/mailto:([^{\s"\']+@[^{\s"\']+)/', $line, $m)) {
      $email = $m[1];
      if (!in_array($email, $whitelist_emails, true) && !str_contains($email, 'example.com')) {
        $errors[] = "$file:$lineNo — mailto: with literal email '$email' (EMAIL-NOEXPOSE-001)";
      }
    }

    // Check 2: Hardcoded Jaraba domain email in content.
    if (preg_match_all($email_pattern, $line, $matches)) {
      foreach ($matches[0] as $email) {
        if (in_array($email, $whitelist_emails, true)) {
          continue;
        }
        $errors[] = "$file:$lineNo — hardcoded email '$email' (EMAIL-NOEXPOSE-001)";
      }
    }

    // Check 3: Hardcoded platform phone (CONTACT-NOHARD-001).
    if (preg_match('/34623174304|\+34\s*623\s*174\s*304/', $line)) {
      $errors[] = "$file:$lineNo — hardcoded platform phone (CONTACT-NOHARD-001)";
    }

    // Check 4: |default('34623174304') pattern.
    if (preg_match("/\\|default\\(['\"]34623174304['\"]\\)/", $line)) {
      $errors[] = "$file:$lineNo — |default('34623174304') pattern (CONTACT-NOHARD-001)";
    }
  }
}

if (!empty($errors)) {
  fwrite(STDERR, "EMAIL-NOEXPOSE-001: Hardcoded emails detected in staged files:\n");
  foreach ($errors as $e) {
    fwrite(STDERR, "  $e\n");
  }
  fwrite(STDERR, "\nFix: Replace with WhatsApp link, theme_get_setting(), or remove.\n");
  fwrite(STDERR, "Whitelist: dpo@jarabaimpact.com (GDPR), admin@jaraba.es (VAPID).\n");
  exit(1);
}

exit(0);
