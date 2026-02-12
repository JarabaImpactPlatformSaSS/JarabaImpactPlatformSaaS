<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Servicio que reacciona a transiciones del workflow editorial i18n.
 *
 * P2-03: Procesa cambios de estado en content moderation para page_content
 * y ejecuta acciones reactivas:
 * - Logging estructurado de cada transicion.
 * - Notificacion por cola a revisores cuando se envia a revision.
 * - Notificacion al autor cuando se aprueba o solicitan cambios.
 *
 * Se invoca desde hook_entity_update() del modulo cuando detecta
 * un cambio en moderation_state para entidades page_content.
 */
class WorkflowTransitionSubscriber {

  use StringTranslationTrait;

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El logger del modulo.
   */
  protected LoggerChannelInterface $logger;

  /**
   * La cola para procesar notificaciones.
   */
  protected QueueFactory $queueFactory;

  /**
   * El usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Construye el servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   El entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   El logger del modulo.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   La factory de colas.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   El usuario actual.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $logger,
    QueueFactory $queue_factory,
    AccountProxyInterface $current_user,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->queueFactory = $queue_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Procesa un cambio de estado de moderacion en page_content.
   *
   * Llamado desde jaraba_page_builder_entity_update() cuando se detecta
   * que moderation_state cambio.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   La entidad page_content actualizada.
   * @param string $original_state
   *   El estado de moderacion anterior.
   * @param string $new_state
   *   El nuevo estado de moderacion.
   */
  public function onStateChanged(ContentEntityInterface $entity, string $original_state, string $new_state): void {
    // Log estructurado de la transicion.
    $this->logger->info('Workflow editorial_i18n: page_content @id transicion @from -> @to por usuario @uid', [
      '@id' => $entity->id(),
      '@from' => $original_state,
      '@to' => $new_state,
      '@uid' => $this->currentUser->id(),
    ]);

    // Despachar notificacion segun la transicion.
    match (TRUE) {
      $new_state === 'review' => $this->notifyReviewers($entity, $original_state),
      $new_state === 'published' && $original_state === 'review' => $this->notifyAuthorApproved($entity),
      $new_state === 'draft' && $original_state === 'review' => $this->notifyAuthorChangesRequested($entity),
      default => NULL,
    };
  }

  /**
   * Encola notificacion a revisores cuando se envia a revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   La entidad page_content.
   * @param string $from_state
   *   Estado anterior.
   */
  protected function notifyReviewers(ContentEntityInterface $entity, string $from_state): void {
    $queue = $this->queueFactory->get('jaraba_page_builder_workflow_notify');
    $queue->createItem([
      'type' => 'submit_for_review',
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_label' => $entity->label(),
      'from_state' => $from_state,
      'to_state' => 'review',
      'actor_uid' => (int) $this->currentUser->id(),
      'timestamp' => \Drupal::time()->getRequestTime(),
    ]);

    $this->logger->info('Notificacion encolada: revision solicitada para @label (id: @id)', [
      '@label' => $entity->label(),
      '@id' => $entity->id(),
    ]);
  }

  /**
   * Encola notificacion al autor cuando se aprueba y publica.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   La entidad page_content.
   */
  protected function notifyAuthorApproved(ContentEntityInterface $entity): void {
    $queue = $this->queueFactory->get('jaraba_page_builder_workflow_notify');
    $queue->createItem([
      'type' => 'approved',
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_label' => $entity->label(),
      'from_state' => 'review',
      'to_state' => 'published',
      'author_uid' => (int) $entity->getOwnerId(),
      'actor_uid' => (int) $this->currentUser->id(),
      'timestamp' => \Drupal::time()->getRequestTime(),
    ]);

    $this->logger->info('Notificacion encolada: @label aprobada y publicada', [
      '@label' => $entity->label(),
    ]);
  }

  /**
   * Encola notificacion al autor cuando se solicitan cambios.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   La entidad page_content.
   */
  protected function notifyAuthorChangesRequested(ContentEntityInterface $entity): void {
    $queue = $this->queueFactory->get('jaraba_page_builder_workflow_notify');
    $queue->createItem([
      'type' => 'changes_requested',
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_label' => $entity->label(),
      'from_state' => 'review',
      'to_state' => 'draft',
      'author_uid' => (int) $entity->getOwnerId(),
      'actor_uid' => (int) $this->currentUser->id(),
      'timestamp' => \Drupal::time()->getRequestTime(),
    ]);

    $this->logger->info('Notificacion encolada: cambios solicitados para @label', [
      '@label' => $entity->label(),
    ]);
  }

}
