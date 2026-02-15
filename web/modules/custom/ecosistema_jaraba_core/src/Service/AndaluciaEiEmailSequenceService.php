<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para el vertical Andalucía +ei.
 *
 * Gestiona la inscripción de usuarios en secuencias automatizadas
 * del ciclo de vida del participante EI. Resuelve sequence keys a entity IDs
 * y delega la inscripción a SequenceManagerService de jaraba_email.
 *
 * Sequences gestionadas:
 * - SEQ_AEI_001: Welcome Participant (post-inscripción)
 * - SEQ_AEI_002: Phase Transition (atención → inserción)
 * - SEQ_AEI_003: Hours Milestone (hitos 25h/50h/75h/100h)
 * - SEQ_AEI_004: Training Completion (módulo LMS completado)
 * - SEQ_AEI_005: Inactivity Reengagement (sin actividad 14 días)
 * - SEQ_AEI_006: Upsell Starter (límite free alcanzado)
 *
 * Plan Elevación Andalucía +ei v1 — Fase 5.
 *
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 */
class AndaluciaEiEmailSequenceService {

  /**
   * Definiciones de secuencias andalucia_ei.
   */
  protected const SEQUENCES = [
    'SEQ_AEI_001' => [
      'label' => 'Andalucía +ei: Welcome Participant',
      'category' => 'onboarding',
      'trigger_type' => 'event',
    ],
    'SEQ_AEI_002' => [
      'label' => 'Andalucía +ei: Phase Transition',
      'category' => 'lifecycle',
      'trigger_type' => 'event',
    ],
    'SEQ_AEI_003' => [
      'label' => 'Andalucía +ei: Hours Milestone',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_AEI_004' => [
      'label' => 'Andalucía +ei: Training Completion',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_AEI_005' => [
      'label' => 'Andalucía +ei: Inactivity Reengagement',
      'category' => 'reengagement',
      'trigger_type' => 'event',
    ],
    'SEQ_AEI_006' => [
      'label' => 'Andalucía +ei: Upsell Free a Starter',
      'category' => 'sales',
      'trigger_type' => 'event',
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
   * Inscribe un usuario en una secuencia de andalucia_ei.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_AEI_001 a SEQ_AEI_006).
   *
   * @return bool
   *   TRUE si se inscribió correctamente.
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
      $this->logger->info('User @user enrolled in sequence @key.', [
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
          'vertical' => 'andalucia_ei',
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
   * Crea las secuencias de andalucia_ei si no existen.
   *
   * Se invoca automáticamente en el primer enroll(). También puede
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
        'vertical' => 'andalucia_ei',
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'andalucia_ei_vertical']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for andalucia_ei vertical.', [
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
