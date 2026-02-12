<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the TenantThemeConfig entity.
 *
 * Almacena la configuración de Design Tokens por tenant.
 * Soporta cascada: Plataforma → Vertical → Plan → Tenant.
 *
 * @ContentEntityType(
 *   id = "tenant_theme_config",
 *   label = @Translation("Configuración de Tema"),
 *   label_collection = @Translation("Configuraciones de Tema"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "tenant_theme_config",
 *   admin_permission = "administer theme config",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/appearance/theme-configs",
 *     "add-form" = "/admin/appearance/theme-configs/add",
 *     "canonical" = "/admin/appearance/theme-configs/{tenant_theme_config}",
 *     "edit-form" = "/admin/appearance/theme-configs/{tenant_theme_config}/edit",
 *     "delete-form" = "/admin/appearance/theme-configs/{tenant_theme_config}/delete",
 *   },
 * )
 */
class TenantThemeConfig extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant al que pertenece esta configuración.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['vertical'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('Vertical base para heredar tokens.'))
            ->setSetting('allowed_values', [
                'platform' => t('Plataforma (Base)'),
                'empleabilidad' => t('Empleabilidad'),
                'emprendimiento' => t('Emprendimiento'),
                'agroconecta' => t('AgroConecta'),
                'comercio' => t('ComercioConecta'),
                'servicios' => t('ServiciosConecta'),
            ])
            ->setDefaultValue('platform')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === DESIGN TOKENS - COLORES ===

        $fields['color_primary'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Primario'))
            ->setDescription(t('Color principal de la marca (hex).'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#FF8C42')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_secondary'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Secundario'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#00A9A5')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_accent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Acento'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#233D63')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_dark'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Oscuro'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#1a1a2e')
            ->setDisplayConfigurable('form', TRUE);

        // Colores UI adicionales
        $fields['color_success'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Éxito'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#10B981')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_warning'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Advertencia'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#F59E0B')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_error'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color Error'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#EF4444')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_bg_body'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Fondo de Página'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#F8FAFC')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_bg_surface'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Fondo de Tarjetas'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#FFFFFF')
            ->setDisplayConfigurable('form', TRUE);

        $fields['color_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color de Texto'))
            ->setSetting('max_length', 7)
            ->setDefaultValue('#1A1A2E')
            ->setDisplayConfigurable('form', TRUE);

        // === IDENTIDAD ===

        $fields['site_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Sitio'))
            ->setSetting('max_length', 100)
            ->setDisplayConfigurable('form', TRUE);

        $fields['site_slogan'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Eslogan'))
            ->setSetting('max_length', 200)
            ->setDisplayConfigurable('form', TRUE);

        $fields['logo_alt'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Logo Alternativo'))
            ->setDescription(t('Logo para fondos oscuros.'))
            ->setSetting('file_extensions', 'png jpg svg webp')
            ->setDisplayConfigurable('form', TRUE);

        // === TIPOGRAFÍA ADICIONAL ===

        $fields['font_size_base'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tamaño Base Fuente'))
            ->setSetting('max_length', 10)
            ->setDefaultValue('16')
            ->setDisplayConfigurable('form', TRUE);

        // === DESIGN TOKENS - ESPACIADO Y BORDES ===

        $fields['font_headings'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Fuente Títulos'))
            ->setDescription(t('Google Font para headings.'))
            ->setSetting('max_length', 50)
            ->setDefaultValue('Outfit')
            ->setDisplayConfigurable('form', TRUE);

        $fields['font_body'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Fuente Cuerpo'))
            ->setSetting('max_length', 50)
            ->setDefaultValue('Inter')
            ->setDisplayConfigurable('form', TRUE);

        // === FUENTES PERSONALIZADAS (G117-5) ===

        $fields['font_heading_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Fuente de Títulos'))
            ->setDescription(t('URL de la fuente personalizada para títulos (woff2 o Google Fonts CSS URL).'))
            ->setSettings(['max_length' => 512])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 23,
            ]);

        $fields['font_body_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Fuente de Cuerpo'))
            ->setDescription(t('URL de la fuente personalizada para cuerpo (woff2 o Google Fonts CSS URL).'))
            ->setSettings(['max_length' => 512])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 24,
            ]);

        $fields['font_heading_family'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Familia Tipográfica de Títulos'))
            ->setDescription(t('Nombre CSS font-family para la fuente personalizada de títulos.'))
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 25,
            ]);

        $fields['font_body_family'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Familia Tipográfica de Cuerpo'))
            ->setDescription(t('Nombre CSS font-family para la fuente personalizada de cuerpo.'))
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 26,
            ]);

        // === DESIGN TOKENS - ESPACIADO Y BORDES ===

        $fields['border_radius'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Border Radius'))
            ->setSetting('allowed_values', [
                'none' => t('Sin redondeo'),
                'sm' => t('Pequeño (4px)'),
                'md' => t('Medio (8px)'),
                'lg' => t('Grande (16px)'),
                'xl' => t('Extra grande (24px)'),
                'full' => t('Completo (pill)'),
            ])
            ->setDefaultValue('md')
            ->setDisplayConfigurable('form', TRUE);

        $fields['shadow_intensity'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Intensidad de Sombras'))
            ->setSetting('allowed_values', [
                'none' => t('Sin sombras'),
                'subtle' => t('Sutiles'),
                'normal' => t('Normal'),
                'strong' => t('Fuertes'),
            ])
            ->setDefaultValue('normal')
            ->setDisplayConfigurable('form', TRUE);

        // === COMPONENTES ===

        $fields['header_variant'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Variante de Header'))
            ->setSetting('allowed_values', [
                'classic' => t('Clásico'),
                'transparent' => t('Transparente'),
                'centered' => t('Centrado'),
                'mega' => t('Mega-menú'),
                'sidebar' => t('Sidebar'),
                'minimal' => t('Minimal'),
            ])
            ->setDefaultValue('classic')
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_variant'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Variante de Hero'))
            ->setSetting('allowed_values', [
                'fullscreen' => t('Fullscreen'),
                'split' => t('Split 50/50'),
                'compact' => t('Compacto'),
                'animated' => t('Animado'),
                'slider' => t('Slider'),
            ])
            ->setDefaultValue('split')
            ->setDisplayConfigurable('form', TRUE);

        // === BRANDING ===

        $fields['logo'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Logo'))
            ->setDescription(t('Logo del tenant.'))
            ->setSetting('file_extensions', 'png jpg svg')
            ->setDisplayConfigurable('form', TRUE);

        $fields['favicon'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Favicon'))
            ->setSetting('file_extensions', 'ico png')
            ->setDisplayConfigurable('form', TRUE);

        $fields['dark_mode_enabled'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Dark Mode Habilitado'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        // === COMPONENTES ADICIONALES ===

        $fields['header_sticky'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Header Sticky'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_cta_enabled'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('CTA en Header'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['header_cta_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto CTA'))
            ->setSetting('max_length', 32)
            ->setDefaultValue('Empezar')
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_overlay'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Hero Overlay'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['card_style'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estilo de Tarjetas'))
            ->setSetting('max_length', 20)
            ->setDefaultValue('elevated')
            ->setDisplayConfigurable('form', TRUE);

        $fields['card_border_radius'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Border Radius Tarjetas'))
            ->setSetting('max_length', 10)
            ->setDefaultValue('12')
            ->setDisplayConfigurable('form', TRUE);

        $fields['card_hover_effect'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Efecto Hover Tarjetas'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['button_style'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estilo de Botones'))
            ->setSetting('max_length', 20)
            ->setDefaultValue('solid')
            ->setDisplayConfigurable('form', TRUE);

        $fields['button_border_radius'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Border Radius Botones'))
            ->setSetting('max_length', 10)
            ->setDefaultValue('8')
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_variant'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Variante de Footer'))
            ->setSetting('max_length', 20)
            ->setDefaultValue('standard')
            ->setDisplayConfigurable('form', TRUE);

        $fields['footer_copyright'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Copyright'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        // === REDES SOCIALES ===

        $fields['social_facebook'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Facebook'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['social_twitter'] = BaseFieldDefinition::create('string')
            ->setLabel(t('X (Twitter)'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['social_linkedin'] = BaseFieldDefinition::create('string')
            ->setLabel(t('LinkedIn'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['social_instagram'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Instagram'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['social_youtube'] = BaseFieldDefinition::create('string')
            ->setLabel(t('YouTube'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        // === OPCIONES AVANZADAS ===

        $fields['animations_enabled'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Animaciones Habilitadas'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['back_to_top_enabled'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Botón Volver Arriba'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        // === CUSTOM CSS ===

        $fields['custom_css'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('CSS Personalizado'))
            ->setDescription(t('CSS adicional (solo Enterprise+).'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Genera las CSS Custom Properties para este config.
     *
     * @return string
     *   Bloque CSS con :root { --var: value; }
     */
    public function generateCssVariables(): string
    {
        $vars = [];

        // === COLORES DE MARCA ===
        if ($primary = $this->get('color_primary')->value) {
            $vars['--ej-color-primary'] = $primary;
            $vars['--ej-color-impulse'] = $primary;
        }
        if ($secondary = $this->get('color_secondary')->value) {
            $vars['--ej-color-secondary'] = $secondary;
            $vars['--ej-color-innovation'] = $secondary;
        }
        if ($accent = $this->get('color_accent')->value) {
            $vars['--ej-color-accent'] = $accent;
            $vars['--ej-color-corporate'] = $accent;
        }
        if ($dark = $this->get('color_dark')->value) {
            $vars['--ej-color-dark'] = $dark;
        }

        // === COLORES UI ===
        if ($this->hasField('color_success') && $success = $this->get('color_success')->value) {
            $vars['--ej-color-success'] = $success;
        }
        if ($this->hasField('color_warning') && $warning = $this->get('color_warning')->value) {
            $vars['--ej-color-warning'] = $warning;
        }
        if ($this->hasField('color_error') && $error = $this->get('color_error')->value) {
            $vars['--ej-color-danger'] = $error;
            $vars['--ej-color-error'] = $error;
        }

        // === FONDOS ===
        if ($this->hasField('color_bg_body') && $bgBody = $this->get('color_bg_body')->value) {
            $vars['--ej-color-bg-body'] = $bgBody;
        }
        if ($this->hasField('color_bg_surface') && $bgSurface = $this->get('color_bg_surface')->value) {
            $vars['--ej-color-bg-surface'] = $bgSurface;
        }
        if ($this->hasField('color_text') && $textColor = $this->get('color_text')->value) {
            $vars['--ej-text-primary'] = $textColor;
        }

        // === TIPOGRAFÍA ===
        // Priorizar fuentes personalizadas sobre las de Google Fonts seleccionadas.
        $customHeadingUrl = $this->hasField('font_heading_url') ? $this->get('font_heading_url')->value : NULL;
        $customHeadingFamily = $this->hasField('font_heading_family') ? $this->get('font_heading_family')->value : NULL;
        $customBodyUrl = $this->hasField('font_body_url') ? $this->get('font_body_url')->value : NULL;
        $customBodyFamily = $this->hasField('font_body_family') ? $this->get('font_body_family')->value : NULL;

        if ($customHeadingUrl && $customHeadingFamily) {
            $vars['--ej-font-family-headings'] = "'{$customHeadingFamily}', sans-serif";
            $vars['--ej-font-headings'] = "'{$customHeadingFamily}', sans-serif";
        }
        elseif ($fontHeadings = $this->get('font_headings')->value) {
            $vars['--ej-font-family-headings'] = "'{$fontHeadings}', sans-serif";
            $vars['--ej-font-headings'] = "'{$fontHeadings}', sans-serif";
        }

        if ($customBodyUrl && $customBodyFamily) {
            $vars['--ej-font-family-body'] = "'{$customBodyFamily}', sans-serif";
            $vars['--ej-font-body'] = "'{$customBodyFamily}', sans-serif";
        }
        elseif ($fontBody = $this->get('font_body')->value) {
            $vars['--ej-font-family-body'] = "'{$fontBody}', sans-serif";
            $vars['--ej-font-body'] = "'{$fontBody}', sans-serif";
        }
        if ($this->hasField('font_size_base') && $fontSize = $this->get('font_size_base')->value) {
            $vars['--ej-font-size-base'] = $fontSize . 'px';
        }

        // === BORDES ===
        $radiusMap = [
            'none' => '0',
            'sm' => '4px',
            'md' => '8px',
            'lg' => '16px',
            'xl' => '24px',
            'full' => '9999px',
        ];
        $radius = $this->get('border_radius')->value ?? 'md';
        $vars['--ej-border-radius'] = $radiusMap[$radius] ?? '8px';

        // Card border radius
        if ($this->hasField('card_border_radius') && $cardRadius = $this->get('card_border_radius')->value) {
            $vars['--ej-card-border-radius'] = $cardRadius . 'px';
        }

        // Button border radius
        if ($this->hasField('button_border_radius') && $btnRadius = $this->get('button_border_radius')->value) {
            $vars['--ej-button-border-radius'] = $btnRadius === '9999' ? '9999px' : $btnRadius . 'px';
        }

        // === GENERAR CSS ===
        $css = ":root {\n";
        foreach ($vars as $var => $value) {
            $css .= "  {$var}: {$value};\n";
        }
        $css .= "}\n";

        // Custom CSS adicional
        if ($customCss = $this->get('custom_css')->value) {
            $css .= "\n/* Custom CSS */\n{$customCss}\n";
        }

        return $css;
    }

}
