<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de documentos seguros en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave del documento.
 */
class SecureDocumentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['original_filename'] = $this->t('Archivo');
    $header['mime_type'] = $this->t('Tipo');
    $header['file_size'] = $this->t('Tamano');
    $header['version'] = $this->t('Version');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activo'),
      'archived' => $this->t('Archivado'),
      'deleted' => $this->t('Eliminado'),
    ];

    $status = $entity->get('status')->value;
    $fileSize = (int) ($entity->get('file_size')->value ?? 0);
    $created = $entity->get('created')->value;

    $row['title'] = $entity->get('title')->value ?? '';
    $row['original_filename'] = $entity->get('original_filename')->value ?? '';
    $row['mime_type'] = $entity->get('mime_type')->value ?? '';
    $row['file_size'] = $fileSize > 0 ? $this->formatBytes($fileSize) : '-';
    $row['version'] = $entity->get('version')->value ?? '1';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';

    return $row + parent::buildRow($entity);
  }

  /**
   * Formatea bytes a formato legible.
   */
  protected function formatBytes(int $bytes): string {
    if ($bytes >= 1048576) {
      return round($bytes / 1048576, 1) . ' MB';
    }
    if ($bytes >= 1024) {
      return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
  }

}
