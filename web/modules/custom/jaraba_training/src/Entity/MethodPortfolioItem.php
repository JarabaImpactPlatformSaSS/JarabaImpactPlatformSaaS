<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad MethodPortfolioItem.
 *
 * Evidencia de portfolio para la certificación Método Jaraba.
 * Cada item demuestra una competencia (Pedir, Evaluar, Iterar, Integrar)
 * aplicada a una capa (Criterio, Supervisión IA, Posicionamiento).
 *
 * ENTITY-001: Implements EntityOwnerInterface, EntityChangedInterface.
 * AUDIT-CONS-001: AccessControlHandler en anotación.
 * TENANT-001: Campo tenant_id como entity_reference.
 * ENTITY-FK-001: certification_id = entity_reference (mismo módulo).
 *
 * @ContentEntityType(
 *   id = "method_portfolio_item",
 *   label = @Translation("Evidencia de Portfolio"),
 *   label_collection = @Translation("Evidencias de Portfolio"),
 *   label_singular = @Translation("evidencia"),
 *   label_plural = @Translation("evidencias"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_training\Access\MethodPortfolioItemAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "method_portfolio_item",
 *   admin_permission = "administer method portfolio",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/portfolio/{method_portfolio_item}",
 *     "collection" = "/admin/content/portfolio",
 *     "add-form" = "/admin/content/portfolio/add",
 *     "edit-form" = "/admin/content/portfolio/{method_portfolio_item}/edit",
 *     "delete-form" = "/admin/content/portfolio/{method_portfolio_item}/delete",
 *   },
 *   field_ui_base_route = "entity.method_portfolio_item.settings",
 * )
 */
class MethodPortfolioItem extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   *
   * @return array<string, \Drupal\Core\Field\BaseFieldDefinition>
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Título descriptivo de la evidencia.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: mismo módulo = entity_reference.
    $fields['certification_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Certificación'))
      ->setDescription(t('Certificación a la que pertenece esta evidencia.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user_certification')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // TENANT-001: TODA entity DEBE tener tenant_id.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción del trabajo realizado y contexto.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['competency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Competencia'))
      ->setDescription(t('Competencia que demuestra esta evidencia.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pedir' => 'Pedir',
        'evaluar' => 'Evaluar',
        'iterar' => 'Iterar',
        'integrar' => 'Integrar',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['layer'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Capa'))
      ->setDescription(t('Capa del Método Jaraba que cubre.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'criterio' => 'Criterio',
        'supervision_ia' => 'Supervisión IA',
        'posicionamiento' => 'Posicionamiento',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Archivo'))
      ->setDescription(t('URL del archivo adjunto (PDF, imagen).'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // URL-PROTOCOL-VALIDATE-001: validación de protocolo en presave.
    $fields['external_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL externa'))
      ->setDescription(t('URL externa (web publicada, perfil LinkedIn, etc.).'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_model_used'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Modelo IA usado'))
      ->setDescription(t('Modelo de IA utilizado para generar el output.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['evaluator_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuación del evaluador'))
      ->setDescription(t('Puntuación 1-4 asignada por el evaluador.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['evaluator_feedback'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Feedback del evaluador'))
      ->setDescription(t('Comentarios y feedback del evaluador sobre esta evidencia.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
