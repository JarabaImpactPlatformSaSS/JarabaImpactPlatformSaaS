<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_email\Entity\EmailCampaign;
use Drupal\jaraba_email\Service\CampaignService;
use Drupal\jaraba_email\Service\NewsletterService;
use Drupal\jaraba_email\Service\SubscriberService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para NewsletterService.
 *
 * @covers \Drupal\jaraba_email\Service\NewsletterService
 * @group jaraba_email
 */
class NewsletterServiceTest extends UnitTestCase {

  protected NewsletterService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected CampaignService $campaignService;
  protected SubscriberService $subscriberService;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->campaignService = $this->createMock(CampaignService::class);
    $this->subscriberService = $this->createMock(SubscriberService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new NewsletterService(
      $this->entityTypeManager,
      $this->campaignService,
      $this->subscriberService,
      $this->logger,
    );
  }

  /**
   * Tests crear newsletter con articulos y listas.
   */
  public function testCreateNewsletterCreatesAndSavesCampaign(): void {
    $campaign = $this->createMock(EmailCampaign::class);
    $campaign->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['name'] === 'Mi Newsletter'
          && $values['type'] === 'newsletter'
          && $values['status'] === 'draft'
          && $values['subject_line'] === 'Asunto personalizado'
          && count($values['article_ids']) === 2
          && count($values['list_ids']) === 1;
      }))
      ->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->createNewsletter(
      'Mi Newsletter',
      [10, 20],
      [1],
      ['subject' => 'Asunto personalizado']
    );

    $this->assertSame($campaign, $result);
  }

  /**
   * Tests resumen semanal sin articulos nuevos retorna NULL.
   */
  public function testCreateWeeklyDigestNoArticlesReturnsNull(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $articleStorage = $this->createMock(EntityStorageInterface::class);
    $articleStorage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('content_article')
      ->willReturn($articleStorage);

    $result = $this->service->createWeeklyDigest(5, [1]);

    $this->assertNull($result);
  }

  /**
   * Tests generar HTML con campana sin articulos retorna cadena vacia.
   */
  public function testGenerateNewsletterHtmlEmptyArticles(): void {
    $campaign = $this->createMock(EmailCampaign::class);

    // Simular campo article_ids vacio.
    $campaign->method('get')
      ->with('article_ids')
      ->willReturn(new \ArrayObject([]));

    $result = $this->service->generateNewsletterHtml($campaign);

    $this->assertSame('', $result);
  }

  /**
   * Tests obtener estadisticas sin campanas enviadas.
   */
  public function testGetNewsletterStatsNoCampaigns(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([])->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $stats = $this->service->getNewsletterStats(30);

    $this->assertEquals(0, $stats['total_campaigns']);
    $this->assertEquals(0, $stats['total_sent']);
    $this->assertEquals(0, $stats['total_opens']);
    $this->assertEquals(0, $stats['total_clicks']);
    $this->assertEquals(0, $stats['avg_open_rate']);
    $this->assertEquals(0, $stats['avg_click_rate']);
  }

  /**
   * Tests estadisticas con campanas calcula promedios correctamente.
   */
  public function testGetNewsletterStatsCalculatesAverages(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $totalSentField1 = new \stdClass();
    $totalSentField1->value = 100;
    $uniqueOpensField1 = new \stdClass();
    $uniqueOpensField1->value = 40;
    $uniqueClicksField1 = new \stdClass();
    $uniqueClicksField1->value = 10;

    $campaign1 = $this->createMock(EmailCampaign::class);
    $campaign1->method('get')->willReturnMap([
      ['total_sent', $totalSentField1],
      ['unique_opens', $uniqueOpensField1],
      ['unique_clicks', $uniqueClicksField1],
    ]);

    $totalSentField2 = new \stdClass();
    $totalSentField2->value = 200;
    $uniqueOpensField2 = new \stdClass();
    $uniqueOpensField2->value = 60;
    $uniqueClicksField2 = new \stdClass();
    $uniqueClicksField2->value = 30;

    $campaign2 = $this->createMock(EmailCampaign::class);
    $campaign2->method('get')->willReturnMap([
      ['total_sent', $totalSentField2],
      ['unique_opens', $uniqueOpensField2],
      ['unique_clicks', $uniqueClicksField2],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$campaign1, $campaign2]);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $stats = $this->service->getNewsletterStats(30);

    $this->assertEquals(2, $stats['total_campaigns']);
    $this->assertEquals(300, $stats['total_sent']);
    $this->assertEquals(100, $stats['total_opens']);
    $this->assertEquals(40, $stats['total_clicks']);
    // avg_open_rate = (100 / 300) * 100 = 33.33...
    $this->assertEqualsWithDelta(33.33, $stats['avg_open_rate'], 0.01);
    // avg_click_rate = (40 / 300) * 100 = 13.33...
    $this->assertEqualsWithDelta(13.33, $stats['avg_click_rate'], 0.01);
  }

}
