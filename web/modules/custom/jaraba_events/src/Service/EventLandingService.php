<?php

namespace Drupal\jaraba_events\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de datos para landing pages automáticas de eventos.
 *
 * ESTRUCTURA:
 * Servicio que construye los datos necesarios para renderizar la landing page
 * de cada evento, incluyendo Schema.org JSON-LD para GEO (Generative Engine
 * Optimization), meta tags SEO, countdown dinámico y eventos relacionados.
 *
 * LÓGICA:
 * Cada evento publicado genera automáticamente una landing page en
 * /eventos/{slug}. Este servicio no renderiza HTML; construye arrays de
 * datos que el controlador pasa a la plantilla Twig. Los datos de Schema.org
 * se inyectan como JSON-LD en el <head> para que los motores de IA
 * (ChatGPT, Claude, Perplexity, Gemini) puedan indexar y citar los eventos.
 *
 * RELACIONES:
 * - EventLandingService -> EntityTypeManager (dependencia)
 * - EventLandingService -> TenantContextService (dependencia)
 * - EventLandingService <- EventFrontendController (consumido por)
 *
 * @package Drupal\jaraba_events\Service
 */
class EventLandingService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var object
   */
  protected $tenantContext;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
  }

  /**
   * Construye los datos completos para la landing page de un evento.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   *
   * @return array
   *   Datos para la plantilla con las siguientes claves:
   *   - 'event' (array): Datos del evento formateados.
   *   - 'schema_org' (array): JSON-LD para Schema.org.
   *   - 'meta_tags' (array): Meta description y Open Graph.
   *   - 'related_events' (array): Eventos relacionados.
   *   - 'countdown' (array): Datos para el countdown (si futuro).
   *   - 'registration_open' (bool): Si acepta registros.
   *   - 'spots_remaining' (int|null): Plazas disponibles.
   */
  public function buildLandingData($event): array {
    $start_date = $event->get('start_date')->value;
    $is_future = $start_date && strtotime($start_date) > time();
    $is_published = $event->get('status_event')->value === 'published';

    $event_data = [
      'id' => $event->id(),
      'title' => $event->get('title')->value,
      'slug' => $event->get('slug')->value,
      'event_type' => $event->get('event_type')->value,
      'format' => $event->get('format')->value,
      'start_date' => $start_date,
      'end_date' => $event->get('end_date')->value,
      'timezone' => $event->get('timezone')->value ?: 'Europe/Madrid',
      'description' => $event->get('description')->value,
      'short_desc' => $event->get('short_desc')->value,
      'speakers' => $event->get('speakers')->value,
      'meeting_url' => $event->get('meeting_url')->uri ?? '',
      'location' => $event->get('location')->value,
      'max_attendees' => (int) $event->get('max_attendees')->value,
      'current_attendees' => (int) $event->get('current_attendees')->value,
      'is_free' => (bool) $event->get('is_free')->value,
      'price' => (float) ($event->get('price')->value ?? 0),
      'early_bird_price' => (float) ($event->get('early_bird_price')->value ?? 0),
      'early_bird_deadline' => $event->get('early_bird_deadline')->value,
      'featured' => (bool) $event->get('featured')->value,
      'image_url' => $this->getImageUrl($event),
    ];

    // Determinar si el early bird está activo
    $early_bird_active = FALSE;
    if ($event_data['early_bird_price'] > 0 && $event_data['early_bird_deadline']) {
      $early_bird_active = strtotime($event_data['early_bird_deadline']) > time();
    }
    $event_data['early_bird_active'] = $early_bird_active;

    // Determinar precio actual
    $event_data['current_price'] = $early_bird_active
      ? $event_data['early_bird_price']
      : $event_data['price'];

    return [
      'event' => $event_data,
      'schema_org' => $this->generateSchemaOrg($event),
      'meta_tags' => $this->generateMetaTags($event),
      'related_events' => $this->getRelatedEvents($event),
      'countdown' => $is_future ? $this->buildCountdown($start_date) : NULL,
      'registration_open' => $is_published && $is_future,
      'spots_remaining' => $event->getSpotsRemaining(),
    ];
  }

  /**
   * Genera el JSON-LD de Schema.org para GEO.
   *
   * LÓGICA:
   * Genera un objeto Event Schema.org con todos los campos requeridos para que
   * los motores de IA (GPTBot, ClaudeBot, PerplexityBot) indexen el evento.
   * Incluye tipo de asistencia (online/offline), oferta de precio, y
   * datos del organizador.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   *
   * @return array
   *   Estructura JSON-LD compatible con Schema.org Event.
   */
  public function generateSchemaOrg($event): array {
    $schema_type = $event->get('schema_type')->value ?: 'Event';
    $format = $event->get('format')->value;

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $schema_type,
      'name' => $event->get('title')->value,
      'description' => $event->get('short_desc')->value ?: $event->get('meta_description')->value,
      'startDate' => $event->get('start_date')->value,
      'eventStatus' => 'https://schema.org/EventScheduled',
    ];

    // Fecha de fin
    if ($event->get('end_date')->value) {
      $schema['endDate'] = $event->get('end_date')->value;
    }

    // Formato de asistencia
    if ($format === 'online') {
      $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
      $schema['location'] = [
        '@type' => 'VirtualLocation',
        'url' => $event->get('meeting_url')->uri ?? '',
      ];
    }
    elseif ($format === 'presencial') {
      $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
      $schema['location'] = [
        '@type' => 'Place',
        'name' => $event->get('location')->value ?: '',
        'address' => $event->get('location')->value ?: '',
      ];
    }
    else {
      $schema['eventAttendanceMode'] = 'https://schema.org/MixedEventAttendanceMode';
    }

    // Imagen
    $image_url = $this->getImageUrl($event);
    if ($image_url) {
      $schema['image'] = $image_url;
    }

    // Oferta de precio
    if ((bool) $event->get('is_free')->value) {
      $schema['isAccessibleForFree'] = TRUE;
      $schema['offers'] = [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
      ];
    }
    else {
      $price = (float) ($event->get('price')->value ?? 0);
      $schema['offers'] = [
        '@type' => 'Offer',
        'price' => number_format($price, 2, '.', ''),
        'priceCurrency' => 'EUR',
        'availability' => 'https://schema.org/InStock',
        'validFrom' => $event->get('created')->value ?? '',
      ];
    }

    // Capacidad
    $max = (int) $event->get('max_attendees')->value;
    if ($max > 0) {
      $schema['maximumAttendeeCapacity'] = $max;
      $schema['remainingAttendeeCapacity'] = $event->getSpotsRemaining() ?? 0;
    }

    // Organizador
    $schema['organizer'] = [
      '@type' => 'Organization',
      'name' => 'Jaraba Impact Platform',
      'url' => \Drupal::request()->getSchemeAndHttpHost(),
    ];

    return $schema;
  }

  /**
   * Obtiene eventos relacionados por tipo y vertical.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Evento de referencia.
   * @param int $limit
   *   Número máximo de eventos relacionados.
   *
   * @return array
   *   Lista de eventos relacionados con datos básicos para tarjetas.
   */
  public function getRelatedEvents($event, int $limit = 3): array {
    $storage = $this->entityTypeManager->getStorage('marketing_event');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_event', 'published')
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=')
      ->condition('id', $event->id(), '<>')
      ->sort('start_date', 'ASC')
      ->range(0, $limit);

    // Filtrar por tenant si tiene tenant_id
    $tenant_id = $event->get('tenant_id')->target_id;
    if ($tenant_id) {
      $query->condition('tenant_id', $tenant_id);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $related = [];
    $events = $storage->loadMultiple($ids);

    foreach ($events as $rel_event) {
      $related[] = [
        'id' => $rel_event->id(),
        'title' => $rel_event->get('title')->value,
        'slug' => $rel_event->get('slug')->value,
        'event_type' => $rel_event->get('event_type')->value,
        'format' => $rel_event->get('format')->value,
        'start_date' => $rel_event->get('start_date')->value,
        'short_desc' => $rel_event->get('short_desc')->value,
        'is_free' => (bool) $rel_event->get('is_free')->value,
        'price' => (float) ($rel_event->get('price')->value ?? 0),
        'image_url' => $this->getImageUrl($rel_event),
        'spots_remaining' => $rel_event->getSpotsRemaining(),
      ];
    }

    return $related;
  }

  /**
   * Genera meta tags para SEO y Open Graph.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   *
   * @return array
   *   Meta tags con claves: description, og_title, og_description, og_image, og_type.
   */
  protected function generateMetaTags($event): array {
    $description = $event->get('meta_description')->value
      ?: $event->get('short_desc')->value
      ?: mb_substr(strip_tags($event->get('description')->value ?? ''), 0, 320);

    return [
      'description' => $description,
      'og_title' => $event->get('title')->value,
      'og_description' => $description,
      'og_image' => $this->getImageUrl($event),
      'og_type' => 'event',
    ];
  }

  /**
   * Construye los datos para el componente countdown.
   *
   * @param string $start_date
   *   Fecha ISO del evento.
   *
   * @return array
   *   Array con days, hours, minutes, seconds, y timestamp unix.
   */
  protected function buildCountdown(string $start_date): array {
    $target = strtotime($start_date);
    $now = time();
    $diff = max(0, $target - $now);

    return [
      'days' => floor($diff / 86400),
      'hours' => floor(($diff % 86400) / 3600),
      'minutes' => floor(($diff % 3600) / 60),
      'seconds' => $diff % 60,
      'timestamp' => $target,
    ];
  }

  /**
   * Obtiene la URL de la imagen principal del evento.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   *
   * @return string|null
   *   URL absoluta de la imagen o NULL si no tiene.
   */
  protected function getImageUrl($event): ?string {
    $image_field = $event->get('image');
    if ($image_field->isEmpty()) {
      return NULL;
    }

    $file = $image_field->entity;
    if ($file) {
      return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }

    return NULL;
  }

}
