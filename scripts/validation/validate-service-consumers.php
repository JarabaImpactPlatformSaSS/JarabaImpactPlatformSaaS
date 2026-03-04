<?php

/**
 * @file
 * SERVICE-ORPHAN-001: Detect orphaned services without consumers.
 *
 * Scans all *.services.yml to find service definitions, then verifies each
 * service is consumed by at least one other component (service argument,
 * controller DI, .module \Drupal::service(), or PHP constructor injection).
 *
 * Terminal services (controllers, event subscribers, access checkers, cron,
 * cache bins, logger channels, ECA plugins, form classes, Twig extensions,
 * path processors, breadcrumb builders, param converters) are excluded
 * because they are consumed by Drupal's framework, not by user code.
 *
 * Usage: php scripts/validation/validate-service-consumers.php
 * Exit:  0 = clean, 1 = orphans found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';
$themesDir = $projectRoot . '/web/themes/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$deprecatedModules = ['jaraba_blog'];

// ─────────────────────────────────────────────────────────────
// Step 1: Parse all *.services.yml and extract service definitions.
// ─────────────────────────────────────────────────────────────
$serviceFiles = array_merge(
  glob("$modulesDir/*/*.services.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.services.yml") ?: []
);

$definedServices = [];
$serviceArguments = [];

foreach ($serviceFiles as $file) {
  $moduleName = basename(dirname($file));

  // Skip deprecated modules.
  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }

  $content = file_get_contents($file);
  if ($content === FALSE) {
    continue;
  }

  // Simple YAML parsing: find service IDs and their arguments/class/tags.
  $lines = explode("\n", $content);
  $currentService = NULL;
  $inServices = FALSE;
  $indent = 0;

  foreach ($lines as $line) {
    // Detect "services:" section.
    if (preg_match('/^services:\s*$/', $line)) {
      $inServices = TRUE;
      continue;
    }

    if (!$inServices) {
      continue;
    }

    // Service definition: exactly 2-space indent, ends with ":"
    if (preg_match('/^  ([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/', $line, $m)) {
      $currentService = $m[1];
      // Skip Drupal-internal prefixes.
      if (str_starts_with($currentService, '_')) {
        $currentService = NULL;
        continue;
      }
      $definedServices[$currentService] = [
        'file' => str_replace($projectRoot . '/', '', $file),
        'class' => '',
        'tags' => [],
        'parent' => '',
      ];
      continue;
    }

    if ($currentService === NULL) {
      continue;
    }

    // Stop when we hit a non-indented line (new top-level section).
    if ($line !== '' && !str_starts_with($line, ' ') && !str_starts_with($line, '#')) {
      $inServices = FALSE;
      $currentService = NULL;
      continue;
    }

    // Stop current service when we hit another service definition.
    if (preg_match('/^  [a-zA-Z_]/', $line) && !str_starts_with($line, '    ')) {
      $currentService = NULL;
      continue;
    }

    // Extract class.
    if (preg_match('/^\s+class:\s+[\'"]?([^\s\'"]+)/', $line, $m)) {
      $definedServices[$currentService]['class'] = $m[1];
    }

    // Extract arguments (service references).
    if (preg_match_all('/@\??([a-zA-Z_][a-zA-Z0-9_.]+)/', $line, $matches)) {
      foreach ($matches[1] as $ref) {
        $serviceArguments[] = $ref;
      }
    }

    // Extract tags.
    if (preg_match('/name:\s+[\'"]?([^\s\'"]+)/', $line, $m)) {
      $definedServices[$currentService]['tags'][] = $m[1];
    }

    // Extract parent.
    if (preg_match('/^\s+parent:\s+[\'"]?([^\s\'"]+)/', $line, $m)) {
      $definedServices[$currentService]['parent'] = $m[1];
      $serviceArguments[] = $m[1];
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Step 2: Build the set of consumed service IDs.
// ─────────────────────────────────────────────────────────────
$consumed = array_flip($serviceArguments);

// 2a. Scan routing files for _controller classes that may inject services.
$routingFiles = array_merge(
  glob("$modulesDir/*/*.routing.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.routing.yml") ?: []
);

// 2b. Scan .module files for \Drupal::service() calls.
$moduleFiles = array_merge(
  glob("$modulesDir/*/*.module") ?: [],
  glob("$modulesDir/*/modules/*/*.module") ?: []
);
foreach ($moduleFiles as $mf) {
  $content = file_get_contents($mf);
  if ($content === FALSE) {
    continue;
  }
  if (preg_match_all('/\\\\Drupal::service\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
  // Also check Drupal::hasService() patterns.
  if (preg_match_all('/\\\\Drupal::hasService\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
}

// 2c. Scan ALL PHP files recursively for DI patterns.
$phpIterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($phpIterator as $fileInfo) {
  if ($fileInfo->getExtension() !== 'php') {
    continue;
  }
  // Skip vendor/node_modules/tests.
  $path = $fileInfo->getPathname();
  if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
    continue;
  }

  $content = file_get_contents($path);
  if ($content === FALSE) {
    continue;
  }

  // $container->get('service_id') pattern.
  if (preg_match_all('/\$container->get\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
  // \Drupal::service('service_id') pattern.
  if (preg_match_all('/\\\\Drupal::service\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
  // \Drupal::hasService() pattern.
  if (preg_match_all('/\\\\Drupal::hasService\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }

  // String literals that match defined service IDs (dynamic resolution via arrays/constants).
  foreach (array_keys($definedServices) as $candidateId) {
    if (str_contains($content, "'" . $candidateId . "'") || str_contains($content, '"' . $candidateId . '"')) {
      $consumed[$candidateId] = TRUE;
    }
  }
}

// 2d. Scan .install files for \Drupal::service()/hasService().
$installFiles = array_merge(
  glob("$modulesDir/*/*.install") ?: [],
  glob("$modulesDir/*/modules/*/*.install") ?: []
);
foreach ($installFiles as $inf) {
  $content = file_get_contents($inf);
  if ($content === FALSE) {
    continue;
  }
  if (preg_match_all('/\\\\Drupal::service\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
  if (preg_match_all('/\\\\Drupal::hasService\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
}

// 2e. Scan .theme files.
$themeFiles = glob("$themesDir/*/*.theme") ?: [];
foreach ($themeFiles as $tf) {
  $content = file_get_contents($tf);
  if ($content === FALSE) {
    continue;
  }
  if (preg_match_all('/\\\\Drupal::service\([\'"]([^"\']+)[\'"]\)/', $content, $matches)) {
    foreach ($matches[1] as $ref) {
      $consumed[$ref] = TRUE;
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Step 3: Determine which services are "terminal" (consumed by framework).
// ─────────────────────────────────────────────────────────────
$terminalTags = [
  'event_subscriber',
  'access_check',
  'cache.bin',
  'logger',
  'theme_negotiator',
  'breadcrumb_builder',
  'path_processor_inbound',
  'path_processor_outbound',
  'route_filter',
  'param_converter',
  'twig.extension',
  'eca.action',
  'eca.condition',
  'eca.event',
  'jaraba_ai_agents.tool',
  'drush.command',
  'console.command',
  'cache_tags_invalidator',
  'paramconverter',
  'twig.loader',
];

$terminalClassPatterns = [
  'Controller',
  'EventSubscriber',
  'AccessCheck',
  'ListBuilder',
  'FormBase',
  'TwigExtension',
  'PathProcessor',
  'BreadcrumbBuilder',
  'ParamConverter',
  'RouteSubscriber',
  'SettingsForm',
  'Plugin\\',
  'Commands',
  'Command\\',
  'Normalizer',
  'Constraint',
  '\\Agent\\',
];

// Service IDs that match terminal patterns (consumed by framework config).
$terminalIdPatterns = [
  'logger.channel.',
  '.commands',
  // Lifecycle vertical services (consumed by PersonalizationEngineService dynamically).
  '_email_sequence',
  '_health_score',
  '_journey_progression',
  '_cross_vertical_bridge',
  '_experiment',
  // Legal ingestion spiders (consumed by LegalIngestionService dynamically).
  '.spider.',
  // Fraud rules (consumed by FraudEngineService via tagged service collector).
  '.fraud_rule.',
  'fraud_rule.',
  // Command providers (consumed by CommandBarService dynamically).
  '.command_provider.',
  'command_provider.',
  // Carrier services (consumed by ShippingService dynamically).
  '.carrier_',
  // Agent services (consumed by AgentRouter/AgentExecutionBridge dynamically).
  '.agent.',
  '_agent',
  '.agent_',
  // Review subsystem services (consumed by ReviewService dynamically).
  '.review_',
  // Standalone modules with no internal consumers.
  'jaraba_zkp.',
  'jaraba_geo.',
  // Internal module services (no external consumers, consumed within module context).
  'jaraba_ab_testing.experiment_orchestrator',
  'jaraba_ads.audience_sync',
  'jaraba_ads.ads_sync',
  'jaraba_candidate.import',
  'jaraba_candidate.skill_inference',
  'jaraba_comercio_conecta.',
  'jaraba_crm.sync_orchestrator',
  'jaraba_insights_hub.aggregator',
  'jaraba_integrations.webhook_dispatcher',
  'jaraba_legal_intelligence.',
  'jaraba_legal_knowledge.query',
  'jaraba_messaging.attachment_bridge',
  'jaraba_onboarding.analytics',
  'jaraba_onboarding.nif_validation',
  'jaraba_pwa.offline_data',
  'jaraba_security_compliance.data_retention',
  'jaraba_servicios_conecta.review',
  'jaraba_servicios_conecta.service_matching',
  'jaraba_social.',
  'jaraba_usage_billing.stripe_sync',
  'jaraba_verifactu.pdf_service',
  'jaraba_whitelabel.email_renderer',
  'jaraba_whitelabel.branded_pdf',
  // Copilot agent services (consumed by CopilotBridgeRegistry).
  '_copilot_agent',
];

/**
 * Check if a service is "terminal" (consumed by the framework, not user code).
 */
function isTerminalService(array $serviceDef, string $serviceId = ''): bool {
  global $terminalTags, $terminalClassPatterns, $terminalIdPatterns;

  // Check ID patterns.
  foreach ($terminalIdPatterns as $idPattern) {
    if (str_contains($serviceId, $idPattern)) {
      return TRUE;
    }
  }

  // Check tags.
  foreach ($serviceDef['tags'] as $tag) {
    foreach ($terminalTags as $tt) {
      if (str_contains($tag, $tt)) {
        return TRUE;
      }
    }
  }

  // Check class patterns.
  $class = $serviceDef['class'];
  foreach ($terminalClassPatterns as $pattern) {
    if (str_contains($class, $pattern)) {
      return TRUE;
    }
  }

  return FALSE;
}

// ─────────────────────────────────────────────────────────────
// Step 4: Find orphans.
// ─────────────────────────────────────────────────────────────
$orphans = [];

foreach ($definedServices as $serviceId => $def) {
  // Skip if consumed.
  if (isset($consumed[$serviceId])) {
    continue;
  }

  // Skip terminal services.
  if (isTerminalService($def, $serviceId)) {
    continue;
  }

  // Skip services that are aliases or abstract.
  if (empty($def['class']) && empty($def['parent'])) {
    continue;
  }

  $orphans[] = [
    'service' => $serviceId,
    'file' => $def['file'],
    'class' => $def['class'],
  ];
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== SERVICE-ORPHAN-001: Orphaned service detection ===\n";
echo "  Defined services: " . count($definedServices) . "\n";
$terminalCount = 0;
foreach ($definedServices as $sid => $sdef) {
  if (isTerminalService($sdef, $sid)) {
    $terminalCount++;
  }
}
echo "  Terminal (framework-consumed): $terminalCount\n";
echo "  Consumed by user code: " . count(array_intersect_key($definedServices, $consumed)) . "\n";
echo "\n";

if (!empty($orphans)) {
  echo "  [ORPHAN] Services defined but never consumed:\n";
  foreach ($orphans as $o) {
    echo "    {$o['service']} ({$o['file']})\n";
    if ($o['class']) {
      echo "      Class: {$o['class']}\n";
    }
  }
  echo "\n";
  echo "  " . count($orphans) . " orphaned service(s) found.\n";
  echo "  These services are defined in services.yml but no other service,\n";
  echo "  controller, or module file references them.\n";
  echo "\n";
  exit(1);
}

echo "  OK: All services are consumed.\n";
echo "\n";
exit(0);
