<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para el vertical JarabaLex.
 *
 * Gestiona la inscripcion de usuarios en secuencias automatizadas
 * del ciclo de vida del profesional juridico. Resuelve sequence keys
 * a entity IDs y delega la inscripcion a SequenceManagerService.
 *
 * Sequences gestionadas:
 * - SEQ_LEX_001: Welcome Legal Professional (post-registro)
 * - SEQ_LEX_002: First Search Completed (primera busqueda exitosa)
 * - SEQ_LEX_003: Alert Configuration (primera alerta configurada)
 * - SEQ_LEX_004: Inactivity Reengagement (sin actividad 14 dias)
 * - SEQ_LEX_005: Upsell Free a Starter (limite free alcanzado)
 *
 * Plan Elevacion JarabaLex v1 — Fase 6.
 *
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 */
class JarabaLexEmailSequenceService {

  /**
   * Definiciones de secuencias jarabalex.
   */
  protected const SEQUENCES = [
    'SEQ_LEX_001' => [
      'label' => 'JarabaLex: Welcome Legal Professional',
      'category' => 'onboarding',
      'trigger_type' => 'event',
    ],
    'SEQ_LEX_002' => [
      'label' => 'JarabaLex: First Search Completed',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_LEX_003' => [
      'label' => 'JarabaLex: Alert Configuration',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_LEX_004' => [
      'label' => 'JarabaLex: Inactivity Reengagement',
      'category' => 'reengagement',
      'trigger_type' => 'event',
    ],
    'SEQ_LEX_005' => [
      'label' => 'JarabaLex: Upsell Free a Starter',
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
   * Inscribe un usuario en una secuencia de jarabalex.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_LEX_001 a SEQ_LEX_005).
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
   */
  protected function resolveSequenceId(string $sequenceKey): ?int {
    try {
      $sequences = $this->entityTypeManager->getStorage('email_sequence')
        ->loadByProperties([
          'name' => $sequenceKey,
          'vertical' => 'jarabalex',
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
   * Crea las secuencias de jarabalex si no existen.
   */
  public function ensureSequences(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('email_sequence');
    }
    catch (\Exception $e) {
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
        'vertical' => 'jarabalex',
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'jarabalex_vertical']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for jarabalex vertical.', [
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
