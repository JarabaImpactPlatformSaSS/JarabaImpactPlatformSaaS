<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Perfil de Comerciante.
 *
 * Estructura: Entidad central de ComercioConecta que representa un
 *   comercio de proximidad registrado en el marketplace. Contiene
 *   datos operativos (CIF, dirección, Stripe Connect) y de presentación
 *   pública (logo, descripción, horarios, galería).
 *
 * Lógica: Un MerchantProfile pertenece a un usuario (uid) y a un
 *   tenant (tenant_id). El slug se autogenera en hook_entity_insert().
 *   El estado de verificación controla el acceso al marketplace público:
 *   solo los comercios 'approved' e 'is_active' se muestran.
 *   El average_rating y total_reviews están desnormalizados para
 *   rendimiento en queries de listado (se actualizan vía hook en Fase 6).
 *
 * @ContentEntityType(
 *   id = "merchant_profile",
 *   label = @Translation("Perfil de Comerciante"),
 *   label_collection = @Translation("Perfiles de Comerciante"),
 *   label_singular = @Translation("perfil de comerciante"),
 *   label_plural = @Translation("perfiles de comerciante"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\MerchantProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\MerchantProfileForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\MerchantProfileForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\MerchantProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\MerchantProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "merchant_profile",
 *   admin_permission = "manage comercio merchants",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "business_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-merchant/{merchant_profile}",
 *     "add-form" = "/admin/content/comercio-merchant/add",
 *     "edit-form" = "/admin/content/comercio-merchant/{merchant_profile}/edit",
 *     "delete-form" = "/admin/content/comercio-merchant/{merchant_profile}/delete",
 *     "collection" = "/admin/content/comercio-merchants",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.merchant_profile.settings",
 * )
 */
class MerchantProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Datos del negocio ---
    $fields['business_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre Comercial'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug URL'))
      ->setDescription(t('Identificador URL-friendly único por tenant.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE);

    $fields['business_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Negocio'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'retail' => t('Comercio minorista'),
        'food' => t('Alimentación'),
        'services' => t('Servicios'),
        'crafts' => t('Artesanía'),
        'other' => t('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    // --- Contacto ---
    $fields['tax_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CIF/NIF'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email de Contacto'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['website'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Sitio Web'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE);

    // --- Dirección ---
    $fields['address_street'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ciudad'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Postal'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_province'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provincia'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 23])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('País'))
      ->setRequired(TRUE)
      ->setDefaultValue('ES')
      ->setSetting('max_length', 2)
      ->setDisplayOptions('form', ['weight' => 24])
      ->setDisplayConfigurable('form', TRUE);

    // --- Geolocalización ---
    $fields['latitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitud'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitud'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE);

    // --- Configuración ---
    $fields['opening_hours'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Horarios de Apertura'))
      ->setDescription(t('JSON con horarios por día.'))
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE);

    $fields['accepts_click_collect'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Click & Collect'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 41])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['delivery_radius_km'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Radio de Reparto (km)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 1)
      ->setDisplayOptions('form', ['weight' => 42])
      ->setDisplayConfigurable('form', TRUE);

    $fields['commission_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Comisión (%)'))
      ->setDescription(t('Override de la comisión del tenant.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 43])
      ->setDisplayConfigurable('form', TRUE);

    // --- Stripe Connect ---
    $fields['stripe_account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Account ID'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE);

    $fields['stripe_onboarding_complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Stripe Onboarding Completado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 51])
      ->setDisplayConfigurable('form', TRUE);

    // --- Estado ---
    $fields['verification_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Verificación'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'documents_submitted' => t('Documentos enviados'),
        'under_review' => t('En revisión'),
        'approved' => t('Aprobado'),
        'rejected' => t('Rechazado'),
        'suspended' => t('Suspendido'),
      ])
      ->setDisplayOptions('form', ['weight' => 60])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 61])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Media ---
    $fields['logo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setSetting('file_directory', 'comercio/merchants/logos')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 70])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cover_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen de Portada'))
      ->setSetting('file_directory', 'comercio/merchants/covers')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 71])
      ->setDisplayConfigurable('form', TRUE);

    $fields['gallery'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Galería'))
      ->setCardinality(6)
      ->setSetting('file_directory', 'comercio/merchants/gallery')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 72])
      ->setDisplayConfigurable('form', TRUE);

    // --- Estadísticas desnormalizadas ---
    $fields['average_rating'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Rating Medio'))
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 80])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_reviews'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Reseñas'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 81])
      ->setDisplayConfigurable('form', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
