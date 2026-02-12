<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de workflow de aprobacion para conectores del marketplace.
 *
 * PROPOSITO:
 * Gestiona el ciclo de vida de envio, revision y aprobacion de
 * conectores creados por desarrolladores externos. Los conectores
 * pasan por: draft -> submitted -> in_review -> approved/rejected.
 *
 * LOGICA:
 * - Solo conectores en estado 'approved' son visibles en el marketplace
 * - Al aprobar, se notifica al desarrollador via email
 * - Al rechazar, se incluye feedback detallado
 * - Los administradores pueden revocar aprobacion en cualquier momento
 */
class AppApprovalService {

  /**
   * Estados del workflow de aprobacion.
   */
  public const STATUS_DRAFT = 'draft';
  public const STATUS_SUBMITTED = 'submitted';
  public const STATUS_IN_REVIEW = 'in_review';
  public const STATUS_APPROVED = 'approved';
  public const STATUS_REJECTED = 'rejected';
  public const STATUS_REVOKED = 'revoked';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   El gestor de envio de correos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Envia un conector para revision.
   *
   * @param int $connectorId
   *   ID del conector a enviar.
   *
   * @return bool
   *   TRUE si se envio correctamente.
   */
  public function submitForReview(int $connectorId): bool {
    try {
      $connector = $this->entityTypeManager->getStorage('connector')->load($connectorId);
      if (!$connector) {
        $this->logger->warning('Conector @id no encontrado para envio a revision.', [
          '@id' => $connectorId,
        ]);
        return FALSE;
      }

      $currentStatus = $connector->get('approval_status')->value ?? self::STATUS_DRAFT;
      if (!in_array($currentStatus, [self::STATUS_DRAFT, self::STATUS_REJECTED], TRUE)) {
        $this->logger->notice('Conector @id no puede enviarse a revision desde estado @status.', [
          '@id' => $connectorId,
          '@status' => $currentStatus,
        ]);
        return FALSE;
      }

      $connector->set('approval_status', self::STATUS_SUBMITTED);
      $connector->set('submitted_at', date('Y-m-d\TH:i:s'));
      $connector->save();

      $this->logger->info('Conector @id enviado a revision.', ['@id' => $connectorId]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al enviar conector @id a revision: @message', [
        '@id' => $connectorId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Aprueba un conector.
   *
   * @param int $connectorId
   *   ID del conector a aprobar.
   * @param string $reviewNotes
   *   Notas del revisor.
   *
   * @return bool
   *   TRUE si se aprobo correctamente.
   */
  public function approve(int $connectorId, string $reviewNotes = ''): bool {
    try {
      $connector = $this->entityTypeManager->getStorage('connector')->load($connectorId);
      if (!$connector) {
        return FALSE;
      }

      $connector->set('approval_status', self::STATUS_APPROVED);
      $connector->set('review_notes', $reviewNotes);
      $connector->set('approved_at', date('Y-m-d\TH:i:s'));
      $connector->save();

      $this->logger->info('Conector @id aprobado.', ['@id' => $connectorId]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al aprobar conector @id: @message', [
        '@id' => $connectorId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Rechaza un conector con feedback.
   *
   * @param int $connectorId
   *   ID del conector a rechazar.
   * @param string $rejectionReason
   *   Motivo del rechazo.
   *
   * @return bool
   *   TRUE si se rechazo correctamente.
   */
  public function reject(int $connectorId, string $rejectionReason): bool {
    try {
      $connector = $this->entityTypeManager->getStorage('connector')->load($connectorId);
      if (!$connector) {
        return FALSE;
      }

      $connector->set('approval_status', self::STATUS_REJECTED);
      $connector->set('review_notes', $rejectionReason);
      $connector->save();

      $this->logger->info('Conector @id rechazado: @reason', [
        '@id' => $connectorId,
        '@reason' => $rejectionReason,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al rechazar conector @id: @message', [
        '@id' => $connectorId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene conectores pendientes de revision.
   *
   * @return array
   *   Array de conectores en estado 'submitted' o 'in_review'.
   */
  public function getPendingReview(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('approval_status', [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW], 'IN')
        ->sort('submitted_at', 'ASC')
        ->execute();

      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener conectores pendientes: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
