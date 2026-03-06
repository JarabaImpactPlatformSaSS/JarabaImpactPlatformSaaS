<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for InsercionLaboral entities.
 */
class InsercionLaboralListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tipo_insercion'] = $this->t('Tipo');
    $header['empresa'] = $this->t('Empresa / Autónomo');
    $header['fecha_alta'] = $this->t('Fecha Alta');
    $header['verificado'] = $this->t('Verificado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $tipoLabels = [
      'cuenta_ajena' => $this->t('Cuenta ajena'),
      'cuenta_propia' => $this->t('Cuenta propia'),
      'agrario' => $this->t('Agrario'),
    ];

    $tipo = $entity->get('tipo_insercion')->value ?? '';
    $verificado = (bool) ($entity->get('verificado')->value ?? FALSE);

    // Determinar nombre empresa/autónomo según tipo.
    $empresa = '';
    if ($tipo === 'cuenta_ajena') {
      $empresa = $entity->get('empresa_nombre')->value ?? '';
    }
    elseif ($tipo === 'cuenta_propia') {
      $empresa = $this->t('Autónomo');
      $cnae = $entity->get('cnae_actividad')->value ?? '';
      if ($cnae !== '') {
        $empresa .= ' (CNAE: ' . $cnae . ')';
      }
    }
    elseif ($tipo === 'agrario') {
      $empresa = $entity->get('empresa_agraria')->value ?? '';
    }

    $row['tipo_insercion'] = $tipoLabels[$tipo] ?? $tipo;
    $row['empresa'] = mb_substr($empresa, 0, 60);
    $row['fecha_alta'] = $entity->get('fecha_alta')->value ?? '-';
    $row['verificado'] = $verificado ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
