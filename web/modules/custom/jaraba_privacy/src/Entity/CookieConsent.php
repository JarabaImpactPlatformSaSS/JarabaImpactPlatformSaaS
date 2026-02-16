<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD COOKIE CONSENT — Registro de consentimiento de cookies.
 *
 * ESTRUCTURA:
 * Content Entity que almacena cada registro de consentimiento de cookies.
 * Cada interacción del usuario con el banner genera un registro inmutable
 * que sirve como prueba de consentimiento según LSSI-CE y Directiva ePrivacy.
 *
 * LÓGICA DE NEGOCIO:
 * - Los registros son de solo lectura una vez creados (audit trail).
 * - El consentimiento es granular por categoría (analytics, marketing, etc.).
 * - Los usuarios anónimos se identifican por session_id.
 * - El consentimiento expira según cookie_expiry_days en settings.
 *
 * RELACIONES:
 * - user_id → User (referencia al usuario, NULL si anónimo)
 * - tenant_id → Group (referencia al tenant del sitio)
 *
 * Spec: Doc 183 §4.2. Plan: FASE 1, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "cookie_consent",
 *   label = @Translation("Cookie Consent"),
 *   label_collection = @Translation("Cookie Consents"),
 *   label_singular = @Translation("cookie consent"),
 *   label_plural = @Translation("cookie consents"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_privacy\ListBuilder\CookieConsentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_privacy\Form\CookieConsentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_privacy\Access\CookieConsentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cookie_consent",
 *   admin_permission = "administer privacy",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/cookie-consent/{cookie_consent}",
 *     "collection" = "/admin/content/cookie-consents",
 *   },
 *   field_ui_base_route = "jaraba_privacy.dpa_agreement.settings",
 * )
 */
class CookieConsent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant del sitio donde se otorgó el consentimiento.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- USUARIO ---

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Usuario'))
      ->setDescription(new TranslatableMarkup('Usuario que otorgó el consentimiento (NULL si anónimo).'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID de sesión'))
      ->setDescription(new TranslatableMarkup('Identificador de sesión para usuarios anónimos.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    // --- CONSENTIMIENTO GRANULAR ---

    $fields['consent_analytics'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Cookies analíticas'))
      ->setDescription(new TranslatableMarkup('Consentimiento para cookies de analítica (Google Analytics, etc.).'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_marketing'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Cookies de marketing'))
      ->setDescription(new TranslatableMarkup('Consentimiento para cookies de publicidad y remarketing.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_functional'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Cookies funcionales'))
      ->setDescription(new TranslatableMarkup('Consentimiento para cookies funcionales (preferencias, chat, etc.).'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_thirdparty'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Cookies de terceros'))
      ->setDescription(new TranslatableMarkup('Consentimiento para cookies de servicios de terceros.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // --- METADATOS ---

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Dirección IP'))
      ->setDescription(new TranslatableMarkup('IP desde la que se otorgó el consentimiento.'))
      ->setSetting('max_length', 45)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consented_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de consentimiento'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC del momento del consentimiento.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    return $fields;
  }

  /**
   * Comprueba si se aceptaron cookies analíticas.
   */
  public function hasAnalyticsConsent(): bool {
    return (bool) $this->get('consent_analytics')->value;
  }

  /**
   * Comprueba si se aceptaron cookies de marketing.
   */
  public function hasMarketingConsent(): bool {
    return (bool) $this->get('consent_marketing')->value;
  }

}
