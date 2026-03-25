<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Listado admin de Evaluacion Competencia IA EI.
 */
class EvaluacionCompetenciaIaEiListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
    );
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['participante'] = $this->t('Participante');
    $header['tipo'] = $this->t('Tipo');
    $header['nivel_global'] = $this->t('Nivel global');
    $header['evaluador'] = $this->t('Evaluador');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    // LABEL-NULLSAFE-001: entity references may not resolve.
    $participante = $entity->hasField('participante_id') && !$entity->get('participante_id')->isEmpty()
      ? $entity->get('participante_id')->entity
      : NULL;
    $participanteLabel = $participante !== NULL
      ? ($participante->label() ?? (string) $participante->id())
      : (string) $this->t('-- sin participante --');

    $tipo = $entity->hasField('tipo') && !$entity->get('tipo')->isEmpty()
      ? (string) $entity->get('tipo')->value
      : '-';

    $nivelGlobal = $entity->hasField('nivel_global') && !$entity->get('nivel_global')->isEmpty()
      ? (string) $entity->get('nivel_global')->value
      : '-';

    $evaluador = $entity->hasField('evaluador') && !$entity->get('evaluador')->isEmpty()
      ? (string) $entity->get('evaluador')->value
      : '-';

    $createdTimestamp = $entity->hasField('created') && !$entity->get('created')->isEmpty()
      ? (int) $entity->get('created')->value
      : 0;
    $createdFormatted = $createdTimestamp > 0
      ? $this->dateFormatter->format($createdTimestamp, 'short')
      : '-';

    $row['participante'] = $participanteLabel;
    $row['tipo'] = $tipo;
    $row['nivel_global'] = $nivelGlobal;
    $row['evaluador'] = $evaluador;
    $row['created'] = $createdFormatted;

    return $row + parent::buildRow($entity);
  }

}
