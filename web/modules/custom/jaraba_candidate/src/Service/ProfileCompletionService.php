<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for tracking candidate profile completion.
 *
 * Calcula el porcentaje de completitud del perfil del candidato
 * y proporciona los pasos siguientes priorizados.
 */
class ProfileCompletionService {

  /**
   * Profile sections with their weight for completion calculation.
   */
  private const SECTIONS = [
    'personal_info' => ['weight' => 20, 'required' => TRUE, 'fields' => ['first_name', 'last_name', 'email', 'phone']],
    'professional_summary' => ['weight' => 15, 'required' => TRUE, 'fields' => ['headline', 'summary']],
    'experience' => ['weight' => 20, 'required' => TRUE, 'entity_type' => 'candidate_experience'],
    'education' => ['weight' => 15, 'required' => TRUE, 'entity_type' => 'candidate_education'],
    'skills' => ['weight' => 15, 'required' => TRUE, 'entity_type' => 'candidate_skill'],
    'photo' => ['weight' => 5, 'required' => FALSE, 'fields' => ['photo']],
    'cv_document' => ['weight' => 5, 'required' => FALSE, 'fields' => ['cv_file_id']],
    'languages' => ['weight' => 5, 'required' => FALSE, 'entity_type' => 'candidate_language'],
  ];

  /**
   * Minimum completion percentage for job matching.
   */
  private const MATCHING_THRESHOLD = 70;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CandidateProfileService $profileService,
  ) {}

  /**
   * Calculates profile completion percentage.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Completion data with 'percentage', 'completed_sections', 'missing_sections'.
   */
  public function calculateCompletion(int $userId): array {
    $profile = $this->loadProfile($userId);

    if (!$profile) {
      return [
        'user_id' => $userId,
        'percentage' => 0.0,
        'completed_sections' => [],
        'missing_sections' => array_keys(self::SECTIONS),
        'ready_for_matching' => FALSE,
      ];
    }

    $completed = [];
    $missing = [];
    $totalWeight = 0;
    $earnedWeight = 0;

    foreach (self::SECTIONS as $sectionKey => $config) {
      $totalWeight += $config['weight'];

      if (isset($config['entity_type'])) {
        $isFilled = $this->hasRelatedEntities($userId, $config['entity_type']);
      }
      else {
        $isFilled = $this->isSectionFilled($profile, $config['fields']);
      }

      if ($isFilled) {
        $completed[] = $sectionKey;
        $earnedWeight += $config['weight'];
      }
      else {
        $missing[] = $sectionKey;
      }
    }

    $percentage = $totalWeight > 0 ? round(($earnedWeight / $totalWeight) * 100, 1) : 0.0;

    return [
      'user_id' => $userId,
      'percentage' => $percentage,
      'completed_sections' => $completed,
      'missing_sections' => $missing,
      'total_sections' => count(self::SECTIONS),
      'completed_count' => count($completed),
      'ready_for_matching' => $percentage >= self::MATCHING_THRESHOLD,
    ];
  }

  /**
   * Gets prioritized next steps to improve the profile.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Ordered list of next steps with priority and impact.
   */
  public function getNextSteps(int $userId): array {
    $completion = $this->calculateCompletion($userId);
    $steps = [];

    foreach ($completion['missing_sections'] as $section) {
      $config = self::SECTIONS[$section] ?? NULL;
      if (!$config) {
        continue;
      }

      $steps[] = [
        'section' => $section,
        'label' => $this->getSectionLabel($section),
        'impact' => $config['weight'],
        'required' => $config['required'],
        'priority' => $config['required'] ? 'high' : 'medium',
        'estimated_minutes' => $this->getEstimatedMinutes($section),
        'description' => $this->getSectionDescription($section),
      ];
    }

    // Sort by: required first, then by weight descending.
    usort($steps, function ($a, $b) {
      if ($a['required'] !== $b['required']) {
        return $b['required'] <=> $a['required'];
      }
      return $b['impact'] <=> $a['impact'];
    });

    return [
      'user_id' => $userId,
      'current_completion' => $completion['percentage'],
      'steps' => $steps,
      'potential_completion' => 100.0,
    ];
  }

  /**
   * Checks if the profile meets minimum matching threshold.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return bool
   *   TRUE if profile is ready for job matching.
   */
  public function isReadyForMatching(int $userId): bool {
    $completion = $this->calculateCompletion($userId);
    return $completion['ready_for_matching'];
  }

  /**
   * Gets missing sections with details.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   List of missing sections with labels and requirements.
   */
  public function getMissingSections(int $userId): array {
    $completion = $this->calculateCompletion($userId);
    $missing = [];

    foreach ($completion['missing_sections'] as $section) {
      $config = self::SECTIONS[$section] ?? NULL;
      if (!$config) {
        continue;
      }

      $missing[] = [
        'section' => $section,
        'label' => $this->getSectionLabel($section),
        'required' => $config['required'],
        'weight' => $config['weight'],
      ];
    }

    return $missing;
  }

  /**
   * Loads a candidate profile.
   */
  protected function loadProfile(int $userId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_profile');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      return $storage->load(reset($ids));
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Checks if a user has related entities of a given type.
   */
  protected function hasRelatedEntities(int $userId, string $entityType): bool {
    try {
      $count = $this->entityTypeManager
        ->getStorage($entityType)
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();
      return $count > 0;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Checks if a profile section is filled.
   */
  protected function isSectionFilled(?object $profile, array $fields): bool {
    if (!$profile) {
      return FALSE;
    }

    foreach ($fields as $field) {
      try {
        if (!$profile->hasField($field)) {
          return FALSE;
        }
        $value = $profile->get($field)->value ?? $profile->get($field)->target_id ?? NULL;
        if (empty($value)) {
          return FALSE;
        }
      }
      catch (\Exception) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Gets human-readable section label.
   */
  protected function getSectionLabel(string $section): string {
    return match ($section) {
      'personal_info' => 'Datos Personales',
      'professional_summary' => 'Marca Profesional',
      'experience' => 'Experiencia Laboral',
      'education' => 'Formación Académica',
      'skills' => 'Habilidades y Competencias',
      'photo' => 'Foto de Perfil',
      'cv_document' => 'Documento CV',
      'languages' => 'Idiomas',
      default => ucfirst(str_replace('_', ' ', $section)),
    };
  }

  /**
   * Gets section description.
   */
  protected function getSectionDescription(string $section): string {
    return match ($section) {
      'personal_info' => 'Completa tu nombre, email y teléfono de contacto.',
      'professional_summary' => 'Define tu titular profesional y escribe un resumen con ayuda de la IA.',
      'experience' => 'Añade tu experiencia laboral relevante.',
      'education' => 'Indica tu formación académica.',
      'skills' => 'Evalúa tus habilidades técnicas y blandas.',
      'photo' => 'Sube una foto profesional para tu perfil.',
      'cv_document' => 'Adjunta tu CV en formato PDF.',
      'languages' => 'Indica los idiomas que dominas y tu nivel.',
      default => '',
    };
  }

  /**
   * Estimated time to complete a section.
   */
  protected function getEstimatedMinutes(string $section): int {
    return match ($section) {
      'personal_info' => 2,
      'professional_summary' => 5,
      'experience' => 10,
      'education' => 5,
      'skills' => 8,
      'photo' => 1,
      'cv_document' => 2,
      'languages' => 3,
      default => 5,
    };
  }

}
