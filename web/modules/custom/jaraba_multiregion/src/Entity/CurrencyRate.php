<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Tipo de Cambio (CurrencyRate).
 *
 * ESTRUCTURA:
 * Entidad de sistema que almacena tipos de cambio entre pares de monedas.
 * Cada registro representa un snapshot del tipo de cambio en un momento
 * determinado, obtenido del BCE (ECB), introducido manualmente o via Stripe.
 * La entidad es inmutable una vez creada: no se edita, solo se crean nuevos
 * registros con tipos actualizados.
 *
 * LOGICA:
 * Los tipos de cambio se obtienen periodicamente del BCE via cron o bajo
 * demanda. El campo source indica el origen del dato (ecb, manual, stripe).
 * El campo fetched_at registra el momento exacto de obtencion. Al ser
 * inmutable, no implementa EntityChangedInterface ni tiene campo changed.
 * Para obtener el tipo vigente se consulta el registro mas reciente del
 * par de monedas ordenado por fetched_at DESC.
 *
 * SINTAXIS:
 * ContentEntityBase sin EntityOwnerInterface ni EntityChangedInterface.
 * Entidad inmutable de sistema. Indice compuesto en from_currency + to_currency
 * para consultas eficientes de pares de monedas. Indice en fetched_at para
 * ordenacion temporal.
 *
 * RELACIONES:
 * - CurrencyRate es una entidad independiente sin FK directas.
 * - Consumida por servicios de conversion de moneda del modulo multiregion.
 *
 * @ContentEntityType(
 *   id = "currency_rate",
 *   label = @Translation("Tipo de Cambio"),
 *   label_collection = @Translation("Tipos de Cambio"),
 *   label_singular = @Translation("tipo de cambio"),
 *   label_plural = @Translation("tipos de cambio"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_multiregion\ListBuilder\CurrencyRateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_multiregion\Form\CurrencyRateForm",
 *       "add" = "Drupal\jaraba_multiregion\Form\CurrencyRateForm",
 *       "edit" = "Drupal\jaraba_multiregion\Form\CurrencyRateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_multiregion\Access\CurrencyRateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "currency_rate",
 *   admin_permission = "administer multiregion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "from_currency",
 *   },
 *   links = {
 *     "collection" = "/admin/content/currency-rates",
 *     "add-form" = "/admin/content/currency-rates/add",
 *     "canonical" = "/admin/content/currency-rates/{currency_rate}",
 *     "edit-form" = "/admin/content/currency-rates/{currency_rate}/edit",
 *     "delete-form" = "/admin/content/currency-rates/{currency_rate}/delete",
 *   },
 *   field_ui_base_route = "jaraba_multiregion.currency_rate.settings",
 * )
 */
class CurrencyRate extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    // Campos base heredados de ContentEntityBase (id, uuid).
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: PAR DE MONEDAS
    // Moneda origen y moneda destino del tipo de cambio.
    // =========================================================================

    $fields['from_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Moneda origen'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 4217 de la moneda de origen (ej: EUR, USD, GBP).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 3)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Moneda destino'))
      ->setDescription(new TranslatableMarkup('Codigo ISO 4217 de la moneda de destino (ej: USD, GBP, BRL).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 3)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: TIPO DE CAMBIO Y FUENTE
    // Valor del tipo de cambio, fuente de datos y momento de obtencion.
    // =========================================================================

    $fields['rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo de cambio'))
      ->setDescription(new TranslatableMarkup('Tipo de cambio: 1 unidad de moneda origen = rate unidades de moneda destino.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 6)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Fuente'))
      ->setDescription(new TranslatableMarkup('Origen del tipo de cambio: BCE, introduccion manual o Stripe.'))
      ->setRequired(TRUE)
      ->setDefaultValue('ecb')
      ->setSetting('allowed_values', [
        'ecb' => 'ECB',
        'manual' => 'Manual',
        'stripe' => 'Stripe',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fetched_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha obtencion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora en que se obtuvo el tipo de cambio de la fuente.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: METADATOS TEMPORALES
    // Solo timestamp de creacion (entidad inmutable, sin changed).
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * Define indices de base de datos para la tabla currency_rate.
   * - from_currency + to_currency: indice compuesto para busqueda por par de monedas.
   * - fetched_at: indice para ordenacion temporal y obtencion del tipo vigente.
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['idx_currency_pair'] = ['from_currency', 'to_currency'];
    $schema['indexes']['idx_fetched_at'] = ['fetched_at'];

    return $schema;
  }

}
