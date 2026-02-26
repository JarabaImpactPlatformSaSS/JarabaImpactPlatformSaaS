<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * A/B Test infrastructure for meta-site headlines.
 *
 * Sprint 5 — Optimización Continua (#17).
 *
 * DESIGN DECISIONS:
 * - Cookie-based variant assignment for consistency across sessions.
 * - Config-driven: tests defined in ecosistema_jaraba_core.ab_tests config.
 * - No external dependency (no Optimizely/VWO needed).
 * - Statistical significance tracked via simple event counting.
 *
 * USAGE:
 * @code
 * $abTest = \Drupal::service('ecosistema_jaraba_core.ab_test');
 * $variant = $abTest->getVariant('homepage_headline');
 * // Returns 'A' or 'B'.
 * @endcode
 */
class AbTestService {

  /**
   * Config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs the A/B test service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user
  ) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Gets the variant for a given test.
   *
   * @param string $test_id
   *   The test identifier (e.g., 'homepage_headline').
   *
   * @return string
   *   The variant ('A' or 'B').
   */
  public function getVariant(string $test_id): string {
    $config = $this->configFactory->get('ecosistema_jaraba_core.ab_tests');
    $tests = $config->get('tests') ?? [];

    // If test is not configured or disabled, return 'A' (control).
    if (empty($tests[$test_id]) || !($tests[$test_id]['enabled'] ?? FALSE)) {
      return 'A';
    }

    // Check cookie for existing assignment.
    $cookieName = 'ab_' . $test_id;
    $request = \Drupal::request();
    $existingVariant = $request->cookies->get($cookieName);

    if ($existingVariant && in_array($existingVariant, ['A', 'B'], TRUE)) {
      return $existingVariant;
    }

    // Assign variant based on traffic split (default 50/50).
    $split = $tests[$test_id]['split'] ?? 50;
    $variant = (random_int(1, 100) <= $split) ? 'A' : 'B';

    // Set cookie for 30 days.
    setcookie($cookieName, $variant, [
      'expires' => time() + (30 * 24 * 60 * 60),
      'path' => '/',
      'secure' => TRUE,
      'httponly' => FALSE,
      'samesite' => 'Lax',
    ]);

    return $variant;
  }

  /**
   * Records a conversion event for a test.
   *
   * @param string $test_id
   *   The test identifier.
   * @param string $variant
   *   The variant ('A' or 'B').
   * @param string $event_type
   *   The event type (e.g., 'click', 'register', 'purchase').
   */
  public function recordConversion(string $test_id, string $variant, string $event_type = 'click'): void {
    $key = "ab_test:{$test_id}:{$variant}:{$event_type}";
    $state = \Drupal::state();
    $current = $state->get($key, 0);
    $state->set($key, $current + 1);
  }

  /**
   * Gets test results.
   *
   * @param string $test_id
   *   The test identifier.
   *
   * @return array
   *   Array with 'A' and 'B' sub-arrays containing conversion counts.
   */
  public function getResults(string $test_id): array {
    $state = \Drupal::state();
    return [
      'A' => [
        'impressions' => $state->get("ab_test:{$test_id}:A:impression", 0),
        'clicks' => $state->get("ab_test:{$test_id}:A:click", 0),
        'registers' => $state->get("ab_test:{$test_id}:A:register", 0),
      ],
      'B' => [
        'impressions' => $state->get("ab_test:{$test_id}:B:impression", 0),
        'clicks' => $state->get("ab_test:{$test_id}:B:click", 0),
        'registers' => $state->get("ab_test:{$test_id}:B:register", 0),
      ],
    ];
  }

}
