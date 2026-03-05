<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * P1-06: Service for bulk CSV import of ProductRetail entities.
 *
 * Validates CSV structure, creates products in batch, and reports results.
 *
 * TENANT-001: All products are created with the merchant's tenant_id.
 * API-WHITELIST-001: Only ALLOWED_COLUMNS are processed from CSV.
 */
class ProductImportService {

  /**
   * Columnas permitidas del CSV (API-WHITELIST-001).
   */
  protected const ALLOWED_COLUMNS = [
    'name',
    'sku',
    'description',
    'price',
    'stock_quantity',
    'category',
    'brand',
    'weight',
    'weight_unit',
    'barcode',
  ];

  /**
   * Máximo de filas por importación.
   */
  protected const MAX_ROWS = 500;

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Procesa un archivo CSV y crea productos.
   *
   * @param string $filePath
   *   Ruta al archivo CSV.
   * @param int $merchantId
   *   ID del perfil del merchant.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array{imported: int, skipped: int, errors: array}
   *   Resultado de la importación.
   */
  public function processFile(string $filePath, int $merchantId, int $tenantId): array {
    $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

    $handle = fopen($filePath, 'r');
    if (!$handle) {
      throw new \RuntimeException('No se puede abrir el archivo CSV.');
    }

    // Leer cabecera.
    $header = fgetcsv($handle, 0, ',', '"', '\\');
    if (!$header) {
      fclose($handle);
      throw new \RuntimeException('El archivo CSV está vacío o no tiene cabecera.');
    }

    // Normalizar cabecera.
    $header = array_map(function ($col) {
      return strtolower(trim(str_replace([' ', '-'], '_', $col)));
    }, $header);

    // Validar columnas requeridas.
    $required = ['name', 'sku', 'price'];
    foreach ($required as $col) {
      if (!in_array($col, $header, TRUE)) {
        fclose($handle);
        throw new \RuntimeException("Columna requerida ausente: {$col}");
      }
    }

    // Filtrar solo columnas permitidas.
    $columnMap = [];
    foreach ($header as $index => $colName) {
      if (in_array($colName, self::ALLOWED_COLUMNS, TRUE)) {
        $columnMap[$index] = $colName;
      }
    }

    $row = 1;
    $storage = $this->entityTypeManager->getStorage('product_retail');

    while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
      $row++;

      if ($row > self::MAX_ROWS + 1) {
        $result['errors'][] = "Fila {$row}: Máximo de " . self::MAX_ROWS . " filas excedido.";
        break;
      }

      // Mapear datos a columnas.
      $rowData = [];
      foreach ($columnMap as $index => $colName) {
        $rowData[$colName] = $data[$index] ?? '';
      }

      // Validar campos requeridos.
      if (empty(trim($rowData['name'] ?? '')) || empty(trim($rowData['sku'] ?? ''))) {
        $result['skipped']++;
        $result['errors'][] = "Fila {$row}: Nombre o SKU vacío.";
        continue;
      }

      $price = (float) str_replace(',', '.', $rowData['price'] ?? '0');
      if ($price <= 0) {
        $result['skipped']++;
        $result['errors'][] = "Fila {$row}: Precio inválido.";
        continue;
      }

      // Verificar SKU duplicado dentro del merchant.
      $existing = $storage->loadByProperties([
        'sku' => trim($rowData['sku']),
        'merchant_id' => $merchantId,
      ]);

      if (!empty($existing)) {
        $result['skipped']++;
        $result['errors'][] = "Fila {$row}: SKU '{$rowData['sku']}' ya existe.";
        continue;
      }

      try {
        $values = [
          'name' => trim($rowData['name']),
          'sku' => trim($rowData['sku']),
          'description' => trim($rowData['description'] ?? ''),
          'price' => $price,
          'currency_code' => 'EUR',
          'stock_quantity' => max(0, (int) ($rowData['stock_quantity'] ?? 0)),
          'merchant_id' => $merchantId,
          'tenant_id' => $tenantId,
          'status' => FALSE,
        ];

        if (!empty($rowData['category'])) {
          $values['category'] = trim($rowData['category']);
        }
        if (!empty($rowData['brand'])) {
          $values['brand'] = trim($rowData['brand']);
        }
        if (!empty($rowData['barcode'])) {
          $values['barcode'] = trim($rowData['barcode']);
        }

        $product = $storage->create($values);
        $product->save();
        $result['imported']++;
      }
      catch (\Throwable $e) {
        $result['skipped']++;
        $result['errors'][] = "Fila {$row}: " . $e->getMessage();
        $this->logger->warning('P1-06 import error row @row: @error', [
          '@row' => $row,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    fclose($handle);

    $this->logger->info('P1-06: CSV import completed. Imported: @imported, Skipped: @skipped', [
      '@imported' => $result['imported'],
      '@skipped' => $result['skipped'],
    ]);

    return $result;
  }

}
