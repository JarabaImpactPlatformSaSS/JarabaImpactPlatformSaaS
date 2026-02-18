<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Envia una notificacion a un usuario usando una plantilla.
   *
   * Logica: Carga la plantilla por machine_name, renderiza subject y body
   *   sustituyendo variables, crea un registro en notification_log y
   *   despacha la notificacion por el canal configurado (email, push, etc.).
   *
   * @param int $userId
   *   ID del usuario destinatario.
   * @param string $templateMachineName
   *   Machine name de la plantilla de notificacion.
   * @param array $variables
   *   Variables para sustituir en la plantilla.
   *
   * @return bool
   *   TRUE si se envio correctamente.
   */
  public function send(int $userId, string $templateMachineName, array $variables = []): bool {
    try {
      $template = $this->loadTemplate($templateMachineName);
      if (!$template) {
        $this->logger->warning('Plantilla de notificacion no encontrada: @name', ['@name' => $templateMachineName]);
        return FALSE;
      }

      if (!(bool) $template->get('is_active')->value) {
        return FALSE;
      }

      $preferences = $this->getUserPreferences($userId);
      $channel = $template->get('channel')->value;
      $category = $template->get('category')->value ?? 'general';

      if (!$this->isChannelEnabled($preferences, $channel, $category)) {
        return FALSE;
      }

      $subject = $this->renderTemplate($template->get('subject_template')->value, $variables);
      $body = $this->renderTemplate($template->get('body_template')->value, $variables);

      $log = $this->createLog($userId, $template, $subject, $body, $channel);

      $sent = $this->dispatch($userId, $channel, $subject, $body);

      if ($log) {
        $log->set('status', $sent ? 'sent' : 'failed');
        $log->set('sent_at', \Drupal::time()->getRequestTime());
        $log->save();
      }

      return $sent;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando notificacion @template a usuario @uid: @e', [
        '@template' => $templateMachineName,
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Envia notificacion a multiples usuarios.
   *
   * @param array $userIds
   *   Lista de IDs de usuarios.
   * @param string $templateMachineName
   *   Machine name de la plantilla.
   * @param array $variables
   *   Variables para sustituir en la plantilla.
   *
   * @return int
   *   Numero de envios exitosos.
   */
  public function sendBulk(array $userIds, string $templateMachineName, array $variables = []): int {
    $success_count = 0;

    // AUDIT-PERF-N05: Pre-load template once before the loop so that
    // send() → loadTemplate() hits the static cache instead of querying
    // the database for every recipient.
    $this->loadTemplate($templateMachineName);

    foreach ($userIds as $userId) {
      if ($this->send((int) $userId, $templateMachineName, $variables)) {
        $success_count++;
      }
    }

    $this->logger->info('Notificacion masiva @template: @success/@total enviadas', [
      '@template' => $templateMachineName,
      '@success' => $success_count,
      '@total' => count($userIds),
    ]);

    return $success_count;
  }

  /**
   * Obtiene las preferencias de notificacion de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Array de preferencias indexado por canal y categoria.
   */
  public function getUserPreferences(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('comercio_notification_preference');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (!$ids) {
        return [];
      }

      $preferences = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $pref) {
        $channel = $pref->get('channel')->value;
        $category = $pref->get('category')->value;
        $enabled = (bool) $pref->get('is_enabled')->value;
        $preferences[$channel][$category] = $enabled;
      }

      return $preferences;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo preferencias de usuario @uid: @e', [
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Actualiza una preferencia de notificacion.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $channel
   *   Canal: email, push, sms, in_app.
   * @param string $category
   *   Categoria de notificacion.
   * @param bool $enabled
   *   Si esta habilitada o no.
   */
  public function updatePreference(int $userId, string $channel, string $category, bool $enabled): void {
    $storage = $this->entityTypeManager->getStorage('comercio_notification_preference');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('channel', $channel)
        ->condition('category', $category)
        ->range(0, 1)
        ->execute();

      if ($ids) {
        $pref = $storage->load(reset($ids));
        if ($pref) {
          $pref->set('is_enabled', $enabled);
          $pref->save();
        }
      }
      else {
        $pref = $storage->create([
          'user_id' => $userId,
          'channel' => $channel,
          'category' => $category,
          'is_enabled' => $enabled,
        ]);
        $pref->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando preferencia de usuario @uid: @e', [
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene historial de notificaciones de un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Lista de entidades de log de notificaciones.
   */
  public function getNotificationHistory(int $userId, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('comercio_notification_log');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      return $ids ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo historial de notificaciones de usuario @uid: @e', [
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Marca una notificacion como leida.
   *
   * @param int $logId
   *   ID del registro de notificacion.
   *
   * @return bool
   *   TRUE si se marco correctamente.
   */
  public function markAsRead(int $logId): bool {
    $storage = $this->entityTypeManager->getStorage('comercio_notification_log');

    try {
      $log = $storage->load($logId);
      if (!$log) {
        return FALSE;
      }

      $log->set('is_read', TRUE);
      $log->set('read_at', \Drupal::time()->getRequestTime());
      $log->save();

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando notificacion @id como leida: @e', [
        '@id' => $logId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Carga una plantilla de notificacion por machine_name.
   *
   * // AUDIT-PERF-N05: Static cache prevents repeated DB queries for the same
   * // template within a single request (e.g. during sendBulk iterations).
   */
  protected function loadTemplate(string $machineName) {
    // AUDIT-PERF-N05: Static per-request cache keyed by machine_name.
    static $cache = [];
    if (array_key_exists($machineName, $cache)) {
      return $cache[$machineName];
    }

    $storage = $this->entityTypeManager->getStorage('comercio_notification_template');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('machine_name', $machineName)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      $cache[$machineName] = NULL;
      return NULL;
    }

    $cache[$machineName] = $storage->load(reset($ids));
    return $cache[$machineName];
  }

  /**
   * Renderiza una plantilla sustituyendo variables.
   */
  protected function renderTemplate(string $template, array $variables): string {
    $rendered = $template;
    foreach ($variables as $key => $value) {
      $rendered = str_replace('{{ ' . $key . ' }}', (string) $value, $rendered);
      $rendered = str_replace('{{' . $key . '}}', (string) $value, $rendered);
    }
    return $rendered;
  }

  /**
   * Verifica si un canal esta habilitado para una categoria.
   */
  protected function isChannelEnabled(array $preferences, string $channel, string $category): bool {
    if (empty($preferences)) {
      return TRUE;
    }

    if (isset($preferences[$channel][$category])) {
      return $preferences[$channel][$category];
    }

    return TRUE;
  }

  /**
   * Crea un registro en el log de notificaciones.
   */
  protected function createLog(int $userId, $template, string $subject, string $body, string $channel) {
    $storage = $this->entityTypeManager->getStorage('comercio_notification_log');

    try {
      $log = $storage->create([
        'user_id' => $userId,
        'template_id' => $template->id(),
        'channel' => $channel,
        'subject' => $subject,
        'body' => $body,
        'status' => 'queued',
        'is_read' => FALSE,
      ]);
      $log->save();
      return $log;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando log de notificacion: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Despacha la notificacion por el canal configurado.
   */
  protected function dispatch(int $userId, string $channel, string $subject, string $body): bool {
    switch ($channel) {
      case 'email':
        return $this->sendEmail($userId, $subject, $body);

      case 'push':
      case 'sms':
      case 'in_app':
        $this->logger->info('Notificacion @channel para usuario @uid encolada', [
          '@channel' => $channel,
          '@uid' => $userId,
        ]);
        return TRUE;

      default:
        $this->logger->warning('Canal de notificacion desconocido: @channel', ['@channel' => $channel]);
        return FALSE;
    }
  }

  /**
   * Envia notificacion por email.
   */
  protected function sendEmail(int $userId, string $subject, string $body): bool {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }

      $email = $user->getEmail();
      if (!$email) {
        return FALSE;
      }

      $params = [
        'subject' => $subject,
        'body' => $body,
      ];

      $result = $this->mailManager->mail(
        'jaraba_comercio_conecta',
        'comercio_notification',
        $email,
        $user->getPreferredLangcode(),
        $params,
        NULL,
        TRUE
      );

      return !empty($result['result']);
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando email a usuario @uid: @e', [
        '@uid' => $userId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
