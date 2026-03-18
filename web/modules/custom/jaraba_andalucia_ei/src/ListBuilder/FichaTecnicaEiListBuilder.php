<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\jaraba_andalucia_ei\Entity\FichaTecnicaEi;

/**
 * Listado admin de Fichas Técnicas PIIL.
 */
class FichaTecnicaEiListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['expediente_ref'] = $this->t('Expediente');
    $header['provincia'] = $this->t('Provincia');
    $header['proyectos'] = $this->t('Proyectos');
    $header['ratio'] = $this->t('Ratio técnicos');
    $header['estado'] = $this->t('Estado SAE');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $estadoLabels = FichaTecnicaEi::ESTADOS_VALIDACION;
    $provinciaLabels = FichaTecnicaEi::PROVINCIAS;

    $estado = $entity->get('estado_validacion')->value;
    $provincia = $entity->get('provincia')->value;
    $tecnicosCount = $entity->getPersonalTecnicoCount();
    $ratioRequerido = $entity->getRatioRequerido();
    $cumple = $entity->cumpleRatio();

    $row['expediente_ref'] = $entity->get('expediente_ref')->value ?? '-';
    $row['provincia'] = $provinciaLabels[$provincia] ?? $provincia;
    $row['proyectos'] = $entity->get('proyectos_concedidos')->value ?? 0;
    $row['ratio'] = $tecnicosCount . '/' . $ratioRequerido . ($cumple ? ' ✓' : ' ⚠');
    $row['estado'] = $estadoLabels[$estado] ?? $estado;

    return $row + parent::buildRow($entity);
  }

}
