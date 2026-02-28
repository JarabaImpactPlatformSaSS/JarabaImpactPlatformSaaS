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
      $description = 'ServiciosConecta: plataforma SaaS para profesionales de servicios. Motor de reservas 24/7, videoconsulta online, marketplace profesional, cobro automatico via Stripe y Copilot IA. Plan gratuito disponible.';

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
        'description' => 'Plataforma de gestion integral para profesionales de servicios: motor de reservas 24/7, videoconsulta via Jitsi, marketplace profesional, cobro automatico via Stripe Connect, paquetes de sesiones, resenas verificadas y Copilot IA.',
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
