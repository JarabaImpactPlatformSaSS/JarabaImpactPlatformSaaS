<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sincronizacion con STO (Sistema de Tramitacion Online).
 *
 * Sincroniza datos de participantes con fichas STO para
 * informes a administraciones publicas.
 */
class StoSyncService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza un participante con su ficha STO.
   *
   * @return object|null
   *   La ficha STO actualizada, o NULL si no aplica.
   */
  public function syncParticipantToStoFicha(object $participant): ?object {
    try {
      if (!$this->entityTypeManager->hasDefinition('sto_ficha')) {
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('sto_ficha');
      $fichas = $storage->loadByProperties([
        'participant' => $participant->id(),
      ]);

      if ($fichas === []) {
        $ficha = $storage->create([
          'participant' => $participant->id(),
          'tenant_id' => $participant instanceof ContentEntityInterface ? ($participant->get('tenant_id')->target_id ?? NULL) : NULL,
        ]);
      }
      else {
        $ficha = reset($fichas);
      }

      // Sync PIIL fields.
      if ($participant instanceof ContentEntityInterface && $participant->hasField('piil_registration_number')) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $ficha */
        $ficha->set('piil_registration', $participant->get('piil_registration_number')->value);
      }
      if ($participant instanceof ContentEntityInterface && $participant->hasField('digital_skills_level')) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $ficha */
        $ficha->set('digital_skills', $participant->get('digital_skills_level')->value);
      }

      $ficha->save();
      $this->logger->info('STO ficha synced for participant @id', [
        '@id' => $participant->id(),
      ]);

      return $ficha;
    }
    catch (\Throwable $e) {
      $this->logger->error('STO sync failed for participant @id: @msg', [
        '@id' => $participant->id(),
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Actualiza ficha STO desde resultados de empleo.
   */
  public function updateStoFichaFromOutcome(object $participant): void {
    try {
      if (!$this->entityTypeManager->hasDefinition('sto_ficha')) {
        return;
      }

      $storage = $this->entityTypeManager->getStorage('sto_ficha');
      $fichas = $storage->loadByProperties([
        'participant' => $participant->id(),
      ]);

      if ($fichas === []) {
        return;
      }

      $ficha = reset($fichas);

      if ($participant instanceof ContentEntityInterface && $participant->hasField('employment_sector')) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $ficha */
        $ficha->set('employment_outcome', $participant->get('employment_sector')->value);
      }
      if ($participant instanceof ContentEntityInterface && $participant->hasField('certification_type')) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $ficha */
        $ficha->set('certification_outcome', $participant->get('certification_type')->value);
      }

      $ficha->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('STO outcome update failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
