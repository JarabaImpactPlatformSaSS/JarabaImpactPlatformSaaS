<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Servicio Ofertado.
 *
 * Estructura: Representa un servicio concreto que ofrece un profesional.
 *   Cada profesional puede ofrecer múltiples servicios con distintas
 *   tarifas, duraciones y modalidades.
 *
 * Lógica: Un ServiceOffering pertenece a un ProviderProfile (provider_id).
 *   Tiene precio, duración, modalidad (presencial/online/híbrido) y
 *   puede pertenecer a un paquete de servicios. El estado 'published'
 *   controla la visibilidad en el marketplace.
 *
 * @ContentEntityType(
 *   id = "service_offering",
 *   label = @Translation("Servicio Ofertado"),
 *   label_collection = @Translation("Servicios Ofertados"),
 *   label_singular = @Translation("servicio ofertado"),
 *   label_plural = @Translation("servicios ofertados"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\ServiceOfferingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\ServiceOfferingForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\ServiceOfferingForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\ServiceOfferingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\ServiceOfferingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "service_offering",
 *   admin_permission = "manage servicios offerings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-offering/{service_offering}",
 *     "add-form" = "/admin/content/servicios-offering/add",
 *     "edit-form" = "/admin/content/servicios-offering/{service_offering}/edit",
 *     "delete-form" = "/admin/content/servicios-offering/{service_offering}/delete",
 *     "collection" = "/admin/content/servicios-offerings",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.service_offering.settings",
 * )
 */
class ServiceOffering extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Datos del servicio ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Servicio'))
      ->setDescription(t('Título público del servicio ofertado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profesional'))
      ->setDescription(t('Profesional que ofrece este servicio.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'provider_profile')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción detallada del servicio.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categoría'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['servicios_category' => 'servicios_category']])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Precio y duración ---
    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio (€)'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Precio'))
      ->setRequired(TRUE)
      ->setDefaultValue('fixed')
      ->setSetting('allowed_values', [
        'fixed' => t('Precio fijo'),
        'hourly' => t('Por hora'),
        'from' => t('Desde'),
        'free' => t('Gratuito (primera consulta)'),
        'quote' => t('Bajo presupuesto'),
      ])
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duración (minutos)'))
      ->setRequired(TRUE)
      ->setDefaultValue(60)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Modalidad ---
    $fields['modality'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setRequired(TRUE)
      ->setDefaultValue('in_person')
      ->setSetting('allowed_values', [
        'in_person' => t('Presencial'),
        'online' => t('Online (Videollamada)'),
        'hybrid' => t('Híbrido'),
        'home_visit' => t('A domicilio'),
        'phone' => t('Telefónica'),
      ])
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_participants'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Máximo Participantes'))
      ->setDescription(t('1 para individual, >1 para grupales.'))
      ->setDefaultValue(1)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE);

    // --- Configuración de reserva ---
    $fields['requires_prepayment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Requiere Pago Anticipado'))
      ->setDescription(t('Sobreescribe la config del profesional para este servicio.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE);

    $fields['advance_booking_min'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Antelación Mínima (horas)'))
      ->setDescription(t('Horas mínimas para reservar este servicio.'))
      ->setDefaultValue(2)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE);

    // --- Estado ---
    $fields['is_published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_featured'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Destacado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 41])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sort_weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Orden de aparición en el perfil del profesional.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 42])
      ->setDisplayConfigurable('form', TRUE);

    // --- Media ---
    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen del Servicio'))
      ->setSetting('file_directory', 'servicios/offerings')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
