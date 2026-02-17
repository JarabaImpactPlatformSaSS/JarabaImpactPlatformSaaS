<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Admin list builder para GeneratedDocument.
 */
class GeneratedDocumentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['generation_mode'] = $this->t('Modo');
    $header['status'] = $this->t('Estado');
    $header['ai_model_version'] = $this->t('Modelo IA');
    $header['created'] = $this->t('Generado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->get('title')->value ?? '';

    $modes = [
      'template_only' => 'Plantilla',
      'ai_assisted' => 'IA Asistida',
      'ai_full' => 'IA Completa',
    ];
    $mode = $entity->get('generation_mode')->value ?? 'template_only';
    $row['generation_mode'] = $modes[$mode] ?? $mode;

    $statuses = [
      'draft' => 'Borrador',
      'reviewing' => 'En Revision',
      'approved' => 'Aprobado',
      'finalized' => 'Finalizado',
    ];
    $status = $entity->get('status')->value ?? 'draft';
    $row['status'] = $statuses[$status] ?? $status;

    $row['ai_model_version'] = $entity->get('ai_model_version')->value ?? '—';
    $row['created'] = $entity->get('created')->value
      ? date('d/m/Y H:i', (int) $entity->get('created')->value)
      : '';
    return $row + parent::buildRow($entity);
  }

}
