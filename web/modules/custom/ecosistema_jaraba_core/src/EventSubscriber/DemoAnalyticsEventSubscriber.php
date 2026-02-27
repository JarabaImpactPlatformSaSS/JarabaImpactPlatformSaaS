<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ecosistema_jaraba_core\Event\DemoSessionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber para analytics de sesiones demo.
 *
 * HAL-DEMO-V3-BACK-005: Los 4 eventos de DemoSessionEvent (CREATED,
 * VALUE_ACTION, CONVERSION, EXPIRED) se dispatean pero no tenían listener.
 * Este subscriber registra métricas de conversión PLG.
 */
class DemoAnalyticsEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DemoSessionEvent::CREATED => ['onSessionCreated', 0],
      DemoSessionEvent::VALUE_ACTION => ['onValueAction', 0],
      DemoSessionEvent::CONVERSION => ['onConversion', 100],
      DemoSessionEvent::EXPIRED => ['onSessionExpired', 0],
    ];
  }

  /**
   * Registra la creación de una nueva sesión demo.
   */
  public function onSessionCreated(DemoSessionEvent $event): void {
    $this->loggerFactory->get('demo_analytics')->info(
      'Demo session created: @session (profile: @profile)',
      [
        '@session' => $event->sessionId,
        '@profile' => $event->profileId,
      ],
    );
  }

  /**
   * Registra acciones de valor durante la demo.
   */
  public function onValueAction(DemoSessionEvent $event): void {
    $action = $event->context['action'] ?? 'unknown';
    $this->loggerFactory->get('demo_analytics')->info(
      'Demo value action: @action (session: @session, profile: @profile)',
      [
        '@action' => $action,
        '@session' => $event->sessionId,
        '@profile' => $event->profileId,
      ],
    );
  }

  /**
   * Registra la conversión demo → cuenta real.
   */
  public function onConversion(DemoSessionEvent $event): void {
    $email = $event->context['email'] ?? 'unknown';
    $this->loggerFactory->get('demo_analytics')->notice(
      'Demo CONVERSION: session @session (profile: @profile, email: @email)',
      [
        '@session' => $event->sessionId,
        '@profile' => $event->profileId,
        '@email' => $email,
      ],
    );
  }

  /**
   * Registra la expiración de una sesión demo.
   */
  public function onSessionExpired(DemoSessionEvent $event): void {
    $this->loggerFactory->get('demo_analytics')->info(
      'Demo session expired: @session (profile: @profile)',
      [
        '@session' => $event->sessionId,
        '@profile' => $event->profileId,
      ],
    );
  }

}
