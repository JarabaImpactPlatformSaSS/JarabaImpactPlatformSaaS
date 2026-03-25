<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Success Case entity.
 *
 * Entidad centralizada para gestionar los casos de éxito que se muestran
 * en los 4 meta-sitios del ecosistema Jaraba. Cada entidad contiene la
 * narrativa completa (reto/solución/resultado), métricas cuantificables,
 * quotes testimoniales y referencias a multimedia (fotos, vídeos).
 *
 * View modes por meta-sitio:
 * - personal_story: pepejaraba.com/casos-de-exito
 * - business_impact: jarabaimpact.com/impacto
 * - institutional_evidence: plataformadeecosistemas.es/impacto
 * - testimonial_card: /instituciones
 *
 * @ContentEntityType(
 *   id = "success_case",
 *   label = @Translation("Success Case"),
 *   label_collection = @Translation("Success Cases"),
 *   label_singular = @Translation("success case"),
 *   label_plural = @Translation("success cases"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_success_cases\SuccessCaseListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_success_cases\Form\SuccessCaseForm",
 *       "add" = "Drupal\jaraba_success_cases\Form\SuccessCaseForm",
 *       "edit" = "Drupal\jaraba_success_cases\Form\SuccessCaseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_success_cases\SuccessCaseAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "success_case",
 *   admin_permission = "administer success cases",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/success-cases/{success_case}",
 *     "add-form" = "/admin/content/success-cases/add",
 *     "edit-form" = "/admin/content/success-cases/{success_case}/edit",
 *     "delete-form" = "/admin/content/success-cases/{success_case}/delete",
 *     "collection" = "/admin/content/success-cases",
 *   },
 *   field_ui_base_route = "entity.success_case.settings",
 * )
 */
class SuccessCase extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Auto-generate slug from name if not set.
    if ($this->get('slug')->isEmpty() && !$this->get('name')->isEmpty()) {
      $slug = $this->generateSlug($this->get('name')->value);
      $this->set('slug', $slug);
    }
  }

  /**
   * Generates a URL-safe slug from a name.
   *
   * @param string $name
   *   The name to slugify.
   *
   * @return string
   *   URL-safe slug.
   */
  protected function generateSlug(string $name): string {
    $slug = mb_strtolower($name);
    // Transliterate common Spanish characters.
    $slug = str_replace(
          ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
          ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
          $slug
      );
    // Replace non-alphanumeric with hyphens.
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    // Trim hyphens.
    return trim($slug, '-');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add owner field (uid).
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // Core Identity
    // =========================================================================
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full Name'))
      ->setDescription(t('The full name of the person (e.g. "Marcela Calabia").'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Slug'))
      ->setDescription(t('URL-safe slug for the frontend route. Auto-generated from name if left empty.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('Whether this success case is visible on the frontend.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -8,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Personal Data
    // =========================================================================
    $fields['profession'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Profession / Current Role'))
      ->setDescription(t('E.g. "Coach de Comunicación Estratégica y Resiliencia".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Company / Brand'))
      ->setDescription(t('E.g. "Camino Viejo".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sector'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sector'))
      ->setDescription(t('E.g. "Turismo rural / Gastronomía".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('City and province, e.g. "Sevilla, Andalucía".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Media
    // =========================================================================
    $fields['hero_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Photo'))
      ->setDescription(t('Portrait or professional headshot of the person.'))
      ->setSetting('file_directory', 'success-cases/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setSetting('max_filesize', '5 MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Social & Links
    // =========================================================================
    $fields['website'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Website'))
      ->setDescription(t('Personal or company website URL.'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['linkedin'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('LinkedIn'))
      ->setDescription(t('LinkedIn profile URL.'))
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Narrative (Challenge → Solution → Result)
    // =========================================================================
    $fields['challenge_before'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Challenge (Before)'))
      ->setDescription(t('Describe the starting situation, pain points, and obstacles before the program.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['solution_during'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Solution (During)'))
      ->setDescription(t('Describe the services, tools, and process used during the accompaniment.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 11,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['result_after'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Result (After)'))
      ->setDescription(t('Describe the concrete achievements and transformation.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 12,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Quotes
    // =========================================================================
    $fields['quote_short'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Short Quote'))
      ->setDescription(t('A 1-2 line testimonial quote for cards and sliders (max ~150 chars).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'basic_string',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quote_long'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Long Quote'))
      ->setDescription(t('Extended testimonial quote for detail pages.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 21,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Metrics
    // =========================================================================
    $fields['metrics_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metrics (JSON)'))
      ->setDescription(t('Key-value metrics in JSON format, e.g. {"revenue_increase":"120%","new_clients":"15"}.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 30,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rating'))
      ->setDescription(t('Rating 1-5 stars.'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 31,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // Tenant (TENANT-001: TODA query DEBE filtrar por tenant)
    // =========================================================================
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The group (tenant) this success case belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Program / Vertical
    // =========================================================================
    $fields['program_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Program Name'))
      ->setDescription(t('E.g. "Andalucía +ei", "Emplea-T".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('The vertical this case belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'jarabalex' => 'JarabaLex',
        'agroconecta' => 'AgroConecta',
        'comercioconecta' => 'ComercioConecta',
        'empleabilidad' => 'Empleabilidad',
        'emprendimiento' => 'Emprendimiento',
        'formacion' => 'Formación',
        'serviciosconecta' => 'ServiciosConecta',
        'andalucia_ei' => 'Andalucía +ei',
        'content_hub' => 'Content Hub',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 41,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['program_funder'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Program Funder'))
      ->setDescription(t('E.g. "Junta de Andalucía", "FSE".'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 42,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['program_year'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Program Year'))
      ->setDescription(t('E.g. "2024", "2023-2024".'))
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 43,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Landing Page — Protagonist
    // =========================================================================
    $fields['protagonist_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Protagonist Display Name'))
      ->setDescription(t('Name as shown on the landing page hero (e.g. "Antonio Morales").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 44,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['protagonist_role'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Protagonist Role'))
      ->setDescription(t('Role/title shown in testimonial (e.g. "Presidente").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 45,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['protagonist_company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Protagonist Company'))
      ->setDescription(t('Company/org shown in testimonial (e.g. "Cooperativa Sierra de Cazorla, Jaén").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 46,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Landing Page — Additional Images
    // =========================================================================
    $fields['protagonist_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Protagonist Image'))
      ->setDescription(t('Photo of the protagonist for context section.'))
      ->setSetting('file_directory', 'success-cases/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setSetting('max_filesize', '5 MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 47,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['before_after_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Before/After Image'))
      ->setDescription(t('Image showing the before/after transformation.'))
      ->setSetting('file_directory', 'success-cases/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setSetting('max_filesize', '5 MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 48,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['discovery_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Discovery Image'))
      ->setDescription(t('Image for the product discovery section.'))
      ->setSetting('file_directory', 'success-cases/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setSetting('max_filesize', '5 MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 49,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['dashboard_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Dashboard Image'))
      ->setDescription(t('Screenshot of the dashboard/results.'))
      ->setSetting('file_directory', 'success-cases/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setSetting('file_extensions', 'png jpg jpeg webp')
      ->setSetting('max_filesize', '5 MB')
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // NOTE: climax_image removed due to MariaDB row size limit (65535 bytes).
    // Use hero_image or protagonist_image for the climax section instead.
    // =========================================================================
    // Landing Page — Structured JSON Data
    // =========================================================================.
    $fields['pain_points_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pain Points (JSON)'))
      ->setDescription(t('Array of pain point objects: [{icon_category, icon_name, title, description, metric_before, metric_after, metric_change}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 52,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['timeline_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Timeline (JSON)'))
      ->setDescription(t('Array of timeline steps: [{day, title, text}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 53,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['discovery_features_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Discovery Features (JSON)'))
      ->setDescription(t('Array of features discovered: [{icon_category, icon_name, title, description}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 54,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['comparison_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comparison Table (JSON)'))
      ->setDescription(t('Comparison columns: {traditional: {label, items: [...]}, competitor: {label, items: [...]}, platform: {label, items: [...]}}.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 55,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['additional_testimonials_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Additional Testimonials (JSON)'))
      ->setDescription(t('Array of short testimonials for social proof: [{quote, name, role}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 56,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['faq_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('FAQ (JSON)'))
      ->setDescription(t('Array of FAQ items (min 10): [{question, answer}]. Used for Schema.org FAQPage.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 57,
        'settings' => ['rows' => 8],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['partner_logos_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Partner Logos (JSON)'))
      ->setDescription(t('Array of partner logos: [{name, url}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 58,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['how_it_works_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('How It Works Steps (JSON)'))
      ->setDescription(t('Array of 3 steps: [{icon_category, icon_name, title, description}].'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 59,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Landing Page — Optional Extras
    // =========================================================================
    $fields['video_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Video URL'))
      ->setDescription(t('Optional video testimonial URL (YouTube/Vimeo embed).'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 60,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // NOTE: cta_urgency_text, headline, subtitle use string_long (TEXT)
    // instead of string (VARCHAR) to avoid MariaDB row size limit.
    $fields['cta_urgency_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CTA Urgency Text'))
      ->setDescription(t('Text for the urgency badge in the hero (e.g. "Solo quedan 3 plazas esta semana").'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 61,
        'settings' => ['rows' => 1],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['schema_date_published'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Published Date (Schema.org)'))
      ->setDescription(t('Date for Schema.org Article (YYYY-MM-DD format).'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 62,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['headline'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Landing Headline'))
      ->setDescription(t('H1 headline for the case study landing page.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 63,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subtitle'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Landing Subtitle'))
      ->setDescription(t('Subtitle shown below the headline in the hero.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 64,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // SEO
    // =========================================================================
    $fields['meta_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Description'))
      ->setDescription(t('SEO meta description for the detail page (max 320 chars).'))
      ->setSetting('max_length', 320)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Presentation Control
    // =========================================================================
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Order of presentation. Lower values appear first.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 60,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['featured'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Featured'))
      ->setDescription(t('Whether this case should be highlighted in heros and carousels.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 61,
        'settings' => ['display_label' => TRUE],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // =========================================================================
    // Timestamps
    // =========================================================================
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the entity was last edited.'));

    return $fields;
  }

}
