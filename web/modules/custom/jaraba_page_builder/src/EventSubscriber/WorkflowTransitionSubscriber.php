<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\EventSubscriber;

use Drupal\content_moderation\Event\ContentModerationStateChangedEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacciona a transiciones del workflow editorial i18n de Page Builder.
 *
 * P2-03: Escucha cambios de estado en content moderation para page_content
 * y ejecuta acciones reactivas:
 * - Logging estructurado de cada transicion.
 * - Notificacion por cola a revisores cuando se envia a revision.
 * - Notificacion al autor cuando se aprueba o solicitan cambios.
 *
 * Solo reacciona al workflow 'editorial_i18n' para no interferir
 * con otros workflows del sistema.
 */
class WorkflowTransitionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * ID del workflow que este subscriber monitorea.
   */
  protected const WORKFLOW_ID = 'editorial_i18n';

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
   * Construye el subscriber.
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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'content_moderation.state_changed' => [
        ['onStateChanged', 0],
      ],
    ];
  }

  /**
   * Reacciona al cambio de estado de content moderation.
   *
   * @param \Drupal\content_moderation\Event\ContentModerationStateChangedEvent $event
   *   El evento de cambio de estado.
   */
  public function onStateChanged(ContentModerationStateChangedEvent $event): void {
    $entity = $event->getModeratedEntity();

    // Solo reaccionar a page_content del workflow editorial_i18n.
    if ($entity->getEntityTypeId() !== 'page_content') {
      return;
    }

    $original_state = $event->getOriginalState();
    $new_state = $event->getNewState();

    // Log estructurado de la transicion.
    $this->logger->info('Workflow editorial_i18n: @entity_type @id transicion @from -> @to por usuario @uid', [
      '@entity_type' => $entity->getEntityTypeId(),
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
  protected function notifyReviewers($entity, string $from_state): void {
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
  protected function notifyAuthorApproved($entity): void {
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
  protected function notifyAuthorChangesRequested($entity): void {
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
