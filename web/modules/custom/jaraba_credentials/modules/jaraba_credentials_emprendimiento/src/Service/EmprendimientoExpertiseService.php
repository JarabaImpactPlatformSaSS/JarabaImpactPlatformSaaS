<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_emprendimiento\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Psr\Log\LoggerInterface;

/**
 * Servicio de evaluación del nivel de expertise en emprendimiento.
 */
class EmprendimientoExpertiseService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * Niveles de expertise y XP requerido.
   */
  public const LEVELS = [
    'explorador' => 0,
    'iniciado' => 200,
    'profesional' => 500,
    'experto' => 1000,
    'master' => 2000,
  ];

  /**
   * XP por tipo de badge.
   */
  public const XP_MAP = [
    'diagnostico_completado' => 100,
    'madurez_digital_basica' => 100,
    'madurez_digital_intermedia' => 200,
    'madurez_digital_avanzada' => 300,
    'business_canvas_creator' => 150,
    'business_canvas_validated' => 250,
    'financial_architect' => 200,
    'pitch_ready' => 200,
    'mvp_launched' => 300,
    'mvp_validated' => 400,
    'first_sale' => 500,
    'first_mentoring_session' => 100,
    'emprendedor_digital_basico' => 300,
    'emprendedor_digital_avanzado' => 500,
    'transformador_digital_expert' => 1000,
  ];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials_emprendimiento');
  }

  /**
   * Evalúa el nivel de expertise del usuario.
   */
  public function evaluateUserLevel(int $uid): string {
    $xp = $this->getUserXp($uid);
    $level = 'explorador';
    foreach (self::LEVELS as $name => $threshold) {
      if ($xp >= $threshold) {
        $level = $name;
      }
    }
    return $level;
  }

  /**
   * Calcula el XP total del usuario basado en badges de emprendimiento.
   */
  public function getUserXp(int $uid): int {
    $ids = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->execute();

    if (empty($ids)) {
      return 0;
    }

    $credentials = $this->entityTypeManager->getStorage('issued_credential')->loadMultiple($ids);
    $totalXp = 0;

    foreach ($credentials as $credential) {
      $templateId = $credential->get('template_id')->target_id ?? NULL;
      if (!$templateId) {
        continue;
      }
      $template = $this->entityTypeManager->getStorage('credential_template')->load($templateId);
      if (!$template) {
        continue;
      }
      $machineName = $template->get('machine_name')->value ?? '';
      $totalXp += self::XP_MAP[$machineName] ?? 0;
    }

    return $totalXp;
  }

  /**
   * Obtiene los beneficios de un nivel.
   */
  public function getLevelBenefits(string $level): array {
    $benefits = [
      'explorador' => [
        'label' => 'Explorador',
        'description' => 'Inicio del journey emprendedor',
        'perks' => ['Acceso a diagnóstico empresarial', 'Comunidad básica'],
      ],
      'iniciado' => [
        'label' => 'Iniciado',
        'description' => 'Primeros pasos en emprendimiento digital',
        'perks' => ['Mentoring básico', 'Templates de canvas', 'Descuento 10% cursos'],
      ],
      'profesional' => [
        'label' => 'Profesional',
        'description' => 'Emprendedor digital con experiencia',
        'perks' => ['Mentoring avanzado', 'Acceso a red de inversores', 'Descuento 20% cursos'],
      ],
      'experto' => [
        'label' => 'Experto',
        'description' => 'Líder en transformación digital',
        'perks' => ['Mentoring VIP', 'Marketplace prioritario', 'Descuento 30% cursos', 'Badge exclusivo'],
      ],
      'master' => [
        'label' => 'Master',
        'description' => 'Transformador digital reconocido',
        'perks' => ['Acceso completo', 'Invitación eventos exclusivos', 'Verificación azul', 'Puede ser mentor'],
      ],
    ];

    return $benefits[$level] ?? $benefits['explorador'];
  }

}
