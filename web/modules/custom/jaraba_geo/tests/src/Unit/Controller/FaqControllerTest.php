<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_geo\Unit\Controller;

use Drupal\jaraba_geo\Controller\FaqController;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the FaqController.
 *
 * @coversDefaultClass \Drupal\jaraba_geo\Controller\FaqController
 * @group jaraba_geo
 */
class FaqControllerTest extends UnitTestCase {

  /**
   * The controller under test.
   */
  protected FaqController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controller = new FaqController();

    // FaqController extends ControllerBase which uses $this->t().
    // We need string_translation in the container.
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests that faqPage returns a render array with #theme key.
   *
   * @covers ::faqPage
   */
  public function testFaqPageReturnsRenderArray(): void {
    $build = $this->controller->faqPage();

    $this->assertIsArray($build);
    $this->assertArrayHasKey('#theme', $build);
    $this->assertSame('geo_faq_page', $build['#theme']);
  }

  /**
   * Tests that faqPage includes FAQ items.
   *
   * @covers ::faqPage
   */
  public function testFaqPageIncludesFaqItems(): void {
    $build = $this->controller->faqPage();

    $this->assertArrayHasKey('#faqs', $build);
    $this->assertIsArray($build['#faqs']);
    $this->assertNotEmpty($build['#faqs']);
  }

  /**
   * Tests that each FAQ item has required structure.
   *
   * @covers ::faqPage
   */
  public function testFaqItemsHaveRequiredKeys(): void {
    $build = $this->controller->faqPage();
    $faqs = $build['#faqs'];

    foreach ($faqs as $index => $faq) {
      $this->assertArrayHasKey('category', $faq, "FAQ item {$index} missing 'category' key.");
      $this->assertArrayHasKey('question', $faq, "FAQ item {$index} missing 'question' key.");
      $this->assertArrayHasKey('answer', $faq, "FAQ item {$index} missing 'answer' key.");
      $this->assertArrayHasKey('keywords', $faq, "FAQ item {$index} missing 'keywords' key.");
      $this->assertIsArray($faq['keywords'], "FAQ item {$index} 'keywords' should be an array.");
    }
  }

  /**
   * Tests that faqPage attaches Schema.org FAQPage structured data.
   *
   * @covers ::faqPage
   */
  public function testFaqPageAttachesFaqSchema(): void {
    $build = $this->controller->faqPage();

    $this->assertArrayHasKey('#attached', $build);
    $this->assertArrayHasKey('html_head', $build['#attached']);

    // Find the schema script tag.
    $htmlHead = $build['#attached']['html_head'];
    $this->assertNotEmpty($htmlHead);

    $schemaTag = $htmlHead[0][0] ?? NULL;
    $this->assertNotNull($schemaTag);
    $this->assertSame('script', $schemaTag['#tag']);
    $this->assertSame('application/ld+json', $schemaTag['#attributes']['type']);

    // Verify the JSON-LD contains FAQPage type.
    $schemaJson = $schemaTag['#value'];
    $schema = json_decode($schemaJson, TRUE);
    $this->assertNotNull($schema, 'Schema JSON should be valid JSON.');
    $this->assertSame('FAQPage', $schema['@type']);
    $this->assertSame('https://schema.org', $schema['@context']);
    $this->assertArrayHasKey('mainEntity', $schema);
    $this->assertNotEmpty($schema['mainEntity']);
  }

  /**
   * Tests that the schema mainEntity items have Question/Answer structure.
   *
   * @covers ::faqPage
   */
  public function testFaqSchemaQuestionsHaveCorrectStructure(): void {
    $build = $this->controller->faqPage();

    $schemaJson = $build['#attached']['html_head'][0][0]['#value'];
    $schema = json_decode($schemaJson, TRUE);

    foreach ($schema['mainEntity'] as $index => $question) {
      $this->assertSame('Question', $question['@type'], "mainEntity[{$index}] should be type Question.");
      $this->assertArrayHasKey('name', $question, "Question[{$index}] missing 'name'.");
      $this->assertNotEmpty($question['name'], "Question[{$index}] 'name' should not be empty.");

      $this->assertArrayHasKey('acceptedAnswer', $question, "Question[{$index}] missing 'acceptedAnswer'.");
      $this->assertSame('Answer', $question['acceptedAnswer']['@type'], "acceptedAnswer[{$index}] should be type Answer.");
      $this->assertArrayHasKey('text', $question['acceptedAnswer'], "Answer[{$index}] missing 'text'.");
      $this->assertNotEmpty($question['acceptedAnswer']['text'], "Answer[{$index}] 'text' should not be empty.");
    }
  }

}
