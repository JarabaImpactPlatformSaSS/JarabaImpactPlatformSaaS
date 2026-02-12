<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad HomepageContent para contenido de homepage.
 *
 * PROPÓSITO:
 * Almacena todo el contenido editable de la homepage principal.
 * Usa Entity References a FeatureCard, StatItem e IntentionCard para
 * máxima flexibilidad con Field UI.
 *
 * @ContentEntityType(
 *   id = "homepage_content",
 *   label = @Translation("Contenido Homepage"),
 *   label_collection = @Translation("Contenido Homepage"),
 *   label_singular = @Translation("contenido homepage"),
 *   label_plural = @Translation("contenidos homepage"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_page_builder\HomepageContentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_page_builder\Form\HomepageContentForm",
 *       "add" = "Drupal\jaraba_page_builder\Form\HomepageContentForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\HomepageContentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_page_builder\HomepageContentAccessControlHandler",
 *   },
 *   base_table = "homepage_content",
 *   data_table = "homepage_content_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer page builder",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/homepage",
 *     "add-form" = "/admin/content/homepage/add",
 *     "canonical" = "/admin/content/homepage/{homepage_content}",
 *     "edit-form" = "/admin/content/homepage/{homepage_content}/edit",
 *     "delete-form" = "/admin/content/homepage/{homepage_content}/delete",
 *   },
 *   field_ui_base_route = "entity.homepage_content.settings",
 * )
 */
class HomepageContent extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage)
    {
        parent::preSave($storage);
        if (!$this->getOwnerId()) {
            $this->setOwnerId(\Drupal::currentUser()->id());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Título interno para identificación.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título interno'))
            ->setDescription(t('Nombre para identificar esta configuración de homepage'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 100,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === HERO SECTION ===
        $fields['hero_eyebrow'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: Eyebrow'))
            ->setDescription(t('Texto pequeño sobre el título del hero'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: Título'))
            ->setDescription(t('Título principal del hero'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 200])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_subtitle'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Hero: Subtítulo'))
            ->setDescription(t('Subtítulo o descripción del hero'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_cta_primary_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: CTA Primario Texto'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 50])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_cta_primary_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: CTA Primario URL'))
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_cta_secondary_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: CTA Secundario Texto'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 50])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_cta_secondary_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: CTA Secundario URL'))
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['hero_scroll_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hero: Texto Scroll'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 50])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === FEATURES SECTION (Entity Reference) ===
        $fields['features'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Características'))
            ->setDescription(t('Tarjetas de características para mostrar'))
            ->setSetting('target_type', 'feature_card')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => 60,
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === STATS SECTION (Entity Reference) ===
        $fields['stats'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Estadísticas'))
            ->setDescription(t('Métricas/estadísticas para mostrar'))
            ->setSetting('target_type', 'stat_item')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === INTENTIONS SECTION (Entity Reference) ===
        $fields['intentions'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Intenciones'))
            ->setDescription(t('Tarjetas de intención/avatar para mostrar'))
            ->setSetting('target_type', 'intention_card')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 30,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID (multi-tenant).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant propietario de este contenido'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 40,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación'));

        // === SEO SECTION ===
        $fields['meta_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('SEO: Meta Title'))
            ->setDescription(t('Título para motores de búsqueda (60-70 caracteres recomendado)'))
            ->setTranslatable(TRUE)
            ->setSettings(['max_length' => 120])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 50,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['meta_description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('SEO: Meta Description'))
            ->setDescription(t('Descripción para motores de búsqueda (150-160 caracteres recomendado)'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 51,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['og_image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('SEO: Open Graph Image'))
            ->setDescription(t('Imagen para redes sociales (recomendado: 1200x630px)'))
            ->setSettings([
                'file_directory' => 'seo/og-images',
                'alt_field' => TRUE,
                'title_field' => FALSE,
                'max_resolution' => '2400x1260',
                'file_extensions' => 'png gif jpg jpeg webp',
            ])
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 52,
            ])
            ->setDisplayConfigurable('form', TRUE);

        return $fields;
    }

    /**
     * Obtiene los datos del hero.
     */
    public function getHeroData(): array
    {
        return [
            'eyebrow' => $this->get('hero_eyebrow')->value ?? '',
            'title' => $this->get('hero_title')->value ?? '',
            'subtitle' => $this->get('hero_subtitle')->value ?? '',
            'cta_primary' => [
                'text' => $this->get('hero_cta_primary_text')->value ?? '',
                'url' => $this->get('hero_cta_primary_url')->value ?? '',
            ],
            'cta_secondary' => [
                'text' => $this->get('hero_cta_secondary_text')->value ?? '',
                'url' => $this->get('hero_cta_secondary_url')->value ?? '',
            ],
            'scroll_text' => $this->get('hero_scroll_text')->value ?? '',
        ];
    }

    /**
     * Obtiene las features referenciadas.
     *
     * @return \Drupal\jaraba_page_builder\Entity\FeatureCard[]
     */
    public function getFeatures(): array
    {
        $features = [];
        foreach ($this->get('features')->referencedEntities() as $feature) {
            $features[] = $feature;
        }
        usort($features, fn($a, $b) => $a->getWeight() <=> $b->getWeight());
        return $features;
    }

    /**
     * Obtiene las stats referenciadas.
     *
     * @return \Drupal\jaraba_page_builder\Entity\StatItem[]
     */
    public function getStats(): array
    {
        $stats = [];
        foreach ($this->get('stats')->referencedEntities() as $stat) {
            $stats[] = $stat;
        }
        usort($stats, fn($a, $b) => $a->getWeight() <=> $b->getWeight());
        return $stats;
    }

    /**
     * Obtiene las intentions referenciadas.
     *
     * @return \Drupal\jaraba_page_builder\Entity\IntentionCard[]
     */
    public function getIntentions(): array
    {
        $intentions = [];
        foreach ($this->get('intentions')->referencedEntities() as $intention) {
            $intentions[] = $intention;
        }
        usort($intentions, fn($a, $b) => $a->getWeight() <=> $b->getWeight());
        return $intentions;
    }

    /**
     * Obtiene los datos SEO de la homepage.
     *
     * @return array
     *   Array con meta_title, meta_description y og_image_url.
     */
    public function getSeoData(): array
    {
        $og_image_url = '';

        if (!$this->get('og_image')->isEmpty()) {
            /** @var \Drupal\file\FileInterface $file */
            $file = $this->get('og_image')->entity;
            if ($file) {
                $og_image_url = \Drupal::service('file_url_generator')
                    ->generateAbsoluteString($file->getFileUri());
            }
        }

        // Limpiar HTML del meta_description (viene de CKEditor)
        $meta_description = $this->get('meta_description')->value ?? '';
        $meta_description = strip_tags($meta_description);
        $meta_description = trim(html_entity_decode($meta_description, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [
            'meta_title' => $this->get('meta_title')->value ?? '',
            'meta_description' => $meta_description,
            'og_image_url' => $og_image_url,
        ];
    }

}
