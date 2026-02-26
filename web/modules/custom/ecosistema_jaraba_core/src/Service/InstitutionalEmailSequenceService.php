<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de email sequences para meta-sitios institucionales (PED + JI).
 *
 * Gestiona las secuencias automatizadas de nurturing para leads
 * institucionales captados vía contacto-institucional, inversores,
 * prensa y solicitudes de demo.
 *
 * Secuencias definidas:
 * - SEQ_INST_001: Bienvenida institucional + dossier corporativo
 * - SEQ_INST_002: Caso de éxito territorial (social proof B2B)
 * - SEQ_INST_003: Presentación plataforma SaaS + demo interactiva
 * - SEQ_INST_004: Propuesta de valor + pricing institucional
 * - SEQ_INST_005: Follow-up a 30 días (re-engagement B2B)
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\MetaSiteEmailSequenceService
 * @see \Drupal\jaraba_email\Service\SequenceManagerService
 */
class InstitutionalEmailSequenceService {

  /**
   * Mapeo de secuencias institucionales.
   */
  protected const SEQUENCES = [
    'welcome' => [
      'key' => 'SEQ_INST_001',
      'label' => 'Bienvenida Institucional + Dossier',
      'category' => 'onboarding',
      'description' => 'Primer contacto institucional: dossier corporativo PED S.L., cifras de impacto (+100M€, +50K beneficiarios), presentación del founder.',
    ],
    'case_study' => [
      'key' => 'SEQ_INST_002',
      'label' => 'Caso de Éxito Territorial',
      'category' => 'nurture',
      'description' => 'Social proof B2B: caso de transformación digital territorial real con métricas verificables. Andalucía +ei, NextGen EU.',
    ],
    'platform_demo' => [
      'key' => 'SEQ_INST_003',
      'label' => 'Plataforma SaaS + Demo Interactiva',
      'category' => 'nurture',
      'description' => 'Presentación de los 5 verticales SaaS con enlace a demo personalizada. Triple motor económico explicado.',
    ],
    'proposal' => [
      'key' => 'SEQ_INST_004',
      'label' => 'Propuesta de Valor + Pricing Institucional',
      'category' => 'sales',
      'description' => 'Pricing institucional, licencias Método Jaraba, franquicias territoriales. Propuesta personalizada por perfil.',
    ],
    'followup' => [
      'key' => 'SEQ_INST_005',
      'label' => 'Follow-up 30 Días',
      'category' => 'reengagement',
      'description' => 'Re-engagement a 30 días: nuevas funcionalidades, resultados recientes, invitación a reunión.',
    ],
  ];

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Inscribe un suscriptor en la secuencia de bienvenida institucional.
   *
   * @param int $subscriberId
   *   ID del suscriptor creado en jaraba_email.
   * @param string $source
   *   Fuente del lead (contact_institucional, inversor, prensa, demo).
   */
  public function enrollInWelcome(int $subscriberId, string $source = 'institutional'): void {
    $sequenceId = $this->resolveSequenceId('welcome');
    if (!$sequenceId) {
      $this->logger->warning('Institutional welcome sequence not found. Auto-creating...');
      $this->ensureSequences();
      $sequenceId = $this->resolveSequenceId('welcome');
    }

    if ($sequenceId && \Drupal::hasService('jaraba_email.sequence_manager')) {
      try {
        /** @var \Drupal\jaraba_email\Service\SequenceManagerService $sequenceManager */
        $sequenceManager = \Drupal::service('jaraba_email.sequence_manager');
        $sequenceManager->enrollSubscriber($subscriberId, $sequenceId);

        $this->logger->info('Institutional enroll: subscriber @id in @seq (source: @source)', [
          '@id' => $subscriberId,
          '@seq' => 'SEQ_INST_001',
          '@source' => $source,
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Institutional enroll failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Resuelve el ID de entidad de una secuencia por su key interna.
   *
   * @param string $internalKey
   *   Clave interna (welcome, case_study, platform_demo, etc.).
   *
   * @return int|null
   *   ID de la entidad EmailSequence o NULL si no existe.
   */
  protected function resolveSequenceId(string $internalKey): ?int {
    if (!isset(self::SEQUENCES[$internalKey])) {
      return NULL;
    }

    $key = self::SEQUENCES[$internalKey]['key'];

    try {
      $storage = $this->entityTypeManager->getStorage('email_sequence');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('machine_name', $key)
        ->range(0, 1)
        ->execute();

      return $ids ? (int) reset($ids) : NULL;
    }
    catch (\Exception $e) {
      $this->logger->warning('Cannot resolve sequence @key: @error', [
        '@key' => $key,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Asegura que todas las secuencias institucionales existan como entidades.
   */
  public function ensureSequences(): void {
    try {
      $storage = $this->entityTypeManager->getStorage('email_sequence');

      foreach (self::SEQUENCES as $internalKey => $def) {
        $existing = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('machine_name', $def['key'])
          ->count()
          ->execute();

        if (!$existing) {
          $storage->create([
            'machine_name' => $def['key'],
            'label' => $def['label'],
            'description' => $def['description'],
            'category' => $def['category'],
            'status' => TRUE,
          ])->save();

          $this->logger->info('Created institutional sequence: @key (@label)', [
            '@key' => $def['key'],
            '@label' => $def['label'],
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to ensure institutional sequences: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Devuelve la definición de todas las secuencias.
   *
   * @return array
   *   Mapeo de secuencias institucionales.
   */
  public static function getSequenceDefinitions(): array {
    return self::SEQUENCES;
  }

}
