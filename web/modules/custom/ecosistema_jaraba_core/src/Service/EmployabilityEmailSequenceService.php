<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para el vertical Empleabilidad.
 *
 * Gestiona la inscripción de usuarios en secuencias automatizadas
 * del ciclo de vida del candidato. Resuelve sequence keys a entity IDs
 * y delega la inscripción a SequenceManagerService de jaraba_email.
 *
 * Sequences gestionadas:
 * - SEQ_EMP_001: Onboarding Candidato (7 días post-diagnóstico)
 * - SEQ_EMP_002: Re-engagement (0 aplicaciones en 7 días)
 * - SEQ_EMP_003: Upsell Free-to-Starter (tras 3ª aplicación)
 * - SEQ_EMP_004: Post-Entrevista (interview scheduled)
 * - SEQ_EMP_005: Retention Post-Empleo (candidato contratado)
 *
 * Plan Elevación Empleabilidad v1 — Fase 6
 *
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 */
class EmployabilityEmailSequenceService {

  /**
   * Definiciones de secuencias empleabilidad.
   */
  protected const SEQUENCES = [
    'SEQ_EMP_001' => [
      'label' => 'Empleabilidad: Onboarding Candidato',
      'category' => 'onboarding',
      'trigger_type' => 'event',
    ],
    'SEQ_EMP_002' => [
      'label' => 'Empleabilidad: Re-engagement Inactivos',
      'category' => 'reengagement',
      'trigger_type' => 'event',
    ],
    'SEQ_EMP_003' => [
      'label' => 'Empleabilidad: Upsell Free a Starter',
      'category' => 'sales',
      'trigger_type' => 'event',
    ],
    'SEQ_EMP_004' => [
      'label' => 'Empleabilidad: Post-Entrevista',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_EMP_005' => [
      'label' => 'Empleabilidad: Retention Post-Empleo',
      'category' => 'post_purchase',
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
   * Inscribe un usuario en una secuencia de empleabilidad.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_EMP_001 a SEQ_EMP_005).
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
          'vertical' => 'empleabilidad',
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
   * Crea las secuencias de empleabilidad si no existen.
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
        'vertical' => 'empleabilidad',
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'employability_vertical']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for empleabilidad vertical.', [
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
