<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Content-Security-Policy headers to canvas editor responses (HAL-AI-09).
 *
 * PROPÓSITO:
 * El editor GrapesJS renderiza HTML de usuario en un iframe sin restricciones.
 * Este subscriber añade CSP headers restrictivos en las rutas del canvas editor
 * para prevenir ejecución de scripts maliciosos inyectados en canvas_data.
 *
 * RUTAS PROTEGIDAS:
 * - jaraba_page_builder.canvas_editor (Page Builder)
 * - jaraba_content_hub.article.canvas_editor (Content Hub articles)
 *
 * CSP POLICY:
 * - script-src 'self' 'unsafe-inline': GrapesJS necesita inline para funcionar.
 * - object-src 'none': Bloquea Flash/plugins.
 * - base-uri 'self': Previene base tag hijacking.
 * - frame-ancestors 'self': Previene clickjacking.
 *
 * @see HAL-AI-09
 */
class CanvasSecurityResponseSubscriber implements EventSubscriberInterface {

  /**
   * Route names that serve canvas editor pages.
   */
  protected const CANVAS_ROUTES = [
    'jaraba_page_builder.canvas_editor',
    'jaraba_content_hub.article.canvas_editor',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after normal response processing but before cache.
    return [
      KernelEvents::RESPONSE => ['onKernelResponse', -10],
    ];
  }

  /**
   * Adds CSP headers to canvas editor responses.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $routeName = $request->attributes->get('_route', '');

    if (!in_array($routeName, self::CANVAS_ROUTES, TRUE)) {
      return;
    }

    $response = $event->getResponse();

    // HAL-AI-09: Restrictive CSP for canvas editor pages.
    $csp = implode('; ', [
      "default-src 'self'",
      "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
      "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
      "img-src 'self' data: https: blob:",
      "font-src 'self' https://fonts.gstatic.com data:",
      "connect-src 'self'",
      "object-src 'none'",
      "base-uri 'self'",
      "frame-ancestors 'self'",
      "form-action 'self'",
    ]);

    $response->headers->set('Content-Security-Policy', $csp);

    // Additional security headers.
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
  }

}
