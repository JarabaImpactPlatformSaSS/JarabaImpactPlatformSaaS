<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad PackServicioEi.
 *
 * Representa un pack de servicio digital ofrecido a participantes
 * del programa Andalucía +ei (5 packs × 3 modalidades).
 *
 * @ContentEntityType(
 *   id = "pack_servicio_ei",
 *   label = @Translation("Pack de Servicio"),
 *   label_collection = @Translation("Packs de Servicio"),
 *   label_singular = @Translation("pack de servicio"),
 *   label_plural = @Translation("packs de servicio"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pack de servicio",
 *     plural = "@count packs de servicio",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\PackServicioEiListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\PackServicioEiAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\PackServicioEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\PackServicioEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\PackServicioEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pack_servicio_ei",
 *   admin_permission = "administer andalucia ei",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo_personalizado",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/pack-servicios-ei/{pack_servicio_ei}",
 *     "add-form" = "/admin/content/pack-servicios-ei/add",
 *     "edit-form" = "/admin/content/pack-servicios-ei/{pack_servicio_ei}/edit",
 *     "delete-form" = "/admin/content/pack-servicios-ei/{pack_servicio_ei}/delete",
 *     "collection" = "/admin/content/pack-servicios-ei",
 *   },
 *   field_ui_base_route = "entity.pack_servicio_ei.settings",
 * )
 */
class PackServicioEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * Pack types.
   */
  public const PACK_TIPOS = [
    'contenido_digital' => 'Pack 1 — Contenido Digital',
    'asistente_virtual' => 'Pack 2 — Asistente Virtual',
    'presencia_online' => 'Pack 3 — Presencia Online',
    'tienda_digital' => 'Pack 4 — Tienda Digital',
    'community_manager' => 'Pack 5 — Community Manager',
  ];

  /**
   * Modalidades.
   */
  public const MODALIDADES = [
    'basico' => 'Básico',
    'estandar' => 'Estándar',
    'premium' => 'Premium',
  ];

  /**
   * Sectores de cliente.
   */
  public const SECTORES_CLIENTE = [
    'hosteleria' => 'Hostelería',
    'comercio' => 'Comercio',
    'profesional' => 'Profesional',
    'agro' => 'Agro',
    'salud' => 'Salud',
    'educacion' => 'Educación',
    'turismo' => 'Turismo',
    'servicios' => 'Servicios',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Owner (ENTITY-001).
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The user who created this pack.'))
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    // Participante referencia (ENTITY-FK-001: misma entidad del módulo).
    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante al que se asigna este pack de servicio.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Pack tipo.
    $fields['pack_tipo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de pack'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::PACK_TIPOS)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Modalidad.
    $fields['modalidad'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::MODALIDADES)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Título personalizado (entity label).
    $fields['titulo_personalizado'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título del servicio'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Descripción.
    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio mensual.
    $fields['precio_mensual'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio mensual (€)'))
      ->setRequired(TRUE)
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Precio setup.
    $fields['precio_setup'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste de alta (€)'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Entregables mensuales (JSON).
    $fields['entregables_mensuales'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Entregables mensuales'))
      ->setDescription(t('JSON array con los entregables mensuales.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Sector cliente.
    $fields['sector_cliente'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sector del cliente'))
      ->setSetting('allowed_values', self::SECTORES_CLIENTE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Publicado.
    $fields['publicado'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicado en catálogo'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Stripe Product ID.
    $fields['stripe_product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Product ID'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Stripe Price ID.
    $fields['stripe_price_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Price ID'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // URL catálogo.
    $fields['url_catalogo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL catálogo'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant (ENTITY-FK-001).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece este pack.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets the pack type.
   */
  public function getPackTipo(): string {
    return (string) ($this->get('pack_tipo')->value ?? '');
  }

  /**
   * Gets the modalidad.
   */
  public function getModalidad(): string {
    return (string) ($this->get('modalidad')->value ?? '');
  }

  /**
   * Gets the monthly price.
   */
  public function getPrecioMensual(): ?string {
    return $this->get('precio_mensual')->value;
  }

  /**
   * Gets the participant ID.
   */
  public function getParticipanteId(): ?int {
    $val = $this->get('participante_id')->target_id;
    return $val !== null ? (int) $val : null;
  }

  /**
   * Whether the pack is published in the catalog.
   */
  public function isPublicado(): bool {
    return (bool) $this->get('publicado')->value;
  }

}
