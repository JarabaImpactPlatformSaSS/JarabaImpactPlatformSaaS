<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * ListBuilder para la entidad ExpedienteDocumento.
 *
 * Define las columnas de la tabla en /admin/content/expediente-documentos.
 */
class ExpedienteDocumentoListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['titulo'] = $this->t('Título');
    $header['categoria'] = $this->t('Categoría');
    $header['participante'] = $this->t('Participante');
    $header['estado'] = $this->t('Estado');
    $header['firmado'] = $this->t('Firmado');
    $header['sto'] = $this->t('STO');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $entity */

    $estadoLabels = [
      'pendiente' => $this->t('Pendiente'),
      'en_revision' => $this->t('En revisión'),
      'aprobado' => $this->t('Aprobado'),
      'rechazado' => $this->t('Rechazado'),
      'requiere_cambios' => $this->t('Requiere cambios'),
    ];

    $categoriaLabels = \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento::CATEGORIAS;

    $participanteLabel = '';
    if (!$entity->get('participante_id')->isEmpty()) {
      $participante = $entity->get('participante_id')->entity;
      $participanteLabel = $participante ? $participante->label() : '#' . $entity->getParticipanteId();
    }

    $row['titulo'] = $entity->getTitulo();
    $row['categoria'] = $categoriaLabels[$entity->getCategoria()] ?? $entity->getCategoria();
    $row['participante'] = $participanteLabel;
    $row['estado'] = $estadoLabels[$entity->getEstadoRevision()] ?? $entity->getEstadoRevision();
    $row['firmado'] = $entity->isFirmado() ? $this->t('Sí') : $this->t('No');
    $row['sto'] = $entity->isRequeridoSto() ? ($entity->get('sto_sincronizado')->value ? $this->t('Sincronizado') : $this->t('Pendiente')) : '—';
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
