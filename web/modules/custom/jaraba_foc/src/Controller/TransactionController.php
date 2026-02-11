<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_foc\Entity\FinancialTransaction;

/**
 * Controlador para el listado y visualización de transacciones financieras.
 */
class TransactionController extends ControllerBase
{

    /**
     * Lista las transacciones financieras.
     */
    public function list(): array
    {
        $storage = $this->entityTypeManager()->getStorage('financial_transaction');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->pager(50);

        $ids = $query->execute();
        $transactions = $ids ? $storage->loadMultiple($ids) : [];

        $header = [
            $this->t('ID'),
            $this->t('Monto'),
            $this->t('Moneda'),
            $this->t('Tipo'),
            $this->t('Origen'),
            $this->t('Fecha'),
        ];

        $rows = [];
        foreach ($transactions as $transaction) {
            $rows[] = [
                $transaction->id(),
                $transaction->get('amount')->value,
                $transaction->get('currency')->value,
                $transaction->get('transaction_type')->entity?->label() ?? '-',
                $transaction->get('source_system')->value,
                \Drupal::service('date.formatter')->format($transaction->get('created')->value, 'short'),
            ];
        }

        return [
            'table' => [
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#empty' => $this->t('No hay transacciones registradas.'),
                '#attributes' => ['class' => ['foc-table']],
            ],
            'pager' => [
                '#type' => 'pager',
            ],
        ];
    }

    /**
     * Muestra una transacción específica.
     */
    public function view(FinancialTransaction $financial_transaction): array
    {
        return $this->entityTypeManager()
            ->getViewBuilder('financial_transaction')
            ->view($financial_transaction);
    }

    /**
     * Título dinámico para la vista de transacción.
     */
    public function viewTitle(FinancialTransaction $financial_transaction): string
    {
        return $this->t('Transacción #@id', ['@id' => $financial_transaction->id()]);
    }

}
