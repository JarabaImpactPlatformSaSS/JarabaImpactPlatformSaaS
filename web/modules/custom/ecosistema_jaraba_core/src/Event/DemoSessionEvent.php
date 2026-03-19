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
 * - LANDING_VIEW: Visitante ve la landing /demo (S10-03).
 * - LEAD_CAPTURED: Soft gate completado con éxito (S10-03).
 * - LEAD_SKIPPED: Visitante saltó el soft gate (S10-03).
 * - CREATED: Sesión generada (después de saveDemoSession).
 * - VALUE_ACTION: Acción de valor registrada (generate_story, browse_marketplace).
 * - CONVERSION: Conversión a cuenta real completada.
 * - EXPIRED: Sesión expirada (antes de eliminar en cleanup).
 */
class DemoSessionEvent extends Event {

  /**
   * S10-03: Event name: landing page viewed.
   */
  public const LANDING_VIEW = 'ecosistema_jaraba_core.demo_session.landing_view';

  /**
   * S10-03: Event name: lead captured via soft gate.
   */
  public const LEAD_CAPTURED = 'ecosistema_jaraba_core.demo_session.lead_captured';

  /**
   * S10-03: Event name: visitor skipped soft gate.
   */
  public const LEAD_SKIPPED = 'ecosistema_jaraba_core.demo_session.lead_skipped';

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
