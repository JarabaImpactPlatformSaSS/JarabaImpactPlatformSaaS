<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for ActuacionSto entities.
 */
class ActuacionStoListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['fecha'] = $this->t('Fecha');
    $header['tipo_actuacion'] = $this->t('Tipo');
    $header['contenido'] = $this->t('Contenido');
    $header['duracion'] = $this->t('Duración');
    $header['lugar'] = $this->t('Lugar');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $tipoLabels = [
      'orientacion_individual' => $this->t('Orientación Ind.'),
      'orientacion_grupal' => $this->t('Orientación Grup.'),
      'formacion' => $this->t('Formación'),
      'tutoria' => $this->t('Tutoría'),
      'prospeccion' => $this->t('Prospección'),
      'intermediacion' => $this->t('Intermediación'),
    ];

    $lugarLabels = [
      'presencial_sede' => $this->t('Presencial'),
      'presencial_empresa' => $this->t('Empresa'),
      'online_videoconf' => $this->t('Online'),
      'online_plataforma' => $this->t('Plataforma'),
      'telefonico' => $this->t('Teléfono'),
    ];

    $tipo = $entity->get('tipo_actuacion')->value ?? '';
    $lugar = $entity->get('lugar')->value ?? '';
    $duracion = (int) ($entity->get('duracion_minutos')->value ?? 0);

    $row['fecha'] = $entity->get('fecha')->value ?? '-';
    $row['tipo_actuacion'] = $tipoLabels[$tipo] ?? $tipo;
    $row['contenido'] = mb_substr($entity->get('contenido')->value ?? '', 0, 60);
    $row['duracion'] = $duracion > 0 ? sprintf('%dh %dmin', intdiv($duracion, 60), $duracion % 60) : '-';
    $row['lugar'] = $lugarLabels[$lugar] ?? $lugar;
    return $row + parent::buildRow($entity);
  }

}
