<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SEO-REDIRECT-NODE-001: Redirige rutas de front page (/node) a URL limpia.
 *
 * Google rastrea /es/node y genera canonical mismatch porque la homepage
 * real es /es. Este subscriber intercepta la peticion y emite 301 redirect
 * antes de que el router resuelva la ruta.
 *
 * Solo redirige el match exacto del front page path (por defecto /node).
 * No afecta a /node/123 ni a ninguna otra subruta.
 */
class SeoRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * Redirige el front page path a la URL limpia con prefijo de idioma.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Obtener el front page path configurado (por defecto /node).
    $frontPage = $this->configFactory->get('system.site')->get('page.front') ?? '/node';

    // Extraer prefijo de idioma del path.
    // A esta prioridad (290), getPathInfo() aun contiene el prefijo.
    $langcode = NULL;
    $pathWithoutPrefix = $path;

    foreach ($this->languageManager->getLanguages() as $code => $language) {
      $prefix = '/' . $code;
      if ($path === $prefix . $frontPage || $path === $prefix . $frontPage . '/') {
        $langcode = $code;
        $pathWithoutPrefix = $frontPage;
        break;
      }
    }

    // Tambien manejar /node sin prefijo de idioma.
    if ($langcode === NULL && ($path === $frontPage || $path === $frontPage . '/')) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
      $pathWithoutPrefix = $frontPage;
    }

    // Solo redirigir si el path coincide exactamente con el front page.
    if ($pathWithoutPrefix !== $frontPage || $langcode === NULL) {
      return;
    }

    // Construir URL de destino: /{langcode} (homepage limpia).
    $destination = '/' . $langcode;
    $qs = $request->getQueryString();
    if ($qs !== NULL) {
      $destination .= '?' . $qs;
    }

    $event->setResponse(new RedirectResponse($destination, 301));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 290],
    ];
  }

}
