<?php

/**
 * @file
 * EMAIL-SINGLE-FLOW-001 + SYMFONY-MAILER-SKIP-001: Verifica integridad del
 * flujo de registro por email único.
 *
 * Checks:
 * 1. Symfony Mailer policy tiene email_skip_sending para register_no_approval_required
 * 2. jaraba_email_mail() usa Markup::create() para body HTML
 * 3. EmailVerificationService genera URL de verificación
 * 4. EmailVerificationController genera one-time login URL post-verificación
 * 5. MjmlCompilerService extrae body content (no full HTML document)
 *
 * Golden Rule #154: Symfony Mailer config_overrides fuerza notify.*=TRUE.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$checks = 0;

// Check 1: Symfony Mailer policy con email_skip_sending.
$checks++;
$policyFile = $projectRoot . '/config/sync/symfony_mailer.mailer_policy.user.register_no_approval_required.yml';
if (!file_exists($policyFile)) {
  $errors[] = "C1: Falta policy YAML: $policyFile";
}
else {
  $content = file_get_contents($policyFile);
  if (!str_contains($content, 'email_skip_sending')) {
    $errors[] = "C1: Policy register_no_approval_required NO tiene email_skip_sending — Symfony Mailer config_overrides fuerza notify=TRUE, el email duplicado se enviará (SYMFONY-MAILER-SKIP-001)";
  }
}

// Check 2: jaraba_email_mail() usa Markup::create().
$checks++;
$mailModule = $projectRoot . '/web/modules/custom/jaraba_email/jaraba_email.module';
if (file_exists($mailModule)) {
  $content = file_get_contents($mailModule);
  if (!str_contains($content, 'Markup::create')) {
    $errors[] = "C2: jaraba_email_mail() NO usa Markup::create() — el body HTML será escapado por LegacyMailerHelper::formatBody()";
  }
}
else {
  $errors[] = "C2: Falta archivo: $mailModule";
}

// Check 3: EmailVerificationService existe y genera URL.
$checks++;
$evService = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Service/EmailVerificationService.php';
if (file_exists($evService)) {
  $content = file_get_contents($evService);
  if (!str_contains($content, 'sendVerificationEmail')) {
    $errors[] = "C3: EmailVerificationService no tiene sendVerificationEmail()";
  }
}
else {
  $errors[] = "C3: Falta EmailVerificationService";
}

// Check 4: EmailVerificationController genera one-time login URL.
$checks++;
$evController = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/EmailVerificationController.php';
if (file_exists($evController)) {
  $content = file_get_contents($evController);
  if (!str_contains($content, 'user_pass_rehash') && !str_contains($content, 'generateOneTimeLoginUrl')) {
    $errors[] = "C4: EmailVerificationController NO genera one-time login URL tras verificación — el usuario queda sin forma de establecer contraseña (EMAIL-SINGLE-FLOW-001)";
  }
}
else {
  $errors[] = "C4: Falta EmailVerificationController";
}

// Check 5: MjmlCompilerService extrae body content.
$checks++;
$mjmlService = $projectRoot . '/web/modules/custom/jaraba_email/src/Service/MjmlCompilerService.php';
if (file_exists($mjmlService)) {
  $content = file_get_contents($mjmlService);
  if (!str_contains($content, 'extractBodyContent')) {
    $errors[] = "C5: MjmlCompilerService NO tiene extractBodyContent() — doble documento HTML en emails (email-wrap + MJML)";
  }
}
else {
  $errors[] = "C5: Falta MjmlCompilerService";
}

// Reportar.
if (!empty($errors)) {
  echo "EMAIL-SINGLE-FLOW-001: FAIL — " . count($errors) . " error(es) de $checks checks\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  exit(1);
}

echo "EMAIL-SINGLE-FLOW-001: OK — $checks/$checks checks passed\n";
exit(0);
