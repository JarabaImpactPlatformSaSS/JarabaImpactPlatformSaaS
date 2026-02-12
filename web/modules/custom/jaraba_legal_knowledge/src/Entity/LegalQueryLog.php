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
 * Define la entidad Legal Query Log.
 *
 * ESTRUCTURA:
 * Registro de consultas legales realizadas por los usuarios a traves
 * del pipeline RAG. Almacena la pregunta, la respuesta generada,
 * las normas citadas y metricas de rendimiento y calidad.
 *
 * LOGICA:
 * Cada consulta se registra con los tokens de entrada/salida para
 * control de costes. El confidence_score indica la fiabilidad de la
 * respuesta. El feedback_rating permite a los usuarios valorar la
 * utilidad de la respuesta para mejorar el sistema.
 *
 * RELACIONES:
 * - LegalQueryLog -> Tenant (tenant_id): tenant del usuario
 * - LegalQueryLog -> User (uid): usuario que realizo la consulta
 * - LegalQueryLog -> LegalNorm (cited_norms): normas citadas (JSON)
 *
 * @ContentEntityType(
 *   id = "legal_query_log",
 *   label = @Translation("Legal Query Log"),
 *   label_collection = @Translation("Legal Query Logs"),
 *   label_singular = @Translation("legal query log"),
 *   label_plural = @Translation("legal query logs"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_knowledge\ListBuilder\LegalQueryLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_knowledge\Access\LegalNormAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_query_log",
 *   admin_permission = "administer legal knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-query-logs/{legal_query_log}",
 *     "collection" = "/admin/content/legal-query-logs",
 *   },
 * )
 */
class LegalQueryLog extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant del usuario que realizo la consulta.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Query Text ---
    $fields['query_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Texto de la Consulta'))
      ->setDescription(t('Pregunta realizada por el usuario.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Response Text ---
    $fields['response_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Texto de la Respuesta'))
      ->setDescription(t('Respuesta generada por el pipeline RAG.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Cited Norms (JSON array of norm IDs) ---
    $fields['cited_norms'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Normas Citadas'))
      ->setDescription(t('IDs de normas citadas en la respuesta (JSON array).'));

    // --- Confidence Score ---
    $fields['confidence_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Puntuacion de Confianza'))
      ->setDescription(t('Score de confianza de la respuesta (0-1).'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Response Time (ms) ---
    $fields['response_time_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tiempo de Respuesta (ms)'))
      ->setDescription(t('Tiempo de respuesta en milisegundos.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- Model Used ---
    $fields['model_used'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Modelo Utilizado'))
      ->setDescription(t('Identificador del modelo LLM utilizado.'))
      ->setSetting('max_length', 64);

    // --- Tokens Input ---
    $fields['tokens_input'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens de Entrada'))
      ->setDescription(t('Cantidad de tokens enviados al modelo.'))
      ->setDefaultValue(0);

    // --- Tokens Output ---
    $fields['tokens_output'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens de Salida'))
      ->setDescription(t('Cantidad de tokens generados por el modelo.'))
      ->setDefaultValue(0);

    // --- Feedback Rating (nullable, 1-5) ---
    $fields['feedback_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoracion'))
      ->setDescription(t('Valoracion del usuario (1-5).'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayConfigurable('view', TRUE);

    // --- Feedback Comment (nullable) ---
    $fields['feedback_comment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Comentario de Feedback'))
      ->setDescription(t('Comentario opcional del usuario sobre la respuesta.'));

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
