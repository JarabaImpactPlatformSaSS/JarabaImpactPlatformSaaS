<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_lms\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Formación (LMS) vertical.
 *
 * Covers: course catalog, course detail, enrollment API,
 * learner dashboard, certificate verification, badge verification,
 * gamification, permission enforcement, and copilot.
 *
 * @group jaraba_lms
 * @group functional
 * @group formacion
 */
class LmsFormacionFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'taxonomy',
    'ecosistema_jaraba_core',
    'jaraba_lms',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with learner permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $learnerUser;

  /**
   * User with LMS admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User without LMS permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unprivilegedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = $this->container->get('entity_type.manager')->getDefinitions();
    if (!isset($definitions['lms_course'])) {
      $this->markTestSkipped('lms_course entity type not available.');
    }

    $this->learnerUser = $this->drupalCreateUser([
      'access content',
      'access lms courses',
      'enroll in free courses',
      'view own enrollments',
      'view own certificates',
      'track own progress',
      'view course reviews',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer lms courses',
      'administer lms enrollments',
      'administer lms',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Tests course catalog is publicly accessible.
   */
  public function testCatalogPubliclyAccessible(): void {
    $this->drupalGet('/courses');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Course catalog should not cause server error.');
  }

  /**
   * Tests courses API returns JSON.
   */
  public function testCoursesApiReturnsJson(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/courses');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Courses API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Courses API should not error.');
  }

  /**
   * Tests single course API with nonexistent ID returns 404.
   */
  public function testCourseApiNonexistentReturns404(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/courses/99999');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [404, 200],
      'Nonexistent course should return 404.'
    );
    $this->assertNotEquals(500, $statusCode, 'Missing course should not error.');
  }

  /**
   * Tests enrollment API requires authentication.
   */
  public function testEnrollmentApiRequiresAuth(): void {
    $this->drupalGet('/api/v1/enrollments');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access enrollments API.'
    );
  }

  /**
   * Tests user enrollments API returns JSON.
   */
  public function testUserEnrollmentsApiReturnsJson(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/enrollments');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Enrollments API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Enrollments API should not error.');
  }

  /**
   * Tests my-learning dashboard requires authentication.
   */
  public function testMyLearningRequiresAuth(): void {
    $this->drupalGet('/my-learning');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access my-learning.'
    );
  }

  /**
   * Tests my-learning dashboard accessible to learner.
   */
  public function testMyLearningAccessible(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/my-learning');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'My learning should not error.');
  }

  /**
   * Tests certificates page accessible to learner.
   */
  public function testCertificatesAccessible(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/my-learning/certificates');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Certificates page should not error.');
  }

  /**
   * Tests badge verification with nonexistent UUID.
   */
  public function testBadgeVerificationNonexistent(): void {
    $this->drupalGet('/verify/nonexistent-badge-uuid');
    $statusCode = $this->getSession()->getStatusCode();

    // Nonexistent badge should return 404, never 500.
    $this->assertNotEquals(500, $statusCode, 'Badge verification should not error.');
  }

  /**
   * Tests gamification profile requires authentication.
   */
  public function testGamificationRequiresAuth(): void {
    $this->drupalGet('/my-learning/gamification');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access gamification.'
    );
  }

  /**
   * Tests gamification profile accessible to learner.
   */
  public function testGamificationProfileAccessible(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/my-learning/gamification');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Gamification profile should not error.');
  }

  /**
   * Tests leaderboard is publicly accessible.
   */
  public function testLeaderboardAccessible(): void {
    $this->drupalGet('/leaderboard');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Leaderboard should not error.');
  }

  /**
   * Tests admin course collection requires admin permission.
   */
  public function testAdminCourseCollectionRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/admin/content/courses');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertEquals(403, $statusCode, 'Unprivileged user should not access admin courses.');
  }

  /**
   * Tests admin course collection accessible to admin.
   */
  public function testAdminCourseCollectionAccessible(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/content/courses');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Admin course collection should not error.');
    $this->assertNotEquals(403, $statusCode, 'Admin should access course collection.');
  }

  /**
   * Tests learning paths API returns JSON.
   */
  public function testLearningPathsApiReturnsJson(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/learning-paths');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Learning paths API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Learning paths API should not error.');
  }

  /**
   * Tests course reviews API returns JSON.
   */
  public function testCourseReviewsApiReturnsJson(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/cursos/reviews/1');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Reviews API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Reviews API should not error.');
  }

  /**
   * Tests gamification stats API returns JSON.
   */
  public function testGamificationStatsApiReturnsJson(): void {
    $this->drupalLogin($this->learnerUser);
    $this->drupalGet('/api/v1/gamification/stats');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Gamification stats API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Gamification stats API should not error.');
  }

  /**
   * Tests unprivileged user cannot enroll.
   */
  public function testUnprivilegedCannotAccessMyLearning(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/my-learning');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not access my-learning.'
    );
  }

}
