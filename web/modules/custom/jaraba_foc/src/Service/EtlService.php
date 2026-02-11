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

        $snapshot = $storage->create([
            'snapshot_date' => date('Y-m-d'),
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'mrr' => $this->metricsCalculator->calculateMRR($scopeId),
            'arr' => $this->metricsCalculator->calculateARR($scopeId),
            'gross_margin' => $this->metricsCalculator->calculateGrossMargin($scopeId),
            'ltv' => $this->metricsCalculator->calculateLTV($scopeId),
            'ltv_cac_ratio' => $this->metricsCalculator->calculateLTVCACRatio($scopeId),
            'cac_payback_months' => $this->metricsCalculator->calculateCACPayback(),
            // TODO: Calcular resto de métricas
        ]);

        $snapshot->save();

        $this->logger->info('Snapshot creado: @id (scope: @scope)', [
            '@id' => $snapshot->id(),
            '@scope' => $scopeType,
        ]);

        return (int) $snapshot->id();
    }

}
