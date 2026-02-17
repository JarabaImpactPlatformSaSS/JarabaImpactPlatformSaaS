<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente entre el vertical ServiciosConecta y el copilot IA.
 *
 * Inyecta contexto especifico del profesional/servicio en BaseAgent
 * para que el copilot ofrezca respuestas relevantes al vertical.
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 2.
 */
class ServiciosConectaCopilotBridgeService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el contexto vertical para inyectar en el copilot.
   */
  public function getVerticalContext(int $userId): array {
    $context = [
      'vertical' => 'serviciosconecta',
      'vertical_label' => 'ServiciosConecta',
      'active_services' => 0,
      'total_bookings' => 0,
      'bookings_this_month' => 0,
      'average_rating' => 0.0,
      'total_reviews' => 0,
      'revenue_this_month' => 0.0,
      'specialties' => [],
      'provider_name' => '',
    ];

    try {
      // Get provider profile for this user.
      $providerIds = $this->entityTypeManager
        ->getStorage('provider_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->range(0, 1)
        ->execute();

      if (empty($providerIds)) {
        return $context;
      }

      $providerId = (int) reset($providerIds);
      $provider = $this->entityTypeManager->getStorage('provider_profile')->load($providerId);

      if (!$provider) {
        return $context;
      }

      $context['provider_name'] = $provider->get('display_name')->value ?? '';
      $context['average_rating'] = (float) ($provider->get('average_rating')->value ?? 0);
      $context['total_reviews'] = (int) ($provider->get('total_reviews')->value ?? 0);

      // Count active services.
      $serviceCount = $this->entityTypeManager
        ->getStorage('service_offering')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->condition('status', 'published')
        ->count()
        ->execute();
      $context['active_services'] = (int) $serviceCount;

      // Count total bookings.
      $totalBookings = $this->entityTypeManager
        ->getStorage('booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->count()
        ->execute();
      $context['total_bookings'] = (int) $totalBookings;

      // Count bookings this month.
      $monthStart = date('Y-m-01\T00:00:00');
      $monthlyBookings = $this->entityTypeManager
        ->getStorage('booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->condition('created', strtotime($monthStart), '>=')
        ->count()
        ->execute();
      $context['bookings_this_month'] = (int) $monthlyBookings;

      // Get specialties from service categories.
      $specialties = [];
      if ($provider->hasField('service_category')) {
        foreach ($provider->get('service_category') as $item) {
          if ($item->entity) {
            $specialties[] = $item->entity->label();
          }
        }
      }
      $context['specialties'] = $specialties;
    }
    catch (\Exception $e) {
      $this->logger->warning('Error loading ServiciosConecta copilot context for user @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Obtiene sugerencias de accion para el profesional.
   */
  public function getSuggestedActions(int $userId): array {
    $context = $this->getVerticalContext($userId);
    $suggestions = [];

    if ($context['active_services'] === 0) {
      $suggestions[] = [
        'action' => 'publish_first_service',
        'message' => 'Publica tu primer servicio para empezar a recibir reservas.',
        'cta_url' => '/mi-servicio/servicios/add',
        'priority' => 10,
      ];
    }

    if ($context['total_bookings'] === 0 && $context['active_services'] > 0) {
      $suggestions[] = [
        'action' => 'share_profile',
        'message' => 'Comparte tu perfil profesional en redes sociales para recibir tus primeras reservas.',
        'cta_url' => '/mi-servicio/perfil',
        'priority' => 20,
      ];
    }

    if ($context['average_rating'] > 0 && $context['average_rating'] < 4.0) {
      $suggestions[] = [
        'action' => 'improve_rating',
        'message' => 'Responde a las resenas de tus clientes para mejorar tu reputacion.',
        'cta_url' => '/mi-servicio/reservas',
        'priority' => 30,
      ];
    }

    if ($context['total_bookings'] > 20 && $context['average_rating'] >= 4.5) {
      $suggestions[] = [
        'action' => 'expand_services',
        'message' => 'Tu consulta tiene excelentes resultados. Considera ampliar tu catalogo de servicios.',
        'cta_url' => '/mi-servicio/servicios/add',
        'priority' => 40,
      ];
    }

    return $suggestions;
  }

  /**
   * Obtiene insights del marketplace relevantes para el profesional.
   */
  public function getMarketInsights(int $userId): array {
    try {
      $totalProviders = (int) $this->entityTypeManager
        ->getStorage('provider_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->condition('verification_status', 'approved')
        ->count()
        ->execute();

      $totalServices = (int) $this->entityTypeManager
        ->getStorage('service_offering')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'published')
        ->count()
        ->execute();

      $context = $this->getVerticalContext($userId);

      return [
        'total_marketplace_providers' => $totalProviders,
        'total_marketplace_services' => $totalServices,
        'user_services' => $context['active_services'],
        'market_share_pct' => $totalServices > 0
          ? round(($context['active_services'] / $totalServices) * 100, 1)
          : 0,
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('Error fetching market insights: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
