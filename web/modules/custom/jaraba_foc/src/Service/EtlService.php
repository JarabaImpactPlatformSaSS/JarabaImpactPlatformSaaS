<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio ETL (Extract-Transform-Load) para datos financieros.
 *
 * PROPÓSITO:
 * Procesa datos de sistemas externos y los almacena como
 * entidades FinancialTransaction en el libro mayor.
 *
 * FUENTES SOPORTADAS:
 * - stripe_connect: Webhooks de Stripe (automático via webhooks)
 * - activecampaign: Sincronización de campañas marketing
 * - manual_import: CSV/Excel para datos históricos
 *
 * FLUJO ETL:
 * 1. Extract: Obtener datos del sistema origen
 * 2. Transform: Mapear a estructura de FinancialTransaction
 * 3. Load: Crear entidades evitando duplicados (via external_id)
 */
class EtlService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\jaraba_foc\Service\MetricsCalculatorService $metricsCalculator
     *   El servicio de cálculo de métricas.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del módulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected MetricsCalculatorService $metricsCalculator,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Importa transacciones desde un archivo CSV.
     *
     * FORMATO CSV ESPERADO:
     * amount,currency,type,date,tenant_id,external_id,description
     * 1000.00,EUR,recurring_revenue,2026-01-13,1,INV-001,Cuota mensual
     *
     * @param string $filePath
     *   Ruta al archivo CSV.
     * @param array $options
     *   Opciones de importación:
     *   - skip_duplicates: Si ignorar registros con external_id existente
     *   - default_tenant: Tenant por defecto si no se especifica
     *
     * @return array
     *   Resultados de la importación: imported, skipped, errors.
     */
    public function importFromCsv(string $filePath, array $options = []): array
    {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath)) {
            $results['errors'][] = 'Archivo no encontrado: ' . $filePath;
            return $results;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $results['errors'][] = 'No se pudo abrir el archivo.';
            return $results;
        }

        // Leer cabecera
        $header = fgetcsv($handle);
        if (!$header) {
            $results['errors'][] = 'El archivo está vacío o no tiene cabecera.';
            fclose($handle);
            return $results;
        }

        // Mapear columnas
        $columnMap = array_flip($header);

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;

            try {
                $result = $this->processRow($row, $columnMap, $options);
                if ($result === 'imported') {
                    $results['imported']++;
                } elseif ($result === 'skipped') {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Fila {$rowNumber}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $this->logger->info('Importación CSV completada: @imported importados, @skipped omitidos', [
            '@imported' => $results['imported'],
            '@skipped' => $results['skipped'],
        ]);

        return $results;
    }

    /**
     * Procesa una fila del CSV.
     *
     * @param array $row
     *   Datos de la fila.
     * @param array $columnMap
     *   Mapa de columnas (nombre => índice).
     * @param array $options
     *   Opciones de importación.
     *
     * @return string
     *   'imported' o 'skipped'.
     */
    protected function processRow(array $row, array $columnMap, array $options): string
    {
        $externalId = $row[$columnMap['external_id'] ?? 0] ?? NULL;

        // Verificar duplicados
        if ($externalId && ($options['skip_duplicates'] ?? TRUE)) {
            if ($this->transactionExists($externalId)) {
                return 'skipped';
            }
        }

        // Mapear datos
        $amount = $row[$columnMap['amount'] ?? 0] ?? 0;
        $currency = $row[$columnMap['currency'] ?? 1] ?? 'EUR';
        $type = $row[$columnMap['type'] ?? 2] ?? 'one_time_sale';
        $date = $row[$columnMap['date'] ?? 3] ?? NULL;
        $tenantId = $row[$columnMap['tenant_id'] ?? 4] ?? ($options['default_tenant'] ?? NULL);
        $description = $row[$columnMap['description'] ?? 6] ?? '';

        // Determinar si es recurrente
        $isRecurring = in_array($type, ['recurring_revenue', 'subscription']);

        // Parsear fecha
        $timestamp = $date ? strtotime($date) : time();

        // Crear transacción
        $this->createTransaction([
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'source_system' => 'manual_import',
            'external_id' => $externalId,
            'is_recurring' => $isRecurring,
            'description' => $description,
            'related_tenant' => $tenantId,
            'transaction_timestamp' => $timestamp,
        ]);

        return 'imported';
    }

    /**
     * Verifica si una transacción ya existe por external_id.
     *
     * @param string $externalId
     *   ID externo a buscar.
     *
     * @return bool
     *   TRUE si ya existe.
     */
    protected function transactionExists(string $externalId): bool
    {
        $storage = $this->entityTypeManager->getStorage('financial_transaction');
        $existing = $storage->loadByProperties(['external_id' => $externalId]);
        return !empty($existing);
    }

    /**
     * Crea una transacción financiera.
     *
     * @param array $data
     *   Datos de la transacción.
     *
     * @return int
     *   ID de la transacción creada.
     */
    protected function createTransaction(array $data): int
    {
        $storage = $this->entityTypeManager->getStorage('financial_transaction');

        $transaction = $storage->create([
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'source_system' => $data['source_system'] ?? 'manual_import',
            'external_id' => $data['external_id'] ?? NULL,
            'is_recurring' => $data['is_recurring'] ?? FALSE,
            'description' => $data['description'] ?? '',
            'related_tenant' => $data['related_tenant'] ?? NULL,
            'transaction_timestamp' => $data['transaction_timestamp'] ?? time(),
        ]);

        $transaction->save();

        return (int) $transaction->id();
    }

    /**
     * Genera un snapshot de métricas.
     *
     * Crea un FocMetricSnapshot con el estado actual de todas las métricas.
     *
     * @param string $scopeType
     *   Tipo de alcance: 'platform', 'vertical', 'tenant'.
     * @param int|null $scopeId
     *   ID del vertical o tenant (NULL para platform).
     *
     * @return int
     *   ID del snapshot creado.
     */
    public function createMetricSnapshot(string $scopeType = 'platform', ?int $scopeId = NULL): int
    {
        $storage = $this->entityTypeManager->getStorage('foc_metric_snapshot');

        // Calculate additional metrics.
        $quickRatio = $this->calculateQuickRatio($scopeId);
        $revenuePerEmployee = $this->calculateRevenuePerEmployee($scopeId);
        $grossMarginValue = $this->metricsCalculator->calculateGrossMargin($scopeId);

        $snapshot = $storage->create([
            'snapshot_date' => date('Y-m-d'),
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'mrr' => $this->metricsCalculator->calculateMRR($scopeId),
            'arr' => $this->metricsCalculator->calculateARR($scopeId),
            'gross_margin' => $grossMarginValue,
            'ltv' => $this->metricsCalculator->calculateLTV($scopeId),
            'ltv_cac_ratio' => $this->metricsCalculator->calculateLTVCACRatio($scopeId),
            'cac_payback_months' => $this->metricsCalculator->calculateCACPayback(),
            'quick_ratio' => $quickRatio,
            'revenue_per_employee' => $revenuePerEmployee,
        ]);

        $snapshot->save();

        $this->logger->info('Snapshot creado: @id (scope: @scope)', [
            '@id' => $snapshot->id(),
            '@scope' => $scopeType,
        ]);

        return (int) $snapshot->id();
    }

    /**
     * Calcula el Quick Ratio (SaaS efficiency metric).
     *
     * FORMULA: (New MRR + Expansion MRR) / (Churned MRR + Contraction MRR)
     * BENCHMARK: >4 es excelente, >2 es saludable, <1 es contracción.
     *
     * @param int|null $scopeId
     *   ID del tenant para filtrar, o NULL para toda la plataforma.
     *
     * @return string
     *   Quick Ratio en formato decimal.
     */
    protected function calculateQuickRatio(?int $scopeId = NULL): string
    {
        try {
            $storage = $this->entityTypeManager->getStorage('financial_transaction');
            $monthStart = strtotime('first day of this month');
            $monthEnd = strtotime('last day of this month 23:59:59');

            // New MRR: new recurring revenue this month (type = 'new_mrr' or first recurring).
            $newMrr = $this->sumTransactionsByType(['new_mrr', 'new_subscription'], $monthStart, $monthEnd, $scopeId);

            // Expansion MRR: upgrades and add-ons.
            $expansionMrr = $this->sumTransactionsByType(['expansion_mrr', 'upgrade'], $monthStart, $monthEnd, $scopeId);

            // Churned MRR: cancellations (stored as negative amounts).
            $churnMrr = abs((float) $this->sumTransactionsByType(['churn_mrr', 'cancellation'], $monthStart, $monthEnd, $scopeId));

            // Contraction MRR: downgrades.
            $contractionMrr = abs((float) $this->sumTransactionsByType(['contraction_mrr', 'downgrade'], $monthStart, $monthEnd, $scopeId));

            $denominator = $churnMrr + $contractionMrr;
            if ($denominator <= 0) {
                // No churn means infinite quick ratio; cap at a high value.
                $numerator = (float) $newMrr + (float) $expansionMrr;
                return $numerator > 0 ? '99.00' : '0.00';
            }

            $quickRatio = ((float) $newMrr + (float) $expansionMrr) / $denominator;

            return number_format($quickRatio, 2, '.', '');
        }
        catch (\Exception $e) {
            $this->logger->debug('Error calculating Quick Ratio: @error', [
                '@error' => $e->getMessage(),
            ]);
            return '0.00';
        }
    }

    /**
     * Calcula Revenue per Employee.
     *
     * FORMULA: Total Revenue / Employee Count
     *
     * @param int|null $scopeId
     *   ID del tenant para filtrar, o NULL para toda la plataforma.
     *
     * @return string
     *   Revenue per employee en formato decimal.
     */
    protected function calculateRevenuePerEmployee(?int $scopeId = NULL): string
    {
        try {
            $arr = (float) $this->metricsCalculator->calculateARR($scopeId);

            // Get employee count from config or a reasonable default.
            $employeeCount = 0;

            if ($scopeId !== NULL) {
                // For a specific tenant, try to load the group entity.
                $group = $this->entityTypeManager->getStorage('group')->load($scopeId);
                if ($group && $group->hasField('employees')) {
                    $employeeCount = (int) ($group->get('employees')->value ?? 0);
                }
            }

            // Platform-level: check configuration.
            if ($employeeCount <= 0) {
                $config = \Drupal::config('jaraba_foc.settings');
                $employeeCount = (int) ($config->get('platform_employee_count') ?? 0);
            }

            if ($employeeCount <= 0) {
                return '0.00';
            }

            return number_format($arr / $employeeCount, 2, '.', '');
        }
        catch (\Exception $e) {
            $this->logger->debug('Error calculating Revenue per Employee: @error', [
                '@error' => $e->getMessage(),
            ]);
            return '0.00';
        }
    }

    /**
     * Suma transacciones por tipo en un período.
     *
     * @param array $types
     *   Tipos de transacción a sumar.
     * @param int $startTimestamp
     *   Inicio del período.
     * @param int $endTimestamp
     *   Fin del período.
     * @param int|null $scopeId
     *   ID del tenant para filtrar.
     *
     * @return string
     *   Suma total.
     */
    protected function sumTransactionsByType(array $types, int $startTimestamp, int $endTimestamp, ?int $scopeId = NULL): string
    {
        try {
            $storage = $this->entityTypeManager->getStorage('financial_transaction');
            $query = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('transaction_timestamp', $startTimestamp, '>=')
                ->condition('transaction_timestamp', $endTimestamp, '<=');

            if (!empty($types)) {
                $query->condition('source_system', $types, 'IN');
            }

            if ($scopeId !== NULL) {
                $query->condition('related_tenant', $scopeId);
            }

            $ids = $query->execute();
            if (empty($ids)) {
                return '0.00';
            }

            $transactions = $storage->loadMultiple($ids);
            $total = 0.0;
            foreach ($transactions as $transaction) {
                $total += (float) ($transaction->get('amount')->value ?? 0);
            }

            return number_format($total, 2, '.', '');
        }
        catch (\Exception $e) {
            return '0.00';
        }
    }

}
