<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event dispatched during demo session lifecycle.
 *
 * S7-02: Permite a módulos reaccionar a eventos de la demo (analytics,
 * notificaciones, integraciones externas) sin acoplar DemoInteractiveService.
 *
 * Events:
 * - CREATED: Sesión generada (después de saveDemoSession).
 * - VALUE_ACTION: Acción de valor registrada (generate_story, browse_marketplace).
 * - CONVERSION: Conversión a cuenta real completada.
 * - EXPIRED: Sesión expirada (antes de eliminar en cleanup).
 */
class DemoSessionEvent extends Event {

  /**
   * Event name: demo session created.
   */
  public const CREATED = 'ecosistema_jaraba_core.demo_session.created';

  /**
   * Event name: value action tracked.
   */
  public const VALUE_ACTION = 'ecosistema_jaraba_core.demo_session.value_action';

  /**
   * Event name: conversion to real account.
   */
  public const CONVERSION = 'ecosistema_jaraba_core.demo_session.conversion';

  /**
   * Event name: session expired.
   */
  public const EXPIRED = 'ecosistema_jaraba_core.demo_session.expired';

  /**
   * Value actions that trigger VALUE_ACTION event.
   */
  public const VALUE_ACTIONS = [
    'generate_story',
    'browse_marketplace',
    'view_products',
    'click_cta',
  ];

  /**
   * Constructs a DemoSessionEvent.
   *
   * @param string $sessionId
   *   The demo session ID.
   * @param string $profileId
   *   The demo profile ID (producer, winery, etc.).
   * @param array $context
   *   Additional context data (action name, email, etc.).
   */
  public function __construct(
    public readonly string $sessionId,
    public readonly string $profileId,
    public readonly array $context = [],
  ) {}

}
