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
 * Defines the Evaluacion Competencia IA entity.
 *
 * Rubrica de 4 niveles (novel/aprendiz/competente/autonomo) para medir
 * competencia digital IA de participantes PIIL en 3 momentos evaluativos.
 *
 * @ContentEntityType(
 *   id = "evaluacion_competencia_ia_ei",
 *   label = @Translation("Evaluación Competencia IA"),
 *   label_collection = @Translation("Evaluaciones Competencia IA"),
 *   label_singular = @Translation("evaluación competencia IA"),
 *   label_plural = @Translation("evaluaciones competencia IA"),
 *   label_count = @PluralTranslation(
 *     singular = "@count evaluación competencia IA",
 *     plural = "@count evaluaciones competencia IA",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\EvaluacionCompetenciaIaEiAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\EvaluacionCompetenciaIaEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\EvaluacionCompetenciaIaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\EvaluacionCompetenciaIaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\EvaluacionCompetenciaIaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "evaluacion_competencia_ia_ei",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/evaluaciones-competencia-ia-ei",
 *     "canonical" = "/admin/content/evaluaciones-competencia-ia-ei/{evaluacion_competencia_ia_ei}",
 *     "edit-form" = "/admin/content/evaluaciones-competencia-ia-ei/{evaluacion_competencia_ia_ei}/edit",
 *     "add-form" = "/admin/content/evaluaciones-competencia-ia-ei/add",
 *   },
 *   field_ui_base_route = "entity.evaluacion_competencia_ia_ei.settings",
 * )
 */
class EvaluacionCompetenciaIaEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

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
      ->setDescription(t('Participante del programa evaluado.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de evaluación'))
      ->setDescription(t('Momento evaluativo: inicial, intermedia o final.'))
      ->setSetting('allowed_values', [
        'inicial' => t('Inicial'),
        'intermedia' => t('Intermedia'),
        'final' => t('Final'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nivel_global'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel global'))
      ->setDescription(t('Nivel de competencia digital IA alcanzado.'))
      ->setSetting('allowed_values', [
        'novel' => t('Novel'),
        'aprendiz' => t('Aprendiz'),
        'competente' => t('Competente'),
        'autonomo' => t('Autónomo'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['indicadores'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Indicadores'))
      ->setDescription(t('Detalle de indicadores evaluados en formato JSON.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['evaluador'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Evaluador'))
      ->setDescription(t('Quién realiza la evaluación.'))
      ->setSetting('allowed_values', [
        'formador' => t('Formador'),
        'autoevaluacion' => t('Autoevaluación'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Observaciones adicionales del evaluador.'))
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
