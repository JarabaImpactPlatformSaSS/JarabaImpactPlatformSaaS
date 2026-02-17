<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Constructor de listado para la entidad Ficha STO.
 *
 * Estructura: Extiende EntityListBuilder para mostrar fichas STO
 *   en tabla administrativa sin operaciones de edicion ni eliminacion
 *   (append-only, ENTITY-APPEND-001).
 *
 * Logica: Muestra numero de ficha, participante, tipo, indicador
 *   de generacion con IA, estado de firma y fecha de creacion.
 *   Solo permite la operacion 'view'.
 */
class StoFichaListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['ficha_number'] = $this->t('Numero');
    $header['participant'] = $this->t('Participante');
    $header['ficha_type'] = $this->t('Tipo');
    $header['ai_generated'] = $this->t('Generada con IA');
    $header['signature_status'] = $this->t('Firma');
    $header['created'] = $this->t('Fecha creacion');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_institutional\Entity\StoFicha $entity */

    // Cargar nombre del participante via referencia.
    $participantName = '';
    if ($entity->hasField('participant_id') && !$entity->get('participant_id')->isEmpty()) {
      $participant = $entity->get('participant_id')->entity;
      if ($participant && $participant->hasField('user_id') && !$participant->get('user_id')->isEmpty()) {
        $user = $participant->get('user_id')->entity;
        $participantName = $user ? $user->getDisplayName() : $this->t('(eliminado)');
      }
      else {
        $participantName = $participant ? $participant->label() : $this->t('(eliminado)');
      }
    }

    // Indicador de generacion con IA.
    $aiGenerated = $entity->get('ai_generated')->value ?? FALSE;
    $aiLabel = $aiGenerated ? $this->t('Si') : $this->t('No');

    $row['ficha_number'] = $entity->get('ficha_number')->value ?? '';
    $row['participant'] = $participantName;
    $row['ficha_type'] = $this->t('@type', ['@type' => $entity->get('ficha_type')->value ?? '']);
    $row['ai_generated'] = $aiLabel;
    $row['signature_status'] = $this->t('@status', ['@status' => $entity->get('signature_status')->value ?? 'pending']);
    $row['created'] = $entity->get('created')->value
      ? date('d/m/Y H:i', (int) $entity->get('created')->value)
      : '';

    return $row;
  }

  /**
   * {@inheritdoc}
   *
   * Las fichas STO son append-only (ENTITY-APPEND-001).
   * Solo se permite la operacion 'view', sin editar ni eliminar.
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
