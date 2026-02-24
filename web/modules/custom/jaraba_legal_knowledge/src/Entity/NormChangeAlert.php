<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Norm Change Alert.
 *
 * ESTRUCTURA:
 * Alerta generada cuando se detecta un cambio en una norma legal
 * (nueva publicacion, modificacion, derogacion, correccion).
 * Las alertas se envian por email a los tenants afectados.
 *
 * LOGICA:
 * El campo severity determina la urgencia de la notificacion:
 * informativa < importante < critica < urgente. El status controla
 * el ciclo de vida: pending -> sent -> read -> dismissed.
 * Las alertas urgentes se envian inmediatamente; las demas en batch.
 *
 * RELACIONES:
 * - NormChangeAlert -> Tenant (tenant_id): tenant afectado
 * - NormChangeAlert -> LegalNorm (norm_id): norma modificada
 * - NormChangeAlert <- hook_mail: genera notificaciones email
 *
 * @ContentEntityType(
 *   id = "norm_change_alert",
 *   label = @Translation("Norm Change Alert"),
 *   label_collection = @Translation("Norm Change Alerts"),
 *   label_singular = @Translation("norm change alert"),
 *   label_plural = @Translation("norm change alerts"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_knowledge\ListBuilder\NormChangeAlertListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_knowledge\Access\LegalNormAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "norm_change_alert",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/norm-change-alerts/{norm_change_alert}",
 *     "collection" = "/admin/content/norm-change-alerts",
 *   },
 *   field_ui_base_route = "entity.norm_change_alert.settings",
 * )
 */
class NormChangeAlert extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant afectado por este cambio normativo.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Norm Reference ---
    $fields['norm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Norma'))
      ->setDescription(t('Norma legal que ha sufrido un cambio.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Change Type ---
    $fields['change_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Cambio'))
      ->setDescription(t('Naturaleza del cambio normativo.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'nueva' => 'Nueva',
        'modificacion' => 'Modificacion',
        'derogacion' => 'Derogacion',
        'correccion' => 'Correccion',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Change Summary ---
    $fields['change_summary'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resumen del Cambio'))
      ->setDescription(t('Descripcion del cambio normativo.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Affected Areas (JSON array) ---
    $fields['affected_areas'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Areas Afectadas'))
      ->setDescription(t('Areas tematicas afectadas por el cambio (JSON array).'));

    // --- Severity ---
    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severidad'))
      ->setDescription(t('Nivel de urgencia de la alerta.'))
      ->setRequired(TRUE)
      ->setDefaultValue('informativa')
      ->setSetting('allowed_values', [
        'informativa' => 'Informativa',
        'importante' => 'Importante',
        'critica' => 'Critica',
        'urgente' => 'Urgente',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado del ciclo de vida de la alerta.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'sent' => 'Enviada',
        'read' => 'Leida',
        'dismissed' => 'Descartada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Notification Sent At (nullable) ---
    $fields['notification_sent_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Notificacion Enviada'))
      ->setDescription(t('Timestamp del envio de la notificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
