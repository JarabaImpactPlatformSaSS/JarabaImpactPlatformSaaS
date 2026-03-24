<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado admin de Entregables Formativos EI.
 */
class EntregableFormativoEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['numero'] = $this->t('Número');
    $header['titulo'] = $this->t('Título');
    $header['sesion_origen'] = $this->t('Sesión origen');
    $header['modulo'] = $this->t('Módulo');
    $header['estado'] = $this->t('Estado');
    $header['generado_con_ia'] = $this->t('IA');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field */

    $row['numero'] = $entity->get('numero')->value ?? '-';
    $row['titulo'] = $entity->label() ?? $this->t('(sin título)');
    $row['sesion_origen'] = $entity->get('sesion_origen')->value ?? '-';

    // Resolve allowed_values label for modulo.
    $moduloValue = $entity->get('modulo')->value;
    $moduloLabels = [
      'orientacion' => $this->t('Orientación'),
      'modulo_0' => $this->t('Módulo 0'),
      'modulo_1' => $this->t('Módulo 1'),
      'modulo_2' => $this->t('Módulo 2'),
      'modulo_3' => $this->t('Módulo 3'),
      'modulo_4' => $this->t('Módulo 4'),
      'modulo_5' => $this->t('Módulo 5'),
    ];
    $row['modulo'] = is_string($moduloValue) && isset($moduloLabels[$moduloValue])
      ? $moduloLabels[$moduloValue]
      : ($moduloValue ?? '-');

    // Resolve allowed_values label for estado.
    $estadoValue = $entity->get('estado')->value;
    $estadoLabels = [
      'pendiente' => $this->t('Pendiente'),
      'en_progreso' => $this->t('En progreso'),
      'completado' => $this->t('Completado'),
      'validado' => $this->t('Validado por formador'),
    ];
    $row['estado'] = is_string($estadoValue) && isset($estadoLabels[$estadoValue])
      ? $estadoLabels[$estadoValue]
      : ($estadoValue ?? '-');

    $generadoConIa = (bool) $entity->get('generado_con_ia')->value;
    $row['generado_con_ia'] = $generadoConIa ? $this->t('Sí') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
