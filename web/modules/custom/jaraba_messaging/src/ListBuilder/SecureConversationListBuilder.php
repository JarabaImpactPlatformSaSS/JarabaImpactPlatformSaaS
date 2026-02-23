<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado admin de conversaciones seguras.
 *
 * Muestra: título, tipo, estado, participantes, mensajes, última actividad.
 */
class SecureConversationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['title'] = $this->t('Title');
    $header['type'] = $this->t('Type');
    $header['status'] = $this->t('Status');
    $header['participants'] = $this->t('Participants');
    $header['messages'] = $this->t('Messages');
    $header['last_activity'] = $this->t('Last Activity');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row = [];

    $row['title'] = $entity->get('title')->value ?? '—';

    $row['type'] = $entity->get('conversation_type')->value ?? '—';

    $status = $entity->get('status')->value ?? 'active';
    $statusColors = [
      'active' => '#10B981',
      'archived' => '#64748B',
      'closed' => '#F59E0B',
      'deleted' => '#EF4444',
    ];
    $color = $statusColors[$status] ?? '#64748B';
    $row['status'] = [
      'data' => [
        '#markup' => '<span style="color:' . $color . ';font-weight:600;">' . ucfirst($status) . '</span>',
      ],
    ];

    $row['participants'] = (int) $entity->get('participant_count')->value;
    $row['messages'] = (int) $entity->get('message_count')->value;

    $lastMessageAt = $entity->get('last_message_at')->value;
    $row['last_activity'] = $lastMessageAt
      ? \Drupal::service('date.formatter')->format((int) $lastMessageAt, 'short')
      : '—';

    return $row + parent::buildRow($entity);
  }

}
