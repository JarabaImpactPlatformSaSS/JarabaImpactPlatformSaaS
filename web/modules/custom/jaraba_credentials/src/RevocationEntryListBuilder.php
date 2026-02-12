<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para RevocationEntry.
 */
class RevocationEntryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['credential'] = $this->t('Credencial');
    $header['revoked_by'] = $this->t('Revocado por');
    $header['reason'] = $this->t('Razón');
    $header['revoked_at'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_credentials\Entity\RevocationEntry $entity */
    $credentialId = $entity->getCredentialId();
    $row['credential'] = $credentialId ? '#' . $credentialId : '-';

    $revokedBy = $entity->getOwner();
    $row['revoked_by'] = $revokedBy ? $revokedBy->getDisplayName() : '-';

    $reasons = [
      'fraud' => $this->t('Fraude'),
      'error' => $this->t('Error'),
      'request' => $this->t('Solicitud'),
      'policy' => $this->t('Política'),
    ];
    $reason = $entity->getReason();
    $row['reason'] = $reasons[$reason] ?? $reason;

    $row['revoked_at'] = date('Y-m-d H:i', (int) $entity->get('revoked_at')->value);

    return $row + parent::buildRow($entity);
  }

}
