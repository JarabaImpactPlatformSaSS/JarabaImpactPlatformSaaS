<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Triaje de Consulta (InquiryTriage).
 *
 * ESTRUCTURA:
 * Resultado del triaje IA de una consulta juridica. Almacena el score
 * de urgencia, tipo de caso sugerido, prioridad y resumen generado
 * por el modelo de IA.
 *
 * LOGICA:
 * El CaseTriageService invoca al proveedor de IA para analizar la
 * consulta y genera una entidad InquiryTriage con las sugerencias.
 * El confidence_score indica la fiabilidad del triaje (0.0-1.0).
 * El campo triage_model registra que modelo de IA se uso.
 *
 * RELACIONES:
 * - InquiryTriage -> ClientInquiry (inquiry_id): consulta triada.
 * - InquiryTriage -> User (uid): usuario que solicito el triaje.
 *
 * @ContentEntityType(
 *   id = "inquiry_triage",
 *   label = @Translation("Triaje de Consulta"),
 *   label_collection = @Translation("Triajes de Consulta"),
 *   label_singular = @Translation("triaje"),
 *   label_plural = @Translation("triajes"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_cases\Access\InquiryTriageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "inquiry_triage",
 *   admin_permission = "manage legal inquiries",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-inquiry-triages",
 *     "canonical" = "/admin/content/legal-inquiry-triages/{inquiry_triage}",
 *   },
 * )
 */
class InquiryTriage extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIA A LA CONSULTA
    // =========================================================================

    $fields['inquiry_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Consulta'))
      ->setDescription(new TranslatableMarkup('Consulta juridica que fue triada.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'client_inquiry')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: RESULTADO DEL TRIAJE IA
    // =========================================================================

    $fields['urgency_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Score de Urgencia'))
      ->setDescription(new TranslatableMarkup('Puntuacion de urgencia 0-100, calculada por IA.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suggested_case_type'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Tipo de Caso Sugerido'))
      ->setDescription(new TranslatableMarkup('Tipo de expediente sugerido por la IA.'))
      ->setSetting('max_length', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suggested_priority'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Prioridad Sugerida'))
      ->setDescription(new TranslatableMarkup('Prioridad sugerida por la IA (low/medium/high/critical).'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_summary'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Resumen IA'))
      ->setDescription(new TranslatableMarkup('Resumen generado por IA de la consulta y recomendaciones.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_areas_detected'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Areas Legales Detectadas'))
      ->setDescription(new TranslatableMarkup('Areas legales detectadas por IA, separadas por coma.'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confidence_score'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Score de Confianza'))
      ->setDescription(new TranslatableMarkup('Fiabilidad del triaje IA (0.0 a 1.0).'))
      ->setDefaultValue(0.0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['triage_model'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Modelo de IA'))
      ->setDescription(new TranslatableMarkup('Modelo de IA utilizado para el triaje (ej: gemini-2.0-flash).'))
      ->setSetting('max_length', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['raw_response'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Respuesta Bruta'))
      ->setDescription(new TranslatableMarkup('Respuesta completa del modelo de IA en JSON.'));

    $fields['metadata'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Metadatos'))
      ->setDescription(new TranslatableMarkup('Datos adicionales del triaje.'));

    // =========================================================================
    // BLOQUE 3: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
