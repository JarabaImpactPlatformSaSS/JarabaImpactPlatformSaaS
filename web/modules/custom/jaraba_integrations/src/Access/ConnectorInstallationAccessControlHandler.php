<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para ConnectorInstallation con aislamiento por tenant.
 *
 * LÓGICA:
 * - Admins de plataforma: acceso total.
 * - Tenants: solo sus propias instalaciones.
 */
class ConnectorInstallationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static($entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer integrations')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Verificar pertenencia al tenant propietario del connector.
    if ($account->hasPermission('install connectors')) {
      if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
        $tenantId = $entity->get('tenant_id')->target_id;
        try {
          $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
          if ($tenant) {
            $group = $tenant->getGroup();
            if ($group) {
              $membership = $this->entityTypeManager
                ->getStorage('group_relationship')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('gid', $group->id())
                ->condition('plugin_id', 'group_membership')
                ->condition('entity_id', $account->id())
                ->count()
                ->execute();

              if ($membership > 0) {
                return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
              }
              return AccessResult::forbidden()->cachePerUser();
            }
          }
        } catch (\Exception $e) {
          // Si hay error resolviendo, denegar acceso.
          return AccessResult::forbidden();
        }
      }

      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer integrations',
      'install connectors',
    ], 'OR');
  }

}
