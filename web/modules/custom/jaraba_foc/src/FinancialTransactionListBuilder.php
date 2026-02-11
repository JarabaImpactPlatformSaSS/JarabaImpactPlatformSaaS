<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad Transacción Financiera.
 *
 * PROPÓSITO:
 * Genera el listado administrativo de transacciones financieras
 * con columnas relevantes para auditoría y análisis.
 *
 * NOTA:
 * No incluye operaciones de edición/eliminación porque la entidad es inmutable.
 */
class FinancialTransactionListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     *
     * Define los encabezados de columna para el listado.
     */
    public function buildHeader(): array
    {
        $header = [
            'id' => $this->t('ID'),
            'amount' => $this->t('Monto'),
            'currency' => $this->t('Moneda'),
            'type' => $this->t('Tipo'),
            'source' => $this->t('Origen'),
            'tenant' => $this->t('Tenant'),
            'date' => $this->t('Fecha'),
        ];
        // No llamamos a parent::buildHeader() para evitar columna de operaciones
        return $header;
    }

    /**
     * {@inheritdoc}
     *
     * Construye una fila del listado con los datos de la transacción.
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_foc\Entity\FinancialTransactionInterface $entity */

        // Formatear el monto con símbolo de moneda
        $amount = $entity->getAmount();
        $currency = $entity->getCurrency();
        $formattedAmount = $entity->isRevenue()
            ? '<span class="foc-revenue">+' . $amount . ' ' . $currency . '</span>'
            : '<span class="foc-expense">' . $amount . ' ' . $currency . '</span>';

        // Obtener referencia al tenant si existe
        $tenantLabel = '-';
        if ($tenantId = $entity->getRelatedTenantId()) {
            $tenant = \Drupal::entityTypeManager()->getStorage('group')->load($tenantId);
            $tenantLabel = $tenant ? $tenant->label() : 'ID: ' . $tenantId;
        }

        // Formatear la fecha
        $timestamp = $entity->getTransactionTimestamp();
        $formattedDate = \Drupal::service('date.formatter')->format($timestamp, 'short');

        $row = [
            'id' => $entity->id(),
            'amount' => [
                'data' => [
                    '#markup' => $formattedAmount,
                ],
            ],
            'currency' => $currency,
            'type' => $entity->getTransactionType() ?? '-',
            'source' => $entity->getSourceSystem(),
            'tenant' => $tenantLabel,
            'date' => $formattedDate,
        ];

        // No incluimos operaciones porque la entidad es inmutable
        return $row;
    }

    /**
     * {@inheritdoc}
     *
     * Sobrescribimos para no mostrar operaciones de edición/eliminación.
     * Las transacciones son inmutables.
     */
    public function getDefaultOperations(EntityInterface $entity): array
    {
        // Solo operación de ver, no editar ni eliminar
        $operations = [];

        if ($entity->access('view')) {
            $operations['view'] = [
                'title' => $this->t('Ver'),
                'weight' => 0,
                'url' => $entity->toUrl('canonical'),
            ];
        }

        return $operations;
    }

}
