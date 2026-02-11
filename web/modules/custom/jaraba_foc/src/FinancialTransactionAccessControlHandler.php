<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller para la entidad Transacción Financiera.
 *
 * PROPÓSITO:
 * Implementa la política de acceso para transacciones financieras INMUTABLES.
 *
 * INMUTABILIDAD (CRÍTICO):
 * ═══════════════════════════════════════════════════════════════════════════
 * Esta entidad NO permite operaciones de:
 * - Edición (update) → SIEMPRE denegado
 * - Eliminación (delete) → SIEMPRE denegado
 *
 * Solo se permiten:
 * - Visualización (view) → Con permiso 'view financial transactions'
 * - Creación (create) → Con permiso 'create financial transactions'
 *
 * Los ajustes contables se realizan mediante asientos compensatorios.
 * ═══════════════════════════════════════════════════════════════════════════
 */
class FinancialTransactionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     *
     * Controla el acceso a operaciones sobre transacciones existentes.
     *
     * LÓGICA:
     * - view: Requiere permiso 'view financial transactions'
     * - update: SIEMPRE DENEGADO (inmutable)
     * - delete: SIEMPRE DENEGADO (inmutable)
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        switch ($operation) {
            case 'view':
                // Permitir visualización con el permiso correspondiente
                return AccessResult::allowedIfHasPermission($account, 'view financial transactions');

            case 'update':
                // ═══════════════════════════════════════════════════════════════════
                // INMUTABILIDAD: Edición SIEMPRE denegada
                // Las transacciones financieras son append-only para garantizar
                // la integridad del libro mayor contable.
                // ═══════════════════════════════════════════════════════════════════
                return AccessResult::forbidden('Las transacciones financieras son inmutables. Use asientos compensatorios para ajustes.')
                    ->addCacheableDependency($entity);

            case 'delete':
                // ═══════════════════════════════════════════════════════════════════
                // INMUTABILIDAD: Eliminación SIEMPRE denegada
                // Mantener registro completo para auditorías y compliance.
                // ═══════════════════════════════════════════════════════════════════
                return AccessResult::forbidden('Las transacciones financieras no pueden eliminarse. Use asientos compensatorios para anulaciones.')
                    ->addCacheableDependency($entity);

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     *
     * Controla el acceso a la creación de nuevas transacciones.
     *
     * LÓGICA:
     * La creación requiere permiso 'create financial transactions'.
     * Este permiso está restringido porque las transacciones afectan
     * directamente las métricas financieras del ecosistema.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        return AccessResult::allowedIfHasPermission($account, 'create financial transactions');
    }

}
