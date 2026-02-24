<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad SiteConfig para configuración global del sitio por tenant.
 *
 * @ContentEntityType(
 *   id = "site_config",
 *   label = @Translation("Configuración del Sitio"),
 *   label_collection = @Translation("Configuraciones de Sitios"),
 *   label_singular = @Translation("configuración del sitio"),
 *   label_plural = @Translation("configuraciones de sitios"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteConfigForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteConfigForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteConfigForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteConfigAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "site_config",
 *   admin_permission = "administer site structure",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "site_name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site-builder/config/{site_config}",
 *     "add-form" = "/admin/structure/site-builder/config/add",
 *     "edit-form" = "/admin/structure/site-builder/config/{site_config}/edit",
 *     "collection" = "/admin/content/site-configs",
 *   },
 *   field_ui_base_route = "entity.site_config.settings",
 * )
 */
class SiteConfig extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant (Group) al que pertenece esta configuración.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant (organización) al que pertenece esta configuración.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->addConstraint('UniqueField')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
            ]);

        // --- Identidad del sitio ---

        $fields['site_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Sitio'))
            ->setDescription(t('Nombre principal del sitio web.'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['site_tagline'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Eslogan'))
            ->setDescription(t('Subtítulo o eslogan del sitio.'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['site_logo'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Logo'))
            ->setDescription(t('Logo principal del sitio.'))
            ->setSetting('target_type', 'file')
            ->setSetting('handler', 'default:file')
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 2,
                'settings' => [
                    'file_extensions' => 'png jpg jpeg svg webp',
                    'file_directory' => 'site-logos',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['site_favicon'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Favicon'))
            ->setDescription(t('Icono del navegador (favicon).'))
            ->setSetting('target_type', 'file')
            ->setSetting('handler', 'default:file')
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 3,
                'settings' => [
                    'file_extensions' => 'ico png svg',
                    'file_directory' => 'site-favicons',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Páginas especiales ---

        $fields['homepage_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página de Inicio'))
            ->setDescription(t('Página que se muestra como homepage.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['blog_index_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Índice del Blog'))
            ->setDescription(t('Página del listado de artículos.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['error_404_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página 404'))
            ->setDescription(t('Página personalizada para errores 404.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- SEO Global ---

        $fields['meta_title_suffix'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sufijo de Meta Title'))
            ->setDescription(t('Texto que se añade al final de los títulos (ej: " | Mi Empresa").'))
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['default_og_image'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Imagen OG por Defecto'))
            ->setDescription(t('Imagen para compartir en redes sociales cuando no hay otra disponible.'))
            ->setSetting('target_type', 'file')
            ->setSetting('handler', 'default:file')
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 21,
                'settings' => [
                    'file_extensions' => 'png jpg jpeg webp',
                    'file_directory' => 'og-images',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['google_analytics_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Google Analytics ID'))
            ->setDescription(t('ID de seguimiento de Google Analytics (ej: G-XXXXXXXXX).'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 22,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['google_tag_manager_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Google Tag Manager ID'))
            ->setDescription(t('ID del contenedor GTM (ej: GTM-XXXXXXX).'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 23,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Redes Sociales (JSON) ---

        $fields['social_links'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Redes Sociales'))
            ->setDescription(t('JSON con enlaces a redes sociales.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 30,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Contacto Global ---

        $fields['contact_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email de Contacto'))
            ->setDescription(t('Email principal de contacto.'))
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 40,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['contact_phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono de Contacto'))
            ->setDescription(t('Número de teléfono principal.'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 41,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['contact_address'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Dirección'))
            ->setDescription(t('Dirección postal de contacto.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 42,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['contact_coordinates'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Coordenadas'))
            ->setDescription(t('JSON con latitud y longitud: {"lat": 37.5, "lng": -4.5}'))
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 43,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Páginas Legales ---

        $fields['privacy_policy_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Política de Privacidad'))
            ->setDescription(t('Página de política de privacidad.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 50,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['terms_conditions_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Términos y Condiciones'))
            ->setDescription(t('Página de términos y condiciones.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 51,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['cookies_policy_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Política de Cookies'))
            ->setDescription(t('Página de política de cookies.'))
            ->setSetting('target_type', 'page_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 52,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Configuración del Header (Canvas v2) ---

        $fields['header_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Header'))
            ->setDescription(t('Variante visual del header.'))
            ->setSettings([
                'allowed_values' => [
                    'classic' => 'Clásico',
                    'centered' => 'Centrado',
                    'hero' => 'Hero (Transparente)',
                    'split' => 'Dividido',
                    'minimal' => 'Minimalista',
                ],
            ])
            ->setDefaultValue('classic')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 60,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_sticky'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Header Sticky'))
            ->setDescription(t('El header permanece fijo al hacer scroll.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 61,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_transparent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Header Transparente'))
            ->setDescription(t('El header es transparente sobre el hero.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 62,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_cta_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto CTA Header'))
            ->setDescription(t('Texto del botón de acción principal en el header.'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 63,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_cta_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL CTA Header'))
            ->setDescription(t('URL del botón de acción principal.'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 64,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Configuración del Footer (Canvas v2) ---

        $fields['footer_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Footer'))
            ->setDescription(t('Variante visual del footer.'))
            ->setSettings([
                'allowed_values' => [
                    'minimal' => 'Minimal',
                    'standard' => 'Standard',
                    'mega' => 'Mega Footer',
                    'split' => 'Split',
                ],
            ])
            ->setDefaultValue('standard')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 70,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_columns'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Columnas del Footer'))
            ->setDescription(t('Número de columnas (2-5).'))
            ->setDefaultValue(4)
            ->setSettings([
                'min' => 2,
                'max' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 71,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_show_social'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar Redes Sociales'))
            ->setDescription(t('Mostrar iconos de redes sociales en el footer.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 72,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_show_newsletter'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Mostrar Newsletter'))
            ->setDescription(t('Mostrar formulario de suscripción al newsletter.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 73,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_copyright'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto Copyright'))
            ->setDescription(t('Texto de copyright. Usa {year} para el año actual.'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDefaultValue('© {year} Todos los derechos reservados.')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 74,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // --- Campos de sistema ---

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación de la configuración.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    /**
     * Obtiene el nombre del sitio.
     */
    public function getSiteName(): string
    {
        return $this->get('site_name')->value ?? '';
    }

    /**
     * Obtiene el eslogan.
     */
    public function getTagline(): ?string
    {
        return $this->get('site_tagline')->value;
    }

    /**
     * Obtiene los enlaces sociales como array.
     */
    public function getSocialLinks(): array
    {
        $json = $this->get('social_links')->value ?? '[]';
        return json_decode($json, TRUE) ?: [];
    }

    /**
     * Establece los enlaces sociales.
     */
    public function setSocialLinks(array $links): self
    {
        $this->set('social_links', json_encode($links));
        return $this;
    }

    /**
     * Obtiene las coordenadas de contacto como array.
     */
    public function getContactCoordinates(): ?array
    {
        $json = $this->get('contact_coordinates')->value ?? 'null';
        return json_decode($json, TRUE);
    }

    // --- Getters para Header (Canvas v2) ---

    /**
     * Obtiene el tipo de header.
     */
    public function getHeaderType(): string
    {
        return $this->get('header_type')->value ?? 'standard';
    }

    /**
     * Indica si el header es sticky.
     */
    public function isHeaderSticky(): bool
    {
        return (bool) $this->get('header_sticky')->value;
    }

    /**
     * Indica si el header es transparente.
     */
    public function isHeaderTransparent(): bool
    {
        return (bool) $this->get('header_transparent')->value;
    }

    /**
     * Obtiene el texto del CTA del header.
     */
    public function getHeaderCtaText(): ?string
    {
        return $this->get('header_cta_text')->value;
    }

    /**
     * Obtiene la URL del CTA del header.
     */
    public function getHeaderCtaUrl(): ?string
    {
        return $this->get('header_cta_url')->value;
    }

    // --- Getters para Footer (Canvas v2) ---

    /**
     * Obtiene el tipo de footer.
     */
    public function getFooterType(): string
    {
        return $this->get('footer_type')->value ?? 'columns';
    }

    /**
     * Obtiene el número de columnas del footer.
     */
    public function getFooterColumns(): int
    {
        return (int) ($this->get('footer_columns')->value ?? 4);
    }

    /**
     * Indica si se muestran los iconos sociales en el footer.
     */
    public function showFooterSocial(): bool
    {
        return (bool) $this->get('footer_show_social')->value;
    }

    /**
     * Indica si se muestra el formulario de newsletter.
     */
    public function showFooterNewsletter(): bool
    {
        return (bool) $this->get('footer_show_newsletter')->value;
    }

    /**
     * Obtiene el texto de copyright con año dinámico.
     */
    public function getFooterCopyright(): string
    {
        $text = $this->get('footer_copyright')->value ?? '© {year} Todos los derechos reservados.';
        return str_replace('{year}', date('Y'), $text);
    }

}
