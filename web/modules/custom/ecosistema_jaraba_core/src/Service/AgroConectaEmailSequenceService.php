<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de secuencias de email para el vertical AgroConecta.
 *
 * Gestiona 6 secuencias de lifecycle del productor y consumidor:
 * - SEQ_AGRO_001: Onboarding Productor
 * - SEQ_AGRO_002: Activacion
 * - SEQ_AGRO_003: Re-engagement
 * - SEQ_AGRO_004: Upsell Starter
 * - SEQ_AGRO_005: Upsell Profesional
 * - SEQ_AGRO_006: Retention
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 7
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityEmailSequenceService
 */
class AgroConectaEmailSequenceService {

  protected const VERTICAL = 'agroconecta';

  protected const SEQUENCES = [
    'SEQ_AGRO_001' => [
      'label' => 'Onboarding Productor AgroConecta',
      'trigger' => 'registration_completed',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_onboarding_productor',
      'delay_hours' => 0,
      'is_system' => TRUE,
    ],
    'SEQ_AGRO_002' => [
      'label' => 'Activacion AgroConecta',
      'trigger' => 'first_product_published',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_activacion',
      'delay_hours' => 24,
      'is_system' => TRUE,
    ],
    'SEQ_AGRO_003' => [
      'label' => 'Re-engagement AgroConecta',
      'trigger' => 'inactivity_7_days',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_reengagement',
      'delay_hours' => 168,
      'is_system' => TRUE,
    ],
    'SEQ_AGRO_004' => [
      'label' => 'Upsell Starter AgroConecta',
      'trigger' => 'product_limit_80_pct',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_upsell_starter',
      'delay_hours' => 48,
      'is_system' => TRUE,
    ],
    'SEQ_AGRO_005' => [
      'label' => 'Upsell Profesional AgroConecta',
      'trigger' => 'starter_limit_80_pct',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_upsell_pro',
      'delay_hours' => 48,
      'is_system' => TRUE,
    ],
    'SEQ_AGRO_006' => [
      'label' => 'Retention Mensual AgroConecta',
      'trigger' => 'monthly_anniversary',
      'avatar' => 'productor',
      'template' => 'agroconecta/seq_retention',
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
      $this->logger->warning('AgroConecta email sequence @key not found.', ['@key' => $sequenceKey]);
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

      $this->logger->info('User @user enrolled in AgroConecta sequence @seq.', [
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
      $this->logger->info('AgroConecta email sequence @key ensured.', ['@key' => $key]);
      $created++;
    }
    return $created;
  }

}
