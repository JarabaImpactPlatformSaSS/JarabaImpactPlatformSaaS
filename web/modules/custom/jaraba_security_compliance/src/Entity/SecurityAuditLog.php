<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SecurityAuditLog.
 *
 * Registro inmutable de auditoría de seguridad de la plataforma.
 * Captura eventos como logins, cambios de permisos, creación de
 * tenants, y cualquier acción relevante para compliance.
 *
 * INMUTABILIDAD: Esta entidad no permite edición una vez creada.
 * Solo se puede visualizar y eliminar (por admins de sitio).
 *
 * Migrada desde ecosistema_jaraba_core\Entity\AuditLog con tabla
 * separada security_audit_log para evitar colisión.
 *
 * @ContentEntityType(
 *   id = "security_audit_log",
 *   label = @Translation("Security Audit Log"),
 *   label_collection = @Translation("Security Audit Logs"),
 *   label_singular = @Translation("security audit log"),
 *   label_plural = @Translation("security audit logs"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_security_compliance\ListBuilder\SecurityAuditLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_security_compliance\Access\SecurityAuditLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "security_audit_log",
 *   admin_permission = "view audit log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/security-audit-logs",
 *     "canonical" = "/admin/content/security-audit-logs/{security_audit_log}",
 *     "delete-form" = "/admin/content/security-audit-logs/{security_audit_log}/delete",
 *   },
 * )
 */
class SecurityAuditLog extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tipo de evento (e.g. 'user.login', 'tenant.created', 'permission.changed').
    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Evento'))
      ->setDescription(t('Identificador del tipo de evento de auditoría.'))
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 128,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ]);

    // Actor: usuario que ejecutó la acción.
    $fields['actor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setDescription(t('Usuario que ejecutó la acción.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ]);

    // Dirección IP.
    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección IP'))
      ->setDescription(t('IP desde la que se realizó la acción.'))
      ->setSettings([
        'max_length' => 45,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ]);

    // Severidad del evento.
    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severidad'))
      ->setDescription(t('Nivel de severidad del evento.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'info' => t('Info'),
        'notice' => t('Notice'),
        'warning' => t('Warning'),
        'critical' => t('Critical'),
      ])
      ->setDefaultValue('info')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 3,
      ]);

    // Tipo de entidad objetivo.
    $fields['target_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Objetivo'))
      ->setDescription(t('Tipo de entidad sobre la que se realizó la acción.'))
      ->setSettings([
        'max_length' => 128,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 4,
      ]);

    // ID de entidad objetivo.
    $fields['target_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID Objetivo'))
      ->setDescription(t('ID de la entidad sobre la que se realizó la acción.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ]);

    // Detalles adicionales en formato JSON (string_long).
    $fields['details'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Detalles'))
      ->setDescription(t('Datos adicionales del evento en formato JSON.'));

    // Tenant relacionado.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) donde ocurrió el evento.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 6,
      ]);

    // Timestamp de creación.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Momento en que se registró el evento.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'medium',
        ],
        'weight' => 7,
      ]);

    return $fields;
  }

  /**
   * Obtiene el tipo de evento.
   *
   * @return string
   *   El identificador del tipo de evento (e.g. 'user.login').
   */
  public function getEventType(): string {
    return $this->get('event_type')->value ?? '';
  }

  /**
   * Obtiene el ID del actor (usuario que ejecutó la acción).
   *
   * @return int|null
   *   El ID del usuario actor, o NULL si no hay actor.
   */
  public function getActorId(): ?int {
    $value = $this->get('actor_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * Obtiene la dirección IP.
   *
   * @return string
   *   La dirección IP desde donde se realizó la acción.
   */
  public function getIpAddress(): string {
    return $this->get('ip_address')->value ?? '';
  }

  /**
   * Obtiene la severidad del evento.
   *
   * @return string
   *   El nivel de severidad: 'info', 'notice', 'warning', o 'critical'.
   */
  public function getSeverity(): string {
    return $this->get('severity')->value ?? 'info';
  }

  /**
   * Obtiene los detalles adicionales del evento.
   *
   * @return array
   *   Array asociativo con los datos adicionales del evento.
   */
  public function getDetails(): array {
    $raw = $this->get('details')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el ID del tenant.
   *
   * @return int|null
   *   El ID del tenant, o NULL si no hay tenant asociado.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
