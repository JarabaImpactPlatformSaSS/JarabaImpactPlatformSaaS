<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD SERVICE AGREEMENT — Acuerdo de Servicio (ToS/SLA/AUP/DPA/NDA).
 *
 * ESTRUCTURA:
 * Content Entity que almacena los acuerdos de servicio de cada tenant.
 * Soporta múltiples tipos (ToS, SLA, AUP, DPA, NDA) con versionado,
 * hash de integridad y control de aceptación.
 *
 * LÓGICA DE NEGOCIO:
 * - Cada tenant tiene múltiples acuerdos versionados, solo uno activo por tipo.
 * - Al publicar una nueva versión, el sistema fuerza re-aceptación si está configurado.
 * - El contenido HTML se hashea (SHA-256) para detectar alteraciones.
 * - El contador de aceptaciones se incrementa automáticamente via TosManagerService.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 184 §2.1. Plan: FASE 5, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "service_agreement",
 *   label = @Translation("Service Agreement"),
 *   label_collection = @Translation("Service Agreements"),
 *   label_singular = @Translation("service agreement"),
 *   label_plural = @Translation("service agreements"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal\ListBuilder\ServiceAgreementListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal\Form\ServiceAgreementForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal\Access\ServiceAgreementAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "service_agreement",
 *   admin_permission = "administer legal",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/service-agreement/{service_agreement}",
 *     "add-form" = "/admin/content/service-agreement/add",
 *     "edit-form" = "/admin/content/service-agreement/{service_agreement}/edit",
 *     "delete-form" = "/admin/content/service-agreement/{service_agreement}/delete",
 *     "collection" = "/admin/content/service-agreements",
 *   },
 *   field_ui_base_route = "jaraba_legal.service_agreement.settings",
 * )
 */
class ServiceAgreement extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT (aislamiento multi-tenant) ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece este acuerdo.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- IDENTIFICACIÓN ---

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Título'))
      ->setDescription(new TranslatableMarkup('Título del acuerdo de servicio.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['agreement_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de acuerdo'))
      ->setDescription(new TranslatableMarkup('Tipo de acuerdo de servicio.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'tos' => new TranslatableMarkup('Terms of Service'),
        'sla' => new TranslatableMarkup('Service Level Agreement'),
        'aup' => new TranslatableMarkup('Acceptable Use Policy'),
        'dpa' => new TranslatableMarkup('Data Processing Agreement'),
        'nda' => new TranslatableMarkup('Non-Disclosure Agreement'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERSIONADO ---

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Versión'))
      ->setDescription(new TranslatableMarkup('Versión del acuerdo (ej: 1.0, 2.0).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CONTENIDO ---

    $fields['content_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Contenido HTML'))
      ->setDescription(new TranslatableMarkup('Texto completo del acuerdo en formato HTML.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash del contenido'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 del contenido para verificación de integridad.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- PUBLICACIÓN ---

    $fields['published_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de publicación'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de publicación del acuerdo.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activo'))
      ->setDescription(new TranslatableMarkup('Indica si este acuerdo es la versión activa.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ACEPTACIÓN ---

    $fields['requires_acceptance'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Requiere aceptación'))
      ->setDescription(new TranslatableMarkup('Indica si los tenants deben aceptar este acuerdo explícitamente.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['accepted_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Número de aceptaciones'))
      ->setDescription(new TranslatableMarkup('Contador de tenants que han aceptado este acuerdo.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // --- VIGENCIA ---

    $fields['effective_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de entrada en vigor'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC desde el que el acuerdo es efectivo.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
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
   * Comprueba si este acuerdo está activo.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * Comprueba si este acuerdo ha sido publicado.
   */
  public function isPublished(): bool {
    return !empty($this->get('published_at')->value);
  }

  /**
   * Comprueba si este acuerdo requiere aceptación por parte del tenant.
   */
  public function requiresAcceptance(): bool {
    return (bool) $this->get('requires_acceptance')->value;
  }

  /**
   * Obtiene el tipo de acuerdo legible.
   */
  public function getAgreementTypeLabel(): string {
    $types = [
      'tos' => 'Terms of Service',
      'sla' => 'Service Level Agreement',
      'aup' => 'Acceptable Use Policy',
      'dpa' => 'Data Processing Agreement',
      'nda' => 'Non-Disclosure Agreement',
    ];
    $type = $this->get('agreement_type')->value;
    return $types[$type] ?? $type;
  }

}
