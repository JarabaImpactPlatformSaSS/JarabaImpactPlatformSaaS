<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de pilot feedback en admin.
 *
 * ESTRUCTURA: Genera la tabla en /admin/content/pilot-feedback.
 */
class PilotFeedbackListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['pilot_tenant'] = $this->t('Tenant');
    $header['feedback_type'] = $this->t('Tipo');
    $header['score'] = $this->t('Puntuacion');
    $header['sentiment'] = $this->t('Sentimiento');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $typeLabels = [
      'nps' => 'NPS',
      'csat' => 'CSAT',
      'feature_request' => $this->t('Funcionalidad'),
      'bug_report' => $this->t('Bug'),
      'general' => $this->t('General'),
    ];

    $sentimentLabels = [
      'positive' => $this->t('Positivo'),
      'neutral' => $this->t('Neutral'),
      'negative' => $this->t('Negativo'),
    ];

    $feedbackType = $entity->get('feedback_type')->value ?? '';
    $sentiment = $entity->get('sentiment')->value ?? '';

    // Resolve pilot tenant label safely (LABEL-NULLSAFE-001).
    $tenantLabel = '-';
    $tenantRef = $entity->get('pilot_tenant')->entity;
    if ($tenantRef) {
      $tenantLabel = $tenantRef->label() ?? (string) $tenantRef->id();
    }

    $created = $entity->get('created')->value;

    $row = [];
    $row['pilot_tenant'] = $tenantLabel;
    $row['feedback_type'] = $typeLabels[$feedbackType] ?? ($feedbackType !== '' ? $feedbackType : '-');
    $row['score'] = (string) ($entity->get('score')->value ?? '-');
    $row['sentiment'] = $sentimentLabels[$sentiment] ?? ($sentiment !== '' ? $sentiment : '-');
    $row['created'] = $created ? date('Y-m-d H:i', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
