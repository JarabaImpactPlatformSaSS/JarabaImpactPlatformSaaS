<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Mi Perfil Profesional" — siempre visible.
 *
 * Migrada desde ecosistema_jaraba_theme.theme lineas 3443-3538.
 * Incluye widget de completitud si jaraba_candidate.profile_completion existe.
 */
class ProfessionalProfileSection extends AbstractUserProfileSection {

  /**
   * Mapping de secciones de completitud para el parcial Twig.
   */
  private const SECTION_LABELS = [
    'personal_info' => 'Datos Personales',
    'professional_summary' => 'Marca Profesional',
    'experience' => 'Experiencia Laboral',
    'education' => 'Formacion Academica',
    'skills' => 'Habilidades y Competencias',
    'photo' => 'Foto de Perfil',
    'cv_document' => 'Documento CV',
    'languages' => 'Idiomas',
    'privacy' => 'Privacidad',
  ];

  private const SECTION_ROUTES = [
    'personal_info' => 'jaraba_candidate.my_profile.personal',
    'professional_summary' => 'jaraba_candidate.my_profile.brand',
    'experience' => 'jaraba_candidate.my_profile.experience',
    'education' => 'jaraba_candidate.my_profile.education',
    'skills' => 'jaraba_candidate.my_profile.skills',
    'photo' => 'jaraba_candidate.my_profile.personal',
    'cv_document' => 'jaraba_candidate.cv_builder',
    'languages' => 'jaraba_candidate.my_profile.languages',
    'privacy' => 'jaraba_candidate.my_profile.privacy',
  ];

  /**
   * Secciones con valores por defecto que siempre estan "completadas".
   */
  private const ALWAYS_COMPLETED = ['privacy'];

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual.
   * @param object|null $profileCompletion
   *   Servicio jaraba_candidate.profile_completion (opcional).
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    protected readonly ?object $profileCompletion = NULL,
  ) {
    parent::__construct($currentUser);
  }

  /**
   *
   */
  public function getId(): string {
    return 'professional_profile';
  }

  /**
   *
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Mi Perfil Profesional');
  }

  /**
   *
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Gestiona tu presencia profesional en el ecosistema');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'innovation';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   *
   */
  public function isApplicable(int $uid): bool {
    return TRUE;
  }

  /**
   *
   */
  public function getLinks(int $uid): array {
    return array_filter([
      $this->makeLink(
        $this->t('Ver perfil profesional'),
        'jaraba_candidate.my_profile',
        'ui', 'user', 'innovation',
        ['description' => $this->t('Visualiza como te ven los reclutadores')],
      ),
      $this->makeLink(
        $this->t('Constructor CV'),
        'jaraba_candidate.cv_builder',
        'ui', 'file-text', 'innovation',
        ['description' => $this->t('Crea tu CV profesional en minutos')],
      ),
      $this->makeLink(
        $this->t('Autoconocimiento'),
        'jaraba_self_discovery.dashboard',
        'ui', 'compass', 'innovation',
        ['description' => $this->t('Descubre tus fortalezas y areas de mejora')],
      ),
    ]);
  }

  /**
   *
   */
  public function getExtraData(int $uid): array {
    if (!$this->profileCompletion) {
      return [];
    }

    try {
      $completion = $this->profileCompletion->calculateCompletion($uid);
    }
    catch (\Throwable) {
      return [];
    }

    $allKeys = array_keys(self::SECTION_LABELS);
    $sectionDetails = [];
    foreach ($allKeys as $key) {
      $sectionDetails[] = [
        'name' => $this->t(self::SECTION_LABELS[$key]),
        'completed' => \in_array($key, self::ALWAYS_COMPLETED, TRUE)
        || \in_array($key, $completion['completed_sections'] ?? [], TRUE),
        'url' => $this->resolveRoute(self::SECTION_ROUTES[$key] ?? 'jaraba_candidate.my_profile.edit') ?: '',
        'slide_panel_title' => $this->t(self::SECTION_LABELS[$key]),
      ];
    }

    $pct = (int) round($completion['percentage'] ?? 0);
    $ctaMessage = NULL;
    if ($pct < 25) {
      $ctaMessage = $this->t('Tu perfil necesita atencion — los candidatos con perfil completo reciben 3x mas visibilidad');
    }
    elseif ($pct < 50) {
      $ctaMessage = $this->t('Buen comienzo — completa las secciones pendientes para destacar');
    }
    elseif ($pct < 80) {
      $ctaMessage = $this->t('Ya casi — solo faltan unos pasos para un perfil destacado');
    }

    return [
      'profile_completeness' => [
        'percentage' => $pct,
        'sections' => $sectionDetails,
        'total_sections' => $completion['total_sections'] ?? \count($allKeys),
        'completed_sections' => $completion['completed_count'] ?? 0,
        'cta_message' => $ctaMessage,
      ],
    ];
  }

}
