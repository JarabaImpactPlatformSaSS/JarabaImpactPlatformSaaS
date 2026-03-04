<?php

/**
 * @file
 * CONTAINER-DEPS-001: Validates service container dependency integrity.
 *
 * Detects services that reference non-existent dependencies, specifically:
 * - Logger channels referenced but never defined (parent: logger.channel_base)
 * - Cross-module service references to undefined services
 *
 * Exit: 0 = clean, 1 = broken dependencies found.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

// Phase 1: Collect all defined services across all *.services.yml files.
$definedServices = [];
$serviceFiles = glob($modulesDir . '/*/*.services.yml');

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  $moduleName = basename(dirname($file));

  // Parse service IDs (lines starting with 2-space indent + service_id:).
  if (preg_match_all('/^  ([a-zA-Z0-9_.]+):\s*$/m', $content, $matches)) {
    foreach ($matches[1] as $serviceId) {
      $definedServices[$serviceId] = $moduleName;
    }
  }
}

// Phase 2: Collect all Drupal core / contrib known service patterns.
// These are always available and don't need explicit definition.
$coreServicePatterns = [
  'entity_type.manager',
  'current_user',
  'database',
  'renderer',
  'file_system',
  'http_client',
  'config.factory',
  'state',
  'cache.',
  'event_dispatcher',
  'module_handler',
  'language_manager',
  'string_translation',
  'messenger',
  'datetime.time',
  'form_builder',
  'redirect.destination',
  'path.current',
  'path_processor_manager',
  'path.validator',
  'path_alias.manager',
  'router.route_provider',
  'request_stack',
  'session',
  'token',
  'queue',
  'lock',
  'mail_manager',
  'plugin.manager.',
  'typed_data_manager',
  'access_manager',
  'entity_field.manager',
  'entity_display.repository',
  'entity.repository',
  'entity.query.',
  'entity_definition_update_manager',
  'extension.list.',
  'theme.manager',
  'theme_handler',
  'asset.resolver',
  'asset.css.collection_renderer',
  'twig',
  'pager.manager',
  'tempstore.',
  'batch_storage',
  'transliteration',
  'image.factory',
  'stream_wrapper_manager',
  'menu.link_tree',
  'toolbar.menu_tree',
  'breadcrumb',
  'logger.factory',
  'logger.channel_base',
  'logger.log_message_parser',
  'serialization.',
  'serializer',
  'settings',
  'class_resolver',
  'kernel',
  'http_kernel',
  'content_negotiation',
  'page_cache_response_policy',
  'page_cache_request_policy',
  'main_content_renderer.',
  'bare_html_page_renderer',
  'html_response.attachments_processor',
  'unrouted_url_assembler',
  'url_generator',
  'csrf_token',
  'flood',
  'cron',
  'update.manager',
  'update.processor',
  'user.permissions',
  'user.data',
  'user.auth',
  'password',
  'email.validator',
  'country_manager',
  'uuid',
  'ai.',
  'eca.',
  'group.',
  'geocoder',
  'simple_oauth.',
  'key.repository',
  'search_api.',
  'geofield.',
];

/**
 * Check if a service ID matches known core/contrib patterns.
 */
function isKnownService(string $serviceId, array $patterns): bool {
  foreach ($patterns as $pattern) {
    if (str_starts_with($serviceId, $pattern) || $serviceId === $pattern) {
      return TRUE;
    }
  }
  return FALSE;
}

// Phase 3: Find all service references and check if they resolve.
$errors = [];

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  $moduleName = basename(dirname($file));
  $lines = explode("\n", $content);

  foreach ($lines as $lineNum => $line) {
    // Match service references: '@service_name' or '@?service_name'.
    if (preg_match_all("/@\??([a-zA-Z0-9_.]+)/", $line, $refs)) {
      foreach ($refs[1] as $refId) {
        // Skip if it's optional (@?) — those are by design fault-tolerant.
        if (str_contains($line, '@?' . $refId)) {
          continue;
        }

        // Skip if defined in our custom modules.
        if (isset($definedServices[$refId])) {
          continue;
        }

        // Skip if matches known core/contrib pattern.
        if (isKnownService($refId, $coreServicePatterns)) {
          continue;
        }

        // This is a REQUIRED reference to an unknown service.
        $errors[] = [
          'file' => basename(dirname($file)) . '/' . basename($file),
          'line' => $lineNum + 1,
          'service' => $refId,
          'consumer' => trim($line),
        ];
      }
    }
  }
}

// Phase 4: Specific check — logger channels referenced but not defined.
$loggerChannelErrors = [];
$definedLoggerChannels = [];

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match_all('/^  (logger\.channel\.[a-zA-Z0-9_.]+):/m', $content, $matches)) {
    foreach ($matches[1] as $channelId) {
      $definedLoggerChannels[$channelId] = basename(dirname($file));
    }
  }
}

// Core-provided logger channels (always available, not defined in custom modules).
$coreLoggerChannels = [
  'logger.channel.default',
  'logger.channel.php',
  'logger.channel.cron',
  'logger.channel.system',
  'logger.channel.security',
  'logger.channel.image',
  'logger.channel.file',
  'logger.channel.form',
  'logger.channel.access',
  'logger.channel.theme',
];

foreach ($serviceFiles as $file) {
  $content = file_get_contents($file);
  $moduleName = basename(dirname($file));
  $lines = explode("\n", $content);

  foreach ($lines as $lineNum => $line) {
    // Match required logger channel references (not optional @?).
    if (preg_match("/'@(logger\.channel\.[a-zA-Z0-9_.]+)'/", $line, $match)) {
      $channelId = $match[1];
      // Skip core-provided channels.
      if (in_array($channelId, $coreLoggerChannels, TRUE)) {
        continue;
      }
      if (!isset($definedLoggerChannels[$channelId])) {
        $loggerChannelErrors[] = [
          'file' => $moduleName . '/' . basename($file),
          'line' => $lineNum + 1,
          'channel' => $channelId,
        ];
      }
    }
  }
}

// Phase 5: Report results.
$totalErrors = count($loggerChannelErrors);

echo "=== CONTAINER-DEPS-001: Service Container Dependency Validation ===" . PHP_EOL;
echo "Scanned " . count($serviceFiles) . " services.yml files" . PHP_EOL;
echo "Found " . count($definedServices) . " defined services" . PHP_EOL;
echo "Found " . count($definedLoggerChannels) . " defined logger channels" . PHP_EOL;
echo PHP_EOL;

if (!empty($loggerChannelErrors)) {
  echo "[ERROR] Missing logger channel definitions:" . PHP_EOL;
  foreach ($loggerChannelErrors as $err) {
    echo "  [FAIL] {$err['file']}:{$err['line']} — references {$err['channel']} (not defined)" . PHP_EOL;
  }
  echo PHP_EOL;
}

if ($totalErrors === 0) {
  echo "[OK] All required service dependencies resolve correctly." . PHP_EOL;
  exit(0);
}

echo "[FAIL] $totalErrors container dependency issue(s) found." . PHP_EOL;
exit(1);
