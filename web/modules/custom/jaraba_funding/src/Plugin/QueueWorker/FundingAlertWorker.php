<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_funding\Service\Alerts\FundingAlertService;
use Drupal\jaraba_funding\Service\Alerts\FundingNotificationDispatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa items de alertas de subvenciones desde la cola.
 *
 * Maneja diferentes tipos de acciones:
 * - create_alert: Crea una nueva alerta.
 * - send_pending: Envia todas las alertas pendientes.
 * - create_reminders: Crea recordatorios de plazos.
 * - send_digest: Envia un digest de alertas a un usuario.
 *
 * @QueueWorker(
 *   id = "jaraba_funding_alert",
 *   title = @Translation("Funding Alert Worker"),
 *   cron = {"time" = 30}
 * )
 */
class FundingAlertWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuracion del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definicion del plugin.
   * @param \Drupal\jaraba_funding\Service\Alerts\FundingAlertService $alertService
   *   Servicio de alertas de subvenciones.
   * @param \Drupal\jaraba_funding\Service\Alerts\FundingNotificationDispatcher $notificationDispatcher
   *   Dispatcher de notificaciones.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected FundingAlertService $alertService,
    protected FundingNotificationDispatcher $notificationDispatcher,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_funding.alerts'),
      $container->get('jaraba_funding.notification_dispatcher'),
      $container->get('logger.channel.jaraba_funding'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de alerta.
   *
   * El $data debe contener 'action' con uno de los valores:
   * - 'create_alert': Requiere tenant_id, user_id, subscription_id,
   *   call_id, alert_type, title, message, severity.
   * - 'send_pending': Sin parametros adicionales.
   * - 'create_reminders': Sin parametros adicionales.
   * - 'send_digest': Requiere user_id, frequency.
   */
  public function processItem($data): void {
    $action = $data['action'] ?? '';

    if (empty($action)) {
      $this->logger->warning('Item de alerta sin accion definida.');
      return;
    }

    try {
      match ($action) {
        'create_alert' => $this->handleCreateAlert($data),
        'send_pending' => $this->handleSendPending(),
        'create_reminders' => $this->handleCreateReminders(),
        'send_digest' => $this->handleSendDigest($data),
        default => $this->logger->warning('Accion de alerta no reconocida: @action.', [
          '@action' => $action,
        ]),
      };
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando item de alerta (@action): @error', [
        '@action' => $action,
        '@error' => $e->getMessage(),
      ]);
      // No relanzar la excepcion para evitar reencolar indefinidamente.
    }
  }

  /**
   * Maneja la creacion de una alerta.
   *
   * @param array $data
   *   Datos del item de cola con parametros de la alerta.
   */
  protected function handleCreateAlert(array $data): void {
    $this->alertService->createAlert(
      (int) ($data['tenant_id'] ?? 0),
      (int) ($data['user_id'] ?? 0),
      (int) ($data['subscription_id'] ?? 0),
      (int) ($data['call_id'] ?? 0),
      (string) ($data['alert_type'] ?? 'new_match'),
      (string) ($data['title'] ?? ''),
      (string) ($data['message'] ?? ''),
      (string) ($data['severity'] ?? 'info'),
    );

    $this->logger->info('Alerta creada via cola para tenant @tenant.', [
      '@tenant' => $data['tenant_id'] ?? 0,
    ]);
  }

  /**
   * Maneja el envio de alertas pendientes.
   */
  protected function handleSendPending(): void {
    $count = $this->alertService->sendPendingAlerts();
    $this->logger->info('Enviadas @count alertas pendientes via cola.', [
      '@count' => $count,
    ]);
  }

  /**
   * Maneja la creacion de recordatorios de plazos.
   */
  protected function handleCreateReminders(): void {
    $count = $this->alertService->createDeadlineReminders();
    $this->logger->info('Creados @count recordatorios de plazos via cola.', [
      '@count' => $count,
    ]);
  }

  /**
   * Maneja el envio de un digest de alertas.
   *
   * @param array $data
   *   Datos del item de cola con user_id y frequency.
   */
  protected function handleSendDigest(array $data): void {
    $userId = (int) ($data['user_id'] ?? 0);
    $frequency = (string) ($data['frequency'] ?? 'daily');

    if ($userId === 0) {
      $this->logger->warning('Digest sin user_id valido.');
      return;
    }

    // Obtener alertas no leidas del usuario.
    $alerts = $this->alertService->getUnreadAlerts($userId, 50);

    if (empty($alerts)) {
      $this->logger->info('Sin alertas para digest de usuario @user.', [
        '@user' => $userId,
      ]);
      return;
    }

    // Construir y "enviar" el digest (via log de momento).
    $digest = $this->notificationDispatcher->buildDigestEmail($userId, $alerts);

    $this->logger->info('Digest @frequency generado para usuario @user: @count alertas.', [
      '@frequency' => $frequency,
      '@user' => $userId,
      '@count' => $digest['alert_count'],
    ]);
  }

}
