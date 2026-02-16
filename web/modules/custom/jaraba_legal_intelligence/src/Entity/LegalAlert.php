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
 * Define la entidad Legal Alert.
 *
 * ESTRUCTURA:
 * Suscripcion de alerta inteligente de un profesional. Cada alerta define
 * un tipo de evento (resolucion anulada, cambio de criterio, nueva doctrina,
 * etc.), un nivel de severidad y filtros opcionales por fuentes, temas y
 * jurisdicciones. Los canales de notificacion determinan como se entrega
 * la alerta (in_app, email, push).
 *
 * LOGICA:
 * Cuando hook_entity_insert/update detecta una resolucion relevante,
 * LegalAlertService evalua los filtros (sources, topics, jurisdictions)
 * contra la resolucion para determinar si dispara la alerta. Los filtros
 * se almacenan como JSON arrays para flexibilidad. trigger_count se
 * incrementa cada vez que la alerta se activa. last_triggered registra
 * el timestamp del ultimo disparo.
 *
 * RELACIONES:
 * - LegalAlert -> User (provider_id): profesional suscrito a la alerta.
 * - LegalAlert -> LegalResolution (via filtros): resoluciones que disparan
 *   la alerta segun los criterios configurados.
 *
 * @ContentEntityType(
 *   id = "legal_alert",
 *   label = @Translation("Legal Alert"),
 *   label_collection = @Translation("Legal Alerts"),
 *   label_singular = @Translation("legal alert"),
 *   label_plural = @Translation("legal alerts"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_intelligence\ListBuilder\LegalAlertListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_legal_intelligence\Access\LegalAlertAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_alert",
 *   admin_permission = "administer legal intelligence",
 *   field_ui_base_route = "jaraba_legal.alert.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/legal-alerts/{legal_alert}",
 *     "collection" = "/admin/content/legal-alerts",
 *     "add-form" = "/admin/content/legal-alerts/add",
 *     "edit-form" = "/admin/content/legal-alerts/{legal_alert}/edit",
 *     "delete-form" = "/admin/content/legal-alerts/{legal_alert}/delete",
 *   },
 * )
 */
class LegalAlert extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

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
    // Campos que identifican la alerta y al profesional suscrito.
    // =========================================================================

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Nombre descriptivo de la alerta configurada por el profesional.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Provider'))
      ->setDescription(t('Profesional suscrito a esta alerta.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: CONFIGURACION DE LA ALERTA
    // Tipo de evento, severidad y filtros que determinan cuando se dispara.
    // =========================================================================

    $fields['alert_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Alert Type'))
      ->setDescription(t('Tipo de evento juridico que dispara la alerta.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'resolution_annulled' => 'Resolucion anulada',
        'criteria_change' => 'Cambio de criterio',
        'new_relevant_doctrine' => 'Nueva doctrina relevante',
        'legislation_modified' => 'Legislacion modificada',
        'procedural_deadline' => 'Plazo procesal',
        'tjue_spain_impact' => 'Impacto TJUE en Espana',
        'tedh_spain' => 'TEDH contra Espana',
        'edpb_guideline' => 'Directriz EDPB',
        'transposition_deadline' => 'Plazo de transposicion',
        'ag_conclusions' => 'Conclusiones Abogado General',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setDescription(t('Nivel de severidad de la alerta. Afecta a la priorizacion en la bandeja del profesional.'))
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'critical' => 'Critica',
        'high' => 'Alta',
        'medium' => 'Media',
        'low' => 'Baja',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: FILTROS
    // JSON arrays que permiten acotar las resoluciones que disparan la alerta.
    // =========================================================================

    $fields['filter_sources'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filter Sources'))
      ->setDescription(t('Filtro de fuentes como JSON array (ej: ["cendoj","boe"]). Vacio = todas las fuentes.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filter_topics'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filter Topics'))
      ->setDescription(t('Filtro de temas como JSON array (ej: ["fiscal","laboral"]). Vacio = todos los temas.'))
      ->setSetting('max_length', 1024)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filter_jurisdictions'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filter Jurisdictions'))
      ->setDescription(t('Filtro de jurisdicciones como JSON array (ej: ["civil","penal"]). Vacio = todas.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: CANALES Y ESTADO
    // Configuracion de entrega y estado de activacion de la alerta.
    // =========================================================================

    $fields['channels'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Notification Channels'))
      ->setDescription(t('Canales de notificacion como JSON array (ej: ["in_app","email","push"]).'))
      ->setSetting('max_length', 256)
      ->setDefaultValue('["in_app"]')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Si la alerta esta habilitada. Las alertas inactivas no se evaluan.'))
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: ESTADISTICAS
    // Campos de seguimiento: ultimo disparo y contador de activaciones.
    // =========================================================================

    $fields['last_triggered'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Triggered'))
      ->setDescription(t('Timestamp de la ultima vez que esta alerta se disparo.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Trigger Count'))
      ->setDescription(t('Numero de veces que esta alerta se ha disparado.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp de creacion del registro en el sistema.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp de ultima modificacion.'));

    return $fields;
  }

  /**
   * Devuelve los filtros de fuentes como array PHP.
   *
   * El campo filter_sources almacena un JSON array con los machine_names
   * de las fuentes que deben disparar la alerta. Si esta vacio, la alerta
   * se evalua contra todas las fuentes.
   *
   * @return array
   *   Array de strings con machine_names de fuentes. Vacio = todas.
   */
  public function getFilterSources(): array {
    $raw = $this->get('filter_sources')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve los filtros de temas como array PHP.
   *
   * @return array
   *   Array de strings con temas. Vacio = todos.
   */
  public function getFilterTopics(): array {
    $raw = $this->get('filter_topics')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve los filtros de jurisdicciones como array PHP.
   *
   * @return array
   *   Array de strings con jurisdicciones. Vacio = todas.
   */
  public function getFilterJurisdictions(): array {
    $raw = $this->get('filter_jurisdictions')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

  /**
   * Devuelve los canales de notificacion como array PHP.
   *
   * @return array
   *   Array de strings con canales (ej: ['in_app', 'email']).
   */
  public function getChannels(): array {
    $raw = $this->get('channels')->value;
    return $raw ? (json_decode($raw, TRUE) ?? []) : [];
  }

}
