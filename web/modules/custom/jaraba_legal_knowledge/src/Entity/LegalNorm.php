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
 * Define la entidad Legal Norm.
 *
 * ESTRUCTURA:
 * Entidad que almacena normas legales (leyes, decretos, resoluciones, etc.)
 * sincronizadas desde el BOE y otras fuentes. Cada norma se fragmenta en
 * chunks para el pipeline RAG y se almacena con sus embeddings en Qdrant.
 *
 * LOGICA:
 * El campo embedding_status controla el ciclo de vida del procesamiento:
 * pending -> completed / failed. El campo status refleja la vigencia juridica:
 * vigente, derogada, modificada, pendiente. Las normas derogadas pueden
 * referenciar a la norma que las sustituye via superseded_by.
 *
 * RELACIONES:
 * - LegalNorm -> Tenant (tenant_id): tenant propietario
 * - LegalNorm -> LegalNorm (superseded_by): norma sustituta (nullable)
 * - LegalNorm <- LegalChunk: fragmentos de texto
 * - LegalNorm <- NormChangeAlert: alertas de cambios
 * - LegalNorm <- LegalQueryLog (cited_norms): citada en consultas
 *
 * @ContentEntityType(
 *   id = "legal_norm",
 *   label = @Translation("Legal Norm"),
 *   label_collection = @Translation("Legal Norms"),
 *   label_singular = @Translation("legal norm"),
 *   label_plural = @Translation("legal norms"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_knowledge\ListBuilder\LegalNormListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_knowledge\Access\LegalNormAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_norm",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-norms/{legal_norm}",
 *     "collection" = "/admin/content/legal-norms",
 *     "add-form" = "/admin/content/legal-norms/add",
 *     "edit-form" = "/admin/content/legal-norms/{legal_norm}/edit",
 *     "delete-form" = "/admin/content/legal-norms/{legal_norm}/delete",
 *   },
 * )
 */
class LegalNorm extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Title ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo oficial de la norma.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Norm Type ---
    $fields['norm_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Norma'))
      ->setDescription(t('Clasificacion juridica de la norma.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'ley_organica' => 'Ley Organica',
        'ley' => 'Ley',
        'real_decreto_ley' => 'Real Decreto-ley',
        'real_decreto' => 'Real Decreto',
        'orden' => 'Orden',
        'resolucion' => 'Resolucion',
        'directiva_ue' => 'Directiva UE',
        'reglamento_ue' => 'Reglamento UE',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BOE ID ---
    $fields['boe_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BOE ID'))
      ->setDescription(t('Identificador unico en el BOE.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- BOE URL ---
    $fields['boe_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('BOE URL'))
      ->setDescription(t('URL de la publicacion en el BOE.'))
      ->setSetting('max_length', 2048)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Publication Date ---
    $fields['publication_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Publicacion'))
      ->setDescription(t('Fecha de publicacion oficial.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Effective Date ---
    $fields['effective_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Entrada en Vigor'))
      ->setDescription(t('Fecha en que la norma entra en vigor.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Expiry Date (nullable) ---
    $fields['expiry_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Expiracion'))
      ->setDescription(t('Fecha de expiracion de la norma, si aplica.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Scope ---
    $fields['scope'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Ambito'))
      ->setDescription(t('Ambito territorial de la norma.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'nacional' => 'Nacional',
        'autonomico' => 'Autonomico',
        'local' => 'Local',
        'europeo' => 'Europeo',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Autonomous Community (nullable) ---
    $fields['autonomous_community'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Comunidad Autonoma'))
      ->setDescription(t('Comunidad autonoma, si el ambito es autonomico.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Subject Areas (JSON array) ---
    $fields['subject_areas'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Areas Tematicas'))
      ->setDescription(t('Areas tematicas de la norma (JSON array).'));

    // --- Full Text ---
    $fields['full_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Texto Completo'))
      ->setDescription(t('Texto integro de la norma.'));

    // --- Summary ---
    $fields['summary'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resumen'))
      ->setDescription(t('Resumen de la norma.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de vigencia de la norma.'))
      ->setRequired(TRUE)
      ->setDefaultValue('vigente')
      ->setSetting('allowed_values', [
        'vigente' => 'Vigente',
        'derogada' => 'Derogada',
        'modificada' => 'Modificada',
        'pendiente' => 'Pendiente',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Superseded By (nullable, entity_reference to legal_norm) ---
    $fields['superseded_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sustituida por'))
      ->setDescription(t('Norma que sustituye a esta (si derogada/modificada).'))
      ->setSetting('target_type', 'legal_norm')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Last Synced At ---
    $fields['last_synced_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima Sincronizacion'))
      ->setDescription(t('Timestamp de la ultima sincronizacion con el BOE.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Embedding Status ---
    $fields['embedding_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Embedding'))
      ->setDescription(t('Estado del procesamiento de embeddings.'))
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'failed' => 'Fallido',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // --- Chunk Count ---
    $fields['chunk_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Numero de Chunks'))
      ->setDescription(t('Cantidad de fragmentos generados para el pipeline RAG.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
