<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad TaxRule.
 *
 * ESTRUCTURA:
 * Construye la tabla de listado de reglas fiscales en la interfaz de
 * administracion. Define las cabeceras de columna y renderiza cada fila
 * con los datos clave de la regla fiscal (pais, impuesto, tipos, UE, vigencia).
 *
 * LOGICA:
 * Muestra el codigo de pais, nombre del impuesto, tipo general y reducido
 * formateados con simbolo %, indicador de pertenencia a la UE y fecha de
 * vigencia. Los tipos impositivos se muestran con guion si no estan definidos.
 * El campo eu_member se presenta como "Si"/"No" con TranslatableMarkup.
 * La fecha effective_from se muestra en formato d/m/Y para consistencia
 * con la interfaz administrativa en espanol.
 *
 * SINTAXIS:
 * Extiende EntityListBuilder. Formatea valores decimales con number_format()
 * y concatena el simbolo '%'. Convierte booleanos a TranslatableMarkup.
 */
class TaxRuleListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Define las cabeceras de la tabla de listado.
   *
   * LOGICA: Columnas — Pais, Impuesto, Tipo general (%), Tipo reducido (%), UE,
   *   Vigente desde. Se concatena con las cabeceras padre (operaciones).
   *
   * SINTAXIS: Cada cabecera usa TranslatableMarkup para internacionalizacion.
   */
  public function buildHeader(): array {
    $header['country_code'] = new TranslatableMarkup('Pais');
    $header['tax_name'] = new TranslatableMarkup('Impuesto');
    $header['standard_rate'] = new TranslatableMarkup('Tipo general (%)');
    $header['reduced_rate'] = new TranslatableMarkup('Tipo reducido (%)');
    $header['eu_member'] = new TranslatableMarkup('UE');
    $header['effective_from'] = new TranslatableMarkup('Vigente desde');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Renderiza una fila de la tabla con los datos de una TaxRule.
   *
   * LOGICA:
   * - country_code: codigo ISO del pais.
   * - tax_name: nombre del impuesto (IVA, TVA, VAT, MwSt...).
   * - standard_rate: tipo general con formato numerico y simbolo %.
   * - reduced_rate: tipo reducido con formato numerico y simbolo %, o guion.
   * - eu_member: "Si" o "No" segun el valor booleano.
   * - effective_from: fecha de vigencia formateada como d/m/Y.
   *
   * SINTAXIS: number_format() para 2 decimales. Acceso a fecha via substring.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['country_code'] = $entity->get('country_code')->value ?? '';
    $row['tax_name'] = $entity->label() ?? '';

    // Formatear tipo general con simbolo %.
    $standard_rate = $entity->get('standard_rate')->value;
    $row['standard_rate'] = $standard_rate !== NULL
      ? number_format((float) $standard_rate, 2, ',', '.') . ' %'
      : '-';

    // Formatear tipo reducido con simbolo %, o guion si no definido.
    $reduced_rate = $entity->get('reduced_rate')->value;
    $row['reduced_rate'] = $reduced_rate !== NULL
      ? number_format((float) $reduced_rate, 2, ',', '.') . ' %'
      : '-';

    // Mostrar pertenencia a la UE como "Si" o "No".
    $eu_member = (bool) $entity->get('eu_member')->value;
    $row['eu_member'] = $eu_member
      ? new TranslatableMarkup('Si')
      : new TranslatableMarkup('No');

    // Formatear fecha de vigencia como d/m/Y.
    $effective_from = $entity->get('effective_from')->value ?? '';
    if ($effective_from !== '') {
      $timestamp = strtotime($effective_from);
      $row['effective_from'] = $timestamp ? date('d/m/Y', $timestamp) : $effective_from;
    }
    else {
      $row['effective_from'] = '-';
    }

    return $row + parent::buildRow($entity);
  }

}
