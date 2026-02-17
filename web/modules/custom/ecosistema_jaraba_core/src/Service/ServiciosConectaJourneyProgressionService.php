<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Journey progression service para el vertical ServiciosConecta.
 *
 * Evalua reglas proactivas que determinan cuando y como el copiloto
 * debe intervenir para guiar al usuario en su recorrido marketplace.
 * Las reglas se basan en el estado del journey y la actividad del usuario.
 *
 * Reglas proactivas (8 profesional + 2 cliente_servicios):
 * - incomplete_profile: Perfil incompleto (profesional, discovery)
 * - no_services: 0 servicios 3 dias (profesional, discovery)
 * - no_availability: Servicios sin slots (profesional, activation)
 * - first_booking_nudge: Slots sin reservas 7 dias (profesional, activation)
 * - review_response: Resenas sin responder 3 dias (profesional, engagement)
 * - services_limit_approaching: 67% limite servicios (profesional, conversion)
 * - upgrade_professional: Starter alto volumen (profesional, conversion)
 * - b2b_expansion: Nivel excelencia (profesional, retention)
 * - booking_abandoned: Reserva iniciada 24h (cliente_servicios, activation)
 * - rebooking_nudge: Ultima reserva >30 dias (cliente_servicios, retention)
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 9.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService
 */
class ServiciosConectaJourneyProgressionService {

  /**
   * Reglas proactivas de intervencion.
   */
  protected const PROACTIVE_RULES = [
    // =============================================
    // PROFESIONAL RULES (8).
    // =============================================
    'incomplete_profile' => [
      'state' => 'discovery',
      'role' => 'profesional',
      'condition' => 'profile_incomplete',
      'message' => 'Completa tu perfil profesional para aparecer en el marketplace.',
      'cta_label' => 'Completar Perfil',
      'cta_url' => '/mi-servicio/perfil',
      'channel' => 'fab_expand',
      'mode' => 'profile_coach',
      'priority' => 10,
    ],
    'no_services' => [
      'state' => 'discovery',
      'role' => 'profesional',
      'condition' => 'zero_services_3_days',
      'message' => 'Publica tu primer servicio y empieza a recibir reservas.',
      'cta_label' => 'Publicar Servicio',
      'cta_url' => '/mi-servicio/servicios/add',
      'channel' => 'fab_dot',
      'mode' => 'quote_assistant',
      'priority' => 20,
    ],
    'no_availability' => [
      'state' => 'activation',
      'role' => 'profesional',
      'condition' => 'services_no_slots',
      'message' => 'Configura tu horario de disponibilidad para recibir reservas.',
      'cta_label' => 'Configurar Horario',
      'cta_url' => '/mi-servicio/calendario',
      'channel' => 'fab_expand',
      'mode' => 'schedule_optimizer',
      'priority' => 30,
    ],
    'first_booking_nudge' => [
      'state' => 'activation',
      'role' => 'profesional',
      'condition' => 'slots_no_bookings_7_days',
      'message' => 'Comparte tu perfil en redes sociales para recibir tus primeras reservas.',
      'cta_label' => 'Compartir Perfil',
      'cta_url' => '/mi-servicio/perfil',
      'channel' => 'fab_badge',
      'mode' => 'marketing_advisor',
      'priority' => 40,
    ],
    'review_response' => [
      'state' => 'engagement',
      'role' => 'profesional',
      'condition' => 'unanswered_reviews_3_days',
      'message' => 'Tienes resenas sin responder. Responde para mejorar tu reputacion.',
      'cta_label' => 'Ver Resenas',
      'cta_url' => '/mi-servicio/reservas',
      'channel' => 'fab_dot',
      'mode' => 'review_responder',
      'priority' => 50,
    ],
    'services_limit_approaching' => [
      'state' => 'conversion',
      'role' => 'profesional',
      'condition' => 'services_67_percent',
      'message' => 'Tu catalogo de servicios esta casi lleno. Pasa a Starter y ofrece hasta 10.',
      'cta_label' => 'Ver Planes',
      'cta_url' => '/billing/upgrade',
      'channel' => 'fab_expand',
      'mode' => 'faq',
      'priority' => 60,
    ],
    'upgrade_professional' => [
      'state' => 'conversion',
      'role' => 'profesional',
      'condition' => 'starter_high_volume',
      'message' => 'Tu volumen de reservas crece. Pasa a Profesional para desbloquear IA, firma digital y buzon cifrado.',
      'cta_label' => 'Actualizar Plan',
      'cta_url' => '/billing/upgrade',
      'channel' => 'fab_expand',
      'mode' => 'faq',
      'priority' => 70,
    ],
    'b2b_expansion' => [
      'state' => 'retention',
      'role' => 'profesional',
      'condition' => 'excellence_level',
      'message' => 'Tu consulta tiene nivel de excelencia. Explora oportunidades de expansion con emprendimiento.',
      'cta_label' => 'Explorar',
      'cta_url' => '/emprender',
      'channel' => 'fab_dot',
      'mode' => 'marketing_advisor',
      'priority' => 80,
    ],
    // =============================================
    // CLIENTE_SERVICIOS RULES (2).
    // =============================================
    'booking_abandoned' => [
      'state' => 'activation',
      'role' => 'cliente_servicios',
      'condition' => 'booking_started_24h',
      'message' => 'Tu reserva esta pendiente. Completa el proceso antes de que se agote la disponibilidad.',
      'cta_label' => 'Completar Reserva',
      'cta_url' => '/servicios',
      'channel' => 'fab_dot',
      'mode' => 'faq',
      'priority' => 15,
    ],
    'rebooking_nudge' => [
      'state' => 'retention',
      'role' => 'cliente_servicios',
      'condition' => 'last_booking_30_days',
      'message' => 'Descubre las novedades de tus profesionales favoritos.',
      'cta_label' => 'Ver Profesionales',
      'cta_url' => '/servicios',
      'channel' => 'fab_badge',
      'mode' => 'faq',
      'priority' => 25,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly StateInterface $state,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evalua reglas proactivas para un usuario.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Primera regla que aplica con 'rule_id' anadido, o NULL.
   */
  public function evaluate(int $userId): ?array {
    $dismissed = $this->getDismissedRules($userId);

    $sortedRules = self::PROACTIVE_RULES;
    uasort($sortedRules, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

    foreach ($sortedRules as $ruleId => $rule) {
      if (in_array($ruleId, $dismissed, TRUE)) {
        continue;
      }
      if ($this->evaluateCondition($userId, $rule['condition'])) {
        return array_merge($rule, ['rule_id' => $ruleId]);
      }
    }

    return NULL;
  }

  /**
   * Obtiene la accion pendiente cacheada (1h TTL).
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Regla pendiente o NULL.
   */
  public function getPendingAction(int $userId): ?array {
    $stateKey = "serviciosconecta_proactive_pending_{$userId}";
    $cached = $this->state->get($stateKey);

    if ($cached) {
      $age = \Drupal::time()->getRequestTime() - ($cached['evaluated_at'] ?? 0);
      if ($age < 3600) {
        return $cached['action'];
      }
    }

    $action = $this->evaluate($userId);
    $this->state->set($stateKey, [
      'action' => $action,
      'evaluated_at' => \Drupal::time()->getRequestTime(),
    ]);

    return $action;
  }

  /**
   * Descarta una regla para un usuario.
   */
  public function dismissAction(int $userId, string $ruleId): void {
    $key = "serviciosconecta_proactive_dismissed_{$userId}";
    $dismissed = $this->state->get($key, []);
    if (!in_array($ruleId, $dismissed, TRUE)) {
      $dismissed[] = $ruleId;
      $this->state->set($key, $dismissed);
    }

    $this->state->delete("serviciosconecta_proactive_pending_{$userId}");

    $this->logger->info('ServiciosConecta proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleId,
      '@uid' => $userId,
    ]);
  }

  /**
   * Evalua reglas en batch (para cron).
   *
   * @return int
   *   Numero de usuarios procesados.
   */
  public function evaluateBatch(): int {
    $processed = 0;
    try {
      $userIds = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

      foreach (array_slice(array_values($userIds), 0, 100) as $userId) {
        $this->getPendingAction((int) $userId);
        $processed++;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('ServiciosConecta journey batch evaluation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $processed;
  }

  /**
   * Verifica una condicion de regla.
   */
  protected function evaluateCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'profile_incomplete' => $this->checkProfileIncomplete($userId),
      'zero_services_3_days' => $this->checkZeroServices($userId),
      'services_no_slots' => $this->checkServicesNoSlots($userId),
      'slots_no_bookings_7_days' => $this->checkSlotsNoBookings($userId),
      'unanswered_reviews_3_days' => $this->checkUnansweredReviews($userId),
      'services_67_percent' => $this->checkServicesLimitApproaching($userId),
      'starter_high_volume' => $this->checkStarterHighVolume($userId),
      'excellence_level' => $this->checkExcellenceLevel($userId),
      'booking_started_24h' => $this->checkBookingAbandoned($userId),
      'last_booking_30_days' => $this->checkRebookingNudge($userId),
      default => FALSE,
    };
  }

  /**
   * Perfil profesional incompleto.
   */
  protected function checkProfileIncomplete(int $userId): bool {
    try {
      $professionals = $this->entityTypeManager
        ->getStorage('serviciosconecta_professional')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($professionals)) {
        return FALSE;
      }

      $professional = reset($professionals);
      $profileComplete = (bool) ($professional->get('profile_complete')->value ?? FALSE);
      return !$profileComplete;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * 0 servicios publicados y registrado hace >3 dias.
   */
  protected function checkZeroServices(int $userId): bool {
    try {
      $services = $this->entityTypeManager->getStorage('serviciosconecta_service')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      if ((int) $services > 0) {
        return FALSE;
      }

      $stateKey = "serviciosconecta_last_activity_{$userId}";
      $registered = (int) $this->state->get($stateKey, 0);
      if ($registered === 0) {
        return FALSE;
      }

      $threshold = \Drupal::time()->getRequestTime() - (3 * 86400);
      return $registered < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Servicios publicados pero sin slots de disponibilidad.
   */
  protected function checkServicesNoSlots(int $userId): bool {
    try {
      $services = $this->entityTypeManager->getStorage('serviciosconecta_service')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      if ((int) $services === 0) {
        return FALSE;
      }

      $slots = $this->entityTypeManager->getStorage('serviciosconecta_slot')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      return (int) $slots === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Slots configurados pero 0 reservas en 7 dias.
   */
  protected function checkSlotsNoBookings(int $userId): bool {
    try {
      $slots = $this->entityTypeManager->getStorage('serviciosconecta_slot')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->count()
        ->execute();

      if ((int) $slots === 0) {
        return FALSE;
      }

      $bookings = $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('professional_id', $userId)
        ->count()
        ->execute();

      if ((int) $bookings > 0) {
        return FALSE;
      }

      // Check first slot was created at least 7 days ago.
      $firstSlot = $this->entityTypeManager->getStorage('serviciosconecta_slot')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('owner_id', $userId)
        ->condition('status', 1)
        ->sort('created', 'ASC')
        ->range(0, 1)
        ->execute();

      if (empty($firstSlot)) {
        return FALSE;
      }

      $slot = $this->entityTypeManager->getStorage('serviciosconecta_slot')
        ->load(reset($firstSlot));
      $created = (int) ($slot->get('created')->value ?? 0);
      $threshold = \Drupal::time()->getRequestTime() - (7 * 86400);
      return $created < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Resenas sin responder durante >3 dias.
   */
  protected function checkUnansweredReviews(int $userId): bool {
    try {
      $reviews = $this->entityTypeManager->getStorage('serviciosconecta_review')
        ->loadByProperties([
          'professional_id' => $userId,
          'response_status' => 'pending',
        ]);
      if (empty($reviews)) {
        return FALSE;
      }
      $threshold = \Drupal::time()->getRequestTime() - (3 * 86400);
      foreach ($reviews as $review) {
        $created = (int) ($review->get('created')->value ?? 0);
        if ($created < $threshold) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Catalogo de servicios al 67% del limite del plan.
   */
  protected function checkServicesLimitApproaching(int $userId): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.serviciosconecta_feature_gate')) {
        return FALSE;
      }
      $featureGate = \Drupal::service('ecosistema_jaraba_core.serviciosconecta_feature_gate');
      $plan = $featureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return FALSE;
      }

      $result = $featureGate->check($userId, 'services');
      if ($result->limit <= 0) {
        return FALSE;
      }
      return (($result->used ?? 0) / $result->limit) >= 0.67;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Plan Starter con alto volumen de reservas.
   */
  protected function checkStarterHighVolume(int $userId): bool {
    try {
      if (!\Drupal::hasService('ecosistema_jaraba_core.serviciosconecta_feature_gate')) {
        return FALSE;
      }
      $featureGate = \Drupal::service('ecosistema_jaraba_core.serviciosconecta_feature_gate');
      $plan = $featureGate->getUserPlan($userId);
      if ($plan !== 'starter') {
        return FALSE;
      }

      $bookingCount = (int) $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('professional_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      return $bookingCount > 20;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Nivel de excelencia: >50 reservas completadas y rating >4.5.
   */
  protected function checkExcellenceLevel(int $userId): bool {
    try {
      $bookingCount = (int) $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('professional_id', $userId)
        ->condition('status', 'completed')
        ->count()
        ->execute();

      if ($bookingCount < 50) {
        return FALSE;
      }

      $stateKey = "serviciosconecta_professional_rating_{$userId}";
      $rating = (float) $this->state->get($stateKey, 0);
      return $rating >= 4.5;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Reserva iniciada sin completar en 24h.
   */
  protected function checkBookingAbandoned(int $userId): bool {
    try {
      $bookings = $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->loadByProperties([
          'client_id' => $userId,
          'status' => 'pending',
        ]);
      if (empty($bookings)) {
        return FALSE;
      }
      $threshold = \Drupal::time()->getRequestTime() - 86400;
      foreach ($bookings as $booking) {
        $created = (int) ($booking->get('created')->value ?? 0);
        if ($created > 0 && $created < $threshold) {
          return TRUE;
        }
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Ultima reserva completada hace >30 dias.
   */
  protected function checkRebookingNudge(int $userId): bool {
    try {
      $bookings = $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('client_id', $userId)
        ->condition('status', 'completed')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($bookings)) {
        return FALSE;
      }

      $booking = $this->entityTypeManager->getStorage('serviciosconecta_booking')
        ->load(reset($bookings));
      $created = (int) ($booking->get('created')->value ?? 0);
      $threshold = \Drupal::time()->getRequestTime() - (30 * 86400);
      return $created < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Obtiene reglas descartadas.
   */
  protected function getDismissedRules(int $userId): array {
    return $this->state->get("serviciosconecta_proactive_dismissed_{$userId}", []);
  }

}
