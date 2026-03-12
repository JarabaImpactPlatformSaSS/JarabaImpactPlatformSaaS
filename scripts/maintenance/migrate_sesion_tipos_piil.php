<?php

/**
 * @file
 * Sprint 14: Migración de TIPOS_SESION legacy a nuevos valores PIIL.
 *
 * Ejecutar con: drush php:script scripts/maintenance/migrate_sesion_tipos_piil.php
 *
 * Este script:
 * 1. Migra tipo_sesion en SesionProgramadaEi (legacy → PIIL)
 * 2. Migra tipo_actuacion en ActuacionSto (legacy → PIIL)
 * 3. Ejecuta recalculación de indicadores para todos los participantes activos
 *
 * WARNING: Ejecutar DESPUÉS de drush updatedb (hook_update_10023).
 */

declare(strict_types=1);

use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Drupal\jaraba_andalucia_ei\Entity\ActuacionSto;

// === FASE 1: Migrar SesionProgramadaEi tipo_sesion ===
print "=== FASE 1: Migrando tipo_sesion en SesionProgramadaEi ===\n";

$sesionStorage = \Drupal::entityTypeManager()->getStorage('sesion_programada_ei');
$allSesionIds = $sesionStorage->getQuery()
  ->accessCheck(FALSE)
  ->execute();

$migrated = 0;
$warnings = [];

foreach ($sesionStorage->loadMultiple($allSesionIds) as $sesion) {
  $tipoOld = $sesion->get('tipo_sesion')->value;
  $tipoNew = SesionProgramadaEiInterface::TIPOS_SESION_LEGACY_MAP[$tipoOld] ?? NULL;

  if ($tipoNew === NULL) {
    // Already migrated or unknown type.
    continue;
  }

  // Warning: formación session without accion_formativa_id.
  if ($tipoNew === 'sesion_formativa' && empty($sesion->get('accion_formativa_id')->target_id)) {
    $warnings[] = sprintf(
      "Sesión #%d (%s): Sin accion_formativa_id. Tipo cambiado a sesion_formativa pero requiere revisión manual.",
      $sesion->id(),
      $tipoOld
    );
  }

  $sesion->set('tipo_sesion', $tipoNew);
  // fase_piil is computed in preSave().
  try {
    $sesion->save();
    $migrated++;
  }
  catch (\Throwable $e) {
    $warnings[] = sprintf("Sesión #%d: Error al migrar - %s", $sesion->id(), $e->getMessage());
  }
}

print "Sesiones migradas: $migrated\n";

// === FASE 2: Migrar ActuacionSto tipo_actuacion ===
print "\n=== FASE 2: Migrando tipo_actuacion en ActuacionSto ===\n";

$actuacionStorage = \Drupal::entityTypeManager()->getStorage('actuacion_sto');
$allActuacionIds = $actuacionStorage->getQuery()
  ->accessCheck(FALSE)
  ->execute();

$actuacionesMigrated = 0;

foreach ($actuacionStorage->loadMultiple($allActuacionIds) as $actuacion) {
  $tipoOld = $actuacion->get('tipo_actuacion')->value;
  $tipoNew = ActuacionSto::TIPOS_LEGACY_MAP[$tipoOld] ?? NULL;

  if ($tipoNew === NULL) {
    // No migration needed or already migrated.
    // But set fase_piil if missing.
    if ($actuacion->hasField('fase_piil') && empty($actuacion->get('fase_piil')->value)) {
      $fase = ActuacionSto::FASE_POR_TIPO[$tipoOld] ?? 'atencion';
      $actuacion->set('fase_piil', $fase);
      $actuacion->save();
    }
    continue;
  }

  $actuacion->set('tipo_actuacion', $tipoNew);
  $actuacion->set('fase_piil', ActuacionSto::FASE_POR_TIPO[$tipoNew] ?? 'atencion');

  try {
    $actuacion->save();
    $actuacionesMigrated++;
  }
  catch (\Throwable $e) {
    $warnings[] = sprintf("Actuación #%d: Error al migrar - %s", $actuacion->id(), $e->getMessage());
  }
}

print "Actuaciones migradas: $actuacionesMigrated\n";

// === FASE 3: Recalcular indicadores ===
print "\n=== FASE 3: Recalculando indicadores de participantes ===\n";

if (\Drupal::hasService('jaraba_andalucia_ei.actuacion_compute')) {
  $computeService = \Drupal::service('jaraba_andalucia_ei.actuacion_compute');
  $count = $computeService->recalcularPrograma();
  print "Participantes recalculados: $count\n";
}
else {
  print "WARNING: Servicio actuacion_compute no disponible.\n";
}

// === RESULTADO ===
print "\n=== RESULTADO ===\n";
print "Sesiones migradas: $migrated\n";
print "Actuaciones migradas: $actuacionesMigrated\n";

if (!empty($warnings)) {
  print "\n⚠ WARNINGS (" . count($warnings) . "):\n";
  foreach ($warnings as $w) {
    print "  - $w\n";
  }
  print "\nEstas sesiones/actuaciones requieren revisión manual del coordinador.\n";
}
else {
  print "✓ Migración completada sin warnings.\n";
}
