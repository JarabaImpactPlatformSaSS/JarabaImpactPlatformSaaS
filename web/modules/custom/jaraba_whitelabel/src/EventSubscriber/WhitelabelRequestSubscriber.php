<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\EventSubscriber;

use Drupal\jaraba_whitelabel\Service\ConfigResolverService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel requests to resolve whitelabel configuration.
 *
 * On each incoming request, this subscriber checks the request domain
 * against the custom_domain entities and, if a match is found, attaches
 * the resolved whitelabel configuration to the request attributes so
 * that controllers and preprocessors can use it.
 */
class WhitelabelRequestSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_whitelabel\Service\ConfigResolverService $configResolver
   *   The whitelabel config resolver service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly ConfigResolverService $configResolver,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Run early so that the config is available to all subsequent listeners.
      KernelEvents::REQUEST => ['onKernelRequest', 100],
    ];
  }

  /**
   * Resolves whitelabel config for the current request domain.
   *
   * If the request is served under a custom domain that maps to a
   * whitelabel configuration, that config is attached to the request
   * attributes as 'whitelabel_config'.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The kernel request event.
   */
  public function onKernelRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    try {
      $request = $event->getRequest();
      $domain = $request->getHost();

      $config = $this->configResolver->getConfigByDomain($domain);

      if ($config !== NULL) {
        $request->attributes->set('whitelabel_config', $config);
        $this->logger->debug('Whitelabel config resolved for domain @domain (tenant @tenant).', [
          '@domain' => $domain,
          '@tenant' => $config['tenant_id'] ?? 'unknown',
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error resolving whitelabel config on request: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
