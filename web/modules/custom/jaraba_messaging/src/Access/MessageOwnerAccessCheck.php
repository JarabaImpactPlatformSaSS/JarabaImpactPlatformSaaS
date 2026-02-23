<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Verifica que el usuario es el remitente del mensaje.
 *
 * LÓGICA:
 * - Carga el mensaje desde la tabla custom secure_message por {message_id}.
 * - Compara sender_id con el ID del usuario actual.
 * - Permite acceso si coincide o si tiene 'administer jaraba messaging'.
 *
 * NOTA:
 * Los mensajes se almacenan en tabla custom (secure_message) y no como
 * entidades Drupal, por requerimientos de MEDIUMBLOB/VARBINARY para
 * cifrado AES-256-GCM.
 */
class MessageOwnerAccessCheck implements AccessInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    protected readonly AccountInterface $currentUser,
    protected readonly Connection $database,
  ) {}

  /**
   * Checks access for message owner routes (edit/delete).
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    // Administradores siempre tienen acceso.
    if ($account->hasPermission('administer jaraba messaging')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $message_id = $route_match->getParameter('message_id');
    if (empty($message_id)) {
      return AccessResult::forbidden('Missing message ID.')->addCacheableDependency($account);
    }

    // Consultar la tabla custom secure_message para obtener sender_id.
    $sender_id = $this->database->select('secure_message', 'sm')
      ->fields('sm', ['sender_id'])
      ->condition('sm.id', (int) $message_id)
      ->execute()
      ->fetchField();

    if ($sender_id === FALSE) {
      return AccessResult::forbidden('Message not found.')
        ->cachePerUser();
    }

    // Verificar que el usuario es el remitente.
    if ((int) $account->id() === (int) $sender_id) {
      return AccessResult::allowed()
        ->cachePerUser();
    }

    return AccessResult::forbidden('User is not the sender of this message.')
      ->cachePerUser();
  }

}
