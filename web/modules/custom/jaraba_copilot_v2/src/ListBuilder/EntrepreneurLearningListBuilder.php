<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad EntrepreneurLearning.
 */
class EntrepreneurLearningListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['key_insight'] = $this->t('Insight');
    $header['hypothesis'] = $this->t('Hipotesis');
    $header['decision'] = $this->t('Decision');
    $header['confidence'] = $this->t('Confianza');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurLearning $entity */
    $insight = $entity->get('key_insight')->value ?? '-';
    $row['key_insight'] = [
      '#markup' => mb_substr($insight, 0, 80) . (mb_strlen($insight) > 80 ? '...' : ''),
    ];

    $hypothesis = $entity->get('hypothesis')->value ?? '-';
    $row['hypothesis'] = [
      '#markup' => mb_substr($hypothesis, 0, 60) . (mb_strlen($hypothesis) > 60 ? '...' : ''),
    ];

    $decision = $entity->get('decision')->value ?? '-';
    $decision_classes = [
      'persevere' => 'badge--success',
      'pivot' => 'badge--warning',
      'iterate' => 'badge--info',
    ];
    $row['decision'] = [
      '#markup' => $decision !== '-'
        ? '<span class="badge ' . ($decision_classes[$decision] ?? 'badge--default') . '">' . $decision . '</span>'
        : '-',
    ];

    $confidence = $entity->get('confidence')->value ?? '-';
    $confidence_classes = [
      'high' => 'badge--success',
      'medium' => 'badge--warning',
      'low' => 'badge--error',
    ];
    $row['confidence'] = [
      '#markup' => $confidence !== '-'
        ? '<span class="badge ' . ($confidence_classes[$confidence] ?? 'badge--default') . '">' . $confidence . '</span>'
        : '-',
    ];

    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}
