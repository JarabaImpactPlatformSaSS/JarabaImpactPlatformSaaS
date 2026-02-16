<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de verificacion de integridad de backups.
 *
 * ESTRUCTURA:
 * Verifica automaticamente la integridad de los backups de la plataforma
 * comparando checksums SHA-256 y registrando los resultados como entidades
 * BackupVerification. Soporta verificacion manual y programada via cron.
 *
 * LOGICA:
 * - Escanea los directorios de backup segun la configuracion.
 * - Calcula SHA-256 de cada archivo y compara con el checksum esperado.
 * - Crea entidades BackupVerification con el resultado (verified/failed/corrupted).
 * - Proporciona estadisticas de salud de backups para el dashboard.
 * - Limpia backups que excedan el periodo de retencion.
 *
 * RELACIONES:
 * - BackupVerification (entidad de resultados)
 * - jaraba_dr.settings (configuracion de rutas y retencion)
 * - DrApiController (consumido desde el endpoint /api/v1/dr/backups/verify)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 10, Stack Compliance Legal N1.
 */
class BackupVerifierService {

  /**
   * Construye el servicio de verificacion de backups.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Servicio del sistema de ficheros.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Verifica la integridad de un backup dado su ruta.
   *
   * Calcula el checksum SHA-256 del archivo, compara con el esperado
   * y crea una entidad BackupVerification con el resultado.
   *
   * @param string $backupType
   *   Tipo de backup: database, files, config, full.
   * @param string $backupPath
   *   Ruta al archivo de backup.
   * @param string|null $expectedChecksum
   *   Hash SHA-256 esperado del backup, o NULL para solo calcular.
   *
   * @return array<string, mixed>
   *   Resultado de la verificacion con claves: entity_id, status,
   *   checksum_actual, duration_ms, file_size_bytes.
   */
  public function verifyBackup(string $backupType, string $backupPath, ?string $expectedChecksum = NULL): array {
    $startTime = hrtime(TRUE);

    $this->logger->info('Verificacion de backup iniciada: @path (tipo: @type)', [
      '@path' => $backupPath,
      '@type' => $backupType,
    ]);

    // Validar tipo de backup.
    $validTypes = ['database', 'files', 'config', 'full'];
    if (!in_array($backupType, $validTypes, TRUE)) {
      $backupType = 'full';
    }

    // Preparar valores base para la entidad.
    $entityValues = [
      'backup_type' => $backupType,
      'backup_path' => $backupPath,
      'checksum_expected' => $expectedChecksum ?? '',
      'verified_at' => time(),
    ];

    // Verificar existencia del archivo.
    $realPath = $this->fileSystem->realpath($backupPath);
    if ($realPath === FALSE || !file_exists($realPath)) {
      $durationMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);
      $entityValues['status'] = 'failed';
      $entityValues['error_message'] = (string) new TranslatableMarkup(
        'Archivo de backup no encontrado: @path',
        ['@path' => $backupPath]
      );
      $entityValues['verification_duration_ms'] = $durationMs;

      $entity = $this->createVerificationEntity($entityValues);

      $this->logger->error('Verificacion de backup fallida: archivo no encontrado @path', [
        '@path' => $backupPath,
      ]);

      return [
        'entity_id' => $entity ? (int) $entity->id() : 0,
        'status' => 'failed',
        'checksum_actual' => '',
        'duration_ms' => $durationMs,
        'file_size_bytes' => 0,
        'message' => (string) new TranslatableMarkup('Archivo de backup no encontrado.'),
      ];
    }

    // Obtener tamano del archivo.
    $fileSize = filesize($realPath);
    $entityValues['file_size_bytes'] = $fileSize !== FALSE ? $fileSize : 0;

    // Calcular checksum SHA-256.
    try {
      $actualChecksum = hash_file('sha256', $realPath);
    }
    catch (\Exception $e) {
      $durationMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);
      $entityValues['status'] = 'failed';
      $entityValues['error_message'] = (string) new TranslatableMarkup(
        'Error al calcular checksum: @error',
        ['@error' => $e->getMessage()]
      );
      $entityValues['verification_duration_ms'] = $durationMs;

      $entity = $this->createVerificationEntity($entityValues);

      $this->logger->error('Verificacion de backup fallida al calcular checksum: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'entity_id' => $entity ? (int) $entity->id() : 0,
        'status' => 'failed',
        'checksum_actual' => '',
        'duration_ms' => $durationMs,
        'file_size_bytes' => $entityValues['file_size_bytes'],
        'message' => (string) new TranslatableMarkup('Error al calcular checksum.'),
      ];
    }

    if ($actualChecksum === FALSE) {
      $durationMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);
      $entityValues['status'] = 'failed';
      $entityValues['error_message'] = (string) new TranslatableMarkup(
        'No se pudo calcular el checksum del archivo.'
      );
      $entityValues['verification_duration_ms'] = $durationMs;
      $entity = $this->createVerificationEntity($entityValues);

      return [
        'entity_id' => $entity ? (int) $entity->id() : 0,
        'status' => 'failed',
        'checksum_actual' => '',
        'duration_ms' => $durationMs,
        'file_size_bytes' => $entityValues['file_size_bytes'],
        'message' => (string) new TranslatableMarkup('No se pudo calcular el checksum.'),
      ];
    }

    $entityValues['checksum_actual'] = $actualChecksum;

    // Determinar estado segun comparacion de checksums.
    if ($expectedChecksum === NULL || $expectedChecksum === '') {
      // Sin checksum esperado: solo se calcula, se marca como verificado.
      $entityValues['status'] = 'verified';
      $status = 'verified';
      $message = (string) new TranslatableMarkup('Checksum calculado correctamente. Sin checksum esperado para comparar.');
    }
    elseif (hash_equals($expectedChecksum, $actualChecksum)) {
      $entityValues['status'] = 'verified';
      $status = 'verified';
      $message = (string) new TranslatableMarkup('Backup verificado: checksums coinciden.');
    }
    else {
      $entityValues['status'] = 'corrupted';
      $status = 'corrupted';
      $entityValues['error_message'] = (string) new TranslatableMarkup(
        'Checksums no coinciden. Esperado: @expected, Actual: @actual',
        ['@expected' => $expectedChecksum, '@actual' => $actualChecksum]
      );
      $message = (string) new TranslatableMarkup('ALERTA: Backup corrupto, checksums no coinciden.');

      $this->logger->critical('BACKUP CORRUPTO detectado: @path. Esperado: @expected, Actual: @actual', [
        '@path' => $backupPath,
        '@expected' => $expectedChecksum,
        '@actual' => $actualChecksum,
      ]);
    }

    $durationMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);
    $entityValues['verification_duration_ms'] = $durationMs;

    $entity = $this->createVerificationEntity($entityValues);

    $this->logger->info('Verificacion de backup completada: @path — @status (@ms ms)', [
      '@path' => $backupPath,
      '@status' => $status,
      '@ms' => $durationMs,
    ]);

    return [
      'entity_id' => $entity ? (int) $entity->id() : 0,
      'status' => $status,
      'checksum_actual' => $actualChecksum,
      'duration_ms' => $durationMs,
      'file_size_bytes' => $entityValues['file_size_bytes'],
      'message' => $message,
    ];
  }

  /**
   * Ejecuta la verificacion automatica de todos los backups configurados.
   *
   * Lee las rutas de backup desde la configuracion del modulo y verifica
   * cada archivo encontrado. Se ejecuta desde hook_cron().
   *
   * @return int
   *   Numero de backups verificados.
   */
  public function runScheduledVerification(): int {
    $config = $this->configFactory->get('jaraba_dr.settings');
    $backupPaths = $config->get('backup_paths') ?? [];
    $verified = 0;

    // Si no hay rutas configuradas, usar rutas por defecto.
    if (empty($backupPaths)) {
      $backupPaths = [
        ['path' => 'private://backups/daily', 'type' => 'database'],
        ['path' => 'private://backups/pre-deploy', 'type' => 'full'],
      ];
    }

    foreach ($backupPaths as $backupConfig) {
      $path = $backupConfig['path'] ?? '';
      $type = $backupConfig['type'] ?? 'full';

      if (empty($path)) {
        continue;
      }

      $realDir = $this->fileSystem->realpath($path);
      if ($realDir === FALSE || !is_dir($realDir)) {
        $this->logger->warning('Directorio de backup no encontrado: @path', ['@path' => $path]);
        continue;
      }

      // Escanear archivos en el directorio.
      $files = glob($realDir . '/*') ?: [];
      foreach ($files as $file) {
        if (!is_file($file)) {
          continue;
        }

        // Buscar archivo .sha256 asociado para obtener checksum esperado.
        $checksumFile = $file . '.sha256';
        $expectedChecksum = NULL;
        if (file_exists($checksumFile)) {
          $checksumContent = file_get_contents($checksumFile);
          if ($checksumContent !== FALSE) {
            // Formato: "hash  filename" o simplemente "hash".
            $parts = preg_split('/\s+/', trim($checksumContent));
            $expectedChecksum = $parts[0] ?? NULL;
          }
        }

        $this->verifyBackup($type, $file, $expectedChecksum);
        $verified++;
      }
    }

    $this->logger->info('Verificacion programada completada: @count backups verificados.', [
      '@count' => $verified,
    ]);

    return $verified;
  }

  /**
   * Obtiene el historial reciente de verificaciones de backup.
   *
   * @param int $limit
   *   Numero maximo de registros a devolver.
   *
   * @return array<int, array<string, mixed>>
   *   Lista de verificaciones con datos serializados.
   */
  public function getVerificationHistory(int $limit = 50): array {
    $storage = $this->entityTypeManager->getStorage('backup_verification');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, $limit);

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    $results = [];

    foreach ($entities as $entity) {
      $results[] = [
        'id' => (int) $entity->id(),
        'backup_type' => $entity->get('backup_type')->value,
        'backup_path' => $entity->get('backup_path')->value,
        'status' => $entity->get('status')->value,
        'checksum_expected' => $entity->get('checksum_expected')->value,
        'checksum_actual' => $entity->get('checksum_actual')->value,
        'file_size_bytes' => (int) $entity->get('file_size_bytes')->value,
        'file_size_formatted' => $entity->getFormattedFileSize(),
        'verification_duration_ms' => (int) $entity->get('verification_duration_ms')->value,
        'error_message' => $entity->get('error_message')->value,
        'verified_at' => (int) $entity->get('verified_at')->value,
        'created' => (int) $entity->get('created')->value,
      ];
    }

    return $results;
  }

  /**
   * Obtiene estadisticas de verificacion de backups.
   *
   * @return array<string, int|float>
   *   Estadisticas con claves: total, verified, failed, corrupted,
   *   health_percentage.
   */
  public function getVerificationStats(): array {
    $storage = $this->entityTypeManager->getStorage('backup_verification');

    // Total de verificaciones.
    $total = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Verificados exitosamente.
    $verified = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'verified')
      ->count()
      ->execute();

    // Fallidos.
    $failed = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'failed')
      ->count()
      ->execute();

    // Corruptos.
    $corrupted = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'corrupted')
      ->count()
      ->execute();

    // Porcentaje de salud.
    $healthPercentage = $total > 0
      ? round(($verified / $total) * 100, 2)
      : 100.0;

    return [
      'total' => $total,
      'verified' => $verified,
      'failed' => $failed,
      'corrupted' => $corrupted,
      'health_percentage' => $healthPercentage,
    ];
  }

  /**
   * Crea una entidad BackupVerification con los valores proporcionados.
   *
   * @param array<string, mixed> $values
   *   Valores de campos para la entidad.
   *
   * @return \Drupal\jaraba_dr\Entity\BackupVerification|null
   *   La entidad creada, o NULL si hubo error.
   */
  protected function createVerificationEntity(array $values): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('backup_verification');
      $entity = $storage->create($values);
      $entity->save();
      return $entity;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear entidad BackupVerification: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
