<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * SEO-DEPLOY-NOTIFY-001: Entity de tracking de notificaciones SEO.
 *
 * Registra cada notificacion enviada a Google (sitemap submissions
 * y URL notifications via Indexing API). Permite rate limiting,
 * reintentos y monitoring desde el dashboard de Insights Hub.
 *
 * @ContentEntityType(
 *   id = "seo_notification_log",
 *   label = @Translation("SEO Notification Log"),
 *   label_collection = @Translation("SEO Notification Logs"),
 *   label_singular = @Translation("SEO notification log entry"),
 *   label_plural = @Translation("SEO notification log entries"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "seo_notification_log",
 *   admin_permission = "administer insights hub",
 *   field_ui_base_route = "entity.seo_notification_log.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/seo-notification-log/{seo_notification_log}",
 *     "collection" = "/admin/content/seo-notification-log",
 *   },
 * )
 */
class SeoNotificationLog extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['domain'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dominio'))
      ->setDescription(t('Hostname del dominio destino.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['notification_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'sitemap_submit' => 'Sitemap Submission',
        'url_updated' => 'URL Updated',
        'url_deleted' => 'URL Deleted',
      ]);

    $fields['target_url'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('URL destino'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('queued')
      ->setSetting('allowed_values', [
        'queued' => 'En cola',
        'success' => 'Exitoso',
        'failed' => 'Fallido',
      ]);

    $fields['response_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Codigo HTTP'))
      ->setDefaultValue(0);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje de error'));

    $fields['source_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo entidad fuente'))
      ->setSetting('max_length', 64);

    $fields['source_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID entidad fuente'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'));

    return $fields;
  }

}
