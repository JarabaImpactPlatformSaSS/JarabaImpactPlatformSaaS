<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros de consentimiento de cookies en admin.
 *
 * Muestra categorías aceptadas, IP, usuario/sesión y fecha.
 * Solo lectura — los registros son inmutables.
 */
class CookieConsentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user_id'] = $this->t('Usuario');
    $header['consent_analytics'] = $this->t('Analíticas');
    $header['consent_marketing'] = $this->t('Marketing');
    $header['consent_functional'] = $this->t('Funcionales');
    $header['consent_thirdparty'] = $this->t('Terceros');
    $header['ip_address'] = $this->t('IP');
    $header['consented_at'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $yes = $this->t('Sí');
    $no = $this->t('No');
    $consented_at = $entity->get('consented_at')->value;

    $user = $entity->get('user_id')->entity;
    $row['user_id'] = $user ? $user->getAccountName() : ($entity->get('session_id')->value ? $this->t('Anónimo') : '-');
    $row['consent_analytics'] = $entity->get('consent_analytics')->value ? $yes : $no;
    $row['consent_marketing'] = $entity->get('consent_marketing')->value ? $yes : $no;
    $row['consent_functional'] = $entity->get('consent_functional')->value ? $yes : $no;
    $row['consent_thirdparty'] = $entity->get('consent_thirdparty')->value ? $yes : $no;
    $row['ip_address'] = $entity->get('ip_address')->value ?? '-';
    $row['consented_at'] = $consented_at ? date('d/m/Y H:i', (int) $consented_at) : '-';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Solo vista para registros inmutables de consentimiento.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = [];
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('Ver'),
        'weight' => 0,
        'url' => $entity->toUrl('canonical'),
      ];
    }
    return $operations;
  }

}
