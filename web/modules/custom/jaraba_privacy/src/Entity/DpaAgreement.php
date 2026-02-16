<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD DPA AGREEMENT — Data Processing Agreement.
 *
 * ESTRUCTURA:
 * Content Entity que almacena los DPA firmados por cada tenant.
 * Cada tenant DEBE firmar un DPA antes de que se active el procesamiento
 * de sus datos personales (RGPD Art. 28).
 *
 * LÓGICA DE NEGOCIO:
 * - Un tenant puede tener múltiples DPAs (versionados), solo uno activo.
 * - Al firmar un nuevo DPA, los anteriores pasan a estado 'superseded'.
 * - La firma incluye hash SHA-256 del contenido, timestamp UTC, IP y user-agent.
 * - El PDF firmado se genera y almacena como file entity.
 * - El modal de firma es bloqueante: sin DPA no hay acceso al panel.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 * - signed_by → User (usuario que firmó el DPA)
 * - pdf_file_id → File (PDF generado con sello de tiempo)
 *
 * Spec: Doc 183 §2.1. Plan: FASE 1, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "dpa_agreement",
 *   label = @Translation("DPA Agreement"),
 *   label_collection = @Translation("DPA Agreements"),
 *   label_singular = @Translation("DPA agreement"),
 *   label_plural = @Translation("DPA agreements"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_privacy\ListBuilder\DpaAgreementListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_privacy\Form\DpaAgreementForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_privacy\Access\DpaAgreementAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dpa_agreement",
 *   admin_permission = "administer privacy",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "version",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/dpa-agreement/{dpa_agreement}",
 *     "add-form" = "/admin/content/dpa-agreement/add",
 *     "edit-form" = "/admin/content/dpa-agreement/{dpa_agreement}/edit",
 *     "delete-form" = "/admin/content/dpa-agreement/{dpa_agreement}/delete",
 *     "collection" = "/admin/content/dpa-agreements",
 *   },
 *   field_ui_base_route = "jaraba_privacy.dpa_agreement.settings",
 * )
 */
class DpaAgreement extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece este DPA.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERSIONADO ---

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Versión'))
      ->setDescription(new TranslatableMarkup('Versión del DPA (ej: 1.0, 2.0).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- FIRMA ---

    $fields['signed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de firma'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de la firma del DPA.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['signed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Firmado por'))
      ->setDescription(new TranslatableMarkup('Usuario que firmó el DPA.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['signer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del firmante'))
      ->setDescription(new TranslatableMarkup('Nombre completo del firmante del DPA.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['signer_role'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Cargo del firmante'))
      ->setDescription(new TranslatableMarkup('Cargo o rol del firmante en la organización.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Dirección IP'))
      ->setDescription(new TranslatableMarkup('IP desde la que se firmó el DPA.'))
      ->setSetting('max_length', 45)
      ->setDisplayConfigurable('view', TRUE);

    // --- INTEGRIDAD ---

    $fields['dpa_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash del DPA'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 del contenido del DPA firmado.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado del DPA.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Borrador'),
        'pending_signature' => new TranslatableMarkup('Pendiente de firma'),
        'active' => new TranslatableMarkup('Activo'),
        'superseded' => new TranslatableMarkup('Reemplazado'),
        'expired' => new TranslatableMarkup('Expirado'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PDF ---

    $fields['pdf_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('PDF firmado'))
      ->setDescription(new TranslatableMarkup('Archivo PDF del DPA firmado con sello de tiempo.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS ESPECÍFICOS DPA ---

    $fields['subprocessors_accepted'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Subprocesadores aceptados'))
      ->setDescription(new TranslatableMarkup('JSON con la lista de subprocesadores aceptados por el tenant.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['data_categories'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Categorías de datos'))
      ->setDescription(new TranslatableMarkup('JSON con las categorías de datos personales tratados.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'))
      ->setDescription(new TranslatableMarkup('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Comprueba si este DPA está activo.
   */
  public function isActive(): bool {
    return $this->get('status')->value === 'active';
  }

  /**
   * Comprueba si este DPA ha sido firmado.
   */
  public function isSigned(): bool {
    return !empty($this->get('signed_at')->value);
  }

  /**
   * Comprueba si este DPA ha sido reemplazado por una versión posterior.
   */
  public function isSuperseded(): bool {
    return $this->get('status')->value === 'superseded';
  }

}
