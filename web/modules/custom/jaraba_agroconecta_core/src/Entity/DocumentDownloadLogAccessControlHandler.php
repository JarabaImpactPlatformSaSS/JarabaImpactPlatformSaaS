<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad DocumentDownloadLog.
 *
 * Solo lectura — no permite crear ni editar manualmente.
 */
class DocumentDownloadLogAccessControlHandler extends DefaultEntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
      // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
      $parentResult = parent::checkAccess($entity, $operation, $account);
      if ($parentResult->isForbidden()) {
        return $parentResult;
      }

        if ($operation === 'view' && $account->hasPermission('administer agroconecta')) {
            return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->cachePerPermissions();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        // Solo se crea programáticamente desde PartnerDocumentService.
        return AccessResult::neutral();
    }

}
