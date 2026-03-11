<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for InscripcionSesionEi entities.
 *
 * LABEL-NULLSAFE-001: Entity has no label key — toLink() uses entity ID.
 */
class InscripcionSesionEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['sesion'] = $this->t('Sesión');
    $header['participante'] = $this->t('Participante');
    $header['estado'] = $this->t('Estado');
    $header['asistencia_verificada'] = $this->t('Verificada');
    $header['horas'] = $this->t('Horas');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface $entity */
    $row['id'] = $entity->id();
    $row['sesion'] = $entity->getSesionId() ?? '-';
    $row['participante'] = $entity->getParticipanteId() ?? '-';
    $row['estado'] = $entity->getEstado();
    $row['asistencia_verificada'] = $entity->isAsistenciaVerificada() ? $this->t('Sí') : $this->t('No');
    $row['horas'] = number_format($entity->getHorasComputadas(), 2);
    return $row + parent::buildRow($entity);
  }

}
