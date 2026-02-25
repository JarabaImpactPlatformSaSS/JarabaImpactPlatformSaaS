<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad ExpedienteDocumento.
 *
 * Documento dentro del expediente de un participante del programa
 * Andalucía +ei. Almacena metadatos y referencia al archivo encriptado
 * en el vault (jaraba_legal_vault).
 *
 * @ContentEntityType(
 *   id = "expediente_documento",
 *   label = @Translation("Documento de Expediente"),
 *   label_collection = @Translation("Documentos de Expediente"),
 *   label_singular = @Translation("documento de expediente"),
 *   label_plural = @Translation("documentos de expediente"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ExpedienteDocumentoListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\ExpedienteDocumentoForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\ExpedienteDocumentoForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\ExpedienteDocumentoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\ExpedienteDocumentoAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "expediente_documento",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/expediente-documentos/{expediente_documento}",
 *     "add-form" = "/admin/content/expediente-documentos/add",
 *     "edit-form" = "/admin/content/expediente-documentos/{expediente_documento}/edit",
 *     "delete-form" = "/admin/content/expediente-documentos/{expediente_documento}/delete",
 *     "collection" = "/admin/content/expediente-documentos",
 *   },
 *   field_ui_base_route = "entity.expediente_documento.settings",
 * )
 */
class ExpedienteDocumento extends ContentEntityBase implements ExpedienteDocumentoInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Categorías de documentos del expediente.
   */
  const CATEGORIAS = [
    // Documentos requeridos por STO.
    'sto_dni' => 'DNI/NIE',
    'sto_empadronamiento' => 'Certificado de empadronamiento',
    'sto_vida_laboral' => 'Informe de vida laboral',
    'sto_demanda_empleo' => 'Tarjeta de demanda de empleo',
    'sto_prestaciones' => 'Certificado de prestaciones',
    'sto_titulo_academico' => 'Título académico',
    'sto_otros' => 'Otros documentos STO',
    // Documentos del programa.
    'programa_contrato' => 'Contrato de participación',
    'programa_consentimiento' => 'Consentimiento RGPD',
    'programa_compromiso' => 'Compromiso de participación',
    // Tareas y entregables.
    'tarea_diagnostico' => 'Diagnóstico inicial',
    'tarea_plan_empleo' => 'Plan de empleo/emprendimiento',
    'tarea_cv' => 'Currículum Vitae actualizado',
    'tarea_carta' => 'Carta de motivación',
    'tarea_proyecto' => 'Proyecto emprendedor',
    'tarea_entregable' => 'Entregable de formación',
    // Certificaciones.
    'cert_formacion' => 'Certificado de formación',
    'cert_competencias' => 'Certificado de competencias',
    'cert_participacion' => 'Certificado de participación',
  ];

  /**
   * {@inheritdoc}
   */
  public function getTitulo(): string {
    return $this->get('titulo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTitulo(string $titulo): self {
    $this->set('titulo', $titulo);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoria(): string {
    return $this->get('categoria')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipanteId(): ?int {
    $value = $this->get('participante_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEstadoRevision(): string {
    return $this->get('estado_revision')->value ?? 'pendiente';
  }

  /**
   * {@inheritdoc}
   */
  public function setEstadoRevision(string $estado): self {
    $this->set('estado_revision', $estado);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchivoVaultId(): ?string {
    return $this->get('archivo_vault_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isFirmado(): bool {
    return (bool) ($this->get('firmado')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRequeridoSto(): bool {
    return (bool) ($this->get('requerido_sto')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionIaScore(): ?float {
    $value = $this->get('revision_ia_score')->value;
    return $value !== NULL ? (float) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner field (uid) provided by EntityOwnerTrait.
    $fields['uid']
      ->setLabel(t('Subido por'))
      ->setDescription(t('Usuario que subió el documento.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === RELACIONES ===

    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante al que pertenece este documento.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este documento.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === METADATOS DEL DOCUMENTO ===

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Nombre descriptivo del documento.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['categoria'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Categoría'))
      ->setDescription(t('Tipo/categoría del documento.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', static::CATEGORIAS))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === REFERENCIA AL VAULT ===

    $fields['archivo_vault_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Vault'))
      ->setDescription(t('Referencia al SecureDocument en jaraba_legal_vault.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archivo_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre archivo'))
      ->setDescription(t('Nombre original del archivo subido.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archivo_mime'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo MIME'))
      ->setDescription(t('Tipo MIME del archivo.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archivo_tamano'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tamaño'))
      ->setDescription(t('Tamaño del archivo en bytes.'))
      ->setDisplayConfigurable('view', TRUE);

    // === ESTADO DE REVISIÓN ===

    $fields['estado_revision'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado revisión'))
      ->setDescription(t('Estado del proceso de revisión.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pendiente' => t('Pendiente'),
        'en_revision' => t('En revisión'),
        'aprobado' => t('Aprobado'),
        'rechazado' => t('Rechazado'),
        'requiere_cambios' => t('Requiere cambios'),
      ])
      ->setDefaultValue('pendiente')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === REVISIÓN IA ===

    $fields['revision_ia_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Puntuación IA'))
      ->setDescription(t('Puntuación de revisión automática (0-100).'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revision_ia_feedback'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Feedback IA'))
      ->setDescription(t('Feedback de revisión automática (JSON).'))
      ->setDisplayConfigurable('view', TRUE);

    // === REVISIÓN HUMANA ===

    $fields['revision_humana_notas'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas del revisor'))
      ->setDescription(t('Notas de la revisión humana.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revisor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revisor'))
      ->setDescription(t('Usuario que realizó la revisión.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === FIRMA DIGITAL ===

    $fields['firmado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Firmado digitalmente'))
      ->setDescription(t('Indica si el documento está firmado.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['firma_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de firma'))
      ->setDescription(t('Fecha en que se firmó digitalmente.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['firma_certificado_info'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Info certificado'))
      ->setDescription(t('Información del certificado de firma (JSON).'))
      ->setDisplayConfigurable('view', TRUE);

    // === SINCRONIZACIÓN STO ===

    $fields['requerido_sto'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Requerido STO'))
      ->setDescription(t('Si es obligatorio para exportación STO.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sto_sincronizado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sincronizado STO'))
      ->setDescription(t('Si el documento ya se sincronizó con STO.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // === CADUCIDAD ===

    $fields['fecha_vencimiento'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de vencimiento'))
      ->setDescription(t('Fecha de caducidad del documento (DNI, certificados).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === PUBLICACIÓN ===

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado'))
      ->setDescription(t('Estado de publicación del documento.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // === CAMPOS DE SISTEMA ===

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
