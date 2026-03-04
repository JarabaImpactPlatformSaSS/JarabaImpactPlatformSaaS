<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Legal Norm Relation.
 *
 * ESTRUCTURA:
 * Entidad que modela relaciones entre normas legales: derogaciones,
 * modificaciones, transposiciones, citas, etc. Forma el grafo normativo
 * del LCIS (Legal Coherence Intelligence System, spec 179 sec 3.3).
 *
 * LOGICA:
 * Cada relacion conecta una norma fuente con una norma destino via un
 * tipo de relacion tipificado (10 valores). Las relaciones pueden afectar
 * a articulos concretos (affected_articles, JSON). El NormativeGraphEnricher
 * consulta estas relaciones para enriquecer los resultados RAG con
 * consciencia de derogaciones y modificaciones parciales.
 *
 * RELACIONES:
 * - LegalNormRelation -> LegalNorm (source_norm_id): norma origen
 * - LegalNormRelation -> LegalNorm (target_norm_id): norma destino
 * - LegalNormRelation -> Tenant (tenant_id): tenant propietario
 * - LegalNormRelation <- NormativeGraphEnricher: consultado para ranking
 * - LegalNormRelation <- NormativeUpdateWorker: creado/actualizado por cola
 *
 * @ContentEntityType(
 *   id = "legal_norm_relation",
 *   label = @Translation("Legal Norm Relation"),
 *   label_collection = @Translation("Legal Norm Relations"),
 *   label_singular = @Translation("legal norm relation"),
 *   label_plural = @Translation("legal norm relations"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_knowledge\ListBuilder\LegalNormRelationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_knowledge\Access\LegalNormRelationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_knowledge\Form\LegalNormRelationForm",
 *       "edit" = "Drupal\jaraba_legal_knowledge\Form\LegalNormRelationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_norm_relation",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-norm-relations/{legal_norm_relation}",
 *     "collection" = "/admin/content/legal-norm-relations",
 *     "add-form" = "/admin/content/legal-norm-relations/add",
 *     "edit-form" = "/admin/content/legal-norm-relations/{legal_norm_relation}/edit",
 *     "delete-form" = "/admin/content/legal-norm-relations/{legal_norm_relation}/delete",
 *   },
 *   field_ui_base_route = "entity.legal_norm_relation.settings",
 * )
 */
class LegalNormRelation extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Allowed relation types between norms.
   */
  public const RELATION_TYPES = [
    'deroga_total' => 'Deroga totalmente',
    'deroga_parcial' => 'Deroga parcialmente',
    'modifica' => 'Modifica',
    'desarrolla' => 'Desarrolla',
    'transpone' => 'Transpone',
    'cita' => 'Cita',
    'complementa' => 'Complementa',
    'prevalece_sobre' => 'Prevalece sobre',
    'es_especial_de' => 'Es especial de (lex specialis)',
    'sustituye' => 'Sustituye',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Source Norm (ENTITY-FK-001: same module = entity_reference) ---
    $fields['source_norm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Norma Origen'))
      ->setDescription(t('Norma que origina la relacion (ej: la que deroga).'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Target Norm (ENTITY-FK-001: same module = entity_reference) ---
    $fields['target_norm_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Norma Destino'))
      ->setDescription(t('Norma afectada por la relacion (ej: la derogada).'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Relation Type ---
    $fields['relation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Relacion'))
      ->setDescription(t('Tipo de relacion normativa entre las dos normas.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::RELATION_TYPES)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Affected Articles (JSON) ---
    $fields['affected_articles'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Articulos Afectados'))
      ->setDescription(t('JSON array de articulos afectados por la relacion (ej: ["art. 15", "disposicion adicional 3a"]).'));

    // --- Effective Date ---
    $fields['effective_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha Efectiva'))
      ->setDescription(t('Fecha en que la relacion entra en vigor.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadata (JSON) ---
    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON con metadatos adicionales de la relacion (ej: {"boe_ref": "BOE-A-2024-1234", "context": "..."}).'));

    // --- Tenant (ENTITY-FK-001) ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
