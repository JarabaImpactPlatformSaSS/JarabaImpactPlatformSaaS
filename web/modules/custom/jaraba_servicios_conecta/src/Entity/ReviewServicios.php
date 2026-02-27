<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Resena de Servicio.
 *
 * Estructura: Entidad que representa una resena dejada por un cliente
 *   sobre un profesional tras completar una reserva. Incluye valoracion,
 *   comentario, estado de moderacion y respuesta del profesional.
 *
 * Logica: Una ReviewServicios vincula reviewer (reviewer_uid) -> provider
 *   (provider_id) -> booking (booking_id). Las resenas pasan por moderacion
 *   (pending -> approved / rejected). Solo las aprobadas se muestran
 *   publicamente. El profesional puede anadir una respuesta. Al aprobar
 *   una resena se recalcula average_rating y total_reviews en ProviderProfile.
 *
 * @ContentEntityType(
 *   id = "review_servicios",
 *   label = @Translation("Resena de Servicio"),
 *   label_collection = @Translation("Resenas de Servicios"),
 *   label_singular = @Translation("resena de servicio"),
 *   label_plural = @Translation("resenas de servicios"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\ReviewServiciosListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\ReviewServiciosForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\ReviewServiciosForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\ReviewServiciosForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\ReviewServiciosAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "review_servicios",
 *   admin_permission = "manage servicios reviews",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "reviewer_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-review/{review_servicios}",
 *     "add-form" = "/admin/content/servicios-review/add",
 *     "edit-form" = "/admin/content/servicios-review/{review_servicios}/edit",
 *     "delete-form" = "/admin/content/servicios-review/{review_servicios}/delete",
 *     "collection" = "/admin/content/servicios-reviews",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.review_servicios.settings",
 * )
 */
class ReviewServicios extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Referencias ---
    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profesional'))
      ->setDescription(t('Profesional evaluado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'provider_profile')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['offering_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Servicio'))
      ->setDescription(t('Servicio especifico evaluado (opcional).'))
      ->setSetting('target_type', 'service_offering')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['booking_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reserva'))
      ->setDescription(t('Reserva asociada a esta resena.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'booking')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reviewer_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Autor de la Resena'))
      ->setDescription(t('Usuario que escribio la resena.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contenido de la resena ---
    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoracion'))
      ->setDescription(t('Puntuacion de 1 a 5 estrellas.'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo breve de la resena.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comment'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comentario'))
      ->setDescription(t('Texto completo de la resena.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // TENANT-BRIDGE-001: tenant_id como entity_reference a group (REV-S1 fix).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant al que pertenece esta resena.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);

    // Compra verificada.
    $fields['verified_purchase'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Reserva verificada'))
      ->setDescription(t('Indica si el autor completo la reserva evaluada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado de moderacion (REV-A2: anadir flagged) ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'approved' => t('Aprobada'),
        'rejected' => t('Rechazada'),
        'flagged' => t('Marcada'),
      ])
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Respuesta del profesional ---
    $fields['provider_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Respuesta del Profesional'))
      ->setDescription(t('Respuesta del profesional a la resena.'))
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Respuesta'))
      ->setDescription(t('Fecha en que el profesional respondio.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campos del trait: helpful_count, photos, ai_summary, ai_summary_generated_at.
    $traitFields = static::reviewableBaseFieldDefinitions();
    $fields['helpful_count'] = $traitFields['helpful_count'];
    $fields['photos'] = $traitFields['photos'];
    $fields['ai_summary'] = $traitFields['ai_summary'];
    $fields['ai_summary_generated_at'] = $traitFields['ai_summary_generated_at'];

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
