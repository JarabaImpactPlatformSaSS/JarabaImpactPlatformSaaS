<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Uptime Check.
 *
 * ESTRUCTURA:
 * Entidad que almacena resultados individuales de checks de uptime.
 * Cada registro es una medicion puntual del estado de un endpoint.
 *
 * LOGICA:
 * Los checks se ejecutan periodicamente via cron segun el intervalo
 * configurado. Cada check registra el estado (up/down/degraded),
 * tiempo de respuesta y codigo de estado HTTP.
 *
 * RELACIONES:
 * - UptimeCheck -> Tenant (tenant_id): tenant propietario
 * - UptimeCheck <- UptimeMonitorService: creado por
 * - UptimeCheck <- UptimeIncident: correlacionado con
 * - UptimeCheck <- UptimeCheckListBuilder: listado en admin
 *
 * @ContentEntityType(
 *   id = "uptime_check",
 *   label = @Translation("Uptime Check"),
 *   label_collection = @Translation("Uptime Checks"),
 *   label_singular = @Translation("uptime check"),
 *   label_plural = @Translation("uptime checks"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_insights_hub\ListBuilder\UptimeCheckListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "uptime_check",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/uptime-checks",
 *   },
 * )
 */
class UptimeCheck extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este check.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant');

    // --- Endpoint ---
    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Endpoint'))
      ->setDescription(t('URL del endpoint monitoreado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado del endpoint en este check.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'up' => 'Operativo',
        'down' => 'Caido',
        'degraded' => 'Degradado',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // --- Response Time ---
    $fields['response_time_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tiempo de Respuesta (ms)'))
      ->setDescription(t('Tiempo de respuesta en milisegundos.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Status Code ---
    $fields['status_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Codigo HTTP'))
      ->setDescription(t('Codigo de estado HTTP de la respuesta.'));

    // --- Error Message ---
    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Mensaje de Error'))
      ->setDescription(t('Mensaje de error si el check fallo.'))
      ->setSetting('max_length', 500);

    // --- Checked At ---
    $fields['checked_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Verificado en'))
      ->setDescription(t('Timestamp del momento del check.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    return $fields;
  }

}
