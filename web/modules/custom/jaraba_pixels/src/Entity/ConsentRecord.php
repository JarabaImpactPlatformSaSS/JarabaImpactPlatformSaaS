<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Registro de Consentimiento.
 *
 * Almacena el consentimiento explícito de cada visitante para
 * tracking de cookies/pixels según categoría (necesarias, analítica,
 * marketing, personalización). Cumple con RGPD/ePrivacy.
 *
 * @ContentEntityType(
 *   id = "consent_record",
 *   label = @Translation("Registro de Consentimiento"),
 *   label_collection = @Translation("Registros de Consentimiento"),
 *   label_singular = @Translation("registro de consentimiento"),
 *   label_plural = @Translation("registros de consentimiento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_pixels\ListBuilder\ConsentRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_pixels\Form\ConsentRecordForm",
 *       "add" = "Drupal\jaraba_pixels\Form\ConsentRecordForm",
 *       "edit" = "Drupal\jaraba_pixels\Form\ConsentRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_pixels\Access\ConsentRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "consent_record",
 *   fieldable = TRUE,
 *   admin_permission = "administer pixels",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "visitor_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/consent-records/{consent_record}",
 *     "add-form" = "/admin/content/consent-records/add",
 *     "edit-form" = "/admin/content/consent-records/{consent_record}/edit",
 *     "delete-form" = "/admin/content/consent-records/{consent_record}/delete",
 *     "collection" = "/admin/content/consent-records",
 *   },
 *   field_ui_base_route = "entity.consent_record.settings",
 * )
 */
class ConsentRecord extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Constantes de tipos de consentimiento.
   */
  public const CONSENT_NECESSARY = 'necessary';
  public const CONSENT_ANALYTICS = 'analytics';
  public const CONSENT_MARKETING = 'marketing';
  public const CONSENT_PERSONALIZATION = 'personalization';

  /**
   * Constantes de estado de consentimiento.
   */
  public const STATUS_GRANTED = 'granted';
  public const STATUS_DENIED = 'denied';
  public const STATUS_WITHDRAWN = 'withdrawn';

  /**
   * Obtiene el visitor ID.
   */
  public function getVisitorId(): string {
    return $this->get('visitor_id')->value ?? '';
  }

  /**
   * Obtiene el tipo de consentimiento.
   */
  public function getConsentType(): string {
    return $this->get('consent_type')->value ?? '';
  }

  /**
   * Obtiene el estado del consentimiento.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? '';
  }

  /**
   * Comprueba si el consentimiento fue otorgado.
   */
  public function isGranted(): bool {
    return $this->getStatus() === self::STATUS_GRANTED;
  }

  /**
   * Comprueba si el consentimiento fue revocado.
   */
  public function isWithdrawn(): bool {
    return $this->getStatus() === self::STATUS_WITHDRAWN;
  }

  /**
   * Obtiene el ID del tenant asociado.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) al que pertenece este registro de consentimiento.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visitor_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Visitor ID'))
      ->setDescription(t('Identificador único del visitante que otorgó el consentimiento.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Consentimiento'))
      ->setDescription(t('Categoría de cookies/tracking a la que aplica el consentimiento.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::CONSENT_NECESSARY => t('Necesarias'),
        self::CONSENT_ANALYTICS => t('Analítica'),
        self::CONSENT_MARKETING => t('Marketing'),
        self::CONSENT_PERSONALIZATION => t('Personalización'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del consentimiento.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_DENIED)
      ->setSetting('allowed_values', [
        self::STATUS_GRANTED => t('Otorgado'),
        self::STATUS_DENIED => t('Denegado'),
        self::STATUS_WITHDRAWN => t('Retirado'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección IP'))
      ->setDescription(t('Dirección IP del visitante en el momento del consentimiento.'))
      ->setSetting('max_length', 45)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setDescription(t('User agent del navegador en el momento del consentimiento.'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Versión de Consentimiento'))
      ->setDescription(t('Versión de la política de consentimiento aceptada.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['revoked_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Revocación'))
      ->setDescription(t('Marca de tiempo en que se revocó el consentimiento.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
