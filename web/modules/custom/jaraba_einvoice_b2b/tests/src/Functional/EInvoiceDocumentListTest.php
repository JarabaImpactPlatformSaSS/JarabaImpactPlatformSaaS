<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the document list API filtering and pagination.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceDocumentListTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests empty document list returns proper structure.
   */
  public function testEmptyDocumentList(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertIsArray($response['data']);
    $this->assertSame(0, count($response['data']));
    $this->assertArrayHasKey('page', $response['meta']);
    $this->assertArrayHasKey('limit', $response['meta']);
    $this->assertArrayHasKey('count', $response['meta']);
  }

  /**
   * Tests document list pagination parameters.
   */
  public function testPaginationParameters(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents', [
      'query' => ['page' => 0, 'limit' => 10],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertSame(0, $response['meta']['page']);
    $this->assertSame(10, $response['meta']['limit']);
  }

  /**
   * Tests document list limit is capped at 100.
   */
  public function testLimitCapping(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents', [
      'query' => ['limit' => 999],
    ]);
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertSame(100, $response['meta']['limit'], 'Limit should be capped at 100.');
  }

  /**
   * Tests document list filter parameters are accepted.
   */
  public function testFilterParameters(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/documents', [
      'query' => [
        'direction' => 'outbound',
        'status' => 'draft',
        'payment_status' => 'pending',
      ],
    ]);
    $this->assertSession()->statusCodeEquals(200);
  }

}
