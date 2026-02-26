<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\CommandBar;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\CommandBar\ActionCommandProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests the ActionCommandProvider search and access filtering.
 *
 * ActionCommandProvider uses StringTranslationTrait. In unit tests without
 * the Drupal container, we must inject a mock TranslationInterface via
 * setStringTranslation() so that t() works without the container.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\CommandBar\ActionCommandProvider
 */
class ActionCommandProviderTest extends TestCase {

  /**
   * The provider under test.
   */
  protected ActionCommandProvider $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a mock TranslationInterface that returns the untranslated string.
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(function ($translatable_string) {
        return $translatable_string->getUntranslatedString();
      });

    $this->provider = new ActionCommandProvider();
    $this->provider->setStringTranslation($translation);
  }

  /**
   * Tests that search finds actions matching label text.
   */
  public function testSearchFindsActions(): void {
    // "article" should match "Create Article" via label or keywords.
    $results = $this->provider->search('article');

    $this->assertNotEmpty($results, 'Expected at least one result for "article".');

    $labels = array_map(fn(array $r) => $r['label'], $results);
    $found = FALSE;
    foreach ($labels as $label) {
      if (str_contains(mb_strtolower((string) $label), 'article')) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found, 'Expected a result containing "article" in label.');

    // Every result must have the required keys.
    foreach ($results as $result) {
      $this->assertArrayHasKey('label', $result);
      $this->assertArrayHasKey('url', $result);
      $this->assertArrayHasKey('icon', $result);
      $this->assertArrayHasKey('category', $result);
      $this->assertArrayHasKey('score', $result);
    }
  }

  /**
   * Tests that search returns empty array when no actions match.
   */
  public function testSearchNoMatch(): void {
    $results = $this->provider->search('zzzznonexistent');
    $this->assertSame([], $results);
  }

  /**
   * Tests that search matches via keywords.
   */
  public function testSearchMatchesKeywords(): void {
    // "copilot" is in the keywords of "Open AI Copilot".
    $results = $this->provider->search('copilot');
    $this->assertNotEmpty($results, 'Expected results for keyword "copilot".');
  }

  /**
   * Tests that the score is higher when the label starts with the query.
   */
  public function testSearchScorePrefixMatch(): void {
    // "Create" starts the label "Create Article", so score should be 95.
    $results = $this->provider->search('create');
    $this->assertNotEmpty($results);

    $firstResult = $results[0];
    $this->assertSame(95, $firstResult['score']);
  }

  /**
   * Tests that the limit parameter caps the number of results.
   */
  public function testSearchRespectsLimit(): void {
    // There are 5 actions. Searching a very broad term that may match multiple.
    // Use a single character that appears in many keywords.
    $results = $this->provider->search('a', 2);
    $this->assertLessThanOrEqual(2, count($results));
  }

  /**
   * Tests that isAccessible returns true for an authenticated user.
   */
  public function testIsAccessibleForAuthenticated(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $this->assertTrue($this->provider->isAccessible($account));
  }

  /**
   * Tests that isAccessible returns false for an anonymous user.
   */
  public function testIsAccessibleForAnonymous(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(FALSE);

    $this->assertFalse($this->provider->isAccessible($account));
  }

}
