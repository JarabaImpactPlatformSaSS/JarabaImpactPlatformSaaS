<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de health score para el vertical ServiciosConecta.
 *
 * Calcula la salud del profesional en 5 dimensiones ponderadas
 * y 8 KPIs agregados del vertical.
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 10.
 */
class ServiciosConectaHealthScoreService {

  protected const DIMENSIONS = [
    'profile_completeness' => ['weight' => 0.20, 'label' => 'Completitud de perfil'],
    'booking_activity' => ['weight' => 0.30, 'label' => 'Actividad de reservas'],
    'client_satisfaction' => ['weight' => 0.25, 'label' => 'Satisfaccion del cliente'],
    'copilot_usage' => ['weight' => 0.10, 'label' => 'Uso del copilot'],
    'marketplace_presence' => ['weight' => 0.15, 'label' => 'Presencia en marketplace'],
  ];

  protected const KPI_TARGETS = [
    'booking_completion_rate' => 85,
    'average_rating' => 4.5,
    'time_to_first_booking' => 14,
    'activation_rate' => 60,
    'engagement_rate' => 50,
    'nps' => 50,
    'arpu' => 25,
    'churn_rate' => 5,
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  public function calculateUserHealth(int $userId): array {
    $dimensions = [];
    $overallScore = 0.0;

    foreach (self::DIMENSIONS as $key => $config) {
      $score = $this->calculateDimension($userId, $key);
      $dimensions[$key] = [
        'label' => $config['label'],
        'weight' => $config['weight'],
        'score' => $score,
        'weighted_score' => $score * $config['weight'],
      ];
      $overallScore += $score * $config['weight'];
    }

    $overallScore = round($overallScore, 1);

    $category = match (TRUE) {
      $overallScore >= 80 => 'healthy',
      $overallScore >= 60 => 'neutral',
      $overallScore >= 40 => 'at_risk',
      default => 'critical',
    };

    return [
      'user_id' => $userId,
      'overall_score' => $overallScore,
      'category' => $category,
      'dimensions' => $dimensions,
    ];
  }

  protected function calculateDimension(int $userId, string $dimension): float {
    try {
      return match ($dimension) {
        'profile_completeness' => $this->calcProfileCompleteness($userId),
        'booking_activity' => $this->calcBookingActivity($userId),
        'client_satisfaction' => $this->calcClientSatisfaction($userId),
        'copilot_usage' => $this->calcCopilotUsage($userId),
        'marketplace_presence' => $this->calcMarketplacePresence($userId),
        default => 0.0,
      };
    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating @dim for user @user: @error', [
        '@dim' => $dimension,
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  protected function calcProfileCompleteness(int $userId): float {
    $providerIds = $this->entityTypeManager->getStorage('provider_profile')
      ->getQuery()->accessCheck(FALSE)->condition('user_id', $userId)->range(0, 1)->execute();
    if (empty($providerIds)) return 0.0;
    $provider = $this->entityTypeManager->getStorage('provider_profile')->load(reset($providerIds));
    if (!$provider) return 0.0;

    $score = 0.0;
    // Photo (20 pts)
    if ($provider->hasField('photo') && !$provider->get('photo')->isEmpty()) $score += 20;
    // Bio (20 pts)
    if ($provider->hasField('bio') && !empty($provider->get('bio')->value)) $score += 20;
    // Specialties (20 pts)
    if ($provider->hasField('service_category') && !$provider->get('service_category')->isEmpty()) $score += 20;
    // Credentials (20 pts)
    if ($provider->hasField('credentials') && !empty($provider->get('credentials')->value)) $score += 20;
    // Schedule configured (20 pts)
    $slotsCount = (int) $this->entityTypeManager->getStorage('availability_slot')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', reset($providerIds))->condition('is_active', TRUE)->count()->execute();
    if ($slotsCount > 0) $score += 20;

    return min(100.0, $score);
  }

  protected function calcBookingActivity(int $userId): float {
    $providerIds = $this->entityTypeManager->getStorage('provider_profile')
      ->getQuery()->accessCheck(FALSE)->condition('user_id', $userId)->range(0, 1)->execute();
    if (empty($providerIds)) return 0.0;
    $providerId = reset($providerIds);

    // Bookings last 30 days (40 pts)
    $monthAgo = date('Y-m-d\TH:i:s', strtotime('-30 days'));
    $recentBookings = (int) $this->entityTypeManager->getStorage('booking')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->condition('created', strtotime($monthAgo), '>=')->count()->execute();
    $bookingScore = min(40.0, ($recentBookings / 10) * 40);

    // Completion rate (30 pts)
    $totalBookings = (int) $this->entityTypeManager->getStorage('booking')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->count()->execute();
    $completedBookings = (int) $this->entityTypeManager->getStorage('booking')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->condition('status', 'completed')->count()->execute();
    $completionRate = $totalBookings > 0 ? ($completedBookings / $totalBookings) : 0;
    $completionScore = $completionRate * 30;

    // Revenue trend (30 pts) — simplified: bookings growth indicator
    $twoMonthsAgo = date('Y-m-d\TH:i:s', strtotime('-60 days'));
    $olderBookings = (int) $this->entityTypeManager->getStorage('booking')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)
      ->condition('created', strtotime($twoMonthsAgo), '>=')->condition('created', strtotime($monthAgo), '<')->count()->execute();
    $growthScore = ($olderBookings > 0 && $recentBookings > $olderBookings) ? 30.0 : ($recentBookings > 0 ? 15.0 : 0.0);

    return min(100.0, $bookingScore + $completionScore + $growthScore);
  }

  protected function calcClientSatisfaction(int $userId): float {
    $providerIds = $this->entityTypeManager->getStorage('provider_profile')
      ->getQuery()->accessCheck(FALSE)->condition('user_id', $userId)->range(0, 1)->execute();
    if (empty($providerIds)) return 0.0;
    $provider = $this->entityTypeManager->getStorage('provider_profile')->load(reset($providerIds));
    if (!$provider) return 0.0;

    // Average rating (40 pts)
    $rating = (float) ($provider->get('average_rating')->value ?? 0);
    $ratingScore = ($rating / 5.0) * 40;

    // Reviews responded (30 pts) — simplified
    $totalReviews = (int) ($provider->get('total_reviews')->value ?? 0);
    $reviewScore = $totalReviews > 0 ? min(30.0, ($totalReviews / 5) * 30) : 0;

    // Response time (30 pts) — simplified placeholder
    $responseScore = $totalReviews > 0 ? 20.0 : 0.0;

    return min(100.0, $ratingScore + $reviewScore + $responseScore);
  }

  protected function calcCopilotUsage(int $userId): float {
    // Placeholder — will integrate with copilot telemetry in future
    return 0.0;
  }

  protected function calcMarketplacePresence(int $userId): float {
    $providerIds = $this->entityTypeManager->getStorage('provider_profile')
      ->getQuery()->accessCheck(FALSE)->condition('user_id', $userId)->range(0, 1)->execute();
    if (empty($providerIds)) return 0.0;
    $providerId = reset($providerIds);

    // Services published (30 pts)
    $servicesCount = (int) $this->entityTypeManager->getStorage('service_offering')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->condition('status', 'published')->count()->execute();
    $servicesScore = min(30.0, ($servicesCount / 5) * 30);

    // Verification status (20 pts)
    $provider = $this->entityTypeManager->getStorage('provider_profile')->load($providerId);
    $verificationScore = ($provider && $provider->get('verification_status')->value === 'approved') ? 20.0 : 0.0;

    // Active availability (20 pts)
    $slotsCount = (int) $this->entityTypeManager->getStorage('availability_slot')
      ->getQuery()->accessCheck(FALSE)->condition('provider_id', $providerId)->condition('is_active', TRUE)->count()->execute();
    $availabilityScore = $slotsCount > 0 ? 20.0 : 0.0;

    // SEO score placeholder (30 pts)
    $seoScore = $servicesCount > 0 ? 15.0 : 0.0;

    return min(100.0, $servicesScore + $verificationScore + $availabilityScore + $seoScore);
  }

  public function calculateVerticalKpis(): array {
    $kpis = [];
    foreach (self::KPI_TARGETS as $key => $target) {
      $value = $this->calculateKpi($key);
      $unit = $this->getKpiUnit($key);
      $status = match (TRUE) {
        $key === 'churn_rate' || $key === 'time_to_first_booking' => $value <= $target ? 'on_track' : ($value <= $target * 1.5 ? 'behind' : 'critical'),
        default => $value >= $target ? 'on_track' : ($value >= $target * 0.7 ? 'behind' : 'critical'),
      };
      $kpis[$key] = [
        'value' => $value,
        'unit' => $unit,
        'target' => $target,
        'status' => $status,
        'label' => $this->getKpiLabel($key),
      ];
    }
    return $kpis;
  }

  protected function calculateKpi(string $key): float {
    // Simplified — in production these would query real data
    return 0.0;
  }

  protected function getKpiUnit(string $key): string {
    return match ($key) {
      'booking_completion_rate', 'activation_rate', 'engagement_rate', 'churn_rate' => '%',
      'average_rating' => 'stars',
      'time_to_first_booking' => 'days',
      'nps' => 'score',
      'arpu' => 'EUR/month',
      default => '',
    };
  }

  protected function getKpiLabel(string $key): string {
    return match ($key) {
      'booking_completion_rate' => 'Tasa de completacion de reservas',
      'average_rating' => 'Rating medio de profesionales',
      'time_to_first_booking' => 'Tiempo hasta primera reserva',
      'activation_rate' => 'Tasa de activacion',
      'engagement_rate' => 'Tasa de engagement',
      'nps' => 'Net Promoter Score',
      'arpu' => 'Ingreso medio por usuario',
      'churn_rate' => 'Tasa de abandono',
      default => $key,
    };
  }

}
