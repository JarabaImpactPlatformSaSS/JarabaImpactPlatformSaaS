<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad TenantRegion.
 *
 * ESTRUCTURA:
 * Construye la tabla de listado de configuraciones regionales de tenant
 * en la interfaz de administracion. Define las cabeceras de columna
 * y renderiza cada fila con los datos clave de la region.
 *
 * LOGICA:
 * Muestra jurisdiccion legal, data region (con label legible), moneda base,
 * numero de IVA y estado de validacion VIES. El campo data_region se mapea
 * a etiquetas en espanol (eu-west -> "Europa Oeste", etc.). El campo
 * vies_validated se presenta como "Si"/"No" con TranslatableMarkup para
 * soporte de internacionalizacion.
 *
 * SINTAXIS:
 * Extiende EntityListBuilder. Usa constante DATA_REGION_LABELS para mapa
 * de etiquetas. Los valores booleanos se convierten a TranslatableMarkup.
 */
class TenantRegionListBuilder extends EntityListBuilder {

  /**
   * Mapa de etiquetas legibles para las zonas de datos.
   *
   * ESTRUCTURA: Constante de clase con clave = valor maquina, valor = etiqueta.
   *
   * LOGICA: Los codigos de data region (eu-west, eu-central, us-east, latam)
   *   se traducen a nombres descriptivos en espanol para la interfaz admin.
   */
  private const DATA_REGION_LABELS = [
    'eu-west' => 'Europa Oeste',
    'eu-central' => 'Europa Central',
    'us-east' => 'Estados Unidos Este',
    'latam' => 'Latinoamerica',
  ];

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Define las cabeceras de la tabla de listado.
   *
   * LOGICA: Columnas — Jurisdiccion, Data Region, Moneda base, VAT, VIES Validado.
   *   Se concatena con las cabeceras padre que incluyen la columna de operaciones.
   *
   * SINTAXIS: Cada cabecera usa TranslatableMarkup para internacionalizacion.
   */
  public function buildHeader(): array {
    $header['legal_jurisdiction'] = new TranslatableMarkup('Jurisdiccion');
    $header['data_region'] = new TranslatableMarkup('Data Region');
    $header['base_currency'] = new TranslatableMarkup('Moneda base');
    $header['vat_number'] = new TranslatableMarkup('VAT');
    $header['vies_validated'] = new TranslatableMarkup('VIES Validado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Renderiza una fila de la tabla con los datos de una TenantRegion.
   *
   * LOGICA:
   * - legal_jurisdiction: codigo ISO de la jurisdiccion legal.
   * - data_region: etiqueta legible obtenida del mapa DATA_REGION_LABELS.
   * - base_currency: codigo ISO 4217 de la moneda base.
   * - vat_number: numero de IVA o guion si no esta definido.
   * - vies_validated: "Si" o "No" segun el valor booleano del campo.
   *
   * SINTAXIS: Acceso a campos via get()->value con operador nullsafe.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['legal_jurisdiction'] = $entity->get('legal_jurisdiction')->value ?? '';

    // Mapear el codigo de data region a su etiqueta legible.
    $data_region = $entity->get('data_region')->value ?? '';
    $row['data_region'] = self::DATA_REGION_LABELS[$data_region] ?? $data_region;

    $row['base_currency'] = $entity->get('base_currency')->value ?? '';
    $row['vat_number'] = $entity->get('vat_number')->value ?? '-';

    // Mostrar estado de validacion VIES como "Si" o "No".
    $vies_validated = (bool) $entity->get('vies_validated')->value;
    $row['vies_validated'] = $vies_validated
      ? new TranslatableMarkup('Si')
      : new TranslatableMarkup('No');

    return $row + parent::buildRow($entity);
  }

}
