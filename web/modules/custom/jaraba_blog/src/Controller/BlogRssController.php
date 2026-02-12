<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_blog\Service\BlogRssService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para el feed RSS del blog.
 */
class BlogRssController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected BlogRssService $rssService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_blog.rss'),
    );
  }

  /**
   * GET /blog/feed.xml - Feed RSS.
   */
  public function feed(): Response {
    try {
      $xml = $this->rssService->generateFeed();

      return new Response($xml, 200, [
        'Content-Type' => 'application/rss+xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=3600',
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('jaraba_blog')->error('Error generando RSS: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new Response('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Error</title></channel></rss>', 500, [
        'Content-Type' => 'application/rss+xml; charset=utf-8',
      ]);
    }
  }

}
