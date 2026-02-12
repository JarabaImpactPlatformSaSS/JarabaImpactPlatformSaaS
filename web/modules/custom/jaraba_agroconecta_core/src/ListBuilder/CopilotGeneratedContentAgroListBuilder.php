<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad CopilotGeneratedContentAgro.
 */
class CopilotGeneratedContentAgroListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['content_type'] = $this->t('Tipo contenido');
    $header['status'] = $this->t('Estado');
    $header['model_used'] = $this->t('Modelo IA');
    $header['tokens_used'] = $this->t('Tokens');
    $header['quality_score'] = $this->t('Calidad');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\CopilotGeneratedContentAgro $entity */
    $row['id'] = $entity->id();

    $row['content_type'] = $entity->getContentType();

    $status = $entity->getStatus();
    $status_classes = [
      'draft' => 'badge--warning',
      'published' => 'badge--success',
      'rejected' => 'badge--error',
    ];
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'published' => $this->t('Publicado'),
      'rejected' => $this->t('Rechazado'),
    ];
    $row['status'] = [
      '#markup' => '<span class="badge ' . ($status_classes[$status] ?? 'badge--default') . '">' . ($status_labels[$status] ?? $status) . '</span>',
    ];

    $row['model_used'] = $entity->getModelUsed() ?: '-';

    $row['tokens_used'] = number_format($entity->getTokensUsed());

    $quality = $entity->getQualityScore();
    $row['quality_score'] = $quality !== NULL ? ((int) $quality) . '/100' : '-';

    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}
