<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD DR INCIDENT -- Incidente de Disaster Recovery.
 *
 * ESTRUCTURA:
 * Content Entity que almacena los incidentes de la plataforma.
 * Cada incidente registra severidad, estado, servicios afectados,
 * causa raiz, resolucion y log de comunicaciones.
 *
 * LOGICA DE NEGOCIO:
 * - Los incidentes siguen el ciclo: investigating -> identified ->
 *   monitoring -> resolved -> postmortem.
 * - La severidad sigue la clasificacion P1-P4 estandar.
 * - affected_services almacena JSON con los servicios impactados.
 * - communication_log almacena JSON con el historial de notificaciones.
 * - DR es a nivel de plataforma, no multi-tenant.
 *
 * RELACIONES:
 * - assigned_to -> User (usuario responsable del incidente)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "dr_incident",
 *   label = @Translation("DR Incident"),
 *   label_collection = @Translation("DR Incidents"),
 *   label_singular = @Translation("DR incident"),
 *   label_plural = @Translation("DR incidents"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_dr\ListBuilder\DrIncidentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_dr\Form\DrIncidentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_dr\Access\DrIncidentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dr_incident",
 *   admin_permission = "administer dr",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/dr-incident/{dr_incident}",
 *     "add-form" = "/admin/content/dr-incident/add",
 *     "edit-form" = "/admin/content/dr-incident/{dr_incident}/edit",
 *     "delete-form" = "/admin/content/dr-incident/{dr_incident}/delete",
 *     "collection" = "/admin/content/dr-incidents",
 *   },
 *   field_ui_base_route = "jaraba_dr.dr_incident.settings",
 * )
 */
class DrIncident extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- IDENTIFICACION ---

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setDescription(new TranslatableMarkup('Titulo descriptivo del incidente.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SEVERIDAD Y ESTADO ---

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Severidad'))
      ->setDescription(new TranslatableMarkup('Nivel de severidad del incidente.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'p1_critical' => new TranslatableMarkup('P1 - Critico'),
        'p2_major' => new TranslatableMarkup('P2 - Mayor'),
        'p3_minor' => new TranslatableMarkup('P3 - Menor'),
        'p4_informational' => new TranslatableMarkup('P4 - Informativo'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual del incidente.'))
      ->setRequired(TRUE)
      ->setDefaultValue('investigating')
      ->setSetting('allowed_values', [
        'investigating' => new TranslatableMarkup('Investigando'),
        'identified' => new TranslatableMarkup('Identificado'),
        'monitoring' => new TranslatableMarkup('Monitorizando'),
        'resolved' => new TranslatableMarkup('Resuelto'),
        'postmortem' => new TranslatableMarkup('Postmortem'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DESCRIPCION Y ANALISIS ---

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setDescription(new TranslatableMarkup('Descripcion detallada del incidente.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['affected_services'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Servicios afectados'))
      ->setDescription(new TranslatableMarkup('JSON con la lista de servicios afectados por el incidente.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['impact'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Impacto'))
      ->setDescription(new TranslatableMarkup('Descripcion del impacto del incidente en los servicios y usuarios.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['root_cause'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Causa raiz'))
      ->setDescription(new TranslatableMarkup('Analisis de la causa raiz del incidente.'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Resolucion'))
      ->setDescription(new TranslatableMarkup('Descripcion de las acciones tomadas para resolver el incidente.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIEMPOS ---

    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Inicio'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de inicio del incidente.'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Resolucion'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de resolucion del incidente.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- COMUNICACION ---

    $fields['communication_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Log de comunicaciones'))
      ->setDescription(new TranslatableMarkup('JSON con el historial de notificaciones enviadas durante el incidente.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['postmortem_url'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL del postmortem'))
      ->setDescription(new TranslatableMarkup('Enlace al documento de postmortem del incidente.'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RESPONSABLE ---

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Asignado a'))
      ->setDescription(new TranslatableMarkup('Usuario responsable del incidente.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creacion del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'))
      ->setDescription(new TranslatableMarkup('Fecha de ultima modificacion.'));

    return $fields;
  }

  /**
   * Comprueba si el incidente esta activo (no resuelto ni en postmortem).
   */
  public function isActive(): bool {
    $resolved_statuses = ['resolved', 'postmortem'];
    return !in_array($this->get('status')->value, $resolved_statuses, TRUE);
  }

  /**
   * Comprueba si el incidente esta resuelto.
   */
  public function isResolved(): bool {
    return $this->get('status')->value === 'resolved';
  }

  /**
   * Comprueba si el incidente es critico (P1).
   */
  public function isCritical(): bool {
    return $this->get('severity')->value === 'p1_critical';
  }

  /**
   * Comprueba si el incidente tiene postmortem asignado.
   */
  public function hasPostmortem(): bool {
    return !empty($this->get('postmortem_url')->value);
  }

  /**
   * Decodifica el JSON de affected_services.
   *
   * @return array<string, mixed>
   *   Array con los servicios afectados o vacio si no hay datos.
   */
  public function getAffectedServicesDecoded(): array {
    $json = $this->get('affected_services')->value;
    if (empty($json)) {
      return [];
    }
    $data = json_decode($json, TRUE);
    return is_array($data) ? $data : [];
  }

  /**
   * Decodifica el JSON de communication_log.
   *
   * @return array<int, mixed>
   *   Array con las entradas del log de comunicaciones.
   */
  public function getCommunicationLogDecoded(): array {
    $json = $this->get('communication_log')->value;
    if (empty($json)) {
      return [];
    }
    $data = json_decode($json, TRUE);
    return is_array($data) ? $data : [];
  }

  /**
   * Calcula la duracion del incidente en segundos.
   *
   * @return int
   *   Duracion en segundos, o 0 si no se ha resuelto.
   */
  public function getDurationSeconds(): int {
    $started = (int) $this->get('started_at')->value;
    $resolved = (int) $this->get('resolved_at')->value;
    if ($started <= 0 || $resolved <= 0) {
      return 0;
    }
    return $resolved - $started;
  }

}
