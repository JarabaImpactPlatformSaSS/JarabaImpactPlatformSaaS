#!/usr/bin/env php
<?php

/**
 * @file
 * AUTONOMOUS-AGENT-HEALTH-001: Validates autonomous AI agent infrastructure.
 *
 * Checks:
 * 1. Supervisor worker configurations exist
 * 2. Redis queue settings configured
 * 3. AutonomousAgentService has all 10 session types
 * 4. ProactiveInsightsService cron hook exists
 * 5. Queue workers exist and are properly annotated
 * 6. Cron retention trigger exists in ecosistema_jaraba_core
 *
 * Usage: php scripts/validation/validate-autonomous-agent-health.php
 */

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "=== AUTONOMOUS-AGENT-HEALTH-001: Autonomous Agent Infrastructure ===\n\n";

// ─────────────────────────────────────────────────────────────────────────
// CHECK 1: Supervisor worker configurations
// ─────────────────────────────────────────────────────────────────────────

$supervisorConf = $projectRoot . '/config/deploy/supervisor-ai-workers.conf';
if (file_exists($supervisorConf)) {
  $content = file_get_contents($supervisorConf);
  $requiredPrograms = [
    'jaraba-ai-a2a',
    'jaraba-ai-insights',
    'jaraba-ai-quality',
    'jaraba-ai-scheduled',
    'jaraba-i18n-translation',
  ];

  $found = 0;
  foreach ($requiredPrograms as $program) {
    if (str_contains($content, $program)) {
      $found++;
    } else {
      $errors[] = "Supervisor: program '{$program}' NOT found in config";
    }
  }
  $passes[] = "Supervisor: {$found}/" . count($requiredPrograms) . " worker programs configured";
} else {
  $warnings[] = "Supervisor config not found at config/deploy/supervisor-ai-workers.conf";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 2: Redis queue settings
// ─────────────────────────────────────────────────────────────────────────

$queueSettings = $projectRoot . '/config/deploy/settings.ai-queues.php';
if (file_exists($queueSettings)) {
  $content = file_get_contents($queueSettings);
  $requiredQueues = [
    'a2a_task_worker',
    'proactive_insight_engine',
    'quality_evaluation',
    'scheduled_agent',
    'jaraba_i18n_canvas_translation',
  ];

  $found = 0;
  foreach ($requiredQueues as $queue) {
    if (str_contains($content, $queue)) {
      $found++;
    } else {
      $errors[] = "Redis queue: '{$queue}' NOT configured in settings.ai-queues.php";
    }
  }
  $passes[] = "Redis queues: {$found}/" . count($requiredQueues) . " configured";
} else {
  $warnings[] = "Redis queue settings not found at config/deploy/settings.ai-queues.php";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 3: AutonomousAgentService session types
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

  $found = 0;
  $missing = [];
  foreach ($requiredTypes as $type) {
    if (str_contains($content, "'" . $type . "'") || str_contains($content, '"' . $type . '"')) {
      $found++;
    } else {
      $missing[] = $type;
    }
  }

  if ($missing === []) {
    $passes[] = "AutonomousAgent: all 10 session types present";
  } else {
    $errors[] = "AutonomousAgent: missing types: " . implode(', ', $missing);
  }
} else {
  $errors[] = "AutonomousAgentService.php NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 4: Queue workers exist
// ─────────────────────────────────────────────────────────────────────────

$requiredWorkers = [
  'A2ATaskWorker' => 'jaraba_ai_agents/src/Plugin/QueueWorker/A2ATaskWorker.php',
  'ProactiveInsightEngineWorker' => 'jaraba_ai_agents/src/Plugin/QueueWorker/ProactiveInsightEngineWorker.php',
  'ScheduledAgentWorker' => 'jaraba_ai_agents/src/Plugin/QueueWorker/ScheduledAgentWorker.php',
  'QualityEvaluationWorker' => 'jaraba_ai_agents/src/Plugin/QueueWorker/QualityEvaluationWorker.php',
  'AutonomousAgentHeartbeatWorker' => 'jaraba_ai_agents/src/Plugin/QueueWorker/AutonomousAgentHeartbeatWorker.php',
  'CanvasTranslationWorker' => 'jaraba_i18n/src/Plugin/QueueWorker/CanvasTranslationWorker.php',
];

$workerCount = 0;
foreach ($requiredWorkers as $name => $path) {
  $fullPath = $projectRoot . '/web/modules/custom/' . $path;
  if (file_exists($fullPath)) {
    $content = file_get_contents($fullPath);
    if (str_contains($content, 'QueueWorkerBase') || str_contains($content, 'QueueWorkerInterface')) {
      $passes[] = "QueueWorker: {$name} exists and extends QueueWorkerBase";
      $workerCount++;
    } else {
      $warnings[] = "QueueWorker: {$name} exists but does not extend QueueWorkerBase";
    }
  } else {
    $errors[] = "QueueWorker MISSING: {$name} at {$path}";
  }
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 5: ProactiveInsightsService exists with cron hook
// ─────────────────────────────────────────────────────────────────────────

$insightsPath = $projectRoot . '/web/modules/custom/jaraba_ai_agents/src/Service/ProactiveInsightsService.php';
if (file_exists($insightsPath)) {
  $content = file_get_contents($insightsPath);
  if (str_contains($content, 'runCron') || str_contains($content, 'cron')) {
    $passes[] = "ProactiveInsights: service exists with cron integration";
  } else {
    $warnings[] = "ProactiveInsights: exists but no cron method found";
  }
} else {
  $errors[] = "ProactiveInsightsService.php NOT FOUND";
}

// Module cron hook for AI agents.
$modulePath = $projectRoot . '/web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.module';
if (file_exists($modulePath)) {
  $content = file_get_contents($modulePath);
  if (str_contains($content, 'jaraba_ai_agents_cron')) {
    $passes[] = "AI Agents: hook_cron implemented";
  } else {
    $warnings[] = "AI Agents: no hook_cron found in module file";
  }
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 6: Cron retention trigger in ecosistema_jaraba_core
// ─────────────────────────────────────────────────────────────────────────

$coreModulePath = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module';
if (file_exists($coreModulePath)) {
  $content = file_get_contents($coreModulePath);
  if (str_contains($content, 'predictive_integration') && str_contains($content, 'triggerRetention')) {
    $passes[] = "Retention cron: PredictiveIntegration + triggerRetention wired in core cron";
  } else {
    if (!str_contains($content, 'predictive_integration')) {
      $errors[] = "Retention cron: predictive_integration NOT referenced in core module cron";
    }
    if (!str_contains($content, 'triggerRetention')) {
      $errors[] = "Retention cron: triggerRetention NOT called in core module cron";
    }
  }
} else {
  $errors[] = "ecosistema_jaraba_core.module NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// CHECK 7: PredictiveIntegrationService bridge
// ─────────────────────────────────────────────────────────────────────────

$predictivePath = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Service/PredictiveIntegrationService.php';
if (file_exists($predictivePath)) {
  $content = file_get_contents($predictivePath);
  $methods = ['getLeadEnrichment', 'getChurnRisk', 'getRevenueForecast', 'detectAnomalies', 'triggerRetention'];
  $found = 0;
  foreach ($methods as $method) {
    if (str_contains($content, $method)) {
      $found++;
    }
  }
  if ($found === count($methods)) {
    $passes[] = "PredictiveIntegration: all 5 bridge methods present";
  } else {
    $errors[] = "PredictiveIntegration: only {$found}/" . count($methods) . " methods found";
  }
} else {
  $errors[] = "PredictiveIntegrationService.php NOT FOUND";
}

// ─────────────────────────────────────────────────────────────────────────
// RESULTS
// ─────────────────────────────────────────────────────────────────────────

echo "--- PASSES (" . count($passes) . ") ---\n";
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

echo "\n=== SCORE: {$passRate}% ({$workerCount} workers, 10 agent types) ===\n";

exit(count($errors) > 0 ? 1 : 0);
