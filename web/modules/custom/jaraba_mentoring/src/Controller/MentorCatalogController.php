<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_mentoring\Service\MentorMatchingService;

/**
 * Controller for the mentor catalog frontend.
 */
class MentorCatalogController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    protected MentorMatchingService $matchingService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
          $container->get('jaraba_mentoring.mentor_matching'),
      );
  }

  /**
   * Displays the mentor catalog.
   *
   * @return array
   *   Render array.
   */
  public function catalog(): array {
    $storage = $this->entityTypeManager()->getStorage('mentor_profile');

    // Get active mentors.
    $pageSize = (int) $this->config('jaraba_mentoring.settings')->get('catalog_page_size') ?: 20;
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('is_available', TRUE)
      ->sort('average_rating', 'DESC')
      ->range(0, $pageSize);

    $mentor_ids = $query->execute();
    $mentors = $storage->loadMultiple($mentor_ids);

    $mentor_cards = [];
    foreach ($mentors as $mentor) {
      /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile $mentor */
      $sectors = array_column($mentor->get('sectors')->getValue(), 'value');

      $mentor_cards[] = [
        'id' => $mentor->id(),
        'display_name' => $mentor->getDisplayName(),
        'headline' => $mentor->getHeadline(),
        'sectors' => $sectors,
        'certification_level' => $mentor->getCertificationLevel(),
        'average_rating' => number_format($mentor->getAverageRating(), 1),
        'total_sessions' => $mentor->get('total_sessions')->value ?? 0,
        'hourly_rate' => number_format($mentor->getHourlyRate(), 0),
        'is_available' => $mentor->isAvailable(),
        'url' => Url::fromRoute('jaraba_mentoring.mentor_public_profile', ['mentor_profile' => $mentor->id()])->toString(),
      ];
    }

    // Get filter options.
    $sector_options = [
      'comercio' => $this->t('Comercio Local'),
      'servicios' => $this->t('Servicios Profesionales'),
      'agro' => $this->t('Agroalimentario'),
      'hosteleria' => $this->t('Hostelería y Turismo'),
      'industria' => $this->t('Industria'),
      'tech' => $this->t('Tecnología'),
    ];

    $stage_options = [
      'idea' => $this->t('Idea / Validación'),
      'lanzamiento' => $this->t('Lanzamiento'),
      'crecimiento' => $this->t('Crecimiento'),
      'escalado' => $this->t('Escalado'),
      'consolidacion' => $this->t('Consolidación'),
    ];

    return [
      '#theme' => 'mentor_catalog',
      '#mentors' => $mentor_cards,
      '#filters' => [
        'sectors' => $sector_options,
        'stages' => $stage_options,
      ],
      '#empty_message' => $this->t('No hay mentores disponibles en este momento.'),
      '#attached' => [
        'library' => [
          'jaraba_mentoring/mentor_catalog',
        ],
      ],
      '#cache' => [
        'tags' => ['mentor_profile_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Sprint B — Pilar 3: Catálogo público de servicios profesionales.
   *
   * Ruta: /servicios-profesionales (acceso público).
   * Muestra los MentoringPackage entities publicados con precio,
   * descripción y CTA de contacto/reserva.
   *
   * ZERO-REGION-001: Devuelve render array simple.
   * NO-HARDCODE-PRICE-001: Precios desde entities.
   */
  public function serviceCatalog(): array {
    $storage = $this->entityTypeManager()->getStorage('mentoring_package');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_published', TRUE)
      ->sort('price', 'ASC')
      ->execute();

    $packages = [];
    foreach ($storage->loadMultiple($ids) as $entity) {
      $packages[] = [
        'id' => (int) $entity->id(),
        'title' => $entity->get('title')->value ?? '',
        'type' => $entity->get('package_type')->value ?? 'single_session',
        'price' => (float) ($entity->get('price')->value ?? 0),
        'sessions' => (int) ($entity->get('sessions_included')->value ?? 1),
        'duration_minutes' => (int) ($entity->get('session_duration_minutes')->value ?? 45),
        'description' => $entity->get('description')->value ?? '',
      ];
    }

    return [
      '#theme' => 'service_catalog',
      '#packages' => $packages,
      '#cache' => [
        'tags' => ['mentoring_package_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Sprint B — Mis servicios contratados.
   *
   * Ruta: /mis-servicios (autenticado).
   * Muestra los MentoringEngagement del usuario actual con estado,
   * sesiones usadas/restantes y próxima sesión.
   */
  public function myServices(): array {
    $uid = (int) $this->currentUser()->id();
    $storage = $this->entityTypeManager()->getStorage('mentoring_engagement');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('mentee_id', $uid)
      ->sort('created', 'DESC')
      ->execute();

    $bookings = [];
    foreach ($storage->loadMultiple($ids) as $entity) {
      $bookings[] = [
        'id' => (int) $entity->id(),
        'status' => $entity->get('status')->value ?? 'pending',
        'sessions_total' => (int) ($entity->get('sessions_total')->value ?? 0),
        'sessions_used' => (int) ($entity->get('sessions_used')->value ?? 0),
        'start_date' => $entity->get('start_date')->value ?? '',
        'expiry_date' => $entity->get('expiry_date')->value ?? '',
      ];
    }

    return [
      '#theme' => 'my_services',
      '#bookings' => $bookings,
      '#empty_message' => $this->t('Aún no tienes servicios contratados.'),
      '#catalog_url' => Url::fromRoute('jaraba_mentoring.service_catalog')->toString(),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
