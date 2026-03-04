<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_notifications\Entity\NotificationPreference;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar preferencias de notificacion.
 *
 * Proporciona metodos para obtener, actualizar e inicializar
 * las preferencias de canales de notificacion por tipo y usuario.
 *
 * Cache: Usa cache estatico por request para evitar queries redundantes.
 */
class NotificationPreferenceService {

  /**
   * Cache estatico de preferencias por usuario.
   *
   * @var array<int, array<string, \Drupal\jaraba_notifications\Entity\NotificationPreference>>
   */
  protected array $cache = [];

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Obtiene todas las preferencias de un usuario indexadas por tipo.
   *
   * @param int $userId
   *   El ID del usuario.
   *
   * @return array<string, \Drupal\jaraba_notifications\Entity\NotificationPreference>
   *   Mapa de tipo => entidad de preferencia.
   */
  public function getUserPreferences(int $userId): array {
    if (isset($this->cache[$userId])) {
      return $this->cache[$userId];
    }

    $storage = $this->entityTypeManager->getStorage('notification_preference');
    $entities = $storage->loadByProperties(['uid' => $userId]);

    $preferences = [];
    foreach ($entities as $entity) {
      $preferences[$entity->getNotificationType()] = $entity;
    }

    $this->cache[$userId] = $preferences;
    return $preferences;
  }

  /**
   * Verifica si un canal esta habilitado para un tipo de notificacion.
   *
   * Si no existen preferencias para el usuario, retorna el default.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $type
   *   El tipo de notificacion (system, social, workflow, ai, marketing).
   * @param string $channel
   *   El canal (email, push, in_app).
   *
   * @return bool
   *   TRUE si el canal esta habilitado.
   */
  public function isChannelEnabled(int $userId, string $type, string $channel): bool {
    $preferences = $this->getUserPreferences($userId);

    if (isset($preferences[$type])) {
      return $preferences[$type]->isChannelEnabled($channel);
    }

    // Sin preferencia configurada — usar default del tipo.
    return NotificationPreference::TYPE_DEFAULTS[$type][$channel] ?? TRUE;
  }

  /**
   * Actualiza una preferencia individual de canal.
   *
   * @param int $userId
   *   El ID del usuario.
   * @param string $type
   *   El tipo de notificacion.
   * @param string $channel
   *   El canal a actualizar.
   * @param bool $enabled
   *   Estado del canal.
   */
  public function updatePreference(int $userId, string $type, string $channel, bool $enabled): void {
    if (!in_array($type, NotificationPreference::VALID_TYPES, TRUE)) {
      return;
    }
    if (!in_array($channel, NotificationPreference::VALID_CHANNELS, TRUE)) {
      return;
    }

    $preferences = $this->getUserPreferences($userId);

    if (isset($preferences[$type])) {
      $entity = $preferences[$type];
      $entity->setChannelEnabled($channel, $enabled);
      $entity->save();
    }
    else {
      // Crear nueva preferencia con defaults + override.
      $defaults = NotificationPreference::TYPE_DEFAULTS[$type] ?? [];
      $storage = $this->entityTypeManager->getStorage('notification_preference');
      $entity = $storage->create([
        'uid' => $userId,
        'notification_type' => $type,
        'email_enabled' => $defaults['email'] ?? TRUE,
        'push_enabled' => $defaults['push'] ?? FALSE,
        'in_app_enabled' => $defaults['in_app'] ?? TRUE,
      ]);
      $entity->setChannelEnabled($channel, $enabled);
      $entity->save();
    }

    // Invalidar cache.
    unset($this->cache[$userId]);

    $this->logger->info('Notification preference updated: @type/@channel=@enabled for user @user', [
      '@type' => $type,
      '@channel' => $channel,
      '@enabled' => $enabled ? 'on' : 'off',
      '@user' => $userId,
    ]);
  }

  /**
   * Inicializa preferencias por defecto para un usuario nuevo.
   *
   * Crea una entidad NotificationPreference por cada tipo con
   * los defaults definidos en NotificationPreference::TYPE_DEFAULTS.
   *
   * @param int $userId
   *   El ID del usuario.
   */
  public function initializeDefaults(int $userId): void {
    $storage = $this->entityTypeManager->getStorage('notification_preference');

    // Verificar si ya existen preferencias.
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $userId)
      ->count()
      ->execute();

    if ((int) $existing > 0) {
      return;
    }

    foreach (NotificationPreference::TYPE_DEFAULTS as $type => $channels) {
      $storage->create([
        'uid' => $userId,
        'notification_type' => $type,
        'email_enabled' => $channels['email'] ?? TRUE,
        'push_enabled' => $channels['push'] ?? FALSE,
        'in_app_enabled' => $channels['in_app'] ?? TRUE,
      ])->save();
    }

    $this->logger->info('Initialized notification preferences for user @user', [
      '@user' => $userId,
    ]);
  }

  /**
   * Obtiene las preferencias como array para template/API.
   *
   * @param int $userId
   *   El ID del usuario.
   *
   * @return array
   *   Array de preferencias por tipo con estados de canales.
   */
  public function getPreferencesMatrix(int $userId): array {
    $preferences = $this->getUserPreferences($userId);
    $matrix = [];

    foreach (NotificationPreference::VALID_TYPES as $type) {
      if (isset($preferences[$type])) {
        $entity = $preferences[$type];
        $matrix[$type] = [
          'email' => $entity->isChannelEnabled('email'),
          'push' => $entity->isChannelEnabled('push'),
          'in_app' => $entity->isChannelEnabled('in_app'),
        ];
      }
      else {
        $matrix[$type] = NotificationPreference::TYPE_DEFAULTS[$type] ?? [
          'email' => TRUE,
          'push' => FALSE,
          'in_app' => TRUE,
        ];
      }
    }

    return $matrix;
  }

}
