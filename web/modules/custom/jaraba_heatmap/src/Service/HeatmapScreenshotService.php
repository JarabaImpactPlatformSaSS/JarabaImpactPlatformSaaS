<?php

declare(strict_types=1);

namespace Drupal\jaraba_heatmap\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para capturar y gestionar screenshots de páginas.
 *
 * Los screenshots sirven como fondo para el overlay de heatmap en Canvas.
 * Se almacenan en el filesystem público del tenant y se referencian desde
 * la tabla heatmap_page_screenshots para asociarlos con page_path.
 *
 * Flujo:
 * 1. Se solicita screenshot para un path (manual o automático)
 * 2. Se verifica si existe uno reciente (<30 días)
 * 3. Si no existe o está expirado: se captura con wkhtmltoimage
 * 4. Se guarda en public://heatmaps/tenant_{id}/ y se registra en BD
 *
 * Ref: Spec 20260130a §7.2 — HeatmapScreenshotService
 */
class HeatmapScreenshotService {

  /**
   * Días de validez de un screenshot antes de recapturar.
   */
  protected const SCREENSHOT_MAX_AGE_DAYS = 30;

  /**
   * Ancho de viewport por defecto para capturas.
   */
  protected const DEFAULT_VIEWPORT_WIDTH = 1280;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a base de datos.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Servicio de sistema de archivos de Drupal.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del módulo.
   */
  public function __construct(
    protected Connection $database,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el screenshot para una página, capturando si es necesario.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $pagePath
   *   Path de la página (ej: /productos/tomates).
   * @param bool $forceRecapture
   *   Si TRUE, ignora cache y recaptura.
   *
   * @return array|null
   *   Array con 'screenshot_uri', 'page_height', 'viewport_width',
   *   'captured_at' o NULL si no se pudo capturar.
   */
  public function getScreenshot(int $tenantId, string $pagePath, bool $forceRecapture = FALSE): ?array {
    // 1. Verificar screenshot existente en BD.
    if (!$forceRecapture) {
      $existing = $this->getExistingScreenshot($tenantId, $pagePath);
      if ($existing && $this->isScreenshotValid($existing)) {
        return $existing;
      }
    }

    // 2. Capturar nuevo screenshot.
    $result = $this->captureScreenshot($tenantId, $pagePath);
    if ($result) {
      $this->saveScreenshotRecord($tenantId, $pagePath, $result);
    }

    return $result;
  }

  /**
   * Consulta si existe screenshot en BD para este tenant+path.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $pagePath
   *   Path de la página.
   *
   * @return array|null
   *   Registro de BD como array asociativo o NULL si no existe.
   */
  protected function getExistingScreenshot(int $tenantId, string $pagePath): ?array {
    $record = $this->database->select('heatmap_page_screenshots', 's')
      ->fields('s')
      ->condition('tenant_id', $tenantId)
      ->condition('page_path', $pagePath)
      ->execute()
      ->fetchAssoc();

    return $record ?: NULL;
  }

  /**
   * Verifica si el screenshot aún es válido (no expirado).
   *
   * @param array $record
   *   Registro de screenshot de la BD.
   *
   * @return bool
   *   TRUE si el screenshot tiene menos de SCREENSHOT_MAX_AGE_DAYS días.
   */
  protected function isScreenshotValid(array $record): bool {
    $maxAge = self::SCREENSHOT_MAX_AGE_DAYS * 86400;
    return (\Drupal::time()->getRequestTime() - (int) $record['captured_at']) < $maxAge;
  }

  /**
   * Captura un screenshot de la página usando wkhtmltoimage.
   *
   * El backend wkhtmltoimage se elige por ser un binario estático sin
   * dependencias de runtime (Node.js). El servicio está diseñado para
   * ser reemplazable por Puppeteer o una API cloud en el futuro.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $pagePath
   *   Path de la página a capturar.
   *
   * @return array|null
   *   Resultado con URI del archivo y dimensiones, o NULL si falla.
   */
  protected function captureScreenshot(int $tenantId, string $pagePath): ?array {
    // Construir URL absoluta de la página.
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    $fullUrl = $baseUrl . $pagePath;

    // Directorio de destino segregado por tenant.
    $directory = "public://heatmaps/tenant_{$tenantId}";
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    // Nombre de archivo basado en el path (sanitizado).
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($pagePath, '/'));
    $filepath = "{$directory}/{$filename}.png";
    $realPath = $this->fileSystem->realpath($filepath) ?: "/tmp/heatmap_{$tenantId}_{$filename}.png";

    // Ejecutar wkhtmltoimage (binario estático, sin dependencias runtime).
    // Seguridad: escapeshellarg() para URL y filepath.
    $command = sprintf(
      'wkhtmltoimage --width %d --quality 80 --quiet %s %s 2>&1',
      self::DEFAULT_VIEWPORT_WIDTH,
      escapeshellarg($fullUrl),
      escapeshellarg($realPath)
    );

    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);

    if ($returnCode !== 0) {
      $this->logger->warning('Screenshot capture failed for @path: @output', [
        '@path' => $pagePath,
        '@output' => implode("\n", $output),
      ]);
      return NULL;
    }

    // Obtener dimensiones de la imagen capturada.
    $imageSize = @getimagesize($realPath);
    $pageHeight = $imageSize ? $imageSize[1] : 0;

    // Mover a filesystem gestionado de Drupal si fue a /tmp.
    if (!str_starts_with($realPath, 'public://')) {
      $this->fileSystem->move($realPath, $filepath, FileSystemInterface::EXISTS_REPLACE);
    }

    return [
      'screenshot_uri' => $filepath,
      'page_height' => $pageHeight,
      'viewport_width' => self::DEFAULT_VIEWPORT_WIDTH,
      'captured_at' => \Drupal::time()->getRequestTime(),
    ];
  }

  /**
   * Guarda o actualiza el registro de screenshot en BD (UPSERT).
   *
   * Usa merge() de Drupal para insertar o actualizar según la clave
   * compuesta (tenant_id, page_path).
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $pagePath
   *   Path de la página.
   * @param array $data
   *   Datos del screenshot (screenshot_uri, page_height, viewport_width,
   *   captured_at).
   */
  protected function saveScreenshotRecord(int $tenantId, string $pagePath, array $data): void {
    $this->database->merge('heatmap_page_screenshots')
      ->keys([
        'tenant_id' => $tenantId,
        'page_path' => $pagePath,
      ])
      ->fields([
        'screenshot_uri' => $data['screenshot_uri'],
        'page_height' => $data['page_height'],
        'viewport_width' => $data['viewport_width'],
        'captured_at' => $data['captured_at'],
      ])
      ->execute();
  }

  /**
   * Elimina screenshots expirados (archivo físico + registro BD).
   *
   * @param int $daysToKeep
   *   Número de días a retener. Por defecto 30.
   *
   * @return int
   *   Número de registros eliminados.
   */
  public function cleanupExpiredScreenshots(int $daysToKeep = 30): int {
    $cutoff = \Drupal::time()->getRequestTime() - ($daysToKeep * 86400);

    // Obtener URIs para eliminar archivos físicos.
    $records = $this->database->select('heatmap_page_screenshots', 's')
      ->fields('s', ['id', 'screenshot_uri'])
      ->condition('captured_at', $cutoff, '<')
      ->execute()
      ->fetchAllAssoc('id');

    foreach ($records as $record) {
      try {
        $this->fileSystem->delete($record->screenshot_uri);
      }
      catch (\Exception $e) {
        // Archivo ya no existe, continuar con limpieza de BD.
      }
    }

    // Eliminar registros de BD.
    return (int) $this->database->delete('heatmap_page_screenshots')
      ->condition('captured_at', $cutoff, '<')
      ->execute();
  }

}
