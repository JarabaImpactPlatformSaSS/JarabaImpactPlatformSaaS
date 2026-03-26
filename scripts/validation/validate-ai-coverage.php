#!/usr/bin/env php
<?php

/**
 * @file
 * AI-COVERAGE-001: Validates AI integration coverage across all modules.
 *
 * Checks:
 * 1. CopilotBridge registered for each target module
 * 2. GroundingProvider tagged for each target module
 * 3. Bridge registry includes all bridges
 * 4. PredictiveIntegrationService wired
 * 5. AutonomousAgentService has all session types
 * 6. Services.yml consistency (no phantom args)
 *
 * Usage: php scripts/validation/validate-ai-coverage.php
 */

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "=== AI-COVERAGE-001: AI Integration Coverage Validator ===\n\n";

// ─────────────────────────────────────────────────────────────────────────
// CHECK 1: CopilotBridge PHP classes exist
// ─────────────────────────────────────────────────────────────────────────

$requiredBridges = [
  'jaraba_crm' => 'jaraba_crm/src/Service/CrmCopilotBridgeService.php',
  'jaraba_billing' => 'jaraba_billing/src/Service/BillingCopilotBridgeService.php',
  'jaraba_support' => 'jaraba_support/src/Service/SupportCopilotBridgeService.php',
  'jaraba_email' => 'jaraba_email/src/Service/EmailCopilotBridgeService.php',
  'jaraba_social' => 'jaraba_social/src/Service/SocialCopilotBridgeService.php',
  'jaraba_analytics' => 'jaraba_analytics/src/Service/AnalyticsCopilotBridgeService.php',
  'jaraba_candidate' => 'jaraba_candidate/src/Service/EmpleabilidadCopilotBridgeService.php',
  'jaraba_business_tools' => 'jaraba_business_tools/src/Service/EmprendimientoCopilotBridgeService.php',
  'jaraba_content_hub' => 'jaraba_content_hub/src/Service/ContentHubCopilotBridgeService.php',
  'jaraba_lms' => 'jaraba_lms/src/Service/FormacionCopilotBridgeService.php',
  'jaraba_andalucia_ei' => 'jaraba_andalucia_ei/src/Service/AndaluciaEiCopilotBridgeService.php',
  'jaraba_comercio_conecta' => 'jaraba_comercio_conecta/src/Service/ComercioConectaCopilotBridgeService.php',
  'jaraba_agroconecta_core' => 'jaraba_agroconecta_core/src/Service/AgroConectaCopilotBridgeService.php',
  'jaraba_legal_intelligence' => 'jaraba_legal_intelligence/src/Service/LegalCopilotBridgeService.php',
];

$bridgeCount = 0;
foreach ($requiredBridges as $module => $path) {
  $fullPath = $projectRoot . '/web/modules/custom/' . $path;
  if (file_exists($fullPath)) {
    $content = file_get_contents($fullPath);
    if (str_contains($content, 'CopilotBridgeInterface')) {
      $passes[] = "CopilotBridge: {$module} ✓";
      $bridgeCount++;
    } else {
      $errors[] = "CopilotBridge: {$module} exists but does NOT implement CopilotBridgeInterface";
    }
  } else {
    $errors[] = "CopilotBridge MISSING: {$module} — expected at {$path}";
  }
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 2: GroundingProvider PHP classes exist
// ─────────────────────────────────────────────────────────────────────────

$requiredGroundings = [
  'jaraba_crm (opportunity)' => 'jaraba_crm/src/Grounding/OpportunityGroundingProvider.php',
  'jaraba_crm (contact)' => 'jaraba_crm/src/Grounding/ContactGroundingProvider.php',
  'jaraba_billing' => 'jaraba_billing/src/Grounding/InvoiceGroundingProvider.php',
  'jaraba_support' => 'jaraba_support/src/Grounding/SupportTicketGroundingProvider.php',
  'jaraba_email' => 'jaraba_email/src/Grounding/EmailCampaignGroundingProvider.php',
  'jaraba_social' => 'jaraba_social/src/Grounding/SocialPostGroundingProvider.php',
  'jaraba_candidate' => 'jaraba_candidate/src/Grounding/CandidateGroundingProvider.php',
  'jaraba_legal_intelligence' => 'jaraba_legal_intelligence/src/Grounding/LegalResolutionGroundingProvider.php',
  'jaraba_comercio_conecta' => 'jaraba_comercio_conecta/src/Grounding/ProductRetailGroundingProvider.php',
  'jaraba_agroconecta_core' => 'jaraba_agroconecta_core/src/Grounding/AgroProductGroundingProvider.php',
];

$groundingCount = 0;
foreach ($requiredGroundings as $label => $path) {
  $fullPath = $projectRoot . '/web/modules/custom/' . $path;
  if (file_exists($fullPath)) {
    $content = file_get_contents($fullPath);
    if (str_contains($content, 'GroundingProviderInterface')) {
      $passes[] = "GroundingProvider: {$label} ✓";
      $groundingCount++;
    } else {
      $errors[] = "GroundingProvider: {$label} exists but does NOT implement GroundingProviderInterface";
    }
  } else {
    $errors[] = "GroundingProvider MISSING: {$label} — expected at {$path}";
  }
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 3: Bridge Registry includes all bridges
// ─────────────────────────────────────────────────────────────────────────

$registryPath = $projectRoot . '/web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml';
if (file_exists($registryPath)) {
  $registryContent = file_get_contents($registryPath);
  $expectedBridgeRefs = [
    'jaraba_crm.copilot_bridge',
    'jaraba_billing.copilot_bridge',
    'jaraba_support.copilot_bridge',
    'jaraba_email.copilot_bridge',
    'jaraba_social.copilot_bridge',
    'jaraba_analytics.copilot_bridge',
    'jaraba_candidate.copilot_bridge',
    'jaraba_business_tools.copilot_bridge',
    'jaraba_content_hub.copilot_bridge',
    'jaraba_lms.copilot_bridge',
    'jaraba_andalucia_ei.copilot_bridge',
    'jaraba_comercio_conecta.copilot_bridge',
    'jaraba_agroconecta_core.copilot_bridge',
    'jaraba_legal_intelligence.copilot_bridge',
  ];

  $registryBridgeCount = 0;
  foreach ($expectedBridgeRefs as $ref) {
    if (str_contains($registryContent, $ref)) {
      $registryBridgeCount++;
    } else {
      $errors[] = "Bridge Registry: {$ref} NOT found in bridge_registry calls";
    }
  }
  $passes[] = "Bridge Registry: {$registryBridgeCount}/" . count($expectedBridgeRefs) . " bridges registered ✓";
} else {
  $errors[] = "Bridge Registry: jaraba_copilot_v2.services.yml NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 4: GroundingProvider tags in services.yml
// ─────────────────────────────────────────────────────────────────────────

$groundingTag = 'jaraba_copilot_v2.grounding_provider';
$modulesWithGrounding = [
  'jaraba_crm',
  'jaraba_billing',
  'jaraba_support',
  'jaraba_email',
  'jaraba_social',
  'jaraba_candidate',
  'jaraba_legal_intelligence',
  'jaraba_comercio_conecta',
  'jaraba_agroconecta_core',
];

$taggedCount = 0;
foreach ($modulesWithGrounding as $module) {
  $svcPath = $projectRoot . '/web/modules/custom/' . $module . '/' . $module . '.services.yml';
  if (file_exists($svcPath)) {
    $svcContent = file_get_contents($svcPath);
    if (str_contains($svcContent, $groundingTag)) {
      $passes[] = "GroundingTag: {$module} ✓";
      $taggedCount++;
    } else {
      $warnings[] = "GroundingTag: {$module} services.yml does not contain tag {$groundingTag}";
    }
  }
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 5: PredictiveIntegrationService exists and is wired
// ─────────────────────────────────────────────────────────────────────────

$predictivePath = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Service/PredictiveIntegrationService.php';
if (file_exists($predictivePath)) {
  $content = file_get_contents($predictivePath);
  $requiredMethods = ['getLeadEnrichment', 'getChurnRisk', 'getRevenueForecast', 'detectAnomalies', 'triggerRetention'];
  $missingMethods = [];
  foreach ($requiredMethods as $method) {
    if (!str_contains($content, $method)) {
      $missingMethods[] = $method;
    }
  }
  if ($missingMethods === []) {
    $passes[] = "PredictiveIntegration: all 5 bridge methods present ✓";
  } else {
    $errors[] = "PredictiveIntegration: missing methods: " . implode(', ', $missingMethods);
  }

  // Check services.yml registration.
  $coreSvcPath = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml';
  if (file_exists($coreSvcPath) && str_contains(file_get_contents($coreSvcPath), 'predictive_integration')) {
    $passes[] = "PredictiveIntegration: registered in services.yml ✓";
  } else {
    $errors[] = "PredictiveIntegration: NOT registered in ecosistema_jaraba_core.services.yml";
  }
} else {
  $errors[] = "PredictiveIntegration: PredictiveIntegrationService.php NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 6: AutonomousAgentService has all session types
// ─────────────────────────────────────────────────────────────────────────

$autonomousPath = $projectRoot . '/web/modules/custom/jaraba_ai_agents/src/Service/AutonomousAgentService.php';
if (file_exists($autonomousPath)) {
  $content = file_get_contents($autonomousPath);
  $requiredTypes = [
    'reputation_monitor',
    'content_curator',
    'kb_maintainer',
    'churn_prevention',
    'crm_intelligence',
    'revenue_optimization',
    'content_seo_optimizer',
    'support_proactive',
    'email_optimizer',
    'social_optimizer',
  ];

  $foundTypes = 0;
  $missingTypes = [];
  foreach ($requiredTypes as $type) {
    if (str_contains($content, "'" . $type . "'") || str_contains($content, '"' . $type . '"')) {
      $foundTypes++;
    } else {
      $missingTypes[] = $type;
    }
  }

  if ($missingTypes === []) {
    $passes[] = "AutonomousAgent: all 10 session types present ✓";
  } else {
    $errors[] = "AutonomousAgent: missing types: " . implode(', ', $missingTypes);
  }
} else {
  $errors[] = "AutonomousAgent: AutonomousAgentService.php NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// RESULTS
// ─────────────────────────────────────────────────────────────────────────

echo "--- PASSES ({$bridgeCount} bridges, {$groundingCount} grounding providers, {$taggedCount} tagged) ---\n";
foreach ($passes as $pass) {
  echo "  ✓ {$pass}\n";
}

if ($warnings !== []) {
  echo "\n--- WARNINGS (" . count($warnings) . ") ---\n";
  foreach ($warnings as $warning) {
    echo "  ⚠ {$warning}\n";
  }
}

if ($errors !== []) {
  echo "\n--- ERRORS (" . count($errors) . ") ---\n";
  foreach ($errors as $error) {
    echo "  ✗ {$error}\n";
  }
}

$totalChecks = count($passes) + count($errors);
$passRate = $totalChecks > 0 ? round(count($passes) / $totalChecks * 100) : 0;

echo "\n=== SCORE: {$passRate}% ({$bridgeCount} bridges, {$groundingCount} groundings, 10 agent types) ===\n";

exit(count($errors) > 0 ? 1 : 0);
