<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SeoPageConfig para configuración SEO por página.
 *
 * Cada entrada almacena la configuración SEO avanzada de una página
 * del Site Builder: meta tags, Open Graph, Twitter Cards, Schema.org,
 * hreflang, geo-targeting y resultados de auditoría.
 *
 * Multi-tenant: filtrado por tenant_id.
 * Field UI: habilitado para extensibilidad.
 *
 * @ContentEntityType(
 *   id = "seo_page_config",
 *   label = @Translation("Configuración SEO de Página"),
 *   label_collection = @Translation("Configuraciones SEO"),
 *   label_singular = @Translation("configuración SEO"),
 *   label_plural = @Translation("configuraciones SEO"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SeoPageConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SeoPageConfigForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SeoPageConfigForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SeoPageConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SeoPageConfigAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "seo_page_config",
 *   admin_permission = "administer site structure",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "meta_title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/seo-config",
 *     "add-form" = "/admin/content/seo-config/add",
 *     "canonical" = "/admin/content/seo-config/{seo_page_config}",
 *     "edit-form" = "/admin/content/seo-config/{seo_page_config}/edit",
 *     "delete-form" = "/admin/content/seo-config/{seo_page_config}/delete",
 *   },
 *   field_ui_base_route = "entity.seo_page_config.settings",
 * )
 */
class SeoPageConfig extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // =====================================================================
        // RELACIONES
        // =====================================================================

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant al que pertenece esta configuración SEO.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
            ]);

        $fields['page_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página'))
            ->setDescription(t('La página del Page Builder asociada a esta configuración SEO.'))
            ->setSetting('target_type', 'page_content')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -90,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // META TAGS BÁSICOS
        // =====================================================================

        $fields['meta_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Meta título'))
            ->setDescription(t('Título SEO de la página (máx. 70 caracteres).'))
            ->setSetting('max_length', 70)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['meta_description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Meta descripción'))
            ->setDescription(t('Descripción SEO de la página (máx. 160 caracteres).'))
            ->setSetting('max_length', 160)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['canonical_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL canónica'))
            ->setDescription(t('URL canónica de la página (máx. 500 caracteres).'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['robots'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Robots'))
            ->setDescription(t('Directivas robots para esta página.'))
            ->setSetting('allowed_values', [
                'index,follow' => 'index, follow',
                'index,nofollow' => 'index, nofollow',
                'noindex,follow' => 'noindex, follow',
                'noindex,nofollow' => 'noindex, nofollow',
            ])
            ->setDefaultValue('index,follow')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['keywords'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Keywords'))
            ->setDescription(t('Palabras clave separadas por coma.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 4,
                'settings' => ['rows' => 2],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // OPEN GRAPH
        // =====================================================================

        $fields['og_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('OG título'))
            ->setDescription(t('Título para Open Graph (máx. 100 caracteres).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['og_description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('OG descripción'))
            ->setDescription(t('Descripción para Open Graph (máx. 200 caracteres).'))
            ->setSetting('max_length', 200)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['og_image'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('OG imagen'))
            ->setDescription(t('Imagen para Open Graph y redes sociales.'))
            ->setSetting('target_type', 'file')
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // TWITTER CARDS
        // =====================================================================

        $fields['twitter_card'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Twitter Card'))
            ->setDescription(t('Tipo de tarjeta de Twitter.'))
            ->setSetting('allowed_values', [
                'summary' => 'Summary',
                'summary_large_image' => 'Summary Large Image',
            ])
            ->setDefaultValue('summary_large_image')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // SCHEMA.ORG JSON-LD
        // =====================================================================

        $fields['schema_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo Schema.org'))
            ->setDescription(t('Tipo de datos estructurados Schema.org para esta página.'))
            ->setSetting('allowed_values', [
                'WebPage' => 'WebPage',
                'Article' => 'Article',
                'BlogPosting' => 'BlogPosting',
                'FAQPage' => 'FAQPage',
                'Product' => 'Product',
                'LocalBusiness' => 'LocalBusiness',
                'Organization' => 'Organization',
            ])
            ->setDefaultValue('WebPage')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['schema_custom_json'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Schema.org JSON-LD personalizado'))
            ->setDescription(t('JSON-LD personalizado para datos estructurados avanzados.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 21,
                'settings' => ['rows' => 6],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // HREFLANG
        // =====================================================================

        $fields['hreflang_config'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración hreflang'))
            ->setDescription(t('JSON con configuración hreflang: [{lang: "es", url: "..."}, ...].'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 25,
                'settings' => ['rows' => 4],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // GEO-TARGETING
        // =====================================================================

        $fields['geo_region'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Región geográfica'))
            ->setDescription(t('Código ISO 3166-2 de la región (ej: ES-AN, US-CA).'))
            ->setSetting('max_length', 10)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 30,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['geo_position'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Posición geográfica'))
            ->setDescription(t('Coordenadas en formato "latitud;longitud" (ej: 37.3886,-5.9823).'))
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 31,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // AUDITORÍA SEO
        // =====================================================================

        $fields['last_audit_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación SEO'))
            ->setDescription(t('Último score de auditoría SEO (0-100).'))
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 40,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['last_audit_date'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha última auditoría'))
            ->setDescription(t('Fecha de la última auditoría SEO realizada.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 41,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // =====================================================================
        // TIMESTAMPS
        // =====================================================================

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Actualizado'))
            ->setDescription(t('Fecha de última actualización.'));

        return $fields;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Obtiene el ID del tenant.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID de la página asociada.
     */
    public function getPageId(): ?int
    {
        return $this->get('page_id')->target_id ? (int) $this->get('page_id')->target_id : NULL;
    }

    /**
     * Obtiene el meta título.
     */
    public function getMetaTitle(): string
    {
        return (string) $this->get('meta_title')->value;
    }

    /**
     * Obtiene la meta descripción.
     */
    public function getMetaDescription(): string
    {
        return (string) $this->get('meta_description')->value;
    }

    /**
     * Obtiene la URL canónica.
     */
    public function getCanonicalUrl(): string
    {
        return (string) $this->get('canonical_url')->value;
    }

    /**
     * Obtiene las directivas robots.
     */
    public function getRobots(): string
    {
        return (string) ($this->get('robots')->value ?? 'index,follow');
    }

    /**
     * Obtiene el tipo Schema.org.
     */
    public function getSchemaType(): string
    {
        return (string) ($this->get('schema_type')->value ?? 'WebPage');
    }

    /**
     * Obtiene el JSON-LD personalizado.
     */
    public function getSchemaCustomJson(): ?array
    {
        $json = $this->get('schema_custom_json')->value;
        if (empty($json)) {
            return NULL;
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : NULL;
    }

    /**
     * Obtiene la configuración hreflang como array.
     */
    public function getHreflangConfig(): array
    {
        $json = $this->get('hreflang_config')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene la región geográfica.
     */
    public function getGeoRegion(): string
    {
        return (string) $this->get('geo_region')->value;
    }

    /**
     * Obtiene la posición geográfica.
     */
    public function getGeoPosition(): string
    {
        return (string) $this->get('geo_position')->value;
    }

    /**
     * Obtiene el último score de auditoría.
     */
    public function getLastAuditScore(): int
    {
        return (int) ($this->get('last_audit_score')->value ?? 0);
    }

    /**
     * Obtiene las keywords como array.
     */
    public function getKeywordsArray(): array
    {
        $keywords = $this->get('keywords')->value;
        if (empty($keywords)) {
            return [];
        }
        return array_map('trim', explode(',', $keywords));
    }

    /**
     * Obtiene el tipo de Twitter Card.
     */
    public function getTwitterCard(): string
    {
        return (string) ($this->get('twitter_card')->value ?? 'summary_large_image');
    }

    /**
     * Obtiene el título OG.
     */
    public function getOgTitle(): string
    {
        return (string) $this->get('og_title')->value;
    }

    /**
     * Obtiene la descripción OG.
     */
    public function getOgDescription(): string
    {
        return (string) $this->get('og_description')->value;
    }

}
