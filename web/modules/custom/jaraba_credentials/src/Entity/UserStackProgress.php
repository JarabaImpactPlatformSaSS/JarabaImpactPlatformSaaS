<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad UserStackProgress.
 *
 * Progreso del usuario hacia la completación de un stack de credenciales.
 *
 * @ContentEntityType(
 *   id = "user_stack_progress",
 *   label = @Translation("Progreso de Stack"),
 *   label_collection = @Translation("Progreso de Stacks"),
 *   label_singular = @Translation("progreso de stack"),
 *   label_plural = @Translation("progresos de stacks"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\UserStackProgressListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\UserStackProgressForm",
 *       "add" = "Drupal\jaraba_credentials\Form\UserStackProgressForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\UserStackProgressForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\UserStackProgressAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_stack_progress",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.user_stack_progress.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/user-stack-progress/{user_stack_progress}",
 *     "add-form" = "/admin/content/user-stack-progress/add",
 *     "edit-form" = "/admin/content/user-stack-progress/{user_stack_progress}/edit",
 *     "delete-form" = "/admin/content/user-stack-progress/{user_stack_progress}/delete",
 *     "collection" = "/admin/content/user-stack-progress",
 *   },
 * )
 */
class UserStackProgress extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  public const STATUS_IN_PROGRESS = 'in_progress';
  public const STATUS_COMPLETED = 'completed';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['stack_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Stack'))
      ->setDescription(t('Stack de credenciales asociado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'credential_stack')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario al que pertenece este progreso.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_templates'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Templates Completados'))
      ->setDescription(t('JSON array de template IDs completados por el usuario.'))
      ->setDefaultValue('[]')
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -8,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['progress_percent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Progreso (%)'))
      ->setDescription(t('Porcentaje de completación (0-100).'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_IN_PROGRESS => t('En progreso'),
        self::STATUS_COMPLETED => t('Completado'),
      ])
      ->setDefaultValue(self::STATUS_IN_PROGRESS)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Iniciado'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Completado'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['result_credential_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Credencial Resultante'))
      ->setDescription(t('Credencial emitida al completar el stack.'))
      ->setSetting('target_type', 'issued_credential')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene los IDs de templates completados.
   */
  public function getCompletedTemplateIds(): array {
    $json = $this->get('completed_templates')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Obtiene el porcentaje de progreso.
   */
  public function getProgressPercent(): int {
    return (int) ($this->get('progress_percent')->value ?? 0);
  }

}
