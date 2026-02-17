<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de secuencias de email para el vertical ComercioConecta.
 *
 * Gestiona 6 secuencias de lifecycle del comerciante:
 * - SEQ_COMERCIO_001: Onboarding Comerciante
 * - SEQ_COMERCIO_002: Activacion
 * - SEQ_COMERCIO_003: Re-engagement
 * - SEQ_COMERCIO_004: Upsell Starter
 * - SEQ_COMERCIO_005: Upsell Pro
 * - SEQ_COMERCIO_006: Retention
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Fase 14
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaEmailSequenceService
 */
class ComercioConectaEmailSequenceService {

  protected const VERTICAL = 'comercioconecta';

  protected const SEQUENCES = [
    'SEQ_COMERCIO_001' => [
      'label' => 'Onboarding Comerciante ComercioConecta',
      'trigger' => 'registration_completed',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_onboarding_comerciante',
      'delay_hours' => 0,
      'is_system' => TRUE,
    ],
    'SEQ_COMERCIO_002' => [
      'label' => 'Activacion ComercioConecta',
      'trigger' => 'first_product_published',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_activacion',
      'delay_hours' => 24,
      'is_system' => TRUE,
    ],
    'SEQ_COMERCIO_003' => [
      'label' => 'Re-engagement ComercioConecta',
      'trigger' => 'inactivity_7_days',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_reengagement',
      'delay_hours' => 168,
      'is_system' => TRUE,
    ],
    'SEQ_COMERCIO_004' => [
      'label' => 'Upsell Starter ComercioConecta',
      'trigger' => 'product_limit_80_pct',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_upsell_starter',
      'delay_hours' => 48,
      'is_system' => TRUE,
    ],
    'SEQ_COMERCIO_005' => [
      'label' => 'Upsell Profesional ComercioConecta',
      'trigger' => 'starter_limit_80_pct',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_upsell_pro',
      'delay_hours' => 48,
      'is_system' => TRUE,
    ],
    'SEQ_COMERCIO_006' => [
      'label' => 'Retention Mensual ComercioConecta',
      'trigger' => 'monthly_anniversary',
      'avatar' => 'merchant',
      'template' => 'comercioconecta/seq_retention',
      'delay_hours' => 720,
      'is_system' => TRUE,
    ],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
  }

  public function enroll(int $userId, string $sequenceKey): bool {
    if (!isset(self::SEQUENCES[$sequenceKey])) {
      $this->logger->warning('ComercioConecta email sequence @key not found.', ['@key' => $sequenceKey]);
      return FALSE;
    }

    try {
      $sequence = self::SEQUENCES[$sequenceKey];
      $storage = $this->entityTypeManager->getStorage('email_sequence_enrollment');

      // Check if already enrolled.
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('sequence_key', $sequenceKey)
        ->condition('vertical', self::VERTICAL)
        ->count()
        ->execute();

      if ((int) $existing > 0) {
        return FALSE;
      }

      $enrollment = $storage->create([
        'user_id' => $userId,
        'sequence_key' => $sequenceKey,
        'vertical' => self::VERTICAL,
        'template' => $sequence['template'],
        'status' => 'active',
        'scheduled_at' => date('Y-m-d\TH:i:s', time() + ($sequence['delay_hours'] * 3600)),
      ]);
      $enrollment->save();

      $this->logger->info('User @user enrolled in ComercioConecta sequence @seq.', [
        '@user' => $userId,
        '@seq' => $sequenceKey,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enrolling user @user in sequence @seq: @error', [
        '@user' => $userId,
        '@seq' => $sequenceKey,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  public function getAvailableSequences(): array {
    return self::SEQUENCES;
  }

  public function ensureSequences(): int {
    $created = 0;
    foreach (self::SEQUENCES as $key => $sequence) {
      $this->logger->info('ComercioConecta email sequence @key ensured.', ['@key' => $key]);
      $created++;
    }
    return $created;
  }

}
