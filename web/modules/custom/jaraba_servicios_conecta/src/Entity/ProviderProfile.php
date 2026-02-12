<?php

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Perfil de Profesional.
 *
 * Estructura: Entidad central de ServiciosConecta que representa un
 *   profesional registrado en el marketplace. Contiene datos de
 *   identificación (nombre, colegiado, NIF), especialidades,
 *   credenciales y presentación pública.
 *
 * Lógica: Un ProviderProfile pertenece a un usuario (uid) y a un
 *   tenant (tenant_id). El slug se autogenera en hook_entity_insert().
 *   El estado de verificación controla el acceso al marketplace público:
 *   solo los profesionales 'approved' e 'is_active' se muestran.
 *   El average_rating y total_reviews están desnormalizados para
 *   rendimiento en queries de listado.
 *
 * @ContentEntityType(
 *   id = "provider_profile",
 *   label = @Translation("Perfil Profesional"),
 *   label_collection = @Translation("Perfiles Profesionales"),
 *   label_singular = @Translation("perfil profesional"),
 *   label_plural = @Translation("perfiles profesionales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\ProviderProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\ProviderProfileForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\ProviderProfileForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\ProviderProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\ProviderProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "provider_profile",
 *   admin_permission = "manage servicios providers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-provider/{provider_profile}",
 *     "add-form" = "/admin/content/servicios-provider/add",
 *     "edit-form" = "/admin/content/servicios-provider/{provider_profile}/edit",
 *     "delete-form" = "/admin/content/servicios-provider/{provider_profile}/delete",
 *     "collection" = "/admin/content/servicios-providers",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.provider_profile.settings",
 * )
 */
class ProviderProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Identidad del profesional ---
    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre Profesional'))
      ->setDescription(t('Nombre público del profesional o despacho.'))
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

    $fields['professional_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título Profesional'))
      ->setDescription(t('Ej: Abogada, Fisioterapeuta, Arquitecto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['service_category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categoría de Servicio'))
      ->setDescription(t('Sector profesional principal.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['servicios_category' => 'servicios_category']])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['specialties'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Especialidades'))
      ->setDescription(t('Subespecialidades dentro de la categoría.'))
      ->setCardinality(10)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['servicios_category' => 'servicios_category']])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Presentación pública del profesional.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    // --- Credenciales profesionales ---
    $fields['license_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de Colegiado'))
      ->setDescription(t('Número de colegiación o licencia profesional.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CIF/NIF'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['insurance_policy'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Póliza de Responsabilidad Civil'))
      ->setDescription(t('Número de póliza de RC profesional.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['years_experience'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Años de Experiencia'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contacto ---
    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email de Contacto'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE);

    $fields['website'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Sitio Web'))
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE);

    // --- Dirección ---
    $fields['address_street'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ciudad'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Postal'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_province'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provincia'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 33])
      ->setDisplayConfigurable('form', TRUE);

    $fields['address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('País'))
      ->setRequired(TRUE)
      ->setDefaultValue('ES')
      ->setSetting('max_length', 2)
      ->setDisplayOptions('form', ['weight' => 34])
      ->setDisplayConfigurable('form', TRUE);

    // --- Geolocalización ---
    $fields['latitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitud'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitud'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', ['weight' => 41])
      ->setDisplayConfigurable('form', TRUE);

    // --- Configuración del servicio ---
    $fields['service_radius_km'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Radio de Servicio (km)'))
      ->setDescription(t('Radio para servicios a domicilio.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 1)
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE);

    $fields['default_session_duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duración Sesión por Defecto (min)'))
      ->setDefaultValue(60)
      ->setDisplayOptions('form', ['weight' => 51])
      ->setDisplayConfigurable('form', TRUE);

    $fields['buffer_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tiempo Colchón entre Citas (min)'))
      ->setDescription(t('Margen entre citas consecutivas.'))
      ->setDefaultValue(15)
      ->setDisplayOptions('form', ['weight' => 52])
      ->setDisplayConfigurable('form', TRUE);

    $fields['advance_booking_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Antelación Máxima de Reserva (días)'))
      ->setDefaultValue(30)
      ->setDisplayOptions('form', ['weight' => 53])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cancellation_hours'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Horas para Cancelación Gratuita'))
      ->setDefaultValue(24)
      ->setDisplayOptions('form', ['weight' => 54])
      ->setDisplayConfigurable('form', TRUE);

    $fields['requires_prepayment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Requiere Pago Anticipado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 55])
      ->setDisplayConfigurable('form', TRUE);

    $fields['accepts_online'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Acepta Consultas Online'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 56])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Stripe Connect ---
    $fields['stripe_account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Account ID'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 60])
      ->setDisplayConfigurable('form', TRUE);

    $fields['stripe_onboarding_complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Stripe Onboarding Completado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 61])
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
      ->setDisplayOptions('form', ['weight' => 70])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 71])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Media ---
    $fields['photo'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Foto de Perfil'))
      ->setSetting('file_directory', 'servicios/providers/photos')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 80])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cover_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen de Portada'))
      ->setSetting('file_directory', 'servicios/providers/covers')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 81])
      ->setDisplayConfigurable('form', TRUE);

    // --- Estadísticas desnormalizadas ---
    $fields['average_rating'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Rating Medio'))
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 90])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_reviews'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Reseñas'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 91])
      ->setDisplayConfigurable('form', TRUE);

    $fields['total_bookings'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Reservas'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 92])
      ->setDisplayConfigurable('form', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
