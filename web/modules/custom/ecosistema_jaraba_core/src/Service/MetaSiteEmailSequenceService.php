<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para el meta-sitio público.
 *
 * Gestiona la inscripción de suscriptores del lead magnet y formulario
 * de contacto en secuencias automatizadas de nurturing.
 *
 * Sequences gestionadas:
 * - SEQ_META_001: Bienvenida + Kit Impulso (inmediato post-suscripción)
 * - SEQ_META_002: Caso de Éxito + Social Proof (día 2)
 * - SEQ_META_003: Guía Verticales (día 5 — cross-pollination)
 * - SEQ_META_004: Demo + Pricing (día 8 — conversión)
 * - SEQ_META_005: Re-engagement (día 15 — si no ha convertido)
 *
 * Patrón idéntico a EmployabilityEmailSequenceService.
 *
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityEmailSequenceService
 */
class MetaSiteEmailSequenceService {

  /**
   * Definiciones de secuencias del meta-sitio.
   *
   * Cada secuencia tiene una clave única (SEQ_META_XXX), categoría
   * (onboarding, nurture, sales, reengagement) y trigger type.
   */
  protected const SEQUENCES = [
    'SEQ_META_001' => [
      'label' => 'MetaSite: Bienvenida + Kit Impulso Digital',
      'category' => 'onboarding',
      'trigger_type' => 'event',
      'description' => 'Se activa inmediatamente al suscribirse desde el lead magnet del meta-sitio. Envía el Kit de Impulso Digital y un email de bienvenida con presentación de la plataforma.',
    ],
    'SEQ_META_002' => [
      'label' => 'MetaSite: Caso de Éxito + Social Proof',
      'category' => 'nurture',
      'trigger_type' => 'scheduled',
      'description' => 'Día 2 post-suscripción. Envía un caso de éxito real (Marcela Calabia o Camino Viejo) para generar confianza y demostrar resultados tangibles.',
    ],
    'SEQ_META_003' => [
      'label' => 'MetaSite: Guía Verticales (Cross-Pollination)',
      'category' => 'nurture',
      'trigger_type' => 'scheduled',
      'description' => 'Día 5. Presenta los 6 verticales de la plataforma según el perfil del suscriptor, facilitando la cross-pollination entre servicios.',
    ],
    'SEQ_META_004' => [
      'label' => 'MetaSite: Demo + Pricing (Conversión)',
      'category' => 'sales',
      'trigger_type' => 'scheduled',
      'description' => 'Día 8. CTA directo a la página de planes (/planes) con comparativa de tiers y oferta de demo personalizada. Objetivo: convertir lead → trial.',
    ],
    'SEQ_META_005' => [
      'label' => 'MetaSite: Re-engagement 15 días',
      'category' => 'reengagement',
      'trigger_type' => 'scheduled',
      'description' => 'Día 15. Si el suscriptor no se ha registrado, envía un último email con propuesta de valor condensada, enlace directo a registro, y opción de agendar llamada.',
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
   * Inscribe un suscriptor en la secuencia de bienvenida del meta-sitio.
   *
   * Se invoca automáticamente desde PublicSubscribeController tras una
   * suscripción exitosa con source = 'kit_impulso_digital'.
   *
   * @param int $subscriberId
   *   ID del email_subscriber entity.
   *
   * @return bool
   *   TRUE si se inscribió correctamente.
   */
  public function enrollInWelcome(int $subscriberId): bool {
    return $this->enroll($subscriberId, 'SEQ_META_001');
  }

  /**
   * Inscribe un suscriptor en una secuencia del meta-sitio.
   *
   * @param int $subscriberId
   *   ID del suscriptor.
   * @param string $sequenceKey
   *   Clave de la secuencia (SEQ_META_001 a SEQ_META_005).
   *
   * @return bool
   *   TRUE si se inscribió correctamente.
   */
  public function enroll(int $subscriberId, string $sequenceKey): bool {
    if (!isset(self::SEQUENCES[$sequenceKey])) {
      $this->logger->warning('MetaSite: unknown sequence key @key', [
        '@key' => $sequenceKey,
      ]);
      return FALSE;
    }

    if (!\Drupal::hasService('jaraba_email.sequence_manager')) {
      $this->logger->notice('MetaSite enroll skipped: jaraba_email.sequence_manager not available.');
      return FALSE;
    }

    $sequenceId = $this->resolveSequenceId($sequenceKey);

    if (!$sequenceId) {
      // Auto-crear secuencias si no existen.
      $this->ensureSequences();
      $sequenceId = $this->resolveSequenceId($sequenceKey);
      if (!$sequenceId) {
        $this->logger->warning('MetaSite: sequence @key could not be resolved after ensure.', [
          '@key' => $sequenceKey,
        ]);
        return FALSE;
      }
    }

    /** @var \Drupal\jaraba_email\Service\SequenceManagerService $sequenceManager */
    $sequenceManager = \Drupal::service('jaraba_email.sequence_manager');
    $enrolled = $sequenceManager->enrollSubscriber($subscriberId, $sequenceId);

    if ($enrolled) {
      $this->logger->info('MetaSite: subscriber @id enrolled in sequence @key.', [
        '@id' => $subscriberId,
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
          'vertical' => 'metasite',
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
   * Crea las secuencias del meta-sitio si no existen.
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
        'description' => $definition['description'] ?? $definition['label'],
        'category' => $definition['category'],
        'vertical' => 'metasite',
        'trigger_type' => $definition['trigger_type'],
        'trigger_config' => json_encode(['source' => 'metasite_public']),
        'is_system' => TRUE,
        'is_active' => TRUE,
        'total_enrolled' => 0,
        'currently_enrolled' => 0,
        'completed' => 0,
      ]);
      $sequence->save();

      $this->logger->info('Created email sequence @key for metasite.', [
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
