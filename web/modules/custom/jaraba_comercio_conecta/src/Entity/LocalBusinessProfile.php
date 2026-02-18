<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Perfil de Negocio Local.
 *
 * Estructura: Entidad de ComercioConecta que representa la presencia
 *   SEO local de un comercio. Contiene los datos estructurados necesarios
 *   para generar Schema.org/LocalBusiness markup, gestionar Google Business
 *   Profile, y calcular la consistencia NAP (Name, Address, Phone) en
 *   directorios externos.
 *
 * Logica: Cada merchant_profile puede tener un LocalBusinessProfile asociado.
 *   Los datos de este perfil se usan para:
 *   - Generar JSON-LD Schema.org en las paginas del comercio
 *   - Sincronizar con Google Business Profile via API
 *   - Calcular el nap_consistency_score comparando con NapEntry registros
 *   - Optimizar el posicionamiento en busquedas locales "cerca de mi"
 *   El uid corresponde al comerciante propietario.
 *
 * @ContentEntityType(
 *   id = "comercio_local_business",
 *   label = @Translation("Perfil de Negocio Local"),
 *   label_collection = @Translation("Perfiles de Negocio Local"),
 *   label_singular = @Translation("perfil de negocio local"),
 *   label_plural = @Translation("perfiles de negocio local"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\LocalBusinessProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\LocalBusinessProfileForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\LocalBusinessProfileForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\LocalBusinessProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\LocalBusinessProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_local_business",
 *   admin_permission = "manage comercio local seo",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "business_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-local-business/{comercio_local_business}",
 *     "add-form" = "/admin/content/comercio-local-business/add",
 *     "edit-form" = "/admin/content/comercio-local-business/{comercio_local_business}/edit",
 *     "delete-form" = "/admin/content/comercio-local-business/{comercio_local_business}/delete",
 *     "collection" = "/admin/content/comercio-local-businesses",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_local_business.settings",
 * )
 */
class LocalBusinessProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este perfil para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setDescription(t('Perfil de comerciante asociado a este negocio local.'))
      ->setSetting('target_type', 'merchant_profile')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['business_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del negocio'))
      ->setDescription(t('Nombre oficial del negocio tal como aparece en directorios y Google Business.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description_seo'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripcion SEO'))
      ->setDescription(t('Meta descripcion para SEO local. Recomendado maximo 160 caracteres.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_street'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Calle'))
      ->setDescription(t('Direccion: calle y numero.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ciudad'))
      ->setDescription(t('Direccion: ciudad o municipio.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo postal'))
      ->setDescription(t('Direccion: codigo postal.'))
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_province'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provincia'))
      ->setDescription(t('Direccion: provincia o comunidad autonoma.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pais'))
      ->setDescription(t('Codigo ISO 3166-1 alpha-2 del pais (ej: ES).'))
      ->setSetting('max_length', 2)
      ->setDefaultValue('ES')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Telefono'))
      ->setDescription(t('Numero de telefono del negocio en formato internacional.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('Direccion de correo electronico del negocio.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['website_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del sitio web'))
      ->setDescription(t('URL completa del sitio web del negocio.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['latitude'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDescription(t('Coordenada de latitud para geolocalizacion y busquedas "cerca de mi".'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDescription(t('Coordenada de longitud para geolocalizacion y busquedas "cerca de mi".'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['opening_hours'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Horario de apertura'))
      ->setDescription(t('JSON con array de objetos {day, open, close} para cada dia de la semana.'))
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['google_place_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Google Place ID'))
      ->setDescription(t('Identificador unico de Google Places para este negocio.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['google_business_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Google Business'))
      ->setDescription(t('URL de la ficha de Google Business Profile del negocio.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schema_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo Schema.org'))
      ->setDescription(t('Tipo de Schema.org para el markup JSON-LD (ej: LocalBusiness, Restaurant, Store).'))
      ->setSetting('max_length', 64)
      ->setDefaultValue('LocalBusiness')
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nap_consistency_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion NAP'))
      ->setDescription(t('Puntuacion de consistencia NAP (Name, Address, Phone) de 0 a 100 en directorios externos.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
