<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ListBuilder para la entidad CurrencyRate.
 *
 * ESTRUCTURA:
 * Construye la tabla de listado de tipos de cambio en la interfaz de
 * administracion. Define las cabeceras de columna y renderiza cada fila
 * con los datos del par de monedas, tipo de cambio, fuente y fecha.
 *
 * LOGICA:
 * Muestra la moneda origen, moneda destino, tipo de cambio formateado a
 * 6 decimales, fuente de datos con etiqueta legible y fecha de obtencion.
 * El campo source se mapea a etiquetas en espanol (ecb -> "BCE",
 * manual -> "Manual", stripe -> "Stripe"). El tipo de cambio se formatea
 * con 6 posiciones decimales para precision en transacciones financieras.
 * La fecha fetched_at se muestra en formato d/m/Y H:i.
 *
 * SINTAXIS:
 * Extiende EntityListBuilder. Usa constante SOURCE_LABELS para mapa de fuentes.
 * number_format() con scale=6 para precision de tipo de cambio.
 */
class CurrencyRateListBuilder extends EntityListBuilder {

  /**
   * Mapa de etiquetas legibles para las fuentes de tipo de cambio.
   *
   * ESTRUCTURA: Constante de clase con clave = valor maquina, valor = etiqueta.
   *
   * LOGICA: Los codigos de fuente (ecb, manual, stripe) se traducen a
   *   nombres descriptivos para la interfaz administrativa.
   */
  private const SOURCE_LABELS = [
    'ecb' => 'BCE',
    'manual' => 'Manual',
    'stripe' => 'Stripe',
  ];

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Define las cabeceras de la tabla de listado.
   *
   * LOGICA: Columnas — Origen, Destino, Tipo, Fuente, Fecha.
   *   Se concatena con las cabeceras padre (operaciones).
   *
   * SINTAXIS: Cada cabecera usa TranslatableMarkup para internacionalizacion.
   */
  public function buildHeader(): array {
    $header['from_currency'] = new TranslatableMarkup('Origen');
    $header['to_currency'] = new TranslatableMarkup('Destino');
    $header['rate'] = new TranslatableMarkup('Tipo');
    $header['source'] = new TranslatableMarkup('Fuente');
    $header['fetched_at'] = new TranslatableMarkup('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * ESTRUCTURA: Renderiza una fila de la tabla con los datos de un CurrencyRate.
   *
   * LOGICA:
   * - from_currency: codigo ISO 4217 de la moneda origen.
   * - to_currency: codigo ISO 4217 de la moneda destino.
   * - rate: tipo de cambio formateado a 6 decimales para precision financiera.
   * - source: etiqueta legible obtenida del mapa SOURCE_LABELS.
   * - fetched_at: fecha de obtencion formateada como d/m/Y H:i.
   *
   * SINTAXIS: number_format() con 6 decimales. strtotime() para conversion de fecha.
   */
  public function buildRow(EntityInterface $entity): array {
    $row['from_currency'] = $entity->get('from_currency')->value ?? '';
    $row['to_currency'] = $entity->get('to_currency')->value ?? '';

    // Formatear tipo de cambio con 6 posiciones decimales.
    $rate = $entity->get('rate')->value;
    $row['rate'] = $rate !== NULL
      ? number_format((float) $rate, 6, ',', '.')
      : '-';

    // Mapear el codigo de fuente a su etiqueta legible.
    $source = $entity->get('source')->value ?? '';
    $row['source'] = self::SOURCE_LABELS[$source] ?? $source;

    // Formatear fecha de obtencion como d/m/Y H:i.
    $fetched_at = $entity->get('fetched_at')->value ?? '';
    if ($fetched_at !== '') {
      $timestamp = strtotime($fetched_at);
      $row['fetched_at'] = $timestamp ? date('d/m/Y H:i', $timestamp) : $fetched_at;
    }
    else {
      $row['fetched_at'] = '-';
    }

    return $row + parent::buildRow($entity);
  }

}
