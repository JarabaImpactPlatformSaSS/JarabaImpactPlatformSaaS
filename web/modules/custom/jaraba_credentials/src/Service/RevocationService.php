<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Entity\RevocationEntry;
use Psr\Log\LoggerInterface;

/**
 * Servicio de revocación de credenciales con audit trail.
 */
class RevocationService {

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials');
  }

  /**
   * Revoca una credencial creando un RevocationEntry.
   *
   * @param int $credentialId
   *   ID de la credencial a revocar.
   * @param int $revokedByUid
   *   ID del usuario que revoca.
   * @param string $reason
   *   Razón de revocación (fraud, error, request, policy).
   * @param string|null $notes
   *   Notas adicionales.
   *
   * @return \Drupal\jaraba_credentials\Entity\RevocationEntry
   *   La entrada de revocación creada.
   *
   * @throws \Exception
   *   Si la credencial no existe o ya está revocada.
   */
  public function revoke(int $credentialId, int $revokedByUid, string $reason, ?string $notes = NULL): RevocationEntry {
    $credentialStorage = $this->entityTypeManager->getStorage('issued_credential');
    /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential|null $credential */
    $credential = $credentialStorage->load($credentialId);

    if (!$credential) {
      throw new \InvalidArgumentException("Credencial #$credentialId no encontrada.");
    }

    if ($credential->get('status')->value === IssuedCredential::STATUS_REVOKED) {
      throw new \LogicException("Credencial #$credentialId ya está revocada.");
    }

    // Crear RevocationEntry.
    $revocationStorage = $this->entityTypeManager->getStorage('revocation_entry');
    /** @var \Drupal\jaraba_credentials\Entity\RevocationEntry $entry */
    $entry = $revocationStorage->create([
      'credential_id' => $credentialId,
      'revoked_by_uid' => $revokedByUid,
      'reason' => $reason,
      'notes' => $notes,
    ]);
    $entry->save();

    // Actualizar estado de la credencial.
    $credential->set('status', IssuedCredential::STATUS_REVOKED);
    $credential->save();

    $this->logger->info('Credencial #@cid revocada por usuario #@uid. Razón: @reason', [
      '@cid' => $credentialId,
      '@uid' => $revokedByUid,
      '@reason' => $reason,
    ]);

    return $entry;
  }

  /**
   * Verifica si una credencial está revocada.
   *
   * @param int $credentialId
   *   ID de la credencial.
   *
   * @return bool
   *   TRUE si está revocada.
   */
  public function isRevoked(int $credentialId): bool {
    $entries = $this->entityTypeManager->getStorage('revocation_entry')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('credential_id', $credentialId)
      ->count()
      ->execute();

    return $entries > 0;
  }

  /**
   * Obtiene el historial de revocaciones de una credencial.
   *
   * @param int $credentialId
   *   ID de la credencial.
   *
   * @return \Drupal\jaraba_credentials\Entity\RevocationEntry[]
   *   Array de entradas de revocación.
   */
  public function getRevocationHistory(int $credentialId): array {
    $ids = $this->entityTypeManager->getStorage('revocation_entry')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('credential_id', $credentialId)
      ->sort('revoked_at', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('revocation_entry')->loadMultiple($ids);
  }

}
