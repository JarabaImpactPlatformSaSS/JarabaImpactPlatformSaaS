<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad SesionProgramadaEi.
 *
 * Representa una sesión programada del programa Andalucía +ei.
 * Puede estar vinculada a una acción formativa o ser independiente
 * (orientación individual, tutoría). Gestiona plazas y recurrencia.
 *
 * @ContentEntityType(
 *   id = "sesion_programada_ei",
 *   label = @Translation("Sesión Programada"),
 *   label_collection = @Translation("Sesiones Programadas"),
 *   label_singular = @Translation("sesión programada"),
 *   label_plural = @Translation("sesiones programadas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\SesionProgramadaEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\SesionProgramadaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\SesionProgramadaEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "sesion_programada_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/sesiones-programadas-ei/{sesion_programada_ei}",
 *     "add-form" = "/admin/content/sesiones-programadas-ei/add",
 *     "edit-form" = "/admin/content/sesiones-programadas-ei/{sesion_programada_ei}/edit",
 *     "delete-form" = "/admin/content/sesiones-programadas-ei/{sesion_programada_ei}/delete",
 *     "collection" = "/admin/content/sesiones-programadas-ei",
 *   },
 *   field_ui_base_route = "entity.sesion_programada_ei.settings",
 * )
 */
class SesionProgramadaEi extends ContentEntityBase implements SesionProgramadaEiInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitulo(): string {
    return $this->get('titulo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTipoSesion(): string {
    return $this->get('tipo_sesion')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFecha(): ?string {
    return $this->get('fecha')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHoraInicio(): string {
    return $this->get('hora_inicio')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHoraFin(): string {
    return $this->get('hora_fin')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getModalidad(): string {
    return $this->get('modalidad')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEstado(): string {
    return $this->get('estado')->value ?? 'programada';
  }

  /**
   * {@inheritdoc}
   */
  public function getFasePrograma(): string {
    return $this->get('fase_programa')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxPlazas(): int {
    return (int) ($this->get('max_plazas')->value ?? 20);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlazasOcupadas(): int {
    return (int) ($this->get('plazas_ocupadas')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlazasDisponibles(): int {
    return max(0, $this->getMaxPlazas() - $this->getPlazasOcupadas());
  }

  /**
   * {@inheritdoc}
   */
  public function isGrupal(): bool {
    $tipo = $this->getTipoSesion();
    return !in_array($tipo, ['orientacion_individual', 'tutoria'], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function hayPlazasDisponibles(): bool {
    if (!$this->isGrupal()) {
      return $this->getPlazasOcupadas() === 0;
    }
    return $this->getPlazasDisponibles() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuracionHoras(): float {
    $inicio = $this->getHoraInicio();
    $fin = $this->getHoraFin();
    if (empty($inicio) || empty($fin)) {
      return 0.0;
    }

    $partesInicio = explode(':', $inicio);
    $partesFin = explode(':', $fin);
    if (count($partesInicio) < 2 || count($partesFin) < 2) {
      return 0.0;
    }

    $minutosInicio = ((int) $partesInicio[0]) * 60 + (int) $partesInicio[1];
    $minutosFin = ((int) $partesFin[0]) * 60 + (int) $partesFin[1];

    if ($minutosFin <= $minutosInicio) {
      return 0.0;
    }

    return round(($minutosFin - $minutosInicio) / 60, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function isRecurrente(): bool {
    return (bool) ($this->get('es_recurrente')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(\Drupal\Core\Entity\EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Recalculate plazas_ocupadas from InscripcionSesionEi entities.
    // PRESAVE-RESILIENCE-001: Optional service with try-catch.
    if (\Drupal::hasService('entity_type.manager')) {
      try {
        $entityTypeManager = \Drupal::entityTypeManager();
        if ($entityTypeManager->hasDefinition('inscripcion_sesion_ei') && !$this->isNew()) {
          $query = $entityTypeManager->getStorage('inscripcion_sesion_ei')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('sesion_id', $this->id())
            ->condition('estado', 'cancelada', '<>');

          // TENANT-001: Filtrar por tenant_id.
          $tenantId = $this->get('tenant_id')->target_id;
          if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
          }

          $count = $query->count()->execute();
          $this->set('plazas_ocupadas', (int) $count);
        }
      }
      catch (\Throwable) {
        // PRESAVE-RESILIENCE-001: Silently continue if entity not available.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner.
    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Usuario que programó la sesión.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta sesión programada.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === DATOS PRINCIPALES ===

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Nombre de la sesión. Ejemplo: "Sesión 3: Competencias Digitales".'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Contenido y objetivos de la sesión.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: Same module = entity_reference.
    $fields['accion_formativa_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Acción Formativa'))
      ->setDescription(t('Acción formativa a la que pertenece esta sesión (opcional).'))
      ->setSetting('target_type', 'accion_formativa_ei')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_sesion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Sesión'))
      ->setDescription(t('Tipo de sesión según la actividad a realizar.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', SesionProgramadaEiInterface::TIPOS_SESION))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fase_programa'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fase del Programa'))
      ->setDescription(t('Fase del itinerario Andalucía +ei en la que se enmarca la sesión.'))
      ->setSetting('allowed_values', array_map('t', SesionProgramadaEiInterface::FASES_PROGRAMA))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === HORARIO ===

    $fields['fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha'))
      ->setDescription(t('Fecha de la sesión.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hora_inicio'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora de Inicio'))
      ->setDescription(t('Hora de inicio en formato HH:MM.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hora_fin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora de Fin'))
      ->setDescription(t('Hora de fin en formato HH:MM.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modalidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setDescription(t('Modalidad de impartición de la sesión.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', SesionProgramadaEiInterface::MODALIDADES))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lugar_descripcion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lugar'))
      ->setDescription(t('Descripción del lugar donde se celebra la sesión.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lugar_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del Lugar'))
      ->setDescription(t('Enlace a la sala virtual o mapa del lugar físico.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FACILITADOR ===

    $fields['facilitador_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Facilitador/a'))
      ->setDescription(t('Profesional que facilita la sesión.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['facilitador_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Facilitador (externo)'))
      ->setDescription(t('Si el facilitador no es usuario de la plataforma.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CAPACIDAD ===

    $fields['max_plazas'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Máximo de Plazas'))
      ->setDescription(t('Número máximo de participantes permitidos.'))
      ->setDefaultValue(20)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['plazas_ocupadas'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Plazas Ocupadas'))
      ->setDescription(t('Número de plazas actualmente ocupadas. Se recalcula automáticamente.'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la sesión programada.'))
      ->setRequired(TRUE)
      ->setDefaultValue('programada')
      ->setSetting('allowed_values', array_map('t', SesionProgramadaEiInterface::ESTADOS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === RECURRENCIA ===

    $fields['es_recurrente'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('¿Es recurrente?'))
      ->setDescription(t('Indica si la sesión se repite según un patrón de recurrencia.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recurrencia_patron'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Patrón de Recurrencia'))
      ->setDescription(t('Patrón de recurrencia en formato JSON (frecuencia, días, fin).'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Self-referencing: ENTITY-FK-001 same entity type = entity_reference.
    $fields['sesion_padre_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sesión Padre'))
      ->setDescription(t('Sesión original de la que se generó esta sesión recurrente.'))
      ->setSetting('target_type', 'sesion_programada_ei')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === TIMESTAMPS ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Última actualización'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
