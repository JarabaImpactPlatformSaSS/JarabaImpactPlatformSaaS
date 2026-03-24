<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad RolProgramaLog.
 *
 * Registra cada asignación o revocación de rol de programa
 * (coordinador, orientador, formador) para auditoría.
 *
 * @ContentEntityType(
 *   id = "rol_programa_log",
 *   label = @Translation("Log de Rol del Programa"),
 *   label_collection = @Translation("Logs de Roles del Programa"),
 *   label_singular = @Translation("log de rol del programa"),
 *   label_plural = @Translation("logs de roles del programa"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\RolProgramaLogAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\RolProgramaLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "rol_programa_log",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/rol-programa-log",
 *   },
 * )
 */
class RolProgramaLog extends ContentEntityBase {

  /**
   * Roles de programa válidos.
   */
  public const ROLES = [
    'coordinador' => 'Coordinador',
    'orientador' => 'Orientador',
    'formador' => 'Formador',
  ];

  /**
   * Acciones válidas.
   */
  public const ACCIONES = [
    'asignar' => 'Asignar',
    'revocar' => 'Revocar',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario afectado'))
      ->setDescription(t('Usuario al que se asigna o revoca el rol.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Asignado por'))
      ->setDescription(t('Usuario que realiza la asignación o revocación.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rol_programa'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Rol del Programa'))
      ->setDescription(t('Rol asignado o revocado.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::ROLES)
      ->setDisplayConfigurable('view', TRUE);

    $fields['accion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Acción'))
      ->setDescription(t('Tipo de acción realizada sobre el rol.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::ACCIONES)
      ->setDisplayConfigurable('view', TRUE);

    $fields['motivo'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Motivo'))
      ->setDescription(t('Motivo opcional de la asignación o revocación.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este log.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
