<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Event;

/**
 * Constantes de eventos de webhook.
 *
 * Los módulos que necesiten reaccionar a webhooks entrantes deben
 * registrar un EventSubscriber para estos eventos en su services.yml:
 *
 * @code
 * mi_modulo.webhook_subscriber:
 *   class: Drupal\mi_modulo\EventSubscriber\MiWebhookSubscriber
 *   tags:
 *     - { name: event_subscriber }
 * @endcode
 */
final class WebhookEvents
{

    /**
     * Se dispara cuando se recibe un webhook de cualquier proveedor.
     *
     * @var string
     */
    public const RECEIVED = 'jaraba_integrations.webhook.received';

}
