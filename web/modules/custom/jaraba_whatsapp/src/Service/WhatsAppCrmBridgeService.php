<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Bridges WhatsApp conversations with CRM entities.
 *
 * Creates/links NegocioProspectadoEi or Contact entities based on lead type.
 * OPTIONAL-CROSSMODULE-001: Cross-module references are optional.
 */
class WhatsAppCrmBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Links a conversation to a CRM entity based on lead type.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   *
   * @return bool
   *   TRUE if linked successfully.
   */
  public function linkToCrm(WaConversationInterface $conversation): bool {
    $leadType = $conversation->getLeadType();

    return match ($leadType) {
      'negocio' => $this->linkToNegocioProspectado($conversation),
      'participante' => $this->linkToParticipante($conversation),
      default => false,
    };
  }

  /**
   * Links conversation to a NegocioProspectadoEi entity.
   */
  protected function linkToNegocioProspectado(WaConversationInterface $conversation): bool {
    if (!$this->entityTypeManager->hasDefinition('negocio_prospectado_ei')) {
      $this->logger->info('NegocioProspectadoEi entity not available — skipping CRM link.');
      return false;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');

      // Check if a prospect already exists for this phone.
      $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('telefono', $conversation->getWaPhone())
        ->range(0, 1)
        ->execute();

      if ($existing !== []) {
        $entityId = (int) reset($existing);
        $conversation->set('linked_entity_type', 'negocio_prospectado_ei');
        $conversation->set('linked_entity_id', $entityId);
        $conversation->save();
        return true;
      }

      // Create new prospect with estado_embudo = 'contactado'.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $prospect */
      $prospect = $storage->create([
        'telefono' => $conversation->getWaPhone(),
        'estado_embudo' => 'contactado',
        'canal_captacion' => 'whatsapp',
        'tenant_id' => $conversation->getTenantId(),
      ]);
      $prospect->save();

      $conversation->set('linked_entity_type', 'negocio_prospectado_ei');
      $conversation->set('linked_entity_id', (int) $prospect->id());
      $conversation->save();

      $this->logger->info('NegocioProspectadoEi @id created from WhatsApp conversation @cid.', [
        '@id' => $prospect->id(),
        '@cid' => $conversation->id(),
      ]);

      return true;
    }
    catch (\Throwable $e) {
      $this->logger->error('CRM link (negocio) failed: @msg', ['@msg' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Links conversation to a participante or user entity.
   */
  protected function linkToParticipante(WaConversationInterface $conversation): bool {
    // Participantes are linked via user lookup by phone.
    // For now, log and return — participant entity creation requires
    // more data than WhatsApp provides.
    $this->logger->info('Participante lead from WhatsApp @phone — manual linking required.', [
      '@phone' => $conversation->getWaPhone(),
    ]);
    return false;
  }

}
