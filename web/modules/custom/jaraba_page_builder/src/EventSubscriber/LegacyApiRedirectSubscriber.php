<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirige rutas API legacy (sin /v1/) al formato versionado.
 *
 * P1-03: Mantiene backward compatibility con clientes que aun usen
 * las rutas antiguas /api/pages/* y /api/page-builder/* redirigiendo
 * con HTTP 301 al nuevo formato /api/v1/*.
 *
 * Las rutas migradas son:
 * - /api/page-builder/generate-content -> /api/v1/page-builder/generate-content
 * - /api/pages/{id}/sections*          -> /api/v1/pages/{id}/sections*
 * - /api/page-builder/section-templates -> /api/v1/page-builder/section-templates
 */
class LegacyApiRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Prefijos legacy que requieren redirect a /api/v1/.
   *
   * Solo se redirigen rutas que fueron migradas en P1-03.
   * Las rutas que ya estaban en /api/v1/ no se ven afectadas.
   *
   * @var string[]
   */
  protected const LEGACY_PREFIXES = [
    '/api/page-builder/generate-content',
    '/api/pages/',
    '/api/page-builder/section-templates',
  ];

  /**
   * Redirige peticiones a rutas API legacy con 301.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   El evento de la peticion.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Solo procesar rutas /api/ que NO esten ya versionadas.
    if (!str_starts_with($path, '/api/') || str_starts_with($path, '/api/v1/')) {
      return;
    }

    foreach (self::LEGACY_PREFIXES as $prefix) {
      if (str_starts_with($path, $prefix)) {
        // Reemplazar /api/ por /api/v1/ manteniendo el resto del path.
        $newPath = '/api/v1/' . substr($path, 5);
        $qs = $request->getQueryString();
        $url = $newPath . ($qs ? '?' . $qs : '');

        $response = new RedirectResponse($url, 301);
        $event->setResponse($response);
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Prioridad alta (antes del router) para interceptar rutas legacy.
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 300],
    ];
  }

}
