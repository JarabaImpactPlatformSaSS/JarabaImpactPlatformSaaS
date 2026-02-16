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
 * Define la entidad Legal Resolution.
 *
 * ESTRUCTURA:
 * Entidad principal del Legal Intelligence Hub. Almacena resoluciones judiciales,
 * consultas vinculantes, normativa y doctrina administrativa de fuentes nacionales
 * (CENDOJ, BOE, DGT, TEAC) y europeas (TJUE, TEDH, EUR-Lex, EDPB).
 * Contiene 35+ campos organizados en bloques: identificacion, metadatos core,
 * texto completo, campos IA, campos UE (doc 178A), referencias Qdrant y SEO.
 *
 * LOGICA:
 * Los campos AI-generated (abstract_ai, key_holdings, topics, cited_legislation)
 * se rellenan por el pipeline NLP de 9 etapas (Fase 2). El campo status_legal
 * controla la vigencia juridica: vigente, derogada, anulada, superada,
 * parcialmente_derogada. Cambios de estado disparan alertas a profesionales
 * via hook_entity_update() en el .module. La deduplicacion se garantiza por
 * content_hash (SHA-256 del texto completo). Los campos EU (celex_number, ecli,
 * case_number, etc.) solo se rellenan para fuentes europeas.
 *
 * RELACIONES:
 * - LegalResolution -> User (uid): creador/importador del registro.
 * - LegalResolution <- LegalBookmark (resolution_id): favoritos de profesionales.
 * - LegalResolution <- LegalCitation (resolution_id): citas en expedientes.
 * - LegalResolution <- legal_citation_graph (source/target): grafo de citas.
 * - LegalResolution -> Qdrant (vector_ids): chunks vectorizados.
 *
 * @ContentEntityType(
 *   id = "legal_resolution",
 *   label = @Translation("Legal Resolution"),
 *   label_collection = @Translation("Legal Resolutions"),
 *   label_singular = @Translation("legal resolution"),
 *   label_plural = @Translation("legal resolutions"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalResolutionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalResolutionAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_intelligence\Form\LegalResolutionForm",
 *       "edit" = "Drupal\jaraba_legal_intelligence\Form\LegalResolutionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_resolution",
 *   admin_permission = "administer legal intelligence",
 *   field_ui_base_route = "jaraba_legal.resolution.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-resolutions/{legal_resolution}",
 *     "collection" = "/admin/content/legal-resolutions",
 *     "add-form" = "/admin/content/legal-resolutions/add",
 *     "edit-form" = "/admin/content/legal-resolutions/{legal_resolution}/edit",
 *     "delete-form" = "/admin/content/legal-resolutions/{legal_resolution}/delete",
 *   },
 * )
 */
class LegalResolution extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: IDENTIFICACION
    // Campos que identifican la resolucion de forma unica en el sistema.
    // source_id + external_ref forman la clave de negocio.
    // content_hash permite deduplicacion por contenido.
    // =========================================================================

    $fields['source_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source ID'))
      ->setDescription(t('Identificador de la fuente de datos: cendoj, boe, dgt, teac, tjue, eurlex, tedh, edpb, etc.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['external_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External Reference'))
      ->setDescription(t('Referencia oficial unica: V0123-24, STS 1234/2024, C-415/11. Se usa como clave de negocio para deduplicacion.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->addConstraint('UniqueField')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content SHA-256 Hash'))
      ->setDescription(t('Hash SHA-256 del texto completo para deduplicacion por contenido.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: METADATOS CORE
    // Campos descriptivos principales de la resolucion: titulo, tipo,
    // organo emisor, jurisdiccion, fechas y estado legal.
    // =========================================================================

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Titulo oficial de la resolucion.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Resolution Type'))
      ->setDescription(t('Tipo de resolucion: sentencia, auto, consulta_vinculante, resolucion, directiva, reglamento, etc.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['issuing_body'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Issuing Body'))
      ->setDescription(t('Organo emisor: TS, TC, AN, TSJ, AP, DGT, TEAC, TJUE, TEDH, etc.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['jurisdiction'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Jurisdiction'))
      ->setDescription(t('Jurisdiccion: civil, penal, laboral, fiscal, contencioso, mercantil, social.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_issued'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Issued'))
      ->setDescription(t('Fecha de emision de la resolucion.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['date_published'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Published'))
      ->setDescription(t('Fecha de publicacion oficial en el diario/boletin correspondiente.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status_legal'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Legal Status'))
      ->setDescription(t('Estado juridico de vigencia. Cambios de estado generan alertas automaticas a profesionales que la tengan citada.'))
      ->setRequired(TRUE)
      ->setDefaultValue('vigente')
      ->setSetting('allowed_values', [
        'vigente' => 'Vigente',
        'derogada' => 'Derogada',
        'anulada' => 'Anulada',
        'superada' => 'Superada',
        'parcialmente_derogada' => 'Parcialmente derogada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: TEXTO COMPLETO + URL ORIGINAL
    // =========================================================================

    $fields['full_text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Full Text'))
      ->setDescription(t('Texto integro de la resolucion. Procesado por Apache Tika (PDF/HTML a texto plano) y pipeline NLP.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['original_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Original URL'))
      ->setDescription(t('URL de la fuente oficial (CENDOJ, BOE, DGT, EUR-Lex, HUDOC, etc.).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: CAMPOS GENERADOS POR IA
    // Rellenados por el pipeline NLP (Fase 2): Gemini 2.0 Flash para
    // clasificacion y resumen, spaCy para NER.
    // =========================================================================

    $fields['abstract_ai'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('AI Abstract'))
      ->setDescription(t('Resumen de 3-5 lineas generado por Gemini 2.0 Flash con strict grounding.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['key_holdings'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Key Holdings / Ratio Decidendi'))
      ->setDescription(t('Ratio decidendi extraida por IA. Puntos clave de la doctrina establecida.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['topics'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Topics'))
      ->setDescription(t('Temas clasificados por IA como JSON array. Mapean a taxonomias legal_topic_*.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['cited_legislation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Cited Legislation'))
      ->setDescription(t('Leyes, articulos y normativa citada en la resolucion (JSON). Extraida por NER juridico spaCy.'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: CAMPOS ESPECIFICOS UE (Doc 178A)
    // Solo se rellenan para fuentes europeas (TJUE, TEDH, EUR-Lex, EDPB).
    // Permiten busqueda facetada por procedimiento, estado demandado,
    // nivel de importancia y articulos CEDH.
    // =========================================================================

    $fields['celex_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CELEX Number'))
      ->setDescription(t('Identificador CELEX de EUR-Lex (ej: 62011CJ0415).'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ecli'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ECLI'))
      ->setDescription(t('European Case Law Identifier (ej: ECLI:EU:C:2013:164).'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Case Number'))
      ->setDescription(t('Numero de asunto del tribunal (ej: C-415/11, 8675/15).'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['procedure_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Procedure Type'))
      ->setDescription(t('Tipo de procedimiento UE: prejudicial, infraccion, anulacion, omision.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['respondent_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Respondent State'))
      ->setDescription(t('Estado demandado, codigo ISO 3166-1 alpha-3 (ej: ESP, FRA, DEU).'))
      ->setSetting('max_length', 3)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cedh_articles'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CEDH Articles'))
      ->setDescription(t('Articulos del CEDH alegados o violados (JSON array). Solo para fuentes TEDH.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['eu_legal_basis'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('EU Legal Basis'))
      ->setDescription(t('Base juridica UE: tratados, directivas, reglamentos citados (JSON array).'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['advocate_general'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Advocate General'))
      ->setDescription(t('Nombre del Abogado General que emitio conclusiones. Solo TJUE.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['importance_level'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Importance Level'))
      ->setDescription(t('Nivel de importancia: 1=key case, 2=media, 3=baja. Afecta al ranking en merge & rank.'))
      ->setDefaultValue(3)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['language_original'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Original Language'))
      ->setDescription(t('Idioma original de la resolucion (ISO 639-1). Default: es.'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('es')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['impact_spain'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Impact on Spanish Law'))
      ->setDescription(t('Analisis generado por IA del impacto de esta resolucion UE en el derecho espanol.'))
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: REFERENCIAS QDRANT
    // Vinculan la resolucion con sus chunks vectorizados en Qdrant.
    // vector_ids contiene los UUIDs de los puntos en la coleccion.
    // =========================================================================

    $fields['vector_ids'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Qdrant Vector IDs'))
      ->setDescription(t('Array de UUIDs de puntos en Qdrant (JSON). Cada punto es un chunk de 512 tokens.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['qdrant_collection'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Qdrant Collection'))
      ->setDescription(t('Nombre de la coleccion Qdrant: legal_intelligence (nacional, 3072 dims) o legal_intelligence_eu (UE, 1024 dims).'))
      ->setSetting('max_length', 64)
      ->setDefaultValue('legal_intelligence')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 7: SEO
    // =========================================================================

    $fields['seo_slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SEO Slug'))
      ->setDescription(t('Slug URL-friendly para paginas publicas (ej: sentencia-ts-1234-2024-desahucio).'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 8: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp de creacion del registro en el sistema.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp de ultima modificacion.'));

    $fields['last_nlp_processed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last NLP Processed'))
      ->setDescription(t('Timestamp de la ultima vez que el pipeline NLP proceso esta resolucion.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Comprueba si la resolucion proviene de una fuente europea.
   *
   * Se usa para determinar la coleccion Qdrant apropiada, aplicar boost
   * de primacia UE en merge & rank, y mostrar indicadores UE en el frontend.
   *
   * @return bool
   *   TRUE si source_id es una fuente europea (tjue, eurlex, tedh, edpb, etc.).
   */
  public function isEuSource(): bool {
    $euSources = ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue'];
    return in_array($this->get('source_id')->value, $euSources, TRUE);
  }

  /**
   * Devuelve los temas clasificados como array PHP.
   *
   * El campo topics almacena un JSON array con los temas clasificados
   * por Gemini 2.0 Flash. Este helper decodifica el JSON para uso
   * en logica de negocio (matching de alertas, facetas de busqueda).
   *
   * @return array
   *   Array de strings con los temas. Vacio si no hay temas o JSON invalido.
   */
  public function getTopics(): array {
    $raw = $this->get('topics')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve la legislacion citada como array PHP.
   *
   * El campo cited_legislation almacena un JSON array con leyes,
   * articulos y normativa extraida por el NER juridico de spaCy.
   *
   * @return array
   *   Array asociativo con la legislacion citada. Vacio si no hay datos.
   */
  public function getCitedLegislation(): array {
    $raw = $this->get('cited_legislation')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve los articulos CEDH como array PHP.
   *
   * Solo aplica a resoluciones del TEDH. Los articulos se almacenan
   * como JSON array (ej: ["art. 6", "art. 8", "art. 13"]).
   *
   * @return array
   *   Array de strings con los articulos CEDH. Vacio si no aplica.
   */
  public function getCedhArticles(): array {
    $raw = $this->get('cedh_articles')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve la base juridica UE como array PHP.
   *
   * @return array
   *   Array con tratados, directivas y reglamentos. Vacio si no aplica.
   */
  public function getEuLegalBasis(): array {
    $raw = $this->get('eu_legal_basis')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve los IDs de vectores Qdrant como array PHP.
   *
   * Cada UUID corresponde a un chunk de 512 tokens indexado en la
   * coleccion Qdrant correspondiente (legal_intelligence o legal_intelligence_eu).
   *
   * @return array
   *   Array de UUIDs de puntos Qdrant. Vacio si no esta indexada.
   */
  public function getVectorIds(): array {
    $raw = $this->get('vector_ids')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Genera cita formateada en el formato solicitado.
   *
   * Produce texto de cita listo para insertar en escritos juridicos
   * en 4 formatos: formal (con ratio decidendi), resumida, bibliografica
   * y nota al pie. Se usa en el slide-panel de insercion de citas.
   *
   * @param string $format
   *   Formato de cita: 'formal', 'resumida', 'bibliografica', 'nota_al_pie'.
   *
   * @return string
   *   Texto de la cita formateada.
   */
  public function formatCitation(string $format = 'formal'): string {
    $body = $this->get('issuing_body')->value ?? '';
    $ref = $this->get('external_ref')->value ?? '';
    $date = $this->get('date_issued')->value ?? '';
    $ratio = $this->get('key_holdings')->value ?? '';

    return match ($format) {
      'formal' => sprintf(
        'Según establece %s %s de %s, de fecha %s: «%s».',
        $this->getResolutionTypeLabel(),
        $ref,
        $body,
        $date,
        mb_substr($ratio, 0, 500)
      ),
      'resumida' => sprintf(
        '%s (%s, %s): %s',
        $ref,
        $body,
        $date,
        mb_substr($ratio, 0, 200)
      ),
      'bibliografica' => sprintf(
        '%s. %s. %s, %s.',
        $body,
        $this->get('title')->value ?? '',
        $ref,
        $date
      ),
      'nota_al_pie' => sprintf(
        'Vid. %s %s, %s (%s).',
        $this->getResolutionTypeLabel(),
        $ref,
        $body,
        $date
      ),
      default => $ref,
    };
  }

  /**
   * Devuelve la etiqueta del tipo de resolucion en espanol con articulo.
   *
   * Se usa en formatCitation() para generar citas formales con el
   * articulo determinado correcto (la Sentencia, el Auto, etc.).
   *
   * @return string
   *   Etiqueta con articulo determinado.
   */
  private function getResolutionTypeLabel(): string {
    return match ($this->get('resolution_type')->value) {
      'sentencia' => 'la Sentencia',
      'auto' => 'el Auto',
      'consulta_vinculante' => 'la Consulta Vinculante',
      'resolucion' => 'la Resolución',
      'directiva' => 'la Directiva',
      'reglamento' => 'el Reglamento',
      'decision' => 'la Decisión',
      'dictamen' => 'el Dictamen',
      default => 'la Resolución',
    };
  }

}
