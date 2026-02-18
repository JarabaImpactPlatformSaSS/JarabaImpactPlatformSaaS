<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de servicios ofertados en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/servicios-offerings.
 *
 * Lógica: Muestra columnas clave: nombre del servicio, profesional,
 *   precio, duración, modalidad y si está publicado.
 */
class ServiceOfferingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Servicio');
    $header['provider_id'] = $this->t('Profesional');
    $header['price'] = $this->t('Precio');
    $header['duration_minutes'] = $this->t('Duración');
    $header['modality'] = $this->t('Modalidad');
    $header['is_published'] = $this->t('Publicado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $modality_labels = [
      'in_person' => $this->t('Presencial'),
      'online' => $this->t('Online'),
      'hybrid' => $this->t('Híbrido'),
      'home_visit' => $this->t('Domicilio'),
      'phone' => $this->t('Telefónica'),
    ];

    $provider = $entity->get('provider_id')->entity;
    $modality = $entity->get('modality')->value;

    $row['title'] = $entity->get('title')->value;
    $row['provider_id'] = $provider ? $provider->get('display_name')->value : '-';
    $row['price'] = number_format((float) $entity->get('price')->value, 2, ',', '.') . ' €';
    $row['duration_minutes'] = $entity->get('duration_minutes')->value . ' min';
    $row['modality'] = $modality_labels[$modality] ?? $modality;
    $row['is_published'] = $entity->get('is_published')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
