<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de Backup Verifications en admin.
 *
 * Muestra tipo, ruta, estado, tamano, duracion y fecha de creacion.
 */
class BackupVerificationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['backup_type'] = $this->t('Tipo');
    $header['backup_path'] = $this->t('Ruta');
    $header['status'] = $this->t('Estado');
    $header['file_size_bytes'] = $this->t('Tamano');
    $header['verification_duration_ms'] = $this->t('Duracion (ms)');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'database' => $this->t('Base de datos'),
      'files' => $this->t('Ficheros'),
      'config' => $this->t('Configuracion'),
      'full' => $this->t('Completo'),
    ];

    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'verified' => $this->t('Verificado'),
      'failed' => $this->t('Fallido'),
      'corrupted' => $this->t('Corrupto'),
    ];

    $type = $entity->get('backup_type')->value;
    $status = $entity->get('status')->value;
    $bytes = (int) $entity->get('file_size_bytes')->value;
    $path = $entity->get('backup_path')->value ?? '';

    // Formato legible del tamano.
    $size_display = '-';
    if ($bytes > 0) {
      $units = ['B', 'KB', 'MB', 'GB', 'TB'];
      $index = 0;
      $size = (float) $bytes;
      while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
      }
      $size_display = sprintf('%.2f %s', $size, $units[$index]);
    }

    $row['backup_type'] = $type_labels[$type] ?? $type;
    $row['backup_path'] = strlen($path) > 60 ? '...' . substr($path, -57) : $path;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['file_size_bytes'] = $size_display;
    $row['verification_duration_ms'] = $entity->get('verification_duration_ms')->value ?? '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
