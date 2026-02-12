<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use GuzzleHttp\ClientInterface;

/**
 * Service for importing LinkedIn profile data.
 *
 * Importa datos de perfiles de LinkedIn y los mapea al formato
 * de la plataforma Jaraba para enriquecer perfiles de candidatos.
 */
class LinkedInImportService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected CandidateProfileService $profileService,
  ) {}

  /**
   * Imports LinkedIn profile data into the candidate profile.
   *
   * @param int $userId
   *   The user ID.
   * @param array $linkedInData
   *   Raw LinkedIn profile data from OAuth.
   *
   * @return array
   *   Import result with 'imported_sections', 'skipped_sections'.
   */
  public function importProfile(int $userId, array $linkedInData): array {
    $imported = [];
    $skipped = [];
    $profileData = [];

    // Map basic info.
    if (!empty($linkedInData['firstName']) || !empty($linkedInData['lastName'])) {
      $profileData['first_name'] = $linkedInData['firstName'] ?? '';
      $profileData['last_name'] = $linkedInData['lastName'] ?? '';
      $imported[] = 'personal_info';
    }

    if (!empty($linkedInData['headline'])) {
      $profileData['professional_summary'] = $linkedInData['headline'];
      $imported[] = 'professional_summary';
    }
    else {
      $skipped[] = 'professional_summary';
    }

    // Map experience.
    if (!empty($linkedInData['positions'])) {
      $profileData['experience'] = $this->mapExperience($linkedInData['positions']);
      $imported[] = 'experience';
    }
    else {
      $skipped[] = 'experience';
    }

    // Map education.
    if (!empty($linkedInData['educations'])) {
      $profileData['education'] = $this->mapEducation($linkedInData['educations']);
      $imported[] = 'education';
    }
    else {
      $skipped[] = 'education';
    }

    // Map skills.
    if (!empty($linkedInData['skills'])) {
      $profileData['skills'] = $this->mapSkills($linkedInData['skills']);
      $imported[] = 'skills';
    }
    else {
      $skipped[] = 'skills';
    }

    // Map profile picture.
    if (!empty($linkedInData['profilePicture'])) {
      $profileData['photo_url'] = $linkedInData['profilePicture'];
      $imported[] = 'photo';
    }

    // Map languages.
    if (!empty($linkedInData['languages'])) {
      $profileData['languages'] = array_map(function ($lang) {
        return [
          'language' => $lang['name'] ?? '',
          'proficiency' => $this->mapProficiency($lang['proficiency'] ?? ''),
        ];
      }, $linkedInData['languages']);
      $imported[] = 'languages';
    }

    // Save the mapped data.
    $profileData['linkedin_import_date'] = date('Y-m-d\TH:i:s');
    $profileData['linkedin_profile_url'] = $linkedInData['publicProfileUrl'] ?? '';

    try {
      $this->profileService->updateProfile($userId, $profileData);
    }
    catch (\Exception $e) {
      return [
        'error' => 'Failed to save imported data: ' . $e->getMessage(),
        'imported_sections' => $imported,
      ];
    }

    return [
      'user_id' => $userId,
      'imported_sections' => $imported,
      'skipped_sections' => $skipped,
      'total_imported' => count($imported),
      'import_date' => date('Y-m-d\TH:i:s'),
    ];
  }

  /**
   * Maps LinkedIn positions to platform experience format.
   *
   * @param array $positions
   *   LinkedIn positions data.
   *
   * @return array
   *   Mapped experience entries.
   */
  public function mapExperience(array $positions): array {
    $experience = [];

    foreach ($positions as $position) {
      $entry = [
        'title' => $position['title'] ?? '',
        'company' => $position['company']['name'] ?? $position['companyName'] ?? '',
        'location' => $position['location'] ?? '',
        'description' => $position['summary'] ?? $position['description'] ?? '',
        'is_current' => !isset($position['endDate']),
      ];

      if (isset($position['startDate'])) {
        $entry['start_date'] = $this->formatLinkedInDate($position['startDate']);
      }
      if (isset($position['endDate'])) {
        $entry['end_date'] = $this->formatLinkedInDate($position['endDate']);
      }

      // Calculate duration in months.
      if (isset($position['startDate'])) {
        $startYear = (int) ($position['startDate']['year'] ?? date('Y'));
        $startMonth = (int) ($position['startDate']['month'] ?? 1);
        $endYear = (int) ($position['endDate']['year'] ?? date('Y'));
        $endMonth = (int) ($position['endDate']['month'] ?? (int) date('m'));
        $entry['duration_months'] = ($endYear - $startYear) * 12 + ($endMonth - $startMonth);
      }

      $experience[] = $entry;
    }

    // Sort by start date descending (most recent first).
    usort($experience, fn($a, $b) => ($b['start_date'] ?? '') <=> ($a['start_date'] ?? ''));

    return $experience;
  }

  /**
   * Maps LinkedIn education entries to platform format.
   *
   * @param array $education
   *   LinkedIn education data.
   *
   * @return array
   *   Mapped education entries.
   */
  public function mapEducation(array $education): array {
    $mapped = [];

    foreach ($education as $edu) {
      $entry = [
        'institution' => $edu['schoolName'] ?? $edu['school'] ?? '',
        'degree' => $edu['degree'] ?? $edu['degreeName'] ?? '',
        'field_of_study' => $edu['fieldOfStudy'] ?? '',
        'description' => $edu['activities'] ?? $edu['description'] ?? '',
      ];

      if (isset($edu['startDate'])) {
        $entry['start_year'] = (int) ($edu['startDate']['year'] ?? 0);
      }
      if (isset($edu['endDate'])) {
        $entry['end_year'] = (int) ($edu['endDate']['year'] ?? 0);
      }

      $mapped[] = $entry;
    }

    return $mapped;
  }

  /**
   * Maps LinkedIn skills to platform taxonomy.
   *
   * @param array $skills
   *   LinkedIn skills data.
   *
   * @return array
   *   Mapped skills with name and endorsement count.
   */
  public function mapSkills(array $skills): array {
    $mapped = [];

    foreach ($skills as $skill) {
      $name = is_string($skill) ? $skill : ($skill['name'] ?? $skill['skill'] ?? '');

      if (empty($name)) {
        continue;
      }

      $mapped[] = [
        'name' => $name,
        'endorsements' => (int) ($skill['endorsementCount'] ?? $skill['endorsements'] ?? 0),
        'category' => $this->categorizeSkill($name),
      ];
    }

    // Sort by endorsements descending.
    usort($mapped, fn($a, $b) => $b['endorsements'] <=> $a['endorsements']);

    return $mapped;
  }

  /**
   * Gets the status of the last import for a user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array|null
   *   Import status or NULL if never imported.
   */
  public function getImportStatus(int $userId): ?array {
    try {
      $profile = $this->profileService->getProfile($userId);
    }
    catch (\Exception) {
      return NULL;
    }

    if (!$profile || empty($profile['linkedin_import_date'])) {
      return NULL;
    }

    return [
      'user_id' => $userId,
      'last_import_date' => $profile['linkedin_import_date'],
      'linkedin_profile_url' => $profile['linkedin_profile_url'] ?? '',
      'status' => 'completed',
    ];
  }

  /**
   * Formats a LinkedIn date object to Y-m-d string.
   */
  protected function formatLinkedInDate(array $date): string {
    $year = $date['year'] ?? date('Y');
    $month = str_pad((string) ($date['month'] ?? 1), 2, '0', STR_PAD_LEFT);
    $day = str_pad((string) ($date['day'] ?? 1), 2, '0', STR_PAD_LEFT);
    return "{$year}-{$month}-{$day}";
  }

  /**
   * Maps LinkedIn proficiency to platform levels.
   */
  protected function mapProficiency(string $proficiency): string {
    $map = [
      'NATIVE_OR_BILINGUAL' => 'native',
      'FULL_PROFESSIONAL' => 'advanced',
      'PROFESSIONAL_WORKING' => 'intermediate',
      'LIMITED_WORKING' => 'basic',
      'ELEMENTARY' => 'beginner',
    ];

    return $map[$proficiency] ?? 'intermediate';
  }

  /**
   * Categorizes a skill name into platform categories.
   */
  protected function categorizeSkill(string $name): string {
    $name = mb_strtolower($name);

    $technical = ['python', 'java', 'php', 'javascript', 'sql', 'react', 'node', 'docker', 'aws', 'azure', 'git'];
    $digital = ['seo', 'social media', 'google ads', 'analytics', 'photoshop', 'wordpress', 'excel'];
    $soft = ['leadership', 'communication', 'teamwork', 'management', 'negotiation', 'presentation'];

    foreach ($technical as $keyword) {
      if (str_contains($name, $keyword)) {
        return 'technical';
      }
    }
    foreach ($digital as $keyword) {
      if (str_contains($name, $keyword)) {
        return 'digital';
      }
    }
    foreach ($soft as $keyword) {
      if (str_contains($name, $keyword)) {
        return 'soft';
      }
    }

    return 'other';
  }

}
