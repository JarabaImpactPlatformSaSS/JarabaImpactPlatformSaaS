<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_candidate\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Empleabilidad vertical.
 *
 * Covers: profile creation, profile editing, CV builder,
 * API endpoints, permission enforcement, and copilot access.
 *
 * @group jaraba_candidate
 * @group functional
 * @group empleabilidad
 */
class CandidateProfileFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'ecosistema_jaraba_core',
    'jaraba_candidate',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Authenticated user with candidate permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $candidateUser;

  /**
   * Authenticated user without candidate permissions.
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
    if (!isset($definitions['candidate_profile'])) {
      $this->markTestSkipped('candidate_profile entity type not available.');
    }

    $this->candidateUser = $this->drupalCreateUser([
      'access content',
      'view candidate profiles',
      'edit own candidate profile',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([
      'access content',
    ]);
  }

  /**
   * Tests my-profile route requires authentication.
   */
  public function testMyProfileRequiresAuth(): void {
    $this->drupalGet('/my-profile');
    $statusCode = $this->getSession()->getStatusCode();

    // Should redirect to login or return 403.
    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access /my-profile.'
    );
  }

  /**
   * Tests authenticated user can access my-profile.
   */
  public function testAuthenticatedUserAccessesMyProfile(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/my-profile');
    $statusCode = $this->getSession()->getStatusCode();

    // 200 if profile exists, or redirect to create.
    $this->assertNotEquals(500, $statusCode, '/my-profile should not cause server error.');
    $this->assertNotEquals(403, $statusCode, 'Authenticated user should access /my-profile.');
  }

  /**
   * Tests profile edit route is accessible to profile owner.
   */
  public function testProfileEditRouteAccessible(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/my-profile/edit');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, '/my-profile/edit should not cause server error.');
  }

  /**
   * Tests CV builder route is accessible.
   */
  public function testCvBuilderRouteAccessible(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/my-profile/cv');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'CV builder should not cause server error.');
  }

  /**
   * Tests jobseeker dashboard requires authentication.
   */
  public function testJobseekerDashboardRequiresAuth(): void {
    $this->drupalGet('/jobseeker');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access /jobseeker.'
    );
  }

  /**
   * Tests profile API requires authentication.
   */
  public function testProfileApiRequiresAuth(): void {
    $this->drupalGet('/api/v1/profile');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access profile API.'
    );
  }

  /**
   * Tests profile API returns JSON for authenticated user.
   */
  public function testProfileApiReturnsJson(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/api/v1/profile');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Profile API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Profile API should not cause server error.');
  }

  /**
   * Tests profile completion API returns valid structure.
   */
  public function testProfileCompletionApiStructure(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/api/v1/profile/completion');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Completion API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Completion API should not error.');
  }

  /**
   * Tests copilot chat endpoint requires authentication.
   */
  public function testCopilotChatRequiresAuth(): void {
    $this->drupalGet('/api/v1/copilot/employability/suggestions');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Anonymous user should not access copilot API.'
    );
  }

  /**
   * Tests copilot suggestions endpoint for authenticated user.
   */
  public function testCopilotSuggestionsAccessible(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/api/v1/copilot/employability/suggestions');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Copilot suggestions should not error.');
  }

  /**
   * Tests that unprivileged user cannot access profile sections.
   */
  public function testUnprivilegedCannotEditProfile(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/my-profile/edit');
    $statusCode = $this->getSession()->getStatusCode();

    // Without edit permission, should get 403 or redirect.
    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not edit profile.'
    );
  }

  /**
   * Tests profile section routes exist and don't error.
   *
   * @dataProvider profileSectionProvider
   */
  public function testProfileSectionsAccessible(string $section): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/my-profile/' . $section);
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, "Section /{$section} should not cause server error.");
  }

  /**
   * Data provider for profile sections.
   */
  public static function profileSectionProvider(): array {
    return [
      'personal' => ['personal'],
      'brand' => ['brand'],
      'experience' => ['experience'],
      'education' => ['education'],
      'skills' => ['skills'],
      'languages' => ['languages'],
      'privacy' => ['privacy'],
    ];
  }

  /**
   * Tests skills API returns JSON.
   */
  public function testSkillsApiReturnsJson(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/api/v1/profile/skills');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Skills API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Skills API should not error.');
  }

  /**
   * Tests experience API returns JSON.
   */
  public function testExperienceApiReturnsJson(): void {
    $this->drupalLogin($this->candidateUser);
    $this->drupalGet('/api/v1/profile/experience');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Experience API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Experience API should not error.');
  }

}
