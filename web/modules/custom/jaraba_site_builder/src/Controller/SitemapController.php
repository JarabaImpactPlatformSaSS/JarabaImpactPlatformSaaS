<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_site_builder\Service\SitemapGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para el sitemap XML público.
 */
class SitemapController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SitemapGeneratorService $sitemapService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_site_builder.sitemap'),
        );
    }

    /**
     * GET /sitemap.xml - Genera y devuelve el sitemap XML.
     */
    public function xml(): Response
    {
        $xml = $this->sitemapService->generateXML();

        $response = new Response($xml);
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

}
