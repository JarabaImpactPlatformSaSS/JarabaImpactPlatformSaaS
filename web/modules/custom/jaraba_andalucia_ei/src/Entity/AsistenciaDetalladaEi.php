<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Asistencia Detallada entity.
 *
 * SPEC-2E-006: Tracks attendance per session per participant, distinguishing
 * presencial from online_sincronica modality (normative: max 20% online = 10h).
 *
 * @ContentEntityType(
 *   id = "asistencia_detallada_ei",
 *   label = @Translation("Asistencia Detallada"),
 *   label_collection = @Translation("Asistencias Detalladas"),
 *   label_singular = @Translation("asistencia detallada"),
 *   label_plural = @Translation("asistencias detalladas"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\AsistenciaDetalladaEiAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\AsistenciaDetalladaEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\AsistenciaDetalladaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\AsistenciaDetalladaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\AsistenciaDetalladaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "asistencia_detallada_ei",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/asistencia-detallada-ei",
 *     "canonical" = "/admin/content/asistencia-detallada-ei/{asistencia_detallada_ei}",
 *     "edit-form" = "/admin/content/asistencia-detallada-ei/{asistencia_detallada_ei}/edit",
 *     "add-form" = "/admin/content/asistencia-detallada-ei/add",
 *   },
 *   field_ui_base_route = "entity.asistencia_detallada_ei.settings",
 * )
 */
class AsistenciaDetalladaEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante del programa.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sesion_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de Sesión'))
      ->setDescription(t('Identificador de sesión del programa: OI-1.1, M0-1, M1-2, etc.'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modulo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Módulo'))
      ->setDescription(t('Módulo del programa al que pertenece la sesión.'))
      ->setSetting('allowed_values', [
        'orientacion' => t('Orientación Inicial'),
        'modulo_0' => t('Módulo 0 — Fundamentos IA'),
        'modulo_1' => t('Módulo 1 — Propuesta de Valor'),
        'modulo_2' => t('Módulo 2 — Finanzas'),
        'modulo_3' => t('Módulo 3 — Trámites'),
        'modulo_4' => t('Módulo 4 — Marketing Digital'),
        'modulo_5' => t('Módulo 5 — Integración'),
        'acompanamiento' => t('Acompañamiento Inserción'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha y hora'))
      ->setDescription(t('Fecha y hora de la sesión.'))
      ->setSetting('datetime_type', 'datetime')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modalidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setDescription(t('Presencial (≥80% formación) u online sincrónica (≤20%). Normativa PIIL.'))
      ->setSetting('allowed_values', [
        'presencial' => t('Presencial'),
        'online_sincronica' => t('Online sincrónica (videoconferencia)'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['horas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Horas'))
      ->setDescription(t('Duración de la sesión en horas.'))
      ->setSetting('precision', 4)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['asistio'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Asistió?'))
      ->setDescription(t('Si el participante asistió a la sesión.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['evidencia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de evidencia'))
      ->setDescription(t('Cómo se evidencia la asistencia.'))
      ->setSetting('allowed_values', [
        'firma_hoja' => t('Firma en hoja de servicio'),
        'conexion_videoconferencia' => t('Log de conexión videoconferencia'),
        'ambas' => t('Ambas'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['registrado_por'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Registrado por'))
      ->setDescription(t('Formador o coordinador que registró la asistencia.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
