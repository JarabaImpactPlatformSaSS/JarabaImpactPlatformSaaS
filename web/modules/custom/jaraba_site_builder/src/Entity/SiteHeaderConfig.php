<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SiteHeaderConfig para configuración del header por tenant.
 *
 * @ContentEntityType(
 *   id = "site_header_config",
 *   label = @Translation("Configuración del Header"),
 *   label_collection = @Translation("Configuraciones de Header"),
 *   label_singular = @Translation("configuración del header"),
 *   label_plural = @Translation("configuraciones de header"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteHeaderConfigListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteHeaderConfigForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteHeaderConfigForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteHeaderConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteHeaderConfigAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site_header_config",
 *   fieldable = TRUE,
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/site-header-config",
 *     "add-form" = "/admin/structure/site-header-config/add",
 *     "canonical" = "/admin/structure/site-header-config/{site_header_config}",
 *     "edit-form" = "/admin/structure/site-header-config/{site_header_config}/edit",
 *     "delete-form" = "/admin/structure/site-header-config/{site_header_config}/delete",
 *   },
 *   field_ui_base_route = "entity.site_header_config.collection",
 * )
 */
class SiteHeaderConfig extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece esta configuración de header.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    // --- Tipo y layout ---

    $fields['header_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Header'))
      ->setDescription(t('Variante visual del header.'))
      ->setSettings([
        'allowed_values' => [
          'standard' => 'Estándar',
          'centered' => 'Centrado',
          'minimal' => 'Minimalista',
          'mega' => 'Mega Menú',
          'transparent' => 'Transparente',
        ],
      ])
      ->setDefaultValue('standard')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Logo ---

    $fields['logo_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Logo'))
      ->setDescription(t('Logo principal del header.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default:file')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 1,
        'settings' => [
          'file_extensions' => 'png jpg jpeg svg webp',
          'file_directory' => 'site-header-logos',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['logo_alt'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alt del Logo'))
      ->setDescription(t('Texto alternativo para accesibilidad del logo.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['logo_width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ancho del Logo (px)'))
      ->setDescription(t('Ancho del logo en píxeles.'))
      ->setDefaultValue(150)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['logo_mobile_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Logo Móvil'))
      ->setDescription(t('Logo alternativo para dispositivos móviles.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default:file')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 4,
        'settings' => [
          'file_extensions' => 'png jpg jpeg svg webp',
          'file_directory' => 'site-header-logos',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Comportamiento sticky/scroll ---

    $fields['is_sticky'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Header Sticky'))
      ->setDescription(t('El header permanece fijo al hacer scroll.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['sticky_offset'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Offset Sticky (px)'))
      ->setDescription(t('Píxeles de scroll antes de activar el sticky.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['transparent_on_hero'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Transparente sobre hero'))
      ->setDescription(t('El header se vuelve transparente sobre secciones hero.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['hide_on_scroll_down'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Ocultar al bajar'))
      ->setDescription(t('Ocultar el header al hacer scroll hacia abajo, mostrar al subir.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Menú principal ---

    $fields['main_menu_position'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Posición del menú'))
      ->setDescription(t('Posición del menú principal en el header.'))
      ->setSettings([
        'allowed_values' => [
          'left' => 'Izquierda',
          'center' => 'Centro',
          'right' => 'Derecha',
        ],
      ])
      ->setDefaultValue('right')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['main_menu_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Menú Principal'))
      ->setDescription(t('Menú que se muestra en el header.'))
      ->setSetting('target_type', 'site_menu')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- CTA Button ---

    $fields['show_cta'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar CTA'))
      ->setDescription(t('Mostrar botón de llamada a la acción en el header.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto CTA'))
      ->setDescription(t('Texto del botón CTA.'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL CTA'))
      ->setDescription(t('URL de destino del botón CTA.'))
      ->setSettings(['max_length' => 500])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 32,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_style'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estilo CTA'))
      ->setDescription(t('Estilo visual del botón CTA.'))
      ->setSettings([
        'allowed_values' => [
          'primary' => 'Primario',
          'secondary' => 'Secundario',
          'outline' => 'Contorno',
          'ghost' => 'Fantasma',
        ],
      ])
      ->setDefaultValue('primary')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 33,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_icon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Icono CTA'))
      ->setDescription(t('Nombre del icono para el botón CTA.'))
      ->setSettings(['max_length' => 50])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 34,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Elementos opcionales ---

    $fields['show_search'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar buscador'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_language_switcher'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar selector de idioma'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 41,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_user_menu'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar menú de usuario'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 42,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_phone'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar teléfono'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 43,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_email'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar email'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 44,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Top Bar ---

    $fields['show_topbar'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar barra superior'))
      ->setDescription(t('Banner superior con texto personalizable.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['topbar_content'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contenido barra superior'))
      ->setDescription(t('HTML/texto del banner superior.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 51,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['topbar_bg_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color fondo barra superior'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#1E3A5F')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 52,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['topbar_text_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color texto barra superior'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#FFFFFF')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 53,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Colores y dimensiones ---

    $fields['bg_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color de fondo'))
      ->setDescription(t('Color de fondo del header.'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#FFFFFF')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 60,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['text_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color de texto'))
      ->setDescription(t('Color del texto del header.'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#1E293B')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 61,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['height_desktop'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Altura desktop (px)'))
      ->setDefaultValue(80)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 62,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['height_mobile'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Altura móvil (px)'))
      ->setDefaultValue(64)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 63,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['shadow'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sombra'))
      ->setDescription(t('Intensidad de la sombra del header.'))
      ->setSettings([
        'allowed_values' => [
          'none' => 'Ninguna',
          'sm' => 'Suave',
          'md' => 'Media',
          'lg' => 'Fuerte',
        ],
      ])
      ->setDefaultValue('sm')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 64,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Sistema ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creación.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'))
      ->setDescription(t('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Obtiene el tipo de header.
   */
  public function getHeaderType(): string {
    return $this->get('header_type')->value ?? 'standard';
  }

  /**
   * Indica si el header es sticky.
   */
  public function isSticky(): bool {
    return (bool) $this->get('is_sticky')->value;
  }

  /**
   * Indica si el topbar está habilitado.
   */
  public function showTopbar(): bool {
    return (bool) $this->get('show_topbar')->value;
  }

  /**
   * Indica si el CTA está habilitado.
   */
  public function showCta(): bool {
    return (bool) $this->get('show_cta')->value;
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
  }

}
