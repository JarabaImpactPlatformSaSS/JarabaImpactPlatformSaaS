<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para WebhookSubscription.
 */
class WebhookSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Nombre');
    $header['target_url'] = $this->t('URL Destino');
    $header['events'] = $this->t('Eventos');
    $header['status'] = $this->t('Estado');
    $header['total_deliveries'] = $this->t('Entregas');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_integrations\Entity\WebhookSubscription $entity */
    $row['label'] = $entity->toLink();

    $url = $entity->getTargetUrl();
    $row['target_url'] = [
      '#markup' => '<code>' . mb_strimwidth($url, 0, 50, '...') . '</code>',
    ];

    $events = $entity->getEvents();
    $row['events'] = implode(', ', array_slice($events, 0, 3)) .
      (count($events) > 3 ? ' (+' . (count($events) - 3) . ')' : '');

    $status = $entity->getSubscriptionStatus();
    $status_classes = [
      'active' => 'badge--success',
      'inactive' => 'badge--warning',
      'failing' => 'badge--error',
    ];
    $badge_class = $status_classes[$status] ?? 'badge--default';
    $row['status'] = [
      '#markup' => '<span class="badge ' . $badge_class . '">' . $status . '</span>',
    ];

    $row['total_deliveries'] = $entity->get('total_deliveries')->value ?? 0;

    return $row + parent::buildRow($entity);
  }

}
