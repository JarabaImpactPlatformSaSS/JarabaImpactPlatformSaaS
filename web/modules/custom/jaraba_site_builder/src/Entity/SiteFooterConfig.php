<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SiteFooterConfig para configuración del footer por tenant.
 *
 * @ContentEntityType(
 *   id = "site_footer_config",
 *   label = @Translation("Configuración del Footer"),
 *   label_collection = @Translation("Configuraciones de Footer"),
 *   label_singular = @Translation("configuración del footer"),
 *   label_plural = @Translation("configuraciones de footer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteFooterConfigListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteFooterConfigForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteFooterConfigForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteFooterConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteFooterConfigAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site_footer_config",
 *   fieldable = TRUE,
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/site-footer-config",
 *     "add-form" = "/admin/structure/site-footer-config/add",
 *     "canonical" = "/admin/structure/site-footer-config/{site_footer_config}",
 *     "edit-form" = "/admin/structure/site-footer-config/{site_footer_config}/edit",
 *     "delete-form" = "/admin/structure/site-footer-config/{site_footer_config}/delete",
 *   },
 *   field_ui_base_route = "entity.site_footer_config.collection",
 * )
 */
class SiteFooterConfig extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece esta configuración de footer.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    // --- Tipo y layout ---

    $fields['footer_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Footer'))
      ->setDescription(t('Variante visual del footer.'))
      ->setSettings([
        'allowed_values' => [
          'simple' => 'Simple',
          'columns' => 'Columnas',
          'mega' => 'Mega Footer',
          'minimal' => 'Minimalista',
          'cta' => 'Con CTA',
        ],
      ])
      ->setDefaultValue('columns')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Logo ---

    $fields['logo_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Logo del Footer'))
      ->setDescription(t('Logo alternativo para el footer.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default:file')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 1,
        'settings' => [
          'file_extensions' => 'png jpg jpeg svg webp',
          'file_directory' => 'site-footer-logos',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_logo'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar Logo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Texto descriptivo que aparece en el footer.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Columnas ---

    $fields['columns_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración de columnas'))
      ->setDescription(t('JSON con configuración de columnas del footer.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Social ---

    $fields['show_social'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar redes sociales'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['social_position'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Posición redes sociales'))
      ->setSettings([
        'allowed_values' => [
          'top' => 'Arriba',
          'bottom' => 'Abajo',
          'column' => 'En columna',
        ],
      ])
      ->setDefaultValue('bottom')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Newsletter ---

    $fields['show_newsletter'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar newsletter'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['newsletter_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título newsletter'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['newsletter_placeholder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Placeholder newsletter'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 32,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['newsletter_cta'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto botón newsletter'))
      ->setSettings(['max_length' => 50])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 33,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- CTA del Footer ---

    $fields['cta_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título CTA'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_subtitle'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Subtítulo CTA'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 41,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_button_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto botón CTA'))
      ->setSettings(['max_length' => 100])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 42,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cta_button_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL botón CTA'))
      ->setSettings(['max_length' => 500])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 43,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Copyright ---

    $fields['copyright_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto Copyright'))
      ->setDescription(t('Usa {year} para insertar el año actual automáticamente.'))
      ->setSettings(['max_length' => 500])
      ->setDefaultValue('© {year} Todos los derechos reservados.')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['show_legal_links'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar enlaces legales'))
      ->setDescription(t('Mostrar enlaces a Privacidad, Términos, Cookies.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 51,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // --- Colores ---

    $fields['bg_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color de fondo'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#1E293B')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 60,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['text_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color de texto'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#94A3B8')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 61,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['accent_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color de acento'))
      ->setDescription(t('Color para enlaces y elementos destacados.'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#3B82F6')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 62,
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
   * Obtiene el tipo de footer.
   */
  public function getFooterType(): string {
    return $this->get('footer_type')->value ?? 'columns';
  }

  /**
   * Obtiene el texto de copyright con año dinámico.
   */
  public function getCopyrightText(): string {
    $text = $this->get('copyright_text')->value ?? '© {year} Todos los derechos reservados.';
    return str_replace('{year}', date('Y'), $text);
  }

  /**
   * Obtiene la configuración de columnas como array.
   */
  public function getColumnsConfig(): array {
    $json = $this->get('columns_config')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Indica si el newsletter está habilitado.
   */
  public function showNewsletter(): bool {
    return (bool) $this->get('show_newsletter')->value;
  }

  /**
   * Indica si las redes sociales están habilitadas.
   */
  public function showSocial(): bool {
    return (bool) $this->get('show_social')->value;
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
  }

}
