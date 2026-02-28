<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ecosistema_jaraba_core\Service\StylePresetService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * BE-09: OOP hook for page_attachments_alter with proper DI.
 *
 * Replaces \Drupal::service('path.current') service locator pattern.
 * Adds PWA meta tags and premium admin styles.
 */
class PageAttachmentsHooks
{

  public function __construct(
    protected readonly CurrentPathStack $currentPath,
    protected readonly StylePresetService $stylePreset,
    protected readonly TenantContextService $tenantContext,
    protected readonly RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * Adds PWA meta tags and premium admin styles.
   */
  #[Hook('page_attachments_alter')]
  public function alterPageAttachments(array &$attachments): void
  {
    $this->addPwaMetaTags($attachments);
    $this->addDesignTokenCss($attachments);
    $this->addPremiumAdminStyles($attachments);
    $this->addLandingSeo($attachments);
  }

  /**
   * Adds PWA meta tags and service worker registration.
   */
  protected function addPwaMetaTags(array &$attachments): void
  {
    // Web App Manifest
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'manifest',
          'href' => '/manifest.webmanifest',
        ],
      ],
      'pwa_manifest',
    ];

    // Theme Color
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'theme-color',
          'content' => '#3b82f6',
        ],
      ],
      'pwa_theme_color',
    ];

    // Apple Mobile Web App Capable (iOS Safari — required for Add to Home Screen)
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'apple-mobile-web-app-capable',
          'content' => 'yes',
        ],
      ],
      'pwa_apple_capable',
    ];

    // Mobile Web App Capable (W3C Standard — Chrome/Android)
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'mobile-web-app-capable',
          'content' => 'yes',
        ],
      ],
      'pwa_mobile_capable',
    ];

    // Apple Status Bar Style
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'apple-mobile-web-app-status-bar-style',
          'content' => 'black-translucent',
        ],
      ],
      'pwa_apple_status_bar',
    ];

    // Apple Mobile Web App Title
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'apple-mobile-web-app-title',
          'content' => 'Jaraba',
        ],
      ],
      'pwa_apple_title',
    ];

    // Apple Touch Icon
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'apple-touch-icon',
          'href' => '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-192x192.png',
        ],
      ],
      'pwa_apple_touch_icon',
    ];

    // Service Worker Registration Script
    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => "
          if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
              navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                  console.log('[PWA] Service Worker registrado:', registration.scope);
                })
                .catch(function(error) {
                  console.log('[PWA] Error al registrar Service Worker:', error);
                });
            });
          }
        ",
      ],
      'pwa_service_worker',
    ];
  }

  /**
   * Inyecta Design Tokens como CSS custom properties en :root.
   *
   * Resuelve la cascada Platform → Vertical → Plan → Tenant
   * y genera un bloque <style> con --ej-* custom properties.
   */
  protected function addDesignTokenCss(array &$attachments): void
  {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tokens = $this->stylePreset->resolveTokensForTenant($tenant);
    $css = $this->stylePreset->generateInlineCss($tokens);

    if (empty($css)) {
      return;
    }

    $attachments['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => $css,
        '#attributes' => [
          'id' => 'ej-design-tokens',
        ],
      ],
      'ej_design_tokens',
    ];
  }

  /**
   * Adds premium admin styles for structure pages.
   */
  protected function addPremiumAdminStyles(array &$attachments): void
  {
    $current_path = $this->currentPath->getPath();

    // Admin tabs fix for Spanish text truncation
    if (str_starts_with($current_path, '/admin/content') || str_contains($current_path, '/es/admin/content')) {
      $attachments['#attached']['library'][] = 'ecosistema_jaraba_core/admin-tabs-fix';
    }

    // Premium paths that need UX Premium styles
    $premium_paths = [
      '/admin/structure/tenants',
      '/admin/structure/verticales',
      '/admin/structure/features',
      '/admin/structure/ai-agents',
      '/admin/structure/saas-plans',
      '/admin/structure/design-tokens',
    ];

    $load_premium = FALSE;
    foreach ($premium_paths as $path) {
      if (str_starts_with($current_path, $path)) {
        $load_premium = TRUE;
        break;
      }
    }

    if ($load_premium) {
      $attachments['#attached']['library'][] = 'ecosistema_jaraba_core/global';
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'style',
          '#value' => $this->getPremiumAdminCss(),
        ],
        'ecosistema_jaraba_premium_structure_styles',
      ];
    }
  }

  /**
   * Adds SEO meta tags and Schema.org JSON-LD for landing pages.
   *
   * Plan Unificacion JarabaLex + Despachos v1 — Fase 4.
   */
  protected function addLandingSeo(array &$attachments): void
  {
    $route = (string) $this->routeMatch->getRouteName();

    // --- Homepage (frontpage) ---
    if (\Drupal::service('path.matcher')->isFrontPage()) {
      $description = 'Jaraba Impact Platform: plataforma SaaS con 10 verticales especializados, 11 agentes IA Gen 2, copiloto integrado, firma digital PAdES y 80+ módulos Drupal 11. Empleabilidad, emprendimiento, agroalimentación, comercio local, servicios profesionales, inteligencia legal, formación, talento y más. Desde 0 EUR/mes.';

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'homepage_meta_description',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'Jaraba Impact Platform — Empleo, emprendimiento y digitalización con IA',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'homepage_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Jaraba Impact Platform — 10 verticales con IA',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'homepage_' . str_replace(':', '_', $name),
        ];
      }
    }

    if ($route === 'ecosistema_jaraba_core.landing.jarabalex') {
      // Meta description.
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => 'JarabaLex: inteligencia legal con IA. Gestiona expedientes, busca jurisprudencia en 8 fuentes oficiales, presenta en LexNET y factura. Desde 0 EUR/mes.',
          ],
        ],
        'jarabalex_meta_description',
      ];

      // Schema.org SoftwareApplication JSON-LD.
      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'JarabaLex',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Plataforma de inteligencia legal con IA: gestion de expedientes, busqueda semantica en CENDOJ/BOE/EUR-Lex, integracion LexNET, facturacion y boveda documental cifrada.',
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan Free: 5 expedientes, 10 busquedas/mes, boveda 100 MB',
        ],
        'featureList' => [
          'Busqueda semantica con IA en 8 fuentes oficiales',
          'Gestion integral de expedientes juridicos',
          'Integracion LexNET (CGPJ)',
          'Facturacion automatizada con serie fiscal legal',
          'Boveda documental cifrada end-to-end',
          'Alertas inteligentes de cambios normativos',
          'Agenda con plazos procesales',
          'Copiloto legal con IA',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'jarabalex_schema_org',
      ];
    }

    if ($route === 'ecosistema_jaraba_core.landing.serviciosconecta') {
      $description = 'ServiciosConecta: plataforma SaaS para profesionales de servicios. Motor de reservas 24/7, videoconsulta online, marketplace profesional, presupuestador IA, firma digital PAdES, buzon de confianza cifrado, cobro automatico via Stripe y Copilot IA. Plan gratuito disponible.';

      // Meta description.
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'serviciosconecta_meta_description',
      ];

      // Schema.org SoftwareApplication JSON-LD.
      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'ServiciosConecta',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Plataforma de gestion integral para profesionales de servicios: motor de reservas 24/7, videoconsulta via Jitsi, marketplace profesional, presupuestador IA, firma digital PAdES, buzon de confianza cifrado, cobro automatico via Stripe Connect, paquetes de sesiones, resenas verificadas y Copilot IA.',
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan Free: 3 servicios, 10 reservas/mes, perfil en marketplace',
        ],
        'featureList' => [
          'Motor de reservas online 24/7 con recordatorios',
          'Videoconsulta integrada via Jitsi Meet',
          'Marketplace profesional con portfolio y certificaciones',
          'Paquetes y bonos de sesiones con descuento',
          'Resenas y reputacion verificada con Schema.org',
          'Cobro automatico via Stripe Connect',
          'Copilot IA para optimizar tu oferta',
          'Dashboard con analitica en tiempo real',
          'Presupuestador automatico con IA',
          'Firma digital PAdES con validez legal europea',
          'Buzon de confianza con mensajeria cifrada end-to-end',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'serviciosconecta_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'ServiciosConecta — Digitaliza tu negocio de servicios',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/serviciosconecta',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'serviciosconecta_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'ServiciosConecta — Digitaliza tu negocio de servicios',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'serviciosconecta_' . str_replace(':', '_', $name),
        ];
      }
    }

    if ($route === 'ecosistema_jaraba_core.landing.comercioconecta') {
      $description = 'ComercioConecta: plataforma SaaS de comercio local digital. Catalogo online, ofertas flash, codigos QR, click & collect, pagos Stripe, Copilot IA, analytics en tiempo real, SEO local y fidelizacion. Plan gratuito disponible.';

      // Meta description.
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'comercioconecta_meta_description',
      ];

      // Schema.org SoftwareApplication JSON-LD.
      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'ComercioConecta',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Plataforma de digitalizacion integral para comercio local: catalogo online con variantes y stock, ofertas flash con temporizador, codigos QR dinamicos, click & collect, pasarela de pagos Stripe Connect, Copilot IA para comerciantes, analytics en tiempo real, SEO local automatizado, programa de fidelizacion, notificaciones push, integracion TPV, resenas verificadas, pedidos delivery, gestion multi-tienda y soporte IA 24/7.',
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan Free: 10 productos, 1 oferta flash, Copilot IA',
        ],
        'featureList' => [
          'Catalogo online profesional con variantes y stock',
          'Ofertas flash con temporizador y notificaciones',
          'Codigos QR dinamicos para escaparate',
          'Click & Collect y gestion de pedidos',
          'Pasarela de pagos via Stripe Connect',
          'Copilot IA para optimizar ventas',
          'Analytics y metricas en tiempo real',
          'SEO local automatizado con Google Business',
          'Programa de fidelizacion con puntos',
          'Notificaciones push a clientes cercanos',
          'Integracion TPV y datafono',
          'Resenas verificadas con Schema.org',
          'Pedidos delivery con zona de reparto',
          'Gestion multi-tienda centralizada',
          'Soporte IA inteligente 24/7',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'comercioconecta_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'ComercioConecta — Digitaliza tu comercio local',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/comercioconecta',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'comercioconecta_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'ComercioConecta — Digitaliza tu comercio local',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'comercioconecta_' . str_replace(':', '_', $name),
        ];
      }
    }

    if ($route === 'ecosistema_jaraba_core.landing.agroconecta') {
      $description = 'AgroConecta: marketplace agro sin intermediarios. Trazabilidad QR, envio MRW/SEUR, Copilot IA con prevision de demanda, analytics avanzados, resenas verificadas, promociones, Hub B2B y cobro seguro Stripe Connect. Plan gratuito.';

      // Meta description.
      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'agroconecta_meta_description',
      ];

      // Schema.org SoftwareApplication JSON-LD.
      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'AgroConecta',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Marketplace agroalimentario sin intermediarios: catalogo con variantes y stock, trazabilidad QR criptografica, envio MRW y SEUR con tracking, Copilot IA con prevision de demanda y sugerencia de precios, analytics en tiempo real, resenas verificadas, promociones y cupones, Hub B2B para partners, notificaciones multi-canal, portal del cliente y cobro seguro via Stripe Connect.',
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan Free: 5 productos, Copilot IA, cobro Stripe',
        ],
        'featureList' => [
          'Producer Copilot IA con generacion de descripciones y precios',
          'QR de trazabilidad con prueba criptografica',
          'Marketplace profesional con busqueda y colecciones',
          'Envio MRW y SEUR con calculo y tracking',
          'Cobro seguro via Stripe Connect multi-vendedor',
          'Resenas verificadas con moderacion y respuestas',
          'Analytics y metricas en tiempo real',
          'Prevision de demanda IA con predicciones ML',
          'Certificaciones digitales eco, DOP y comercio justo',
          'Pedidos WhatsApp Business con recuperacion de carritos',
          'Promociones porcentuales, fijas y cupones',
          'Inteligencia de mercado y monitoreo de competencia',
          'Hub B2B Partners con documentos compliance',
          'Notificaciones email, SMS y push configurables',
          'Portal del cliente con favoritos y preferencias',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'agroconecta_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'AgroConecta — Vende del campo a la mesa sin intermediarios',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/agroconecta',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'agroconecta_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'AgroConecta — Vende del campo a la mesa sin intermediarios',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'agroconecta_' . str_replace(':', '_', $name),
        ];
      }
    }

    // --- Andalucia +ei ---
    if ($route === 'jaraba_andalucia_ei.landing') {
      $description = 'Programa gratuito de emprendimiento e insercion laboral con IA: mentoria personalizada, formacion adaptativa, expediente digital seguro, evaluacion IA de perfil, informes PDF, revision de documentos, comunicacion con mentores y exportacion STO. Subvencionado por la Junta de Andalucia y el FSE.';

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'andalucia_ei_meta_description',
      ];

      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Andalucia +ei — Emprendimiento e Insercion con IA',
        'applicationCategory' => 'EducationalApplication',
        'operatingSystem' => 'Web',
        'description' => $description,
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Programa 100% gratuito subvencionado por la Junta de Andalucia y el FSE',
        ],
        'featureList' => [
          'Mentoria con Inteligencia Artificial 24/7',
          'Formacion Adaptativa Certificada (+50h)',
          'Insercion Laboral Real con acompanamiento',
          'Expediente Digital Seguro (18 categorias)',
          'Evaluacion IA de tu Perfil (<48h)',
          'Seguimiento de Progreso en tiempo real',
          'Informes PDF Personalizados',
          'Revision IA de Documentos (CV, carta, plan de negocio)',
          'Comunicacion Directa con Mentores',
          'Exportacion STO Automatica (Junta de Andalucia)',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'andalucia_ei_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'Andalucia +ei — Emprendimiento e Insercion con IA',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/andalucia-ei/programa',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'andalucia_ei_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Andalucia +ei — Emprendimiento e Insercion con IA',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'andalucia_ei_' . str_replace(':', '_', $name),
        ];
      }
    }

    // --- Emprendimiento ---
    if ($route === 'ecosistema_jaraba_core.landing.emprendimiento') {
      $description = 'Plataforma de emprendimiento con IA: calculadora de madurez digital, Business Model Canvas con IA, validación de MVP con Lean Startup, mentoría 1:1, health score emprendedor, 15 insignias digitales, copilot proactivo, motor de experimentos A/B y acceso a financiación ICO/ENISA.';

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'emprendimiento_meta_description',
      ];

      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Emprendimiento — De idea a negocio rentable',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => $description,
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan gratuito con calculadora de madurez, 1 Business Model Canvas, 3 hipótesis activas y copilot IA',
        ],
        'featureList' => [
          'Calculadora de Madurez Digital con reporte PDF',
          'Business Model Canvas con IA iterativo',
          'Validación de MVP con Lean Startup y experimentos A/B',
          'Mentoría 1:1 con empresarios experimentados',
          'Proyecciones financieras con IA',
          'Acceso a financiación ICO, ENISA y aceleradoras',
          'Health Score Emprendedor (5 dimensiones, 0-100)',
          '15 Insignias + 3 Diplomas Digitales',
          'Niveles de Expertise con beneficios (Explorador a Master)',
          'Copilot IA Proactivo (7 nudges automáticos)',
          'Journey Personalizado por Avatar (3 itinerarios)',
          'Motor de Experimentos A/B (4 ámbitos, 10 eventos)',
          'Puentes Cross-Vertical a Formación, Comercio y Servicios',
          'Email Nurturing Automatizado por fase',
          'Cross-Sell Inteligente (4 ofertas personalizadas)',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'emprendimiento_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'Emprendimiento — De idea a negocio rentable',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/emprendimiento',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'emprendimiento_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Emprendimiento — De idea a negocio rentable',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'emprendimiento_' . str_replace(':', '_', $name),
        ];
      }
    }

    // --- Empleabilidad ---
    if ($route === 'ecosistema_jaraba_core.landing.empleabilidad') {
      $description = 'Plataforma de empleabilidad con IA: diagnóstico de competencias, rutas formativas adaptativas, CV builder con 4 plantillas ATS, matching inteligente empresa-candidato, simulador de entrevistas con IA, health score profesional, copilot con 6 modos especializados, portal de empleo, LMS gamificado y credenciales digitales verificables.';

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'empleabilidad_meta_description',
      ];

      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Empleabilidad — Tu carrera profesional con IA',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => $description,
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan gratuito con diagnóstico básico, 1 CV, 3 alertas de empleo y copilot IA',
        ],
        'featureList' => [
          'Diagnóstico Express de Competencias con IA (25 indicadores)',
          'Rutas Formativas Adaptativas por nivel y objetivo',
          'CV Builder con IA y 4 plantillas optimizadas ATS',
          'Matching Inteligente Empresa-Candidato (compatibilidad %)',
          'Simulador de Entrevistas con IA y feedback detallado',
          'Credenciales Digitales Verificables (OpenBadge v3)',
          'Health Score Profesional (6 dimensiones, 0-100)',
          'Copilot IA con 6 Modos Especializados',
          'Journey Personalizado por Avatar (3 itinerarios)',
          'Portal de Empleo con búsqueda semántica',
          'Dashboard para Empresas y Reclutadores',
          'Importación LinkedIn con análisis automático',
          'Email Nurturing Automatizado por fase profesional',
          'LMS Gamificado con insignias y leaderboards',
          'Notificaciones Web Push para ofertas y alertas',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'empleabilidad_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'Empleabilidad — Tu carrera profesional con IA',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/empleabilidad',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'empleabilidad_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Empleabilidad — Tu carrera profesional con IA',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'empleabilidad_' . str_replace(':', '_', $name),
        ];
      }
    }

    // --- Talento (recruiter-facing empleabilidad) ---
    if ($route === 'ecosistema_jaraba_core.vertical.talento') {
      $description = 'Plataforma de reclutamiento con IA: matching inteligente en 5 dimensiones, mini-ATS con 8 estados, asistente IA del reclutador con 6 acciones, perfil de empresa verificado, notificaciones multi-canal, alertas de talento inteligentes, detección de fraude anti-spam y API REST completa.';

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'description',
            'content' => $description,
          ],
        ],
        'talento_meta_description',
      ];

      $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Talento — Encuentra el talento que necesitas con IA',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => $description,
        'offers' => [
          '@type' => 'Offer',
          'price' => '0',
          'priceCurrency' => 'EUR',
          'description' => 'Plan gratuito con 1 oferta activa, búsqueda básica y notificaciones email',
        ],
        'featureList' => [
          'Búsqueda Avanzada de Talento con filtros semánticos',
          'Asistente IA del Reclutador (6 acciones automatizadas)',
          'Dashboard Analytics de contratación en tiempo real',
          'Gestión Colaborativa en equipo con trazabilidad',
          'Mini-ATS con 8 estados de pipeline',
          'Matching Inteligente 5 Dimensiones (skills, experiencia, educación, ubicación, semántico)',
          'Perfil de Empresa Verificado',
          'Health Score del Reclutador (5 fases, 0-100)',
          'Notificaciones Multi-Canal (email + Web Push + webhooks)',
          'Alertas de Talento Inteligentes con digest diario/semanal',
          'Detección de Fraude Anti-Spam integrada',
          'API REST completa con documentación OpenAPI',
        ],
      ];

      $attachments['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' => ['type' => 'application/ld+json'],
          '#value' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ],
        'talento_schema_org',
      ];

      // OG meta tags.
      $ogTags = [
        'og:title' => 'Talento — Encuentra el talento que necesitas con IA',
        'og:description' => $description,
        'og:type' => 'website',
        'og:url' => '/talento',
      ];

      foreach ($ogTags as $property => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'property' => $property,
              'content' => $content,
            ],
          ],
          'talento_' . str_replace(':', '_', $property),
        ];
      }

      // Twitter Card meta tags.
      $twitterTags = [
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Talento — Encuentra el talento que necesitas con IA',
        'twitter:description' => $description,
      ];

      foreach ($twitterTags as $name => $content) {
        $attachments['#attached']['html_head'][] = [
          [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
              'name' => $name,
              'content' => $content,
            ],
          ],
          'talento_' . str_replace(':', '_', $name),
        ];
      }
    }
  }

  /**
   * Returns the premium admin inline CSS.
   */
  protected function getPremiumAdminCss(): string
  {
    return '
      /* UX Premium para páginas de estructura - v2 sin gaps */
      body.path-admin { background: #0f172a !important; }
      body.path-admin .layout-container { background: #0f172a !important; }
      body.path-admin .gin-layer-wrapper,
      body.path-admin .region-content,
      body.path-admin main.page-content {
        background: #0f172a !important; color: #e2e8f0 !important; padding: 1rem 2rem !important;
      }
      body.path-admin .region-header,
      body.path-admin .layout-container > header,
      body.path-admin .gin-secondary-toolbar,
      body.path-admin .region-sticky,
      body.path-admin .region-breadcrumb {
        background: #0f172a !important; margin: 0 !important;
        padding-left: 1rem !important; padding-right: 1rem !important;
      }
      body.path-admin .layout-container,
      body.path-admin .gin-layer-wrapper {
        margin: 0 !important; padding: 0 !important; max-width: 100% !important;
        width: 100% !important; background: #0f172a !important;
      }
      body.path-admin .region-content {
        background: #0f172a !important; color: #e2e8f0 !important;
        padding: 1rem 2rem !important; margin: 0 !important;
      }
      body.path-admin .breadcrumb,
      body.path-admin .gin-breadcrumb-wrapper {
        padding-left: 1rem !important; background: #0f172a !important;
      }
      body.path-admin h1.page-title,
      body.path-admin .block-page-title-block h1 {
        color: #e2e8f0 !important;
        background: linear-gradient(135deg, #60a5fa, #a78bfa);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        background-clip: text; margin-bottom: 1rem !important;
        margin-top: 0 !important; margin-left: 2rem !important;
        padding-left: 0 !important; font-size: 1.75rem;
      }
      body.path-admin .block-page-title-block { padding-left: 0 !important; }
      body.path-admin .block-page-title-block,
      body.path-admin .block-local-tasks-block,
      body.path-admin .block-local-actions-block,
      body.path-admin .region-content > *,
      body.path-admin .view-content,
      body.path-admin .views-element-container {
        margin-top: 0 !important; margin-bottom: 0.75rem !important;
      }
      body.path-admin table {
        background: rgba(30, 41, 59, 0.8) !important;
        border: 1px solid rgba(238, 238, 238, 0.3) !important;
        border-radius: 12px !important; overflow: hidden;
        border-collapse: separate !important; margin-top: 1rem !important;
      }
      body.path-admin table thead tr { background: rgba(30, 41, 59, 0.95) !important; }
      body.path-admin table thead th {
        background: rgba(30, 41, 59, 0.95) !important; color: #94a3b8 !important;
        border-bottom: 1px solid rgba(238, 238, 238, 0.3) !important;
        font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 1rem !important;
      }
      body.path-admin table tbody tr { background: rgba(30, 41, 59, 0.6) !important; }
      body.path-admin table tbody td {
        color: #e2e8f0 !important;
        border-bottom: 1px solid rgba(148, 163, 184, 0.15) !important;
        background: rgba(30, 41, 59, 0.6) !important; padding: 0.875rem 1rem !important;
      }
      body.path-admin table tbody tr:hover td { background: rgba(30, 41, 59, 0.9) !important; }
      body.path-admin .action-links a,
      body.path-admin a.button--primary,
      body.path-admin .button--primary,
      body.path-admin .btn.btn-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        color: white !important; border-radius: 8px !important;
        padding: 0.75rem 1.5rem !important; border: none !important;
        text-decoration: none !important; display: inline-block;
        font-weight: 500; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
      }
      body.path-admin .action-links a:hover,
      body.path-admin a.button--primary:hover,
      body.path-admin .btn.btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
        transform: translateY(-1px); text-decoration: none !important;
      }
      body.path-admin .button,
      body.path-admin button.button,
      body.path-admin a.button,
      body.path-admin input[type="submit"],
      body.path-admin .form-actions .button {
        border-radius: 8px !important; padding: 0.75rem 1.5rem !important;
        font-weight: 500 !important; text-decoration: none !important; border: none !important;
      }
      body.path-admin .button--danger,
      body.path-admin a.button--danger,
      body.path-admin .button--delete,
      body.path-admin a.button--delete,
      body.path-admin input.button--danger,
      body.path-admin button.button--danger,
      body.path-admin .form-actions a[href*="delete"] {
        background: transparent !important; color: #ef4444 !important;
        border: 2px solid #ef4444 !important; border-radius: 8px !important;
      }
      body.path-admin .button--danger:hover,
      body.path-admin a.button--danger:hover,
      body.path-admin .button--delete:hover,
      body.path-admin a.button--delete:hover,
      body.path-admin .form-actions a[href*="delete"]:hover {
        background: rgba(239, 68, 68, 0.1) !important; transform: translateY(-1px);
      }
      body.path-admin a.btn,
      body.path-admin .premium-header__actions a { text-decoration: none !important; }
      body.path-admin a:not(.button):not(.btn) { color: #60a5fa; }
      body.path-admin .dropbutton-wrapper .dropbutton-widget {
        background: rgba(30, 41, 59, 0.95) !important;
        border: 1px solid rgba(238, 238, 238, 0.3) !important; border-radius: 8px !important;
      }
      body.path-admin .dropbutton-wrapper .dropbutton-widget button,
      body.path-admin .dropbutton-wrapper .dropbutton-widget a {
        color: #e2e8f0 !important; background: transparent !important;
        text-decoration: none !important;
      }
      body.path-admin .dropbutton-wrapper .dropbutton-widget a:hover {
        background: rgba(96, 165, 250, 0.2) !important;
      }
      body.path-admin .dropbutton-wrapper .secondary-action {
        background: #1e293b !important;
        border: 1px solid rgba(238, 238, 238, 0.3) !important;
      }
      body.path-admin .dropbutton-wrapper .secondary-action a {
        background: #1e293b !important; color: #e2e8f0 !important;
        padding: 0.5rem 1rem !important;
      }
      body.path-admin .dropbutton-wrapper .secondary-action a:hover { background: #334155 !important; }
      body.path-admin .dropbutton .dropbutton-widget ul {
        background: #1e293b !important;
        border: 1px solid rgba(238, 238, 238, 0.3) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
      }
      body.path-admin .dropbutton .dropbutton-widget ul li { background: #1e293b !important; }
      body.path-admin .dropbutton .dropbutton-widget ul li a {
        background: #1e293b !important; color: #e2e8f0 !important;
        padding: 0.5rem 1rem !important;
      }
      body.path-admin .dropbutton .dropbutton-widget ul li a:hover { background: #334155 !important; }
      body.path-admin .messages { border-radius: 8px; margin-bottom: 1rem; }
      body.path-admin .breadcrumb ol li a { color: #94a3b8 !important; text-decoration: none !important; }
      body.path-admin .breadcrumb ol li a:hover { color: #60a5fa !important; }
      body.path-admin .tabs--primary {
        border-bottom: 1px solid rgba(148, 163, 184, 0.2); margin-bottom: 1rem;
      }
      body.path-admin .tabs--primary a { color: #94a3b8 !important; text-decoration: none !important; }
      body.path-admin .tabs--primary a.is-active {
        color: #60a5fa !important; border-bottom: 2px solid #60a5fa;
      }
    ';
  }

}
