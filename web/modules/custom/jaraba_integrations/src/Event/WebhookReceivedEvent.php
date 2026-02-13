<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Evento disparado al recibir un webhook entrante.
 *
 * Contiene toda la información del webhook para que los
 * EventSubscribers puedan filtrar y procesar según proveedor,
 * tipo de evento y tenant.
 *
 * DIRECTRICES:
 * - TENANT-001: tenantId incluido para filtrado downstream.
 * - DRUPAL11-001: Constructor promotion con readonly (PHP 8.4).
 */
class WebhookReceivedEvent extends Event
{

    /**
     * Constructor.
     *
     * @param string $webhookId
     *   ID del endpoint de webhook que recibió la petición.
     * @param string $provider
     *   Proveedor del webhook (stripe, github, make, etc.).
     * @param string $eventType
     *   Tipo de evento (payment.completed, push, etc.).
     * @param array $payload
     *   Payload completo del webhook decodificado.
     * @param int|null $tenantId
     *   ID del tenant asociado al webhook, si se puede determinar.
     */
    public function __construct(
        public readonly string $webhookId,
        public readonly string $provider,
        public readonly string $eventType,
        public readonly array $payload,
        public readonly ?int $tenantId = NULL,
    ) {
    }

}
