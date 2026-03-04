<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Genera meta tags SEO basados en VerticalBrandConfig.
 *
 * Provee title templates, meta description, OpenGraph, Twitter Cards,
 * JSON-LD Schema.org y breadcrumbs basados en la configuracion
 * de marca del vertical.
 */
class VerticalBrandSeoService {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Obtiene meta tags SEO para un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Array con keys: title_template, description, og, twitter, canonical.
   */
  public function getSeoMetaTags(string $vertical): array {
    $config = $this->loadBrandConfig($vertical);
    if (!$config) {
      return $this->getDefaultSeoTags();
    }

    $publicName = $config->get('public_name') ?? 'Jaraba';
    $description = $config->get('seo_description') ?? '';
    $titleTemplate = $config->get('seo_title_template') ?? '{page_title} | ' . $publicName;
    $ogImage = $config->get('og_image_url') ?? '';

    return [
      'title_template' => $titleTemplate,
      'description' => $description,
      'og' => [
        'og:site_name' => $publicName,
        'og:description' => $description,
        'og:image' => $ogImage,
        'og:type' => 'website',
      ],
      'twitter' => [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => $publicName,
        'twitter:description' => $description,
        'twitter:image' => $ogImage,
      ],
    ];
  }

  /**
   * Genera markup JSON-LD Schema.org para un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return array
   *   Schema.org JSON-LD como array.
   */
  public function getSchemaOrgMarkup(string $vertical): array {
    $config = $this->loadBrandConfig($vertical);
    if (!$config) {
      return [];
    }

    $schemaType = $config->get('schema_org_type') ?? 'Organization';
    $publicName = $config->get('public_name') ?? 'Jaraba';
    $description = $config->get('seo_description') ?? '';

    return [
      '@context' => 'https://schema.org',
      '@type' => $schemaType,
      'name' => $publicName,
      'description' => $description,
      'url' => '',
    ];
  }

  /**
   * Genera Schema.org BreadcrumbList.
   *
   * @param string $vertical
   *   Vertical canonico.
   * @param array $trail
   *   Array de breadcrumbs: [['title' => string, 'url' => string]].
   *
   * @return array
   *   JSON-LD BreadcrumbList.
   */
  public function getBreadcrumbSchema(string $vertical, array $trail): array {
    $items = [];
    foreach ($trail as $index => $crumb) {
      $items[] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $crumb['title'] ?? '',
        'item' => $crumb['url'] ?? '',
      ];
    }

    return [
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => $items,
    ];
  }

  /**
   * Carga la configuracion de marca de un vertical.
   */
  protected function loadBrandConfig(string $vertical): ?object {
    $config = $this->configFactory->get('ecosistema_jaraba_core.vertical_brand.' . $vertical);
    if ($config->isNew()) {
      return NULL;
    }
    return $config;
  }

  /**
   * Tags SEO por defecto cuando no hay config.
   */
  protected function getDefaultSeoTags(): array {
    return [
      'title_template' => '{page_title} | Jaraba',
      'description' => '',
      'og' => [
        'og:site_name' => 'Jaraba Impact Platform',
        'og:type' => 'website',
      ],
      'twitter' => [
        'twitter:card' => 'summary',
      ],
    ];
  }

}
