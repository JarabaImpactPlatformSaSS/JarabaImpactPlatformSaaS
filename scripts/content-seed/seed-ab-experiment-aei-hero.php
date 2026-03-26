<?php

/**
 * @file
 * Content seed: ABExperiment + 3 ABVariant para el CTA hero de reclutamiento.
 *
 * Idempotente: busca por machine_name antes de crear.
 * Ejecutar: drush scr scripts/content-seed/seed-ab-experiment-aei-hero.php
 *
 * CONTENT-SEED-PIPELINE-001: UUID-anchored, idempotente.
 */

declare(strict_types=1);

use Drupal\Core\Entity\EntityStorageInterface;

$experiment_storage = \Drupal::entityTypeManager()->getStorage('ab_experiment');
$variant_storage = \Drupal::entityTypeManager()->getStorage('ab_variant');

$machine_name = 'aei_hero_cta';

// Idempotente: verificar si ya existe.
$existing = $experiment_storage->loadByProperties(['machine_name' => $machine_name]);
if (!empty($existing)) {
  $experiment = reset($existing);
  echo "ABExperiment '{$machine_name}' ya existe (ID: {$experiment->id()}). Saltando.\n";
}
else {
  // Resolver tenant_id: usar el tenant de andalucia_ei o el primero disponible.
  $tenant_id = 1;
  if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
    try {
      $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $current_tenant_id = $tenant_context->getCurrentTenantId();
      if ($current_tenant_id) {
        $tenant_id = $current_tenant_id;
      }
    }
    catch (\Throwable $e) {
      // Fallback a tenant_id = 1.
    }
  }

  $experiment = $experiment_storage->create([
    'label' => 'Andalucía +ei: CTA Hero Principal',
    'machine_name' => $machine_name,
    'experiment_type' => 'cta_variant',
    'hypothesis' => 'Un CTA con urgencia temporal o enfocado en beneficio mejora la conversión vs el CTA estándar',
    'primary_metric' => 'conversion_rate',
    'secondary_metrics' => json_encode(['click_rate', 'engagement']),
    'target_audience' => 'all',
    'traffic_percentage' => 100,
    'status' => 'draft',
    'confidence_threshold' => '0.95',
    'minimum_sample_size' => 200,
    'minimum_runtime_days' => 14,
    'auto_complete' => TRUE,
    'tenant_id' => $tenant_id,
    'uid' => 1,
  ]);
  $experiment->save();
  echo "ABExperiment '{$machine_name}' creado (ID: {$experiment->id()}).\n";

  // Crear 3 variantes.
  $variants = [
    [
      'label' => 'Control — CTA estándar',
      'variant_key' => 'control',
      'is_control' => TRUE,
      'traffic_weight' => 34,
      'variant_data' => json_encode([
        'cta_text' => 'Solicitar plaza ahora',
        'cta_color' => 'naranja-impulso',
      ]),
    ],
    [
      'label' => 'Urgencia temporal',
      'variant_key' => 'urgency',
      'is_control' => FALSE,
      'traffic_weight' => 33,
      'variant_data' => json_encode([
        'cta_text' => 'Solo quedan {plazas} plazas — Solicita ahora',
        'cta_color' => 'naranja-impulso',
      ]),
    ],
    [
      'label' => 'Beneficio económico',
      'variant_key' => 'benefit',
      'is_control' => FALSE,
      'traffic_weight' => 33,
      'variant_data' => json_encode([
        'cta_text' => 'Empieza gratis + 528 € de incentivo',
        'cta_color' => 'verde-innovacion',
      ]),
    ],
  ];

  foreach ($variants as $variant_def) {
    $variant = $variant_storage->create([
      'label' => $variant_def['label'],
      'variant_key' => $variant_def['variant_key'],
      'experiment_id' => $experiment->id(),
      'is_control' => $variant_def['is_control'],
      'traffic_weight' => $variant_def['traffic_weight'],
      'variant_data' => $variant_def['variant_data'],
      'tenant_id' => $tenant_id,
    ]);
    $variant->save();
    echo "  ABVariant '{$variant_def['variant_key']}' creada (ID: {$variant->id()}).\n";
  }
}

echo "Seed completo.\n";
