<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para el vertical Emprendimiento.
 *
 * Gestiona la inscripción de usuarios en secuencias automatizadas
 * del ciclo de vida del emprendedor. Resuelve sequence keys a entity IDs
 * y delega la inscripción a SequenceManagerService de jaraba_email.
 *
 * Sequences gestionadas:
 * - SEQ_ENT_001: Onboarding Fundador (post-registro idea)
 * - SEQ_ENT_002: Canvas Abandonment (BMC sin editar 7 días)
 * - SEQ_ENT_003: Upsell Free a Starter (3ª hipótesis en plan free)
 * - SEQ_ENT_004: MVP Celebration (experiment con decision VALIDATED)
 * - SEQ_ENT_005: Retention Post-Funding (funding application aprobada)
 *
 * Plan Elevación Emprendimiento v2 — Fase 3 (G3).
 *
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 */
class EmprendimientoEmailSequenceService {

  /**
   * Definiciones de secuencias emprendimiento.
   */
  protected const SEQUENCES = [
    'SEQ_ENT_001' => [
      'label' => 'Emprendimiento: Onboarding Fundador',
      'category' => 'onboarding',
      'trigger_type' => 'event',
    ],
    'SEQ_ENT_002' => [
      'label' => 'Emprendimiento: Canvas Abandonment',
      'category' => 'reengagement',
      'trigger_type' => 'event',
    ],
    'SEQ_ENT_003' => [
      'label' => 'Emprendimiento: Upsell Free a Starter',
      'category' => 'sales',
      'trigger_type' => 'event',
    ],
    'SEQ_ENT_004' => [
      'label' => 'Emprendimiento: MVP Celebration',
      'category' => 'nurture',
      'trigger_type' => 'event',
    ],
    'SEQ_ENT_005' => [
      'label' => 'Emprendimiento: Retention Post-Funding',
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
   * Inscribe un usuario en una secuencia de emprendimiento.
   *
   * @param int $userId
   *   ID del usuario a inscribir.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_ENT_001 a SEQ_ENT_005).
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
          'vertical' => 'emprendimiento',
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
   * Crea las secuencias de emprendimiento si no existen.
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
        'vertical' => 'emprendimiento',
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'emprendimiento_vertical']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for emprendimiento vertical.', [
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
