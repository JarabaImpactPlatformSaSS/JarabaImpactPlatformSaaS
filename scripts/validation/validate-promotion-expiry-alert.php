<?php

/**
 * @file
 * PROMOTION-EXPIRY-ALERT-001: Detecta promociones próximas a expirar.
 *
 * Verifica que no hay PromotionConfig con:
 * - status=active Y date_end < NOW + 7 días (a punto de expirar)
 * - status=active Y date_end < NOW (ya expirada pero activa)
 *
 * Complementa hook_requirements en ecosistema_jaraba_core que mostraría
 * el warning en /admin/reports/status.
 *
 * Uso: php scripts/validation/validate-promotion-expiry-alert.php
 */

echo "=== PROMOTION-EXPIRY-ALERT-001: Detección de promociones próximas a expirar ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

// CHECK 1: PromotionConfig config files exist.
$checks++;
$configPath = $modulesPath . '/ecosistema_jaraba_core/config/install';
$configFiles = glob($configPath . '/ecosistema_jaraba_core.promotion_config.*.yml') ?: [];
echo "[INFO] " . count($configFiles) . " config(s) PromotionConfig encontrada(s)\n";

if (count($configFiles) === 0) {
  echo "\n[OK] PROMOTION-EXPIRY-ALERT-001: Sin promociones configuradas.\n";
  exit(0);
}

$now = date('Y-m-d');
$warnDate = date('Y-m-d', strtotime('+7 days'));

foreach ($configFiles as $file) {
  $filename = basename($file);
  $content = file_get_contents($file);

  // Parse status.
  preg_match('/^status:\s*(.+)$/m', $content, $statusMatch);
  $status = trim($statusMatch[1] ?? 'false');
  $isActive = in_array($status, ['true', '1', 'TRUE'], true);

  if (!$isActive) {
    $checks++;
    echo "[SKIP] {$filename}: status=inactive\n";
    continue;
  }

  // Parse date_end.
  preg_match('/^date_end:\s*[\'"]?(\d{4}-\d{2}-\d{2})[\'"]?/m', $content, $endMatch);
  $dateEnd = $endMatch[1] ?? '';

  if ($dateEnd === '') {
    $checks++;
    echo "[PASS] {$filename}: sin fecha fin (permanente)\n";
    continue;
  }

  $checks++;

  // CHECK: Ya expirada.
  if ($dateEnd < $now) {
    $errors[] = "{$filename}: EXPIRADA el {$dateEnd} pero status=active. Desactivar o extender.";
    echo "[FAIL] {$filename}: EXPIRADA el {$dateEnd} (status=active)\n";
    continue;
  }

  // CHECK: Próxima a expirar (< 7 días).
  if ($dateEnd <= $warnDate) {
    $daysLeft = (int) ((strtotime($dateEnd) - strtotime($now)) / 86400);
    $warnings[] = "{$filename}: expira en {$daysLeft} día(s) ({$dateEnd}). Revisar si necesita extensión.";
    echo "[WARN] {$filename}: expira en {$daysLeft} día(s) ({$dateEnd})\n";
    continue;
  }

  // OK: fecha fin lejana.
  $daysLeft = (int) ((strtotime($dateEnd) - strtotime($now)) / 86400);
  echo "[PASS] {$filename}: vigente hasta {$dateEnd} ({$daysLeft} días restantes)\n";
}

// CHECK: hook_requirements implementa alerta de expiración.
$checks++;
$moduleFile = $modulesPath . '/ecosistema_jaraba_core/ecosistema_jaraba_core.install';
if (file_exists($moduleFile)) {
  $installContent = file_get_contents($moduleFile);
  if (strpos($installContent, 'promotion_config') !== false) {
    echo "\n[PASS] hook_update incluye promotion_config\n";
  } else {
    $warnings[] = 'hook_requirements no verifica expiración de promociones en /admin/reports/status';
    echo "\n[WARN] hook_requirements sin verificación de expiración\n";
  }
}

echo "\n=== Resumen ===\n";
echo "Checks: {$checks}\n";
echo "Errores: " . count($errors) . " (promociones expiradas con status=active)\n";
echo "Advertencias: " . count($warnings) . " (próximas a expirar < 7 días)\n";

if (!empty($errors)) {
  echo "\nErrores:\n";
  foreach ($errors as $e) { echo "  - {$e}\n"; }
}
if (!empty($warnings)) {
  echo "\nAdvertencias:\n";
  foreach ($warnings as $w) { echo "  - {$w}\n"; }
}

$exitCode = count($errors) > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " PROMOTION-EXPIRY-ALERT-001: Validación completada.\n";
exit($exitCode);
