<?php

namespace Drupal\jaraba_events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_events\Exception\DuplicateRegistrationException;
use Drupal\jaraba_events\Exception\EventFullException;
use Drupal\jaraba_events\Exception\EventNotOpenException;
use Drupal\jaraba_events\Service\EventAnalyticsService;
use Drupal\jaraba_events\Service\EventLandingService;
use Drupal\jaraba_events\Service\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador frontend para las páginas públicas de eventos.
 *
 * ESTRUCTURA:
 * Controlador que gestiona las páginas públicas (Zero Region) del módulo
 * de eventos: marketplace (/eventos), landing page (/eventos/{slug}),
 * formulario de registro (/eventos/{slug}/registro) y confirmación de
 * double opt-in (/eventos/{slug}/confirmacion/{token}).
 *
 * LÓGICA:
 * Cada método construye un render array con #theme propio que referencia
 * una plantilla Twig sin regiones de Drupal. Los datos se obtienen de los
 * servicios inyectados (landing, registration, analytics). Las bibliotecas
 * CSS/JS se adjuntan mediante #attached.
 *
 * RELACIONES:
 * - EventFrontendController -> EventLandingService (landing data, schema.org)
 * - EventFrontendController -> EventRegistrationService (registro, confirmación)
 * - EventFrontendController -> EventAnalyticsService (métricas para dashboard)
 * - EventFrontendController -> EntityTypeManager (carga de entidades por slug)
 *
 * @package Drupal\jaraba_events\Controller
 */
class EventFrontendController extends ControllerBase {

  /**
   * Servicio de datos para landing pages de eventos.
   *
   * @var \Drupal\jaraba_events\Service\EventLandingService|null
   */
  protected ?EventLandingService $landingService = NULL;

  /**
   * Servicio de registro de asistentes a eventos.
   *
   * @var \Drupal\jaraba_events\Service\EventRegistrationService|null
   */
  protected ?EventRegistrationService $registrationService = NULL;

  /**
   * Servicio de analítica de eventos.
   *
   * @var \Drupal\jaraba_events\Service\EventAnalyticsService|null
   */
  protected ?EventAnalyticsService $analyticsService = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->landingService = $container->get('jaraba_events.landing');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->registrationService = $container->get('jaraba_events.registration');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->analyticsService = $container->get('jaraba_events.analytics');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    return $instance;
  }

  /**
   * Página de marketplace de eventos (/eventos).
   *
   * LÓGICA:
   * Lista los eventos publicados y futuros del tenant actual, ordenados
   * por fecha de inicio ascendente. Soporta filtros por tipo de evento,
   * formato y búsqueda libre vía parámetros GET. La paginación se
   * controla con los parámetros page y limit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La solicitud HTTP con parámetros de filtro opcionales:
   *   - 'type' (string): Filtrar por event_type.
   *   - 'format' (string): Filtrar por formato (online, presencial, hibrido).
   *   - 'q' (string): Búsqueda por título o descripción.
   *   - 'page' (int): Página actual para paginación.
   *
   * @return array
   *   Render array con #theme 'jaraba_events_marketplace'.
   */
  public function marketplace(Request $request): array {
    $storage = $this->entityTypeManager()->getStorage('marketing_event');

    // Consulta base: eventos publicados y futuros
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_event', 'published')
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=')
      ->sort('featured', 'DESC')
      ->sort('start_date', 'ASC');

    // Filtro por tipo de evento
    $type_filter = $request->query->get('type');
    if ($type_filter && in_array($type_filter, [
      'webinar', 'workshop', 'conference', 'meetup', 'demo', 'networking',
    ])) {
      $query->condition('event_type', $type_filter);
    }

    // Filtro por formato
    $format_filter = $request->query->get('format');
    if ($format_filter && in_array($format_filter, [
      'online', 'presencial', 'hibrido',
    ])) {
      $query->condition('format', $format_filter);
    }

    // Paginación
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = 12;
    $query->range($page * $limit, $limit);

    // Contar total para paginación
    $count_query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_event', 'published')
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=');

    if ($type_filter) {
      $count_query->condition('event_type', $type_filter);
    }
    if ($format_filter) {
      $count_query->condition('format', $format_filter);
    }

    $total = (int) $count_query->count()->execute();
    $total_pages = (int) ceil($total / $limit);

    // Cargar eventos
    $ids = $query->execute();
    $events = !empty($ids) ? $storage->loadMultiple($ids) : [];

    // Construir datos de tarjetas
    $event_cards = [];
    foreach ($events as $event) {
      $event_cards[] = [
        'id' => $event->id(),
        'title' => $event->get('title')->value,
        'slug' => $event->get('slug')->value,
        'event_type' => $event->get('event_type')->value,
        'format' => $event->get('format')->value,
        'start_date' => $event->get('start_date')->value,
        'short_desc' => $event->get('short_desc')->value,
        'is_free' => (bool) $event->get('is_free')->value,
        'price' => (float) ($event->get('price')->value ?? 0),
        'featured' => (bool) $event->get('featured')->value,
        'image_url' => $this->landingService
          ? $this->getEventImageUrl($event)
          : NULL,
        'spots_remaining' => $event->getSpotsRemaining(),
      ];
    }

    // Obtener eventos destacados (máximo 3)
    $featured = array_filter($event_cards, fn($e) => $e['featured']);
    $featured = array_slice($featured, 0, 3);

    // Tipos disponibles para filtro
    $event_types = [
      'webinar' => $this->t('Webinar'),
      'workshop' => $this->t('Taller'),
      'conference' => $this->t('Conferencia'),
      'meetup' => $this->t('Meetup'),
      'demo' => $this->t('Demo'),
      'networking' => $this->t('Networking'),
    ];

    $formats = [
      'online' => $this->t('Online'),
      'presencial' => $this->t('Presencial'),
      'hibrido' => $this->t('Híbrido'),
    ];

    return [
      '#theme' => 'jaraba_events_marketplace',
      '#events' => $event_cards,
      '#featured_events' => $featured,
      '#event_types' => $event_types,
      '#formats' => $formats,
      '#active_type' => $type_filter,
      '#active_format' => $format_filter,
      '#pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_events' => $total,
        'per_page' => $limit,
      ],
      '#attached' => [
        'library' => [
          'jaraba_events/events-marketplace',
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Landing page individual de un evento (/eventos/{slug}).
   *
   * LÓGICA:
   * Carga el evento por slug, construye los datos de landing mediante
   * EventLandingService (incluye Schema.org JSON-LD para GEO, meta tags,
   * countdown y eventos relacionados) y renderiza la plantilla.
   *
   * @param string $slug
   *   Slug URL-friendly del evento.
   *
   * @return array
   *   Render array con #theme 'jaraba_events_landing'.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si no existe un evento publicado con ese slug.
   */
  public function eventLanding(string $slug): array {
    $event = $this->loadEventBySlug($slug);

    if (!$event) {
      throw new NotFoundHttpException();
    }

    // Construir datos de landing completos
    $landing_data = $this->landingService
      ? $this->landingService->buildLandingData($event)
      : ['event' => [], 'schema_org' => [], 'meta_tags' => [], 'related_events' => [], 'countdown' => NULL, 'registration_open' => FALSE, 'spots_remaining' => 0];

    // Inyectar JSON-LD de Schema.org en <head> para GEO
    $schema_json = json_encode($landing_data['schema_org'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $build = [
      '#theme' => 'jaraba_events_landing',
      '#event' => $landing_data['event'],
      '#schema_org' => $landing_data['schema_org'],
      '#meta_tags' => $landing_data['meta_tags'],
      '#related_events' => $landing_data['related_events'],
      '#countdown' => $landing_data['countdown'],
      '#registration_open' => $landing_data['registration_open'],
      '#spots_remaining' => $landing_data['spots_remaining'],
      '#attached' => [
        'library' => [
          'jaraba_events/event-landing',
        ],
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => ['type' => 'application/ld+json'],
              '#value' => $schema_json,
            ],
            'jaraba_events_schema_org',
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'tags' => ['marketing_event:' . $event->id()],
      ],
    ];

    // Meta tags en <head>
    if (!empty($landing_data['meta_tags'])) {
      $meta = $landing_data['meta_tags'];
      $head_tags = [];

      if (!empty($meta['description'])) {
        $head_tags[] = [
          ['#type' => 'html_tag', '#tag' => 'meta', '#attributes' => ['name' => 'description', 'content' => $meta['description']]],
          'jaraba_events_meta_description',
        ];
      }
      if (!empty($meta['og_title'])) {
        $head_tags[] = [
          ['#type' => 'html_tag', '#tag' => 'meta', '#attributes' => ['property' => 'og:title', 'content' => $meta['og_title']]],
          'jaraba_events_og_title',
        ];
      }
      if (!empty($meta['og_description'])) {
        $head_tags[] = [
          ['#type' => 'html_tag', '#tag' => 'meta', '#attributes' => ['property' => 'og:description', 'content' => $meta['og_description']]],
          'jaraba_events_og_description',
        ];
      }
      if (!empty($meta['og_image'])) {
        $head_tags[] = [
          ['#type' => 'html_tag', '#tag' => 'meta', '#attributes' => ['property' => 'og:image', 'content' => $meta['og_image']]],
          'jaraba_events_og_image',
        ];
      }
      $head_tags[] = [
        ['#type' => 'html_tag', '#tag' => 'meta', '#attributes' => ['property' => 'og:type', 'content' => 'event']],
        'jaraba_events_og_type',
      ];

      $build['#attached']['html_head'] = array_merge(
        $build['#attached']['html_head'],
        $head_tags
      );
    }

    return $build;
  }

  /**
   * Formulario de registro público para un evento (/eventos/{slug}/registro).
   *
   * LÓGICA:
   * Si la solicitud es GET, renderiza el formulario de registro con los datos
   * del evento (precio, plazas, early bird). Si es POST, procesa el registro
   * mediante EventRegistrationService y redirige a la confirmación.
   *
   * @param string $slug
   *   Slug URL-friendly del evento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La solicitud HTTP (GET o POST).
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array con el formulario o redirección a confirmación.
   */
  public function registerForm(string $slug, Request $request): array {
    $event = $this->loadEventBySlug($slug);

    if (!$event) {
      throw new NotFoundHttpException();
    }

    // Verificar que el evento acepta registros
    $is_published = $event->get('status_event')->value === 'published';
    $start_date = $event->get('start_date')->value;
    $is_future = $start_date && strtotime($start_date) > time();

    if (!$is_published || !$is_future) {
      $this->messenger()->addWarning($this->t('This event is not currently accepting registrations.'));
    }

    // Datos del evento para el formulario
    $event_data = [
      'id' => $event->id(),
      'title' => $event->get('title')->value,
      'slug' => $event->get('slug')->value,
      'event_type' => $event->get('event_type')->value,
      'format' => $event->get('format')->value,
      'start_date' => $start_date,
      'end_date' => $event->get('end_date')->value,
      'is_free' => (bool) $event->get('is_free')->value,
      'price' => (float) ($event->get('price')->value ?? 0),
      'early_bird_price' => (float) ($event->get('early_bird_price')->value ?? 0),
      'early_bird_deadline' => $event->get('early_bird_deadline')->value,
      'spots_remaining' => $event->getSpotsRemaining(),
      'location' => $event->get('location')->value,
    ];

    // Determinar precio actual
    $early_bird_active = FALSE;
    if ($event_data['early_bird_price'] > 0 && $event_data['early_bird_deadline']) {
      $early_bird_active = strtotime($event_data['early_bird_deadline']) > time();
    }
    $event_data['early_bird_active'] = $early_bird_active;
    $event_data['current_price'] = $early_bird_active
      ? $event_data['early_bird_price']
      : $event_data['price'];

    // Variables para manejar errores y éxito
    $form_errors = [];
    $registration_success = FALSE;
    $submitted_data = [];

    // Procesar POST si hay datos
    if ($request->isMethod('POST') && $this->registrationService) {
      $submitted_data = [
        'name' => trim($request->request->get('name', '')),
        'email' => trim($request->request->get('email', '')),
        'phone' => trim($request->request->get('phone', '')),
        'source' => 'web',
        'utm_source' => $request->query->get('utm_source', ''),
      ];

      // Validación básica
      if (empty($submitted_data['name'])) {
        $form_errors['name'] = $this->t('Name is required.');
      }
      if (empty($submitted_data['email']) || !filter_var($submitted_data['email'], FILTER_VALIDATE_EMAIL)) {
        $form_errors['email'] = $this->t('A valid email address is required.');
      }

      if (empty($form_errors)) {
        try {
          $registration = $this->registrationService->register(
            (int) $event->id(),
            $submitted_data
          );
          $registration_success = TRUE;
        }
        catch (EventNotOpenException $e) {
          $form_errors['general'] = $this->t('This event is not currently accepting registrations.');
        }
        catch (DuplicateRegistrationException $e) {
          $form_errors['email'] = $this->t('This email is already registered for this event.');
        }
        catch (EventFullException $e) {
          $form_errors['general'] = $this->t('This event is full. You have been added to the waiting list.');
        }
      }
    }

    return [
      '#theme' => 'jaraba_events_register',
      '#event' => $event_data,
      '#form_errors' => $form_errors,
      '#registration_success' => $registration_success,
      '#submitted_data' => $submitted_data,
      '#registration_open' => $is_published && $is_future,
      '#attached' => [
        'library' => [
          'jaraba_events/event-registration',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Página de confirmación de registro (/eventos/{slug}/confirmacion/{token}).
   *
   * LÓGICA:
   * Confirma el registro mediante el token de double opt-in y muestra
   * la página de confirmación con los datos del ticket.
   *
   * @param string $slug
   *   Slug URL-friendly del evento.
   * @param string $token
   *   Token de confirmación de 64 caracteres hex.
   *
   * @return array
   *   Render array con #theme 'jaraba_events_confirmation'.
   */
  public function confirmRegistration(string $slug, string $token): array {
    $event = $this->loadEventBySlug($slug);

    if (!$event) {
      throw new NotFoundHttpException();
    }

    $confirmed = FALSE;
    $registration_data = [];
    $error_message = '';

    if ($this->registrationService) {
      try {
        $registration = $this->registrationService->confirmByToken($token);
        $confirmed = TRUE;

        $registration_data = [
          'attendee_name' => $registration->get('attendee_name')->value,
          'attendee_email' => $registration->get('attendee_email')->value,
          'ticket_code' => $registration->get('ticket_code')->value,
          'registration_status' => $registration->get('registration_status')->value,
        ];
      }
      catch (\RuntimeException $e) {
        $error_message = $this->t('Invalid or expired confirmation token.');
      }
    }

    $event_data = [
      'title' => $event->get('title')->value,
      'slug' => $event->get('slug')->value,
      'start_date' => $event->get('start_date')->value,
      'end_date' => $event->get('end_date')->value,
      'format' => $event->get('format')->value,
      'location' => $event->get('location')->value,
      'meeting_url' => $event->get('meeting_url')->uri ?? '',
    ];

    return [
      '#theme' => 'jaraba_events_confirmation',
      '#event' => $event_data,
      '#confirmed' => $confirmed,
      '#registration' => $registration_data,
      '#error_message' => $error_message,
      '#attached' => [
        'library' => [
          'jaraba_events/event-confirmation',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Carga un evento de marketing por su slug.
   *
   * @param string $slug
   *   Slug URL-friendly del evento.
   *
   * @return \Drupal\jaraba_events\Entity\MarketingEvent|null
   *   La entidad del evento o NULL si no existe.
   */
  protected function loadEventBySlug(string $slug) {
    $storage = $this->entityTypeManager()->getStorage('marketing_event');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('slug', $slug)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene la URL de la imagen principal de un evento.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   *
   * @return string|null
   *   URL absoluta de la imagen o NULL.
   */
  protected function getEventImageUrl($event): ?string {
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
