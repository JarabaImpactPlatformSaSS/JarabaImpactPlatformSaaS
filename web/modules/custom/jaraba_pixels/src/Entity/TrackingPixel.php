<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Pixel de Seguimiento.
 *
 * Almacena la configuración de cada pixel de marketing asociado
 * a un tenant: Google Analytics, Meta Pixel, Google Ads,
 * LinkedIn Insight, TikTok Pixel o custom.
 *
 * @ContentEntityType(
 *   id = "tracking_pixel",
 *   label = @Translation("Pixel de Seguimiento"),
 *   label_collection = @Translation("Pixels de Seguimiento"),
 *   label_singular = @Translation("pixel de seguimiento"),
 *   label_plural = @Translation("pixels de seguimiento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_pixels\ListBuilder\TrackingPixelListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_pixels\Form\TrackingPixelForm",
 *       "add" = "Drupal\jaraba_pixels\Form\TrackingPixelForm",
 *       "edit" = "Drupal\jaraba_pixels\Form\TrackingPixelForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_pixels\Access\TrackingPixelAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tracking_pixel",
 *   fieldable = TRUE,
 *   admin_permission = "administer pixels",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tracking-pixels/{tracking_pixel}",
 *     "add-form" = "/admin/content/tracking-pixels/add",
 *     "edit-form" = "/admin/content/tracking-pixels/{tracking_pixel}/edit",
 *     "delete-form" = "/admin/content/tracking-pixels/{tracking_pixel}/delete",
 *     "collection" = "/admin/content/tracking-pixels",
 *   },
 *   field_ui_base_route = "entity.tracking_pixel.settings",
 * )
 */
class TrackingPixel extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Constantes de plataformas soportadas.
   */
  public const PLATFORM_GOOGLE_ANALYTICS = 'google_analytics';
  public const PLATFORM_META_PIXEL = 'meta_pixel';
  public const PLATFORM_GOOGLE_ADS = 'google_ads';
  public const PLATFORM_LINKEDIN_INSIGHT = 'linkedin_insight';
  public const PLATFORM_TIKTOK_PIXEL = 'tiktok_pixel';
  public const PLATFORM_CUSTOM = 'custom';

  /**
   * Obtiene el nombre del pixel.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Obtiene la plataforma del pixel.
   */
  public function getPlatform(): string {
    return $this->get('platform')->value ?? '';
  }

  /**
   * Obtiene el ID del pixel en la plataforma.
   */
  public function getPixelId(): string {
    return $this->get('pixel_id')->value ?? '';
  }

  /**
   * Comprueba si el pixel está activo.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * Obtiene la configuración del pixel como array.
   */
  public function getPixelConfig(): array {
    $value = $this->get('pixel_config')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene los eventos de conversión como array.
   */
  public function getConversionEvents(): array {
    $value = $this->get('conversion_events')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
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
      ->setDescription(t('Tenant (grupo) al que pertenece este pixel.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo del pixel de seguimiento.'))
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

    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plataforma'))
      ->setDescription(t('Plataforma de marketing del pixel.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::PLATFORM_GOOGLE_ANALYTICS => t('Google Analytics'),
        self::PLATFORM_META_PIXEL => t('Meta Pixel'),
        self::PLATFORM_GOOGLE_ADS => t('Google Ads'),
        self::PLATFORM_LINKEDIN_INSIGHT => t('LinkedIn Insight'),
        self::PLATFORM_TIKTOK_PIXEL => t('TikTok Pixel'),
        self::PLATFORM_CUSTOM => t('Custom'),
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

    $fields['pixel_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pixel ID'))
      ->setDescription(t('Identificador del pixel en la plataforma de marketing.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pixel_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración del Pixel'))
      ->setDescription(t('Configuración en formato JSON específica de la plataforma.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el pixel está activo y enviando eventos.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversion_events'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Eventos de Conversión'))
      ->setDescription(t('JSON con la definición de eventos de conversión mapeados.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 6,
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
