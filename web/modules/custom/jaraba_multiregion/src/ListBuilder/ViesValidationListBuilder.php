<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad ViesValidation.
 *
 * ESTRUCTURA:
 * Construye la tabla de listado de validaciones VIES en la interfaz de
 * administracion. Define las cabeceras de columna y renderiza cada fila
 * con los datos del resultado de validacion VIES de numeros de IVA.
 *
 * LOGICA:
 * Muestra el numero de IVA validado, codigo de pais, resultado de validez,
 * nombre de la empresa devuelto por el servicio VIES y fecha de validacion.
 * El campo is_valid se presenta como "Si"/"No" con TranslatableMarkup.
 * La fecha validated_at se formatea como d/m/Y H:i para consistencia con
 * la interfaz administrativa en espanol.
 *
 * SINTAXIS:
 * Extiende EntityListBuilder. Convierte booleanos a TranslatableMarkup.
 * strtotime() y date() para formateo de fecha de validacion.
 */
class ViesValidationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Define las cabeceras de la tabla de listado.
   *
   * LOGICA: Columnas — Numero VAT, Pais, Valido, Empresa, Fecha.
   *   Se concatena con las cabeceras padre (operaciones).
   *
   * SINTAXIS: Cada cabecera usa TranslatableMarkup para internacionalizacion.
   */
  public function buildHeader(): array {
    $header['vat_number'] = new TranslatableMarkup('Numero VAT');
    $header['country_code'] = new TranslatableMarkup('Pais');
    $header['is_valid'] = new TranslatableMarkup('Valido');
    $header['company_name'] = new TranslatableMarkup('Empresa');
    $header['validated_at'] = new TranslatableMarkup('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Renderiza una fila de la tabla con los datos de una ViesValidation.
   *
   * LOGICA:
   * - vat_number: numero de IVA completo validado contra el servicio VIES.
   * - country_code: codigo ISO del pais del numero de IVA.
   * - is_valid: "Si" o "No" segun el resultado de la validacion VIES.
   * - company_name: razon social devuelta por VIES, o guion si no disponible.
   * - validated_at: fecha y hora de la validacion en formato d/m/Y H:i.
   *
   * SINTAXIS: Booleano a TranslatableMarkup. strtotime() para conversion de fecha.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['vat_number'] = $entity->get('vat_number')->value ?? '';
    $row['country_code'] = $entity->get('country_code')->value ?? '';

    // Mostrar resultado de validacion VIES como "Si" o "No".
    $is_valid = (bool) $entity->get('is_valid')->value;
    $row['is_valid'] = $is_valid
      ? new TranslatableMarkup('Si')
      : new TranslatableMarkup('No');

    $row['company_name'] = $entity->get('company_name')->value ?? '-';

    // Formatear fecha de validacion como d/m/Y H:i.
    $validated_at = $entity->get('validated_at')->value ?? '';
    if ($validated_at !== '') {
      $timestamp = strtotime($validated_at);
      $row['validated_at'] = $timestamp ? date('d/m/Y H:i', $timestamp) : $validated_at;
    }
    else {
      $row['validated_at'] = '-';
    }

    return $row + parent::buildRow($entity);
  }

}
