<?php

/**
 * @file
 * AUTH-FLOW-E2E-001: Verifica la integridad del flujo
 * registro -> verificacion -> login.
 *
 * Checks:
 * C1: Route ecosistema_jaraba_core.email_verify exists with _access: TRUE
 * C2: EmailVerificationController::verify() method exists
 * C3: EmailVerificationController has generateOneTimeLoginUrl() method
 * C4: user.reset.login route parameters (uid, timestamp, hash)
 * C5: EmailVerificationService::sendVerificationEmail() calls mailManager->mail()
 * C6: hook_user_insert calls email_verification service
 * C7: Symfony Mailer policy has email_skip_sending for register_no_approval_required
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$checks = 0;
$coreModule = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core';

// C1: Route exists with _access: TRUE.
$checks++;
$routingFile = $coreModule . '/ecosistema_jaraba_core.routing.yml';
if (!file_exists($routingFile)) {
  $errors[] = "C1: Routing file not found: $routingFile";
}
else {
  $routingContent = file_get_contents($routingFile);
  if (!str_contains($routingContent, 'ecosistema_jaraba_core.email_verify')) {
    $errors[] = 'C1: Route ecosistema_jaraba_core.email_verify not found in routing.yml';
  }
  else {
    // Extract the route block to check _access: TRUE.
    if (preg_match('/ecosistema_jaraba_core\.email_verify:.*?(?=^\S|\z)/ms', $routingContent, $routeMatch)) {
      $routeBlock = $routeMatch[0];
      if (!preg_match("/_access:\s*['\"]?TRUE['\"]?/", $routeBlock)) {
        $errors[] = "C1: Route email_verify does not have _access: 'TRUE' — anonymous users cannot verify their email";
      }
    }
  }
}

// C2: EmailVerificationController::verify() method exists.
$checks++;
$controllerFile = $coreModule . '/src/Controller/EmailVerificationController.php';
if (!file_exists($controllerFile)) {
  $errors[] = "C2: EmailVerificationController.php not found: $controllerFile";
}
else {
  $controllerContent = file_get_contents($controllerFile);
  if (!str_contains($controllerContent, 'function verify(')) {
    $errors[] = 'C2: EmailVerificationController::verify() method not found';
  }
}

// C3: EmailVerificationController has generateOneTimeLoginUrl() method.
$checks++;
if (isset($controllerContent)) {
  if (!str_contains($controllerContent, 'function generateOneTimeLoginUrl(')) {
    $errors[] = 'C3: EmailVerificationController::generateOneTimeLoginUrl() not found — post-verify login redirect will fail';
  }
}
else {
  $errors[] = 'C3: Cannot check — controller file not loaded';
}

// C4: user.reset.login route parameters (uid, timestamp, hash) used correctly.
$checks++;
if (isset($controllerContent)) {
  if (!str_contains($controllerContent, 'user.reset.login')) {
    $errors[] = 'C4: Route user.reset.login not referenced in EmailVerificationController — one-time login URL will not work';
  }
  else {
    $missingParams = [];
    foreach (['uid', 'timestamp', 'hash'] as $param) {
      if (!str_contains($controllerContent, "'$param'")) {
        $missingParams[] = $param;
      }
    }
    if (!empty($missingParams)) {
      $errors[] = 'C4: user.reset.login missing parameters: ' . implode(', ', $missingParams);
    }
  }
}

// C5: EmailVerificationService::sendVerificationEmail() calls mailManager->mail().
$checks++;
$serviceFile = $coreModule . '/src/Service/EmailVerificationService.php';
if (!file_exists($serviceFile)) {
  $errors[] = "C5: EmailVerificationService.php not found: $serviceFile";
}
else {
  $serviceContent = file_get_contents($serviceFile);
  if (!str_contains($serviceContent, 'function sendVerificationEmail(')) {
    $errors[] = 'C5: sendVerificationEmail() method not found in EmailVerificationService';
  }
  elseif (!str_contains($serviceContent, 'mailManager') || !str_contains($serviceContent, '->mail(')) {
    $errors[] = 'C5: sendVerificationEmail() does not call mailManager->mail() — verification emails will not be sent';
  }
}

// C6: hook_user_insert calls email_verification service.
$checks++;
$moduleFile = $coreModule . '/ecosistema_jaraba_core.module';
if (!file_exists($moduleFile)) {
  $errors[] = "C6: Module file not found: $moduleFile";
}
else {
  $moduleContent = file_get_contents($moduleFile);
  if (!str_contains($moduleContent, 'function ecosistema_jaraba_core_user_insert(')) {
    $errors[] = 'C6: hook_user_insert not found in ecosistema_jaraba_core.module';
  }
  elseif (!str_contains($moduleContent, 'email_verification')) {
    $errors[] = 'C6: hook_user_insert does not reference email_verification service — new users will not receive verification email';
  }
}

// C7: Symfony Mailer policy has email_skip_sending for register_no_approval_required.
$checks++;
$policyFile = $projectRoot . '/config/sync/symfony_mailer.mailer_policy.user.register_no_approval_required.yml';
if (!file_exists($policyFile)) {
  $errors[] = "C7: Symfony Mailer policy not found: $policyFile — Drupal core will send duplicate registration email";
}
else {
  $policyContent = file_get_contents($policyFile);
  if (!str_contains($policyContent, 'email_skip_sending')) {
    $errors[] = 'C7: Policy register_no_approval_required does not have email_skip_sending — duplicate registration email will be sent alongside verification email';
  }
}

// Report.
if (empty($errors)) {
  echo "AUTH-FLOW-E2E-001: OK ($checks checks passed) — register->verify->login flow correctly wired\n";
  exit(0);
}

echo "AUTH-FLOW-E2E-001: FAIL\n";
foreach ($errors as $error) {
  echo "  - $error\n";
}
exit(1);
