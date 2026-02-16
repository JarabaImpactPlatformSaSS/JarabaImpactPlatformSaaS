<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD PRIVACY POLICY — Política de privacidad parametrizable por vertical.
 *
 * ESTRUCTURA:
 * Content Entity que almacena las políticas de privacidad versionadas.
 * Cada vertical del ecosistema puede tener su propia política personalizada,
 * generada automáticamente por el PrivacyPolicyGeneratorService.
 *
 * LÓGICA DE NEGOCIO:
 * - Las políticas se versionan: cada nueva versión invalida la anterior.
 * - El contenido HTML se sanitiza con Xss::filterAdmin() antes de renderizar.
 * - El hash SHA-256 del contenido permite verificar integridad.
 * - Solo una política puede estar activa por vertical y tenant.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 183 §3.2. Plan: FASE 1, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "privacy_policy",
 *   label = @Translation("Privacy Policy"),
 *   label_collection = @Translation("Privacy Policies"),
 *   label_singular = @Translation("privacy policy"),
 *   label_plural = @Translation("privacy policies"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_privacy\ListBuilder\PrivacyPolicyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_privacy\Form\PrivacyPolicyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_privacy\Access\PrivacyPolicyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "privacy_policy",
 *   admin_permission = "administer privacy",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "version",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/privacy-policy/{privacy_policy}",
 *     "add-form" = "/admin/content/privacy-policy/add",
 *     "edit-form" = "/admin/content/privacy-policy/{privacy_policy}/edit",
 *     "delete-form" = "/admin/content/privacy-policy/{privacy_policy}/delete",
 *     "collection" = "/admin/content/privacy-policies",
 *   },
 *   field_ui_base_route = "jaraba_privacy.privacy_policy.settings",
 * )
 */
class PrivacyPolicy extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece esta política.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERTICAL ---

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Vertical'))
      ->setDescription(new TranslatableMarkup('Vertical del ecosistema para la que aplica esta política.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'empleo' => new TranslatableMarkup('Empleo'),
        'emprendimiento' => new TranslatableMarkup('Emprendimiento'),
        'comercio' => new TranslatableMarkup('Comercio'),
        'formacion' => new TranslatableMarkup('Formación'),
        'instituciones' => new TranslatableMarkup('Instituciones'),
        'general' => new TranslatableMarkup('General'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERSIONADO ---

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Versión'))
      ->setDescription(new TranslatableMarkup('Versión de la política (ej: 1.0, 2.0).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CONTENIDO ---

    $fields['content_html'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Contenido HTML'))
      ->setDescription(new TranslatableMarkup('Contenido completo de la política de privacidad en HTML.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2, 'type' => 'text_textarea'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Hash del contenido'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 del contenido HTML para verificar integridad.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- PUBLICACIÓN ---

    $fields['published_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de publicación'))
      ->setDescription(new TranslatableMarkup('Timestamp de cuándo se publicó esta versión.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activa'))
      ->setDescription(new TranslatableMarkup('Indica si esta es la versión activa de la política.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PERSONALIZACIÓN ---

    $fields['custom_sections'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Secciones personalizadas'))
      ->setDescription(new TranslatableMarkup('JSON con secciones adicionales personalizadas por el tenant.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['dpo_contact'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Contacto DPO'))
      ->setDescription(new TranslatableMarkup('Email o datos de contacto del DPO para esta política.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
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
   * Comprueba si esta política está activa.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

}
