<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de secuencias de email para el vertical ServiciosConecta.
 *
 * Gestiona 6 secuencias de lifecycle del profesional:
 * - SEQ_SVC_001: Onboarding Profesional
 * - SEQ_SVC_002: Primer Servicio Publicado
 * - SEQ_SVC_003: Re-engagement Inactivos
 * - SEQ_SVC_004: Upsell Free a Starter
 * - SEQ_SVC_005: Upsell Starter a Profesional
 * - SEQ_SVC_006: Resumen Mensual + Cross-sell
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 7
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaEmailSequenceService
 */
class ServiciosConectaEmailSequenceService {

  protected const VERTICAL = 'serviciosconecta';

  /**
   * Definiciones de secuencias ServiciosConecta.
   */
  protected const SEQUENCES = [
    'SEQ_SVC_001' => [
      'label' => 'ServiciosConecta: Onboarding Profesional',
      'category' => 'onboarding',
      'trigger_type' => 'event',
    ],
    'SEQ_SVC_002' => [
      'label' => 'ServiciosConecta: Primer Servicio Publicado',
      'category' => 'activation',
      'trigger_type' => 'event',
    ],
    'SEQ_SVC_003' => [
      'label' => 'ServiciosConecta: Re-engagement Inactivos',
      'category' => 'reengagement',
      'trigger_type' => 'scheduled',
    ],
    'SEQ_SVC_004' => [
      'label' => 'ServiciosConecta: Upsell Free a Starter',
      'category' => 'sales',
      'trigger_type' => 'threshold',
    ],
    'SEQ_SVC_005' => [
      'label' => 'ServiciosConecta: Upsell Starter a Profesional',
      'category' => 'sales',
      'trigger_type' => 'threshold',
    ],
    'SEQ_SVC_006' => [
      'label' => 'ServiciosConecta: Resumen Mensual + Cross-sell',
      'category' => 'retention',
      'trigger_type' => 'scheduled',
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Inscribe un usuario en una secuencia de ServiciosConecta.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_SVC_001 a SEQ_SVC_006).
   *
   * @return bool
   *   TRUE si se inscribio correctamente.
   */
  public function enroll(int $userId, string $sequenceKey): bool {
    if (!\Drupal::hasService('jaraba_email.sequence_manager')) {
      return FALSE;
    }

    $sequenceId = $this->resolveSequenceId($sequenceKey);

    if (!$sequenceId) {
      // Auto-crear secuencias si no existen.
      $this->ensureSequences();
      $sequenceId = $this->resolveSequenceId($sequenceKey);
      if (!$sequenceId) {
        $this->logger->warning('Email sequence @key could not be resolved.', [
          '@key' => $sequenceKey,
        ]);
        return FALSE;
      }
    }

    /** @var \Drupal\jaraba_email\Service\SequenceManagerService $sequenceManager */
    $sequenceManager = \Drupal::service('jaraba_email.sequence_manager');
    $enrolled = $sequenceManager->enrollSubscriber($userId, $sequenceId);

    if ($enrolled) {
      $this->logger->info('User @user enrolled in ServiciosConecta sequence @key.', [
        '@user' => $userId,
        '@key' => $sequenceKey,
      ]);
    }

    return $enrolled;
  }

  /**
   * Resuelve una clave de secuencia a su entity ID.
   *
   * @param string $sequenceKey
   *   Clave de la secuencia.
   *
   * @return int|null
   *   Entity ID o NULL si no existe.
   */
  protected function resolveSequenceId(string $sequenceKey): ?int {
    try {
      $sequences = $this->entityTypeManager->getStorage('email_sequence')
        ->loadByProperties([
          'name' => $sequenceKey,
          'vertical' => self::VERTICAL,
        ]);

      if (!empty($sequences)) {
        $seq = reset($sequences);
        return (int) $seq->id();
      }
    }
    catch (\Exception $e) {
      // Entity type may not exist if jaraba_email not installed.
    }

    return NULL;
  }

  /**
   * Crea las secuencias de ServiciosConecta si no existen.
   *
   * Se invoca automaticamente en el primer enroll(). Tambien puede
   * ejecutarse desde un drush command o hook_install().
   */
  public function ensureSequences(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('email_sequence');
    }
    catch (\Exception $e) {
      // Entity type not available (jaraba_email not installed).
      return;
    }

    foreach (self::SEQUENCES as $key => $definition) {
      $existing = $storage->loadByProperties(['name' => $key]);
      if (!empty($existing)) {
        continue;
      }

      $sequence = $storage->create([
        'name' => $key,
        'description' => $definition['label'],
        'category' => $definition['category'],
        'vertical' => self::VERTICAL,
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'serviciosconecta_vertical']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for serviciosconecta vertical.', [
        '@key' => $key,
      ]);
    }
  }

  /**
   * Obtiene las claves de secuencias disponibles.
   *
   * @return array
   *   Array de claves con sus metadatos.
   */
  public function getAvailableSequences(): array {
    return self::SEQUENCES;
  }

}
