<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_servicios_conecta\Service\ProviderService;
use Drupal\jaraba_servicios_conecta\Service\ServiceOfferingService;
use Drupal\jaraba_servicios_conecta\Service\AvailabilityService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller del portal del profesional (/mi-servicio).
 *
 * Estructura: Gestiona las páginas privadas del profesional:
 *   dashboard, servicios, reservas, calendario y perfil.
 *
 * Lógica: Cada método verifica que el usuario actual tenga un
 *   ProviderProfile asociado antes de mostrar datos.
 */
class ProviderPortalController extends ControllerBase {

  /**
   * El servicio de profesionales.
   */
  protected ProviderService $providerService;

  /**
   * El servicio de servicios ofertados.
   */
  protected ServiceOfferingService $offeringService;

  /**
   * El servicio de disponibilidad.
   */
  protected AvailabilityService $availabilityService;

  /**
   * Constructor.
   */
  public function __construct(
    ProviderService $provider_service,
    ServiceOfferingService $offering_service,
    AvailabilityService $availability_service,
  ) {
    $this->providerService = $provider_service;
    $this->offeringService = $offering_service;
    $this->availabilityService = $availability_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_servicios_conecta.provider'),
      $container->get('jaraba_servicios_conecta.service_offering'),
      $container->get('jaraba_servicios_conecta.availability'),
    );
  }

  /**
   * Dashboard principal del profesional.
   */
  public function dashboard(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $upcoming = $this->availabilityService->getUpcomingBookings((int) $provider->id(), 5);

    // KPIs del profesional
    $kpis = [
      'total_bookings' => (int) $provider->get('total_bookings')->value,
      'average_rating' => (float) $provider->get('average_rating')->value,
      'total_reviews' => (int) $provider->get('total_reviews')->value,
      'upcoming_count' => count($upcoming),
    ];

    return [
      '#theme' => 'servicios_provider_dashboard',
      '#provider' => $provider,
      '#kpis' => $kpis,
      '#upcoming_bookings' => $upcoming,
      '#pending_bookings' => [],
      '#recent_reviews' => [],
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/provider-portal'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['provider_profile:' . $provider->id()],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Listado de servicios del profesional.
   */
  public function offerings(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $offerings = $this->offeringService->getProviderOfferings((int) $provider->id());

    return [
      '#theme' => 'servicios_provider_offerings',
      '#provider' => $provider,
      '#offerings' => $offerings,
      '#total' => count($offerings),
      '#current_filters' => [],
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/provider-portal'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['provider_profile:' . $provider->id(), 'service_offering_list'],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Formulario para añadir nuevo servicio.
   */
  public function offeringAdd(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $offering = $this->entityTypeManager()
      ->getStorage('service_offering')
      ->create([
        'provider_id' => $provider->id(),
        'uid' => $this->currentUser()->id(),
      ]);

    $form = $this->entityFormBuilder()->getForm($offering, 'add');
    return $form;
  }

  /**
   * Listado de reservas del profesional.
   */
  public function bookings(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $upcoming = $this->availabilityService->getUpcomingBookings((int) $provider->id(), 20);

    return [
      '#markup' => '<div class="servicios-provider-bookings">'
        . '<h2>' . $this->t('Mis Reservas') . '</h2>'
        . '<p>' . $this->t('@count reservas próximas.', ['@count' => count($upcoming)]) . '</p>'
        . '</div>',
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/provider-portal'],
      ],
    ];
  }

  /**
   * Calendario del profesional.
   */
  public function calendar(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $slots = $this->availabilityService->getProviderSlots((int) $provider->id());

    return [
      '#theme' => 'servicios_provider_calendar',
      '#provider' => $provider,
      '#bookings' => [],
      '#availability_slots' => $slots,
      '#current_week' => date('Y-m-d'),
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/provider-portal'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['provider_profile:' . $provider->id(), 'availability_slot_list'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Perfil del profesional (edición).
   */
  public function profile(): array {
    $provider = $this->providerService->getCurrentUserProvider();
    if (!$provider) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil profesional asociado.'));
    }

    $form = $this->entityFormBuilder()->getForm($provider, 'edit');
    return $form;
  }

}
