<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Legal Coherence Log.
 *
 * ESTRUCTURA:
 * Entidad de auditoria que registra cada validacion de coherencia juridica
 * realizada por el LCIS (Legal Coherence Intelligence System). Cumplimiento
 * del EU AI Act Art. 12 (requisito de logging de interacciones AI).
 *
 * LOGICA:
 * Cada registro almacena: la consulta original, el tipo de intencion detectada,
 * el score de coherencia, los resultados de los 7 checks del validator,
 * los resultados de la verificacion semantica, las normas citadas, la cadena
 * jerarquica usada, y si la respuesta fue bloqueada o entregada con disclaimer.
 * Vinculo a trazas distribuidas via trace_id (TRACE-CONTEXT-001).
 *
 * Esta entidad es de SOLO LECTURA (audit log). NO tiene formulario de edicion.
 * Se crea programaticamente desde LegalCoherenceValidatorService y
 * LegalCoherenceVerifierService.
 *
 * RELACIONES:
 * - LegalCoherenceLog -> Tenant (tenant_id): tenant propietario
 * - LegalCoherenceLog <- LegalCoherenceValidatorService (creado por)
 * - LegalCoherenceLog <- LegalCoherenceVerifierService (creado por)
 * - LegalCoherenceLog -> TraceContextService (trace_id: vinculo a traza)
 *
 * @ContentEntityType(
 *   id = "legal_coherence_log",
 *   label = @Translation("Legal Coherence Log"),
 *   label_collection = @Translation("Legal Coherence Logs"),
 *   label_singular = @Translation("legal coherence log"),
 *   label_plural = @Translation("legal coherence logs"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalCoherenceLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalCoherenceLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_coherence_log",
 *   admin_permission = "administer legal intelligence",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-coherence-logs/{legal_coherence_log}",
 *     "collection" = "/admin/content/legal-coherence-logs",
 *   },
 *   field_ui_base_route = "entity.legal_coherence_log.settings",
 * )
 */
class LegalCoherenceLog extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Query Text ---
    $fields['query_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Consulta'))
      ->setDescription(t('Texto de la consulta original del usuario.'));

    // --- Intent Type ---
    $fields['intent_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Intencion'))
      ->setDescription(t('Tipo de intencion detectada por el clasificador.'))
      ->setSetting('allowed_values', [
        'legal' => 'Legal',
        'non_legal' => 'No Legal',
        'ambiguous' => 'Ambigua',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // --- Coherence Score ---
    $fields['coherence_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Score de Coherencia'))
      ->setDescription(t('Puntuacion global de coherencia juridica (0.0-1.0).'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Validator Results (JSON) ---
    $fields['validator_results'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resultados del Validador'))
      ->setDescription(t('JSON con el resultado de los 7 checks deterministas.'));

    // --- Verifier Results (JSON) ---
    $fields['verifier_results'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resultados del Verificador'))
      ->setDescription(t('JSON con el resultado de la verificacion semantica profunda.'));

    // --- Norm Citations (JSON) ---
    $fields['norm_citations'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Normas Citadas'))
      ->setDescription(t('JSON array de normas citadas en la respuesta.'));

    // --- Hierarchy Chain (JSON) ---
    $fields['hierarchy_chain'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Cadena Jerarquica'))
      ->setDescription(t('JSON con la cadena jerarquica usada para ranking.'));

    // --- Disclaimer Appended ---
    $fields['disclaimer_appended'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Disclaimer Anadido'))
      ->setDescription(t('Si se anadio disclaimer legal a la respuesta.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Retries Needed ---
    $fields['retries_needed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Reintentos'))
      ->setDescription(t('Numero de regeneraciones necesarias.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Blocked ---
    $fields['blocked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Bloqueada'))
      ->setDescription(t('Si la respuesta fue bloqueada por incoherencia.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Block Reason ---
    $fields['block_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Razon de Bloqueo'))
      ->setDescription(t('Razon del bloqueo, si aplica.'))
      ->setSetting('max_length', 512);

    // --- Response Snippet ---
    $fields['response_snippet'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Snippet de Respuesta'))
      ->setDescription(t('Primeros 500 caracteres de la respuesta (sin PII).'));

    // --- Vertical ---
    $fields['vertical'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Vertical de origen de la consulta.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('view', TRUE);

    // --- Trace ID (TRACE-CONTEXT-001) ---
    $fields['trace_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Trace ID'))
      ->setDescription(t('Identificador de traza distribuida para correlacion con AIObservability.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
