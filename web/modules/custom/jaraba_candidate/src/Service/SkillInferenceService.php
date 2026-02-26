<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * GAP-AUD-018: AI-powered skill inference from unstructured text.
 *
 * Extracts skills from CVs, experience descriptions, and free text
 * using LLM analysis. Falls back gracefully if AI agent is unavailable.
 */
class SkillInferenceService {

  /**
   * Skill categories for structured output.
   */
  private const CATEGORIES = ['technical', 'soft', 'digital', 'languages'];

  public function __construct(
    protected SkillsService $skillsService,
    protected LoggerInterface $logger,
    protected ?object $aiAgent = NULL,
    protected ?object $embeddingService = NULL,
  ) {}

  /**
   * Infers skills from free text using AI analysis.
   *
   * @param string $text
   *   Unstructured text (CV, experience description, etc.).
   * @param array $context
   *   Optional context: 'user_id', 'job_id', 'language'.
   *
   * @return array
   *   Array with 'skills' (categorized), 'confidence', 'raw_analysis'.
   */
  public function inferFromText(string $text, array $context = []): array {
    $fallback = [
      'skills' => [],
      'confidence' => 0.0,
      'raw_analysis' => '',
      'source' => 'none',
    ];

    if (empty(trim($text))) {
      return $fallback;
    }

    // If no AI agent available, use rule-based extraction.
    if ($this->aiAgent === NULL) {
      return $this->inferRuleBased($text);
    }

    try {
      $prompt = $this->buildInferencePrompt($text, $context);

      $result = $this->aiAgent->execute([
        'prompt' => $prompt,
        'tier' => 'balanced',
        'max_tokens' => 1024,
        'temperature' => 0.3,
      ]);

      $responseText = $result['response'] ?? $result['text'] ?? '';
      if (empty($responseText)) {
        $this->logger->warning('SkillInference: empty AI response, falling back to rule-based.');
        return $this->inferRuleBased($text);
      }

      $parsed = $this->parseSkillsResponse($responseText);
      if (empty($parsed['skills'])) {
        return $this->inferRuleBased($text);
      }

      return [
        'skills' => $parsed['skills'],
        'confidence' => $parsed['confidence'] ?? 0.8,
        'raw_analysis' => $responseText,
        'source' => 'ai',
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('SkillInference AI error: @error', ['@error' => $e->getMessage()]);
      return $this->inferRuleBased($text);
    }
  }

  /**
   * Matches inferred skills against job requirements.
   *
   * @param array $inferredSkills
   *   Skills from inferFromText().
   * @param int $jobId
   *   Job posting entity ID.
   *
   * @return array
   *   Gap analysis with 'matches', 'gaps', 'match_percentage'.
   */
  public function matchAgainstJob(array $inferredSkills, int $jobId): array {
    $result = [
      'matches' => [],
      'gaps' => [],
      'match_percentage' => 0.0,
      'recommendations' => [],
    ];

    try {
      $storage = \Drupal::entityTypeManager()->getStorage('job_posting');
      $job = $storage->load($jobId);
      if (!$job) {
        return $result;
      }

      $requiredSkills = $job->hasField('required_skills')
        ? ($job->get('required_skills')->value ?? '')
        : '';

      if (empty($requiredSkills)) {
        return $result;
      }

      $requiredList = array_map('trim', explode(',', $requiredSkills));
      $inferredFlat = $this->flattenSkills($inferredSkills);

      $matches = [];
      $gaps = [];

      foreach ($requiredList as $required) {
        $requiredLower = mb_strtolower($required);
        $found = FALSE;

        foreach ($inferredFlat as $skill) {
          if (mb_strtolower($skill['name']) === $requiredLower ||
              str_contains(mb_strtolower($skill['name']), $requiredLower) ||
              str_contains($requiredLower, mb_strtolower($skill['name']))) {
            $matches[] = [
              'skill' => $required,
              'level' => $skill['level'] ?? 3,
              'category' => $skill['category'] ?? 'other',
            ];
            $found = TRUE;
            break;
          }
        }

        if (!$found) {
          $gaps[] = [
            'skill' => $required,
            'priority' => 'high',
          ];
        }
      }

      $totalRequired = count($requiredList);
      $result['matches'] = $matches;
      $result['gaps'] = $gaps;
      $result['match_percentage'] = $totalRequired > 0
        ? round((count($matches) / $totalRequired) * 100, 1)
        : 0.0;

      // AI-powered training recommendations for gaps.
      if (!empty($gaps) && $this->aiAgent !== NULL) {
        $result['recommendations'] = $this->generateGapRecommendations($gaps);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('SkillInference matchAgainstJob error: @error', ['@error' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Builds the AI prompt for skill inference.
   */
  protected function buildInferencePrompt(string $text, array $context): string {
    $language = $context['language'] ?? 'es';

    $basePrompt = <<<PROMPT
Analyze the following text and extract professional skills. Return a JSON object with this exact structure:
{
  "skills": {
    "technical": [{"name": "skill name", "level": 1-5}],
    "soft": [{"name": "skill name", "level": 1-5}],
    "digital": [{"name": "skill name", "level": 1-5}],
    "languages": [{"name": "language", "level": 1-5}]
  },
  "confidence": 0.0-1.0,
  "summary": "brief analysis"
}

Level scale: 1=beginner, 2=basic, 3=intermediate, 4=advanced, 5=expert.
Infer levels from context clues (years of experience, certifications, role seniority).
Only include skills clearly mentioned or strongly implied. Language: $language.

Text to analyze:
---
$text
---
PROMPT;

    return AIIdentityRule::apply($basePrompt, TRUE);
  }

  /**
   * Parses the AI response into structured skills.
   */
  protected function parseSkillsResponse(string $response): array {
    // Extract JSON from response (may contain surrounding text).
    if (preg_match('/\{[\s\S]*"skills"[\s\S]*\}/u', $response, $matches)) {
      $decoded = json_decode($matches[0], TRUE);
      if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['skills'])) {
        return [
          'skills' => $this->validateSkills($decoded['skills']),
          'confidence' => (float) ($decoded['confidence'] ?? 0.8),
        ];
      }
    }

    return ['skills' => [], 'confidence' => 0.0];
  }

  /**
   * Validates and normalizes parsed skills structure.
   */
  protected function validateSkills(array $skills): array {
    $validated = [];

    foreach (self::CATEGORIES as $category) {
      $validated[$category] = [];
      if (!isset($skills[$category]) || !is_array($skills[$category])) {
        continue;
      }

      foreach ($skills[$category] as $skill) {
        if (empty($skill['name'])) {
          continue;
        }
        $validated[$category][] = [
          'name' => (string) $skill['name'],
          'level' => max(1, min(5, (int) ($skill['level'] ?? 3))),
        ];
      }
    }

    return $validated;
  }

  /**
   * Rule-based skill extraction as fallback.
   */
  protected function inferRuleBased(string $text): array {
    $textLower = mb_strtolower($text);
    $skills = [];

    $patterns = [
      'technical' => [
        'php' => 3, 'javascript' => 3, 'python' => 3, 'java' => 3,
        'sql' => 3, 'html' => 2, 'css' => 2, 'react' => 3,
        'drupal' => 3, 'wordpress' => 2, 'docker' => 3, 'kubernetes' => 4,
        'aws' => 3, 'azure' => 3, 'machine learning' => 4, 'data science' => 4,
      ],
      'soft' => [
        'liderazgo' => 3, 'leadership' => 3, 'comunicación' => 3, 'communication' => 3,
        'trabajo en equipo' => 3, 'teamwork' => 3, 'negociación' => 3,
        'resolución de problemas' => 3, 'problem solving' => 3,
        'gestión de proyectos' => 3, 'project management' => 3,
      ],
      'digital' => [
        'excel' => 2, 'google analytics' => 3, 'seo' => 3, 'sem' => 3,
        'social media' => 2, 'ecommerce' => 3, 'salesforce' => 3,
        'hubspot' => 3, 'power bi' => 3, 'tableau' => 3,
      ],
      'languages' => [
        'inglés' => 3, 'english' => 3, 'francés' => 3, 'french' => 3,
        'alemán' => 3, 'german' => 3, 'portugués' => 3, 'portuguese' => 3,
        'español' => 4, 'spanish' => 4,
      ],
    ];

    foreach ($patterns as $category => $keywords) {
      $skills[$category] = [];
      foreach ($keywords as $keyword => $defaultLevel) {
        if (str_contains($textLower, $keyword)) {
          $skills[$category][] = [
            'name' => ucfirst($keyword),
            'level' => $defaultLevel,
          ];
        }
      }
    }

    $totalFound = array_sum(array_map('count', $skills));

    return [
      'skills' => $skills,
      'confidence' => $totalFound > 0 ? min(0.5, $totalFound * 0.05) : 0.0,
      'raw_analysis' => '',
      'source' => 'rule_based',
    ];
  }

  /**
   * Flattens categorized skills into a flat array.
   */
  protected function flattenSkills(array $categorizedSkills): array {
    $flat = [];
    $skills = $categorizedSkills['skills'] ?? $categorizedSkills;

    foreach (self::CATEGORIES as $category) {
      if (!isset($skills[$category]) || !is_array($skills[$category])) {
        continue;
      }
      foreach ($skills[$category] as $skill) {
        $skill['category'] = $category;
        $flat[] = $skill;
      }
    }

    return $flat;
  }

  /**
   * Generates AI-powered training recommendations for skill gaps.
   */
  protected function generateGapRecommendations(array $gaps): array {
    if ($this->aiAgent === NULL || empty($gaps)) {
      return [];
    }

    try {
      $gapNames = array_map(fn($g) => $g['skill'], $gaps);
      $gapList = implode(', ', $gapNames);

      $prompt = AIIdentityRule::apply(
        "Suggest brief, actionable training recommendations for these skill gaps: $gapList. " .
        "Return JSON array: [{\"skill\": \"name\", \"recommendation\": \"brief action\", \"type\": \"course|workshop|practice\", \"estimated_hours\": N}]. Max 5 items.",
        TRUE
      );

      $result = $this->aiAgent->execute([
        'prompt' => $prompt,
        'tier' => 'fast',
        'max_tokens' => 512,
        'temperature' => 0.3,
      ]);

      $responseText = $result['response'] ?? $result['text'] ?? '';
      if (preg_match('/\[[\s\S]*\]/u', $responseText, $matches)) {
        $decoded = json_decode($matches[0], TRUE);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
          return array_slice($decoded, 0, 5);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('SkillInference recommendations error: @error', ['@error' => $e->getMessage()]);
    }

    return [];
  }

}
