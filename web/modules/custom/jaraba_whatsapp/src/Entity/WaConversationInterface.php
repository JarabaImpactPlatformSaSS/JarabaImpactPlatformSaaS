<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interfaz para la entidad WaConversation.
 */
interface WaConversationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Estados posibles de una conversación.
   */
  public const STATUS_INITIATED = 'initiated_by_system';
  public const STATUS_ACTIVE = 'active';
  public const STATUS_ESCALATED = 'escalated';
  public const STATUS_CLOSED = 'closed';
  public const STATUS_SPAM = 'spam';

  /**
   * Tipos de lead posibles.
   */
  public const LEAD_PARTICIPANTE = 'participante';
  public const LEAD_NEGOCIO = 'negocio';
  public const LEAD_OTRO = 'otro';
  public const LEAD_SIN_CLASIFICAR = 'sin_clasificar';

  /**
   * Devuelve el número de teléfono WhatsApp.
   */
  public function getWaPhone(): string;

  /**
   * Devuelve el tipo de lead clasificado.
   */
  public function getLeadType(): string;

  /**
   * Devuelve el estado de la conversación.
   */
  public function getStatus(): string;

  /**
   * Devuelve el conteo de mensajes.
   */
  public function getMessageCount(): int;

  /**
   * Incrementa el conteo de mensajes.
   */
  public function incrementMessageCount(): static;

  /**
   * Establece el estado de la conversación.
   */
  public function setStatus(string $status): self;

  /**
   * Devuelve el tenant ID.
   */
  public function getTenantId(): ?int;

}
