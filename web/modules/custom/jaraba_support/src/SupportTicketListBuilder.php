<?php

declare(strict_types=1);

namespace Drupal\jaraba_support;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for Support Ticket entities in /admin/content/support-tickets.
 */
class SupportTicketListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['ticket_number'] = $this->t('Ticket #');
    $header['subject'] = $this->t('Subject');
    $header['status'] = $this->t('Status');
    $header['priority'] = $this->t('Priority');
    $header['vertical'] = $this->t('Vertical');
    $header['reporter'] = $this->t('Reporter');
    $header['assignee'] = $this->t('Assignee');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_support\Entity\SupportTicketInterface $entity */
    $row['ticket_number'] = $entity->getTicketNumber();
    $row['subject'] = $entity->toLink();
    $row['status'] = $entity->getStatus();
    $row['priority'] = $entity->getPriority();
    $row['vertical'] = $entity->get('vertical')->value ?? '';
    $row['reporter'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : '';
    $row['assignee'] = '';
    if (!$entity->get('assignee_uid')->isEmpty()) {
      $assignee = $entity->get('assignee_uid')->entity;
      $row['assignee'] = $assignee ? $assignee->getDisplayName() : '';
    }
    $row['created'] = \Drupal::service('date.formatter')->format(
      $entity->get('created')->value,
      'short'
    );
    return $row + parent::buildRow($entity);
  }

}
