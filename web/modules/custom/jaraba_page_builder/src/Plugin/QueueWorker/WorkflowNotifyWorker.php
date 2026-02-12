<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa notificaciones de transiciones del workflow editorial.
 *
 * P2-03: Envia emails a revisores o autores segun el tipo de transicion.
 * Los items son encolados por WorkflowTransitionSubscriber.
 *
 * @QueueWorker(
 *   id = "jaraba_page_builder_workflow_notify",
 *   title = @Translation("Notificaciones Workflow Editorial"),
 *   cron = {"time" = 30}
 * )
 */
class WorkflowNotifyWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * El mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $logger,
    MailManagerInterface $mail_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_page_builder'),
      $container->get('plugin.manager.mail'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $type = $data['type'] ?? 'unknown';

    match ($type) {
      'submit_for_review' => $this->processSubmitForReview($data),
      'approved' => $this->processApproved($data),
      'changes_requested' => $this->processChangesRequested($data),
      default => $this->logger->warning('Tipo de notificacion workflow desconocido: @type', [
        '@type' => $type,
      ]),
    };
  }

  /**
   * Notifica a usuarios con permiso de revision.
   *
   * @param array $data
   *   Datos de la notificacion.
   */
  protected function processSubmitForReview(array $data): void {
    $reviewers = $this->getReviewers();
    $actor = $this->loadUser((int) $data['actor_uid']);
    $actor_name = $actor ? $actor->getDisplayName() : $this->t('Usuario desconocido');

    foreach ($reviewers as $reviewer) {
      $params = [
        'subject' => $this->t('Revision solicitada: @label', ['@label' => $data['entity_label']]),
        'body' => $this->t("@actor ha enviado '@label' para revision.\n\nPor favor, revisa el contenido y aprueba o solicita cambios.", [
          '@actor' => $actor_name,
          '@label' => $data['entity_label'],
        ]),
        'entity_id' => $data['entity_id'],
      ];

      $this->sendMail($reviewer->getEmail(), 'workflow_review_request', $params);
    }

    $this->logger->info('Notificacion de revision enviada a @count revisores para @label', [
      '@count' => count($reviewers),
      '@label' => $data['entity_label'],
    ]);
  }

  /**
   * Notifica al autor que su contenido fue aprobado.
   *
   * @param array $data
   *   Datos de la notificacion.
   */
  protected function processApproved(array $data): void {
    $author = $this->loadUser((int) $data['author_uid']);
    if (!$author || !$author->getEmail()) {
      return;
    }

    $approver = $this->loadUser((int) $data['actor_uid']);
    $approver_name = $approver ? $approver->getDisplayName() : $this->t('Un revisor');

    $params = [
      'subject' => $this->t('Contenido aprobado: @label', ['@label' => $data['entity_label']]),
      'body' => $this->t("@approver ha aprobado y publicado '@label'.\n\nEl contenido ya esta visible para los usuarios.", [
        '@approver' => $approver_name,
        '@label' => $data['entity_label'],
      ]),
      'entity_id' => $data['entity_id'],
    ];

    $this->sendMail($author->getEmail(), 'workflow_approved', $params);

    $this->logger->info('Notificacion de aprobacion enviada a @author para @label', [
      '@author' => $author->getDisplayName(),
      '@label' => $data['entity_label'],
    ]);
  }

  /**
   * Notifica al autor que se solicitaron cambios.
   *
   * @param array $data
   *   Datos de la notificacion.
   */
  protected function processChangesRequested(array $data): void {
    $author = $this->loadUser((int) $data['author_uid']);
    if (!$author || !$author->getEmail()) {
      return;
    }

    $reviewer = $this->loadUser((int) $data['actor_uid']);
    $reviewer_name = $reviewer ? $reviewer->getDisplayName() : $this->t('Un revisor');

    $params = [
      'subject' => $this->t('Cambios solicitados: @label', ['@label' => $data['entity_label']]),
      'body' => $this->t("@reviewer ha solicitado cambios en '@label'.\n\nPor favor, revisa y realiza las modificaciones necesarias.", [
        '@reviewer' => $reviewer_name,
        '@label' => $data['entity_label'],
      ]),
      'entity_id' => $data['entity_id'],
    ];

    $this->sendMail($author->getEmail(), 'workflow_changes_requested', $params);

    $this->logger->info('Notificacion de cambios solicitados enviada a @author para @label', [
      '@author' => $author->getDisplayName(),
      '@label' => $data['entity_label'],
    ]);
  }

  /**
   * Obtiene usuarios con permiso de aprobar publicaciones.
   *
   * @return \Drupal\user\UserInterface[]
   *   Lista de usuarios revisores.
   */
  protected function getReviewers(): array {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $role_storage = $this->entityTypeManager->getStorage('user_role');

    // Buscar roles que tengan el permiso de aprobar.
    $permission = 'use editorial_i18n transition approve_and_publish';
    $reviewer_roles = [];
    $roles = $role_storage->loadMultiple();
    foreach ($roles as $role) {
      if ($role->hasPermission($permission) || $role->isAdmin()) {
        $reviewer_roles[] = $role->id();
      }
    }

    if (empty($reviewer_roles)) {
      return [];
    }

    $uids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', $reviewer_roles, 'IN')
      ->range(0, 50)
      ->execute();

    return $uids ? $user_storage->loadMultiple($uids) : [];
  }

  /**
   * Carga un usuario por UID.
   *
   * @param int $uid
   *   El UID del usuario.
   *
   * @return \Drupal\user\UserInterface|null
   *   El usuario o NULL.
   */
  protected function loadUser(int $uid): mixed {
    return $this->entityTypeManager->getStorage('user')->load($uid);
  }

  /**
   * Envia un email via el mail manager de Drupal.
   *
   * @param string $to
   *   Direccion de destino.
   * @param string $key
   *   Clave del mail (para hook_mail).
   * @param array $params
   *   Parametros del mail.
   */
  protected function sendMail(string $to, string $key, array $params): void {
    if (empty($to)) {
      return;
    }

    $this->mailManager->mail(
      'jaraba_page_builder',
      $key,
      $to,
      'es',
      $params,
      NULL,
      TRUE
    );
  }

}
