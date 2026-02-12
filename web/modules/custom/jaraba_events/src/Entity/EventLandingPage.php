<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Landing Page de Evento.
 *
 * Estructura: Entidad que representa una landing page personalizada
 *   vinculada a un evento de marketing. Contiene datos de diseño
 *   (layout, hero, colores), contenido (descripción, CTA, secciones),
 *   SEO (título, meta, Schema.org, Open Graph) y métricas
 *   (vistas, conversiones).
 *
 * Lógica: Una EventLandingPage pertenece a un tenant (tenant_id) y
 *   a un evento de marketing (event_id). El slug es único por tenant
 *   y se usa como URL amigable para la landing pública. El campo
 *   is_published controla la visibilidad: solo las landing pages
 *   publicadas se muestran en el frontend. Los campos views_count
 *   y conversions_count se actualizan desde el servicio de analítica.
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automáticos.
 *
 * @ContentEntityType(
 *   id = "event_landing_page",
 *   label = @Translation("Landing Page de Evento"),
 *   label_collection = @Translation("Landing Pages de Eventos"),
 *   label_singular = @Translation("landing page de evento"),
 *   label_plural = @Translation("landing pages de eventos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_events\ListBuilder\EventLandingPageListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_events\Form\EventLandingPageForm",
 *       "add" = "Drupal\jaraba_events\Form\EventLandingPageForm",
 *       "edit" = "Drupal\jaraba_events\Form\EventLandingPageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_events\Access\EventLandingPageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_landing_page",
 *   fieldable = TRUE,
 *   admin_permission = "administer events",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/event-landing-pages/{event_landing_page}",
 *     "add-form" = "/admin/content/event-landing-pages/add",
 *     "edit-form" = "/admin/content/event-landing-pages/{event_landing_page}/edit",
 *     "delete-form" = "/admin/content/event-landing-pages/{event_landing_page}/delete",
 *     "collection" = "/admin/content/event-landing-pages",
 *   },
 *   field_ui_base_route = "entity.event_landing_page.settings",
 * )
 */
class EventLandingPage extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Relación multi-tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant propietario de esta landing page.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Relación con evento ---
    $fields['event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Evento'))
      ->setDescription(t('Evento de marketing al que pertenece esta landing page.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'marketing_event')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Identificación ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Título de la landing page del evento.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug URL'))
      ->setDescription(t('Identificador URL-friendly único por tenant para la landing page.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Diseño y layout ---
    $fields['layout'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Layout'))
      ->setDescription(t('Diseño visual de la landing page.'))
      ->setDefaultValue('standard')
      ->setSetting('allowed_values', [
        'standard' => t('Estándar'),
        'minimal' => t('Minimalista'),
        'full_width' => t('Ancho Completo'),
        'video_hero' => t('Vídeo Hero'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hero_image'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Imagen Hero'))
      ->setDescription(t('URL de la imagen principal del hero banner.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hero_video_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Vídeo Hero'))
      ->setDescription(t('URL del vídeo para el hero banner (YouTube, Vimeo, etc.).'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contenido ---
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción completa de la landing page.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cta_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto CTA'))
      ->setDescription(t('Texto del botón de llamada a la acción.'))
      ->setDefaultValue('Registrarse')
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cta_color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color CTA'))
      ->setDescription(t('Color hexadecimal del botón CTA (ej: #FF5722).'))
      ->setSetting('max_length', 7)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Secciones visibles ---
    $fields['show_speakers'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar Ponentes'))
      ->setDescription(t('Mostrar la sección de ponentes en la landing.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['show_schedule'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar Agenda'))
      ->setDescription(t('Mostrar la sección de agenda/horario en la landing.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['show_testimonials'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Mostrar Testimonios'))
      ->setDescription(t('Mostrar la sección de testimonios en la landing.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Personalización avanzada ---
    $fields['custom_css'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('CSS Personalizado'))
      ->setDescription(t('Estilos CSS adicionales para esta landing page.'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['custom_js'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('JavaScript Personalizado'))
      ->setDescription(t('Scripts JavaScript adicionales para esta landing page.'))
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SEO y Open Graph ---
    $fields['seo_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título SEO'))
      ->setDescription(t('Título optimizado para buscadores (meta title).'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seo_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Descripción SEO'))
      ->setDescription(t('Meta descripción para buscadores y motores IA.'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['og_image'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Imagen Open Graph'))
      ->setDescription(t('URL de la imagen para compartir en redes sociales.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', ['weight' => 27])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schema_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Schema.org JSON-LD'))
      ->setDescription(t('Datos estructurados en formato JSON-LD para SEO.'))
      ->setDisplayOptions('form', ['weight' => 28])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado y métricas ---
    $fields['is_published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publicada'))
      ->setDescription(t('Indica si la landing page es visible públicamente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['views_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visitas'))
      ->setDescription(t('Número total de visitas a la landing page.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversions_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversiones'))
      ->setDescription(t('Número total de conversiones (registros desde la landing).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si la landing page está publicada.
   *
   * Estructura: Método helper que evalúa el campo is_published.
   * Lógica: Devuelve TRUE solo cuando la landing está publicada.
   * Sintaxis: Lectura directa del valor del campo boolean.
   *
   * @return bool
   *   TRUE si la landing page está publicada.
   */
  public function isPublished(): bool {
    return (bool) $this->get('is_published')->value;
  }

  /**
   * Calcula la tasa de conversión de la landing page.
   *
   * Estructura: Método helper que combina views_count y conversions_count.
   * Lógica: Si no hay visitas, devuelve 0.0. En caso contrario,
   *   devuelve el porcentaje de conversiones sobre visitas.
   * Sintaxis: Operación aritmética sobre campos integer.
   *
   * @return float
   *   Porcentaje de conversión (0.0 - 100.0).
   */
  public function getConversionRate(): float {
    $views = (int) $this->get('views_count')->value;
    if ($views <= 0) {
      return 0.0;
    }
    $conversions = (int) $this->get('conversions_count')->value;
    return round(($conversions / $views) * 100, 2);
  }

  /**
   * Incrementa el contador de visitas en 1.
   *
   * Estructura: Método mutador sobre el campo views_count.
   * Lógica: Suma 1 al valor actual de views_count.
   * Sintaxis: Escritura directa sobre campo integer con retorno fluido.
   *
   * @return $this
   */
  public function incrementViews(): self {
    $current = (int) $this->get('views_count')->value;
    $this->set('views_count', $current + 1);
    return $this;
  }

  /**
   * Incrementa el contador de conversiones en 1.
   *
   * Estructura: Método mutador sobre el campo conversions_count.
   * Lógica: Suma 1 al valor actual de conversions_count.
   * Sintaxis: Escritura directa sobre campo integer con retorno fluido.
   *
   * @return $this
   */
  public function incrementConversions(): self {
    $current = (int) $this->get('conversions_count')->value;
    $this->set('conversions_count', $current + 1);
    return $this;
  }

}
