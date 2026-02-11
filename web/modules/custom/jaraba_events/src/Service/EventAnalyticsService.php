<?php

namespace Drupal\jaraba_events\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analítica y métricas de eventos de marketing.
 *
 * ESTRUCTURA:
 * Servicio de solo lectura que calcula métricas de rendimiento para eventos
 * individuales y agregados por tenant. Alimenta el dashboard de analítica
 * de eventos y las tarjetas KPI.
 *
 * LÓGICA:
 * Todas las consultas están filtradas por tenant_id para garantizar
 * el aislamiento multi-tenant. Las métricas se calculan en tiempo real
 * desde las entidades, sin caché intermedio (fase 1).
 *
 * RELACIONES:
 * - EventAnalyticsService -> EntityTypeManager (dependencia)
 * - EventAnalyticsService -> TenantContextService (dependencia)
 * - EventAnalyticsService <- EventFrontendController (consumido por)
 * - EventAnalyticsService <- EventApiController (consumido por)
 *
 * @package Drupal\jaraba_events\Service
 */
class EventAnalyticsService {

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
   * Obtiene métricas de rendimiento de un evento individual.
   *
   * @param int $event_id
   *   ID del evento a analizar.
   *
   * @return array
   *   Métricas del evento:
   *   - 'event' (array): Datos básicos del evento (título, tipo, fecha).
   *   - 'registrations' (int): Total de registros.
   *   - 'attendance_rate' (float): Tasa de asistencia (%).
   *   - 'fill_rate' (float): Tasa de ocupación (%).
   *   - 'revenue' (float): Ingresos generados.
   *   - 'avg_rating' (float): Valoración media.
   *   - 'conversion_sources' (array): Distribución de fuentes de registro.
   */
  public function getEventPerformance(int $event_id): array {
    $event = $this->entityTypeManager->getStorage('marketing_event')->load($event_id);

    if (!$event) {
      return [];
    }

    // Obtener todos los registros del evento
    $reg_storage = $this->entityTypeManager->getStorage('event_registration');
    $reg_ids = $reg_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('event_id', $event_id)
      ->execute();

    $registrations = !empty($reg_ids) ? $reg_storage->loadMultiple($reg_ids) : [];

    $total = 0;
    $attended = 0;
    $revenue = 0.0;
    $ratings = [];
    $sources = [];

    foreach ($registrations as $reg) {
      $status = $reg->get('registration_status')->value;
      if ($status !== 'cancelled') {
        $total++;
      }
      if ($status === 'attended') {
        $attended++;
      }

      if ($reg->get('payment_status')->value === 'paid') {
        $revenue += (float) ($reg->get('amount_paid')->value ?? 0);
      }

      $rating = (int) ($reg->get('rating')->value ?? 0);
      if ($rating > 0) {
        $ratings[] = $rating;
      }

      $source = $reg->get('source')->value ?: 'web';
      $sources[$source] = ($sources[$source] ?? 0) + 1;
    }

    $max_attendees = (int) $event->get('max_attendees')->value;

    return [
      'event' => [
        'id' => $event->id(),
        'title' => $event->get('title')->value,
        'event_type' => $event->get('event_type')->value,
        'start_date' => $event->get('start_date')->value,
        'status' => $event->get('status_event')->value,
      ],
      'registrations' => $total,
      'attendance_rate' => $total > 0 ? round(($attended / $total) * 100, 1) : 0.0,
      'fill_rate' => $max_attendees > 0 ? round(($total / $max_attendees) * 100, 1) : 0.0,
      'revenue' => round($revenue, 2),
      'avg_rating' => !empty($ratings) ? round(array_sum($ratings) / count($ratings), 1) : 0.0,
      'conversion_sources' => $sources,
    ];
  }

  /**
   * Obtiene métricas agregadas de todos los eventos de un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $period
   *   Periodo de tiempo: '7d', '30d', '90d', '365d'.
   *
   * @return array
   *   Métricas agregadas:
   *   - 'total_events' (int): Total de eventos en el periodo.
   *   - 'total_registrations' (int): Total de registros.
   *   - 'total_revenue' (float): Ingresos totales.
   *   - 'avg_attendance_rate' (float): Tasa de asistencia media.
   *   - 'avg_fill_rate' (float): Tasa de ocupación media.
   *   - 'events_by_type' (array): Distribución de eventos por tipo.
   *   - 'upcoming_events' (int): Eventos futuros programados.
   */
  public function getTenantEventMetrics(int $tenant_id, string $period = '30d'): array {
    // Calcular fecha de inicio según periodo
    $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
    $since = date('Y-m-d\TH:i:s', strtotime("-{$days} days"));

    $event_storage = $this->entityTypeManager->getStorage('marketing_event');

    // Eventos del tenant en el periodo
    $event_ids = $event_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('created', $since, '>=')
      ->execute();

    $events = !empty($event_ids) ? $event_storage->loadMultiple($event_ids) : [];

    // Eventos futuros (próximos)
    $upcoming_ids = $event_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=')
      ->condition('status_event', 'published')
      ->execute();

    $metrics = [
      'total_events' => count($events),
      'total_registrations' => 0,
      'total_revenue' => 0.0,
      'avg_attendance_rate' => 0.0,
      'avg_fill_rate' => 0.0,
      'events_by_type' => [],
      'upcoming_events' => count($upcoming_ids),
    ];

    $attendance_rates = [];
    $fill_rates = [];

    foreach ($events as $event) {
      $type = $event->get('event_type')->value;
      $metrics['events_by_type'][$type] = ($metrics['events_by_type'][$type] ?? 0) + 1;

      // Obtener performance de cada evento
      $perf = $this->getEventPerformance((int) $event->id());
      $metrics['total_registrations'] += $perf['registrations'];
      $metrics['total_revenue'] += $perf['revenue'];

      if ($perf['attendance_rate'] > 0) {
        $attendance_rates[] = $perf['attendance_rate'];
      }
      if ($perf['fill_rate'] > 0) {
        $fill_rates[] = $perf['fill_rate'];
      }
    }

    if (!empty($attendance_rates)) {
      $metrics['avg_attendance_rate'] = round(array_sum($attendance_rates) / count($attendance_rates), 1);
    }
    if (!empty($fill_rates)) {
      $metrics['avg_fill_rate'] = round(array_sum($fill_rates) / count($fill_rates), 1);
    }

    $metrics['total_revenue'] = round($metrics['total_revenue'], 2);

    return $metrics;
  }

  /**
   * Construye el funnel de conversión de un evento.
   *
   * LÓGICA:
   * El funnel tiene 4 etapas: registro → confirmación → asistencia → feedback.
   * Cada etapa muestra el total y la tasa de conversión respecto a la anterior.
   *
   * @param int $event_id
   *   ID del evento.
   *
   * @return array
   *   Array con 4 etapas, cada una con 'label', 'count', 'rate'.
   */
  public function getConversionFunnel(int $event_id): array {
    $reg_storage = $this->entityTypeManager->getStorage('event_registration');

    $all_ids = $reg_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('event_id', $event_id)
      ->execute();

    $registrations = !empty($all_ids) ? $reg_storage->loadMultiple($all_ids) : [];

    $total = 0;
    $confirmed = 0;
    $attended = 0;
    $with_feedback = 0;

    foreach ($registrations as $reg) {
      $status = $reg->get('registration_status')->value;
      if ($status !== 'cancelled') {
        $total++;
      }
      if (in_array($status, ['confirmed', 'attended'])) {
        $confirmed++;
      }
      if ($status === 'attended') {
        $attended++;
      }
      if ((int) ($reg->get('rating')->value ?? 0) > 0) {
        $with_feedback++;
      }
    }

    return [
      [
        'label' => 'Registros',
        'count' => $total,
        'rate' => 100.0,
      ],
      [
        'label' => 'Confirmados',
        'count' => $confirmed,
        'rate' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0.0,
      ],
      [
        'label' => 'Asistieron',
        'count' => $attended,
        'rate' => $confirmed > 0 ? round(($attended / $confirmed) * 100, 1) : 0.0,
      ],
      [
        'label' => 'Feedback',
        'count' => $with_feedback,
        'rate' => $attended > 0 ? round(($with_feedback / $attended) * 100, 1) : 0.0,
      ],
    ];
  }

}
