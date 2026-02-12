<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para OauthClient.
 */
class OauthClientListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Aplicación');
    $header['client_id'] = $this->t('Client ID');
    $header['scopes'] = $this->t('Scopes');
    $header['is_active'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_integrations\Entity\OauthClient $entity */
    $row['name'] = $entity->toLink();
    $row['client_id'] = [
      '#markup' => '<code>' . $entity->getClientId() . '</code>',
    ];
    $row['scopes'] = implode(', ', $entity->getScopes());

    $is_active = $entity->isActive();
    $row['is_active'] = [
      '#markup' => '<span class="badge ' . ($is_active ? 'badge--success' : 'badge--warning') . '">' .
        ($is_active ? $this->t('Activo') : $this->t('Inactivo')) . '</span>',
    ];

    return $row + parent::buildRow($entity);
  }

}
