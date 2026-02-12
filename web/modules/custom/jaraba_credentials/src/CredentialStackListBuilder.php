<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para CredentialStack.
 */
class CredentialStackListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['machine_name'] = $this->t('Nombre Máquina');
    $header['min_required'] = $this->t('Mín. Requerido');
    $header['bonus'] = $this->t('Bonus');
    $header['status'] = $this->t('Estado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_credentials\Entity\CredentialStack $entity */
    $row['name'] = $entity->get('name')->value ?? '-';
    $row['machine_name'] = $entity->get('machine_name')->value ?? '-';
    $row['min_required'] = $entity->getMinRequired() . ' badges';

    $credits = (int) ($entity->get('bonus_credits')->value ?? 0);
    $xp = (int) ($entity->get('bonus_xp')->value ?? 0);
    $row['bonus'] = $credits . ' cr / ' . $xp . ' xp';

    $active = (bool) ($entity->get('status')->value ?? TRUE);
    $row['status'] = $active ? $this->t('Activo') : $this->t('Inactivo');

    return $row + parent::buildRow($entity);
  }

}
