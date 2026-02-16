<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de fuentes legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-sources.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: nombre,
 *   nombre maquina, frecuencia de sincronizacion, estado activo,
 *   ultima sincronizacion, total de documentos y conteo de errores.
 *
 * RELACIONES:
 * - LegalSourceListBuilder -> LegalSource entity (lista)
 * - LegalSourceListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalSourceListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['machine_name'] = $this->t('Nombre Maquina');
    $header['frequency'] = $this->t('Frecuencia');
    $header['active'] = $this->t('Activo');
    $header['last_sync'] = $this->t('Ultima Sincronizacion');
    $header['total_documents'] = $this->t('Total Documentos');
    $header['error_count'] = $this->t('Errores');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $frequency_labels = [
      'hourly' => $this->t('Cada hora'),
      'daily' => $this->t('Diaria'),
      'weekly' => $this->t('Semanal'),
      'monthly' => $this->t('Mensual'),
      'manual' => $this->t('Manual'),
    ];

    $frequency = $entity->get('frequency')->value;
    $active = $entity->get('active')->value;
    $lastSync = $entity->get('last_sync')->value;

    $row['name'] = $entity->get('name')->value ?? '-';
    $row['machine_name'] = $entity->get('machine_name')->value ?? '-';
    $row['frequency'] = $frequency_labels[$frequency] ?? $frequency;
    $row['active'] = $active ? $this->t('Si') : $this->t('No');
    $row['last_sync'] = $lastSync ? date('Y-m-d H:i', (int) $lastSync) : '-';
    $row['total_documents'] = (string) ($entity->get('total_documents')->value ?? 0);
    $row['error_count'] = (string) ($entity->get('error_count')->value ?? 0);
    return $row + parent::buildRow($entity);
  }

}
