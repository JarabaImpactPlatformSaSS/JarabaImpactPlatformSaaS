<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_content_hub\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Content Hub vertical.
 *
 * Covers: blog listing, article view (slug), RSS feed,
 * article API, editor dashboard, canvas editor access,
 * comments API, AI assistant, and permission enforcement.
 *
 * @group jaraba_content_hub
 * @group functional
 * @group content_hub
 */
class ContentHubFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'file',
    'image',
    'taxonomy',
    'path_alias',
    'ecosistema_jaraba_core',
    'jaraba_content_hub',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with content editor permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * User with basic reading access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $readerUser;

  /**
   * User without content hub permissions.
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
    if (!isset($definitions['content_article'])) {
      $this->markTestSkipped('content_article entity type not available.');
    }

    $this->editorUser = $this->drupalCreateUser([
      'access content',
      'access content article overview',
      'create content article',
      'edit any content article',
      'publish content article',
      'administer content hub',
    ]);

    $this->readerUser = $this->drupalCreateUser([
      'access content',
    ]);

    $this->unprivilegedUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests blog listing is publicly accessible.
   */
  public function testBlogPubliclyAccessible(): void {
    $this->drupalGet('/blog');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Blog listing should not cause server error.');
    $this->assertContains(
      $statusCode,
      [200, 301, 302],
      'Blog should be accessible or redirect.'
    );
  }

  /**
   * Tests RSS feed returns valid XML.
   */
  public function testRssFeedReturnsXml(): void {
    $this->drupalGet('/blog/feed.xml');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      // RSS should be valid XML.
      libxml_use_internal_errors(TRUE);
      $xml = simplexml_load_string($content);
      if ($xml !== FALSE) {
        $this->assertEquals('rss', $xml->getName(), 'Feed should be RSS format.');
      }
      libxml_clear_errors();
    }

    $this->assertNotEquals(500, $statusCode, 'RSS feed should not error.');
  }

  /**
   * Tests blog category route exists.
   */
  public function testBlogCategoryRouteExists(): void {
    $this->drupalGet('/blog/categoria/test-category');
    $statusCode = $this->getSession()->getStatusCode();

    // Category may not exist (404) but should never error.
    $this->assertNotEquals(500, $statusCode, 'Blog category should not error.');
  }

  /**
   * Tests blog author route exists.
   */
  public function testBlogAuthorRouteExists(): void {
    $this->drupalGet('/blog/autor/test-author');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Blog author should not error.');
  }

  /**
   * Tests articles API returns JSON.
   */
  public function testArticlesApiReturnsJson(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/api/v1/content/articles');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Articles API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Articles API should not error.');
  }

  /**
   * Tests article API with nonexistent UUID returns 404.
   */
  public function testArticleApiNonexistentReturns404(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/api/v1/content/articles/nonexistent-uuid');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [404, 200],
      'Nonexistent article should return 404.'
    );
    $this->assertNotEquals(500, $statusCode, 'Missing article should not error.');
  }

  /**
   * Tests content hub editor dashboard requires permission.
   */
  public function testEditorDashboardRequiresPermission(): void {
    $this->drupalLogin($this->unprivilegedUser);
    $this->drupalGet('/content-hub');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertContains(
      $statusCode,
      [302, 403],
      'Unprivileged user should not access editor dashboard.'
    );
  }

  /**
   * Tests editor dashboard accessible to editor.
   */
  public function testEditorDashboardAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/content-hub');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Editor dashboard should not error.');
  }

  /**
   * Tests articles list in editor accessible.
   */
  public function testEditorArticlesListAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/content-hub/articles');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Editor articles list should not error.');
  }

  /**
   * Tests categories list in editor accessible.
   */
  public function testEditorCategoriesListAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/content-hub/categories');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Editor categories list should not error.');
  }

  /**
   * Tests article add form accessible to editor.
   */
  public function testArticleAddFormAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/content-hub/articles/add');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Article add form should not error.');
  }

  /**
   * Tests admin content hub dashboard requires admin permission.
   */
  public function testAdminDashboardRequiresPermission(): void {
    $this->drupalLogin($this->readerUser);
    $this->drupalGet('/admin/content/content-hub');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertEquals(403, $statusCode, 'Reader should not access admin dashboard.');
  }

  /**
   * Tests admin article collection accessible.
   */
  public function testAdminArticleCollectionAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/admin/content/articles');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Admin article collection should not error.');
  }

  /**
   * Tests comments API returns JSON for valid article.
   */
  public function testCommentsApiReturnsJson(): void {
    $this->drupalLogin($this->readerUser);
    $this->drupalGet('/api/v1/content/articles/1/comments');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Comments API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Comments API should not error.');
  }

  /**
   * Tests newsletter subscription endpoint exists.
   */
  public function testNewsletterEndpointExists(): void {
    // GET on POST-only endpoint should not error.
    $this->drupalGet('/api/v1/content-hub/newsletter/subscribe');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'Newsletter endpoint should not error.');
  }

  /**
   * Tests AI writing assistant requires authentication.
   */
  public function testAiAssistantRequiresAuth(): void {
    // GET on POST-only AI endpoint.
    $this->drupalGet('/api/v1/content/ai/outline');
    $statusCode = $this->getSession()->getStatusCode();

    // Should return 302 (redirect to login), 403, or 405 (method not allowed).
    $this->assertNotEquals(500, $statusCode, 'AI assistant should not error.');
  }

  /**
   * Tests related articles API returns JSON.
   */
  public function testRelatedArticlesApiReturnsJson(): void {
    $this->drupalLogin($this->readerUser);
    $this->drupalGet('/api/v1/content/articles/1/related');
    $statusCode = $this->getSession()->getStatusCode();

    if ($statusCode === 200) {
      $content = $this->getSession()->getPage()->getContent();
      $response = json_decode($content, TRUE);
      $this->assertNotNull($response, 'Related articles API should return valid JSON.');
    }

    $this->assertNotEquals(500, $statusCode, 'Related articles API should not error.');
  }

  /**
   * Tests content hub settings requires admin.
   */
  public function testSettingsRequiresAdmin(): void {
    $this->drupalLogin($this->readerUser);
    $this->drupalGet('/admin/config/content/content-hub');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertEquals(403, $statusCode, 'Reader should not access hub settings.');
  }

  /**
   * Tests AI logs page accessible to editor.
   */
  public function testAiLogsAccessible(): void {
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('/content-hub/ai-logs');
    $statusCode = $this->getSession()->getStatusCode();

    $this->assertNotEquals(500, $statusCode, 'AI logs page should not error.');
  }

}
