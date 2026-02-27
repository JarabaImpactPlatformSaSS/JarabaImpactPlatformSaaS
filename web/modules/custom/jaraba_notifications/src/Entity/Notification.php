<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Notification para el centro de notificaciones.
 *
 * Almacena notificaciones por usuario y tenant con soporte para tipos
 * (system, social, workflow, ai), estado de lectura, y link de accion.
 *
 * Directivas:
 * - ENTITY-OWNER-PATTERN-001: uid field con EntityOwnerTrait
 * - TENANT-001: tenant_id entity reference con filtro obligatorio
 * - FIELD-UI-SETTINGS-TAB-001: field_ui_base_route para admin/structure
 * - PRESAVE-RESILIENCE-001: preSave() sin servicios opcionales
 *
 * @ContentEntityType(
 *   id = "notification",
 *   label = @Translation("Notificacion"),
 *   label_collection = @Translation("Notificaciones"),
 *   label_singular = @Translation("notificacion"),
 *   label_plural = @Translation("notificaciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_notifications\NotificationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_notifications\Access\NotificationAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "notification",
 *   admin_permission = "administer site structure",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/notifications",
 *     "add-form" = "/admin/content/notifications/add",
 *     "canonical" = "/admin/content/notifications/{notification}",
 *     "edit-form" = "/admin/content/notifications/{notification}/edit",
 *     "delete-form" = "/admin/content/notifications/{notification}/delete",
 *   },
 *   field_ui_base_route = "entity.notification.settings",
 * )
 */
class Notification extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Tenant — aislamiento multi-tenant (TENANT-001).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece esta notificacion.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tipo de notificacion.
    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo'))
      ->setDescription(t('Categoria de la notificacion.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'system' => 'Sistema',
        'social' => 'Social',
        'workflow' => 'Workflow',
        'ai' => 'IA',
      ])
      ->setDefaultValue('system')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Titulo corto.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo corto de la notificacion.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Mensaje descriptivo.
    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje'))
      ->setDescription(t('Descripcion extendida de la notificacion.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Link de accion.
    $fields['link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Enlace'))
      ->setDescription(t('URL de accion asociada a la notificacion.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de lectura.
    $fields['read_status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Leida'))
      ->setDescription(t('Si la notificacion ha sido leida.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Dismissed (soft delete).
    $fields['dismissed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Descartada'))
      ->setDescription(t('Si la notificacion ha sido descartada por el usuario.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creada'))
      ->setDescription(t('Fecha de creacion de la notificacion.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizada'))
      ->setDescription(t('Fecha de ultima actualizacion.'));

    return $fields;
  }

  /**
   * Obtiene el tipo de notificacion.
   */
  public function getNotificationType(): string {
    return (string) $this->get('type')->value;
  }

  /**
   * Obtiene el titulo.
   */
  public function getTitle(): string {
    return (string) $this->get('title')->value;
  }

  /**
   * Obtiene el mensaje.
   */
  public function getMessage(): string {
    return (string) $this->get('message')->value;
  }

  /**
   * Obtiene el link de accion.
   */
  public function getLink(): string {
    return (string) $this->get('link')->value;
  }

  /**
   * Comprueba si la notificacion ha sido leida.
   */
  public function isRead(): bool {
    return (bool) $this->get('read_status')->value;
  }

  /**
   * Marca como leida.
   */
  public function markRead(): self {
    $this->set('read_status', TRUE);
    return $this;
  }

  /**
   * Comprueba si ha sido descartada.
   */
  public function isDismissed(): bool {
    return (bool) $this->get('dismissed')->value;
  }

  /**
   * Marca como descartada.
   */
  public function dismiss(): self {
    $this->set('dismissed', TRUE);
    return $this;
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    $target = $this->get('tenant_id')->target_id;
    return $target ? (int) $target : NULL;
  }

}
