<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\jaraba_pixels\Service\EventMapperService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EventMapperService.
 *
 * @group jaraba_pixels
 * @coversDefaultClass \Drupal\jaraba_pixels\Service\EventMapperService
 */
class EventMapperServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_pixels\Service\EventMapperService
   */
  protected EventMapperService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translateString')
      ->willReturnCallback(function ($string) {
        return $string;
      });

    $this->service = new EventMapperService($stringTranslation);
  }

  /**
   * Tests that 'purchase' maps to 'Purchase' for Meta.
   *
   * @covers ::mapEvent
   */
  public function testMapEventMetaPurchase(): void {
    $result = $this->service->mapEvent('purchase', 'meta');
    $this->assertSame('Purchase', $result);
  }

  /**
   * Tests that 'purchase' maps to 'purchase' for Google.
   *
   * @covers ::mapEvent
   */
  public function testMapEventGooglePurchase(): void {
    $result = $this->service->mapEvent('purchase', 'google');
    $this->assertSame('purchase', $result);
  }

  /**
   * Tests that 'purchase' maps to 'Conversion' for LinkedIn.
   *
   * @covers ::mapEvent
   */
  public function testMapEventLinkedInPurchase(): void {
    $result = $this->service->mapEvent('purchase', 'linkedin');
    $this->assertSame('Conversion', $result);
  }

  /**
   * Tests that 'purchase' maps to 'PlaceAnOrder' for TikTok.
   *
   * @covers ::mapEvent
   */
  public function testMapEventTikTokPurchase(): void {
    $result = $this->service->mapEvent('purchase', 'tiktok');
    $this->assertSame('PlaceAnOrder', $result);
  }

  /**
   * Tests that an unmapped event returns null for Meta.
   *
   * @covers ::mapEvent
   */
  public function testMapEventReturnsNullForUnmapped(): void {
    $result = $this->service->mapEvent('custom_event', 'meta');
    $this->assertNull($result);
  }

  /**
   * Tests that an unknown platform returns null.
   *
   * @covers ::mapEvent
   */
  public function testMapEventReturnsNullForUnknownPlatform(): void {
    $result = $this->service->mapEvent('page_view', 'snapchat');
    $this->assertNull($result);
  }

  /**
   * Tests that formatMetaPayload returns all required keys.
   *
   * @covers ::formatMetaPayload
   */
  public function testFormatMetaPayloadStructure(): void {
    $analyticsData = [
      'event_type' => 'purchase',
      'timestamp' => 1700000000,
      'page_url' => 'https://example.com/checkout',
      'user_email' => 'test@example.com',
      'ip_address' => '192.168.1.1',
      'user_agent' => 'Mozilla/5.0',
      'value' => 49.99,
      'currency' => 'EUR',
    ];
    $eventId = 'evt-meta-structure-1';

    $result = $this->service->formatMetaPayload($analyticsData, $eventId);

    $this->assertArrayHasKey('event_name', $result);
    $this->assertArrayHasKey('event_time', $result);
    $this->assertArrayHasKey('event_id', $result);
    $this->assertArrayHasKey('event_source_url', $result);
    $this->assertArrayHasKey('action_source', $result);
    $this->assertArrayHasKey('user_data', $result);
    $this->assertArrayHasKey('custom_data', $result);

    $this->assertSame('Purchase', $result['event_name']);
    $this->assertSame(1700000000, $result['event_time']);
    $this->assertSame($eventId, $result['event_id']);
    $this->assertSame('https://example.com/checkout', $result['event_source_url']);
    $this->assertSame('website', $result['action_source']);
  }

  /**
   * Tests that formatMetaPayload hashes user_email with sha256.
   *
   * @covers ::formatMetaPayload
   */
  public function testFormatMetaPayloadHashesEmail(): void {
    $email = 'test@example.com';
    $analyticsData = [
      'event_type' => 'page_view',
      'timestamp' => 1700000000,
      'page_url' => 'https://example.com',
      'user_email' => $email,
    ];
    $eventId = 'evt-meta-hash-1';

    $result = $this->service->formatMetaPayload($analyticsData, $eventId);

    $expectedHash = hash('sha256', strtolower(trim($email)));

    $this->assertArrayHasKey('user_data', $result);
    $this->assertArrayHasKey('em', $result['user_data']);
    $this->assertSame([$expectedHash], $result['user_data']['em']);
  }

  /**
   * Tests that formatGooglePayload has correct structure.
   *
   * @covers ::formatGooglePayload
   */
  public function testFormatGooglePayloadStructure(): void {
    $analyticsData = [
      'event_type' => 'purchase',
      'page_url' => 'https://example.com/checkout',
      'page_title' => 'Checkout Complete',
      'value' => 99.99,
      'currency' => 'EUR',
    ];
    $clientId = 'client-id-123';

    $result = $this->service->formatGooglePayload($analyticsData, $clientId);

    $this->assertArrayHasKey('client_id', $result);
    $this->assertSame($clientId, $result['client_id']);

    $this->assertArrayHasKey('events', $result);
    $this->assertIsArray($result['events']);
    $this->assertCount(1, $result['events']);

    $event = $result['events'][0];
    $this->assertArrayHasKey('name', $event);
    $this->assertArrayHasKey('params', $event);
    $this->assertSame('purchase', $event['name']);
  }

  /**
   * Tests that getSupportedPlatforms returns 4 platforms.
   *
   * @covers ::getSupportedPlatforms
   */
  public function testGetSupportedPlatformsReturns4(): void {
    $platforms = $this->service->getSupportedPlatforms();

    $this->assertCount(4, $platforms);
    $this->assertArrayHasKey('meta', $platforms);
    $this->assertArrayHasKey('google', $platforms);
    $this->assertArrayHasKey('linkedin', $platforms);
    $this->assertArrayHasKey('tiktok', $platforms);
  }

}
