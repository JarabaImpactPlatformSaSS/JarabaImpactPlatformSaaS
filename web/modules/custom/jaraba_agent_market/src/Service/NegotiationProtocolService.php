<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_market\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_identity\Service\DidManagerService;
use Psr\Log\LoggerInterface;

/**
 * Implementación del Protocolo JDTP (Jaraba Digital Twin Protocol).
 *
 * Gestiona el ciclo de vida de una negociación entre agentes autónomos.
 */
class NegotiationProtocolService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly DidManagerService $didManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Inicia una nueva sesión de negociación.
   */
  public function initiateSession(string $initiatorDid, string $targetDid, array $initialOffer): string {
    // 1. Validar identidades (en un sistema real, resolveríamos el DID Document).
    
    // 2. Crear sesión.
    $session = $this->entityTypeManager->getStorage('negotiation_session')->create([
      'initiator_did' => $initiatorDid,
      'responder_did' => $targetDid,
      'status' => 'active',
      'ledger' => json_encode([]), // Historial vacío.
    ]);
    
    // 3. Registrar primera oferta firmada.
    $this->recordStep($session, $initiatorDid, 'OFFER', $initialOffer);
    
    $session->save();
    return $session->id();
  }

  /**
   * Registra un paso en la negociación (Oferta/Contraoferta/Aceptación).
   */
  public function recordStep(object $session, string $actorDid, string $action, array $payload): void {
    // 1. Construir mensaje JDTP.
    $message = [
      'type' => $action,
      'actor' => $actorDid,
      'timestamp' => time(),
      'payload' => $payload,
    ];

    // 2. Firmar mensaje con la identidad soberana.
    // Esto garantiza no-repudio. El agente "se hace cargo" de su oferta.
    $signature = $this->didManager->signPayload($actorDid, json_encode($message));
    $message['signature'] = $signature;

    // 3. Actualizar ledger (Append-only log).
    $ledger = json_decode($session->get('ledger')->value, TRUE) ?? [];
    $ledger[] = $message;
    
    $session->set('ledger', json_encode($ledger));
    
    // 4. Actualizar estado si es terminal.
    if ($action === 'ACCEPT') {
      $session->set('status', 'closed_won');
      // Aquí se dispararía el Smart Contract / Pago.
    } elseif ($action === 'REJECT') {
      $session->set('status', 'closed_lost');
    }
  }

}
