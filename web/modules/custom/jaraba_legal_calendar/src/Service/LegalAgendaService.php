<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Agenda unificada: plazos + vistas + eventos externos.
 *
 * Proporciona vistas consolidadas de todos los eventos del profesional:
 * plazos legales, senalados judiciales y eventos de calendarios externos.
 */
class LegalAgendaService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene plazos proximos para el usuario actual.
   *
   * @param int $days
   *   Numero de dias a consultar.
   *
   * @return array
   *   Array de entidades LegalDeadline.
   */
  public function getUpcomingDeadlines(int $days = 30): array {
    $now = new \DateTime();
    $end = (new \DateTime())->modify("+{$days} days");

    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $ids = $storage->getQuery()
      ->condition('due_date', $now->format('Y-m-d\TH:i:s'), '>=')
      ->condition('due_date', $end->format('Y-m-d\TH:i:s'), '<=')
      ->condition('status', ['pending', 'in_progress'], 'IN')
      ->sort('due_date', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene senalados proximos.
   *
   * @param int $days
   *   Numero de dias a consultar.
   *
   * @return array
   *   Array de entidades CourtHearing.
   */
  public function getHearings(int $days = 30): array {
    $now = new \DateTime();
    $end = (new \DateTime())->modify("+{$days} days");

    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $ids = $storage->getQuery()
      ->condition('scheduled_at', $now->format('Y-m-d\TH:i:s'), '>=')
      ->condition('scheduled_at', $end->format('Y-m-d\TH:i:s'), '<=')
      ->condition('status', ['scheduled', 'confirmed'], 'IN')
      ->sort('scheduled_at', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Vista de dia: todos los eventos para una fecha.
   *
   * @param string $date
   *   Fecha en formato Y-m-d.
   *
   * @return array
   *   Array con keys 'deadlines', 'hearings', 'external'.
   */
  public function getDayView(string $date): array {
    $dayStart = $date . 'T00:00:00';
    $dayEnd = $date . 'T23:59:59';

    return [
      'deadlines' => $this->getDeadlinesInRange($dayStart, $dayEnd),
      'hearings' => $this->getHearingsInRange($dayStart, $dayEnd),
      'external' => $this->getExternalEventsInRange($dayStart, $dayEnd),
    ];
  }

  /**
   * Vista de semana.
   *
   * @param string $startDate
   *   Fecha de inicio de la semana (Y-m-d).
   *
   * @return array
   *   Array con keys 'deadlines', 'hearings', 'external'.
   */
  public function getWeekView(string $startDate): array {
    $start = $startDate . 'T00:00:00';
    $end = (new \DateTime($startDate))->modify('+7 days')->format('Y-m-d') . 'T23:59:59';

    return [
      'deadlines' => $this->getDeadlinesInRange($start, $end),
      'hearings' => $this->getHearingsInRange($start, $end),
      'external' => $this->getExternalEventsInRange($start, $end),
    ];
  }

  /**
   * Plazos en un rango de fechas.
   */
  protected function getDeadlinesInRange(string $start, string $end): array {
    $storage = $this->entityTypeManager->getStorage('legal_deadline');
    $ids = $storage->getQuery()
      ->condition('due_date', $start, '>=')
      ->condition('due_date', $end, '<=')
      ->sort('due_date', 'ASC')
      ->accessCheck(TRUE)
      ->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Senalados en un rango de fechas.
   */
  protected function getHearingsInRange(string $start, string $end): array {
    $storage = $this->entityTypeManager->getStorage('court_hearing');
    $ids = $storage->getQuery()
      ->condition('scheduled_at', $start, '>=')
      ->condition('scheduled_at', $end, '<=')
      ->sort('scheduled_at', 'ASC')
      ->accessCheck(TRUE)
      ->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Eventos externos en un rango de fechas.
   */
  protected function getExternalEventsInRange(string $start, string $end): array {
    $storage = $this->entityTypeManager->getStorage('external_event_cache');
    $ids = $storage->getQuery()
      ->condition('start_datetime', $end, '<=')
      ->condition('end_datetime', $start, '>=')
      ->condition('status', 'cancelled', '<>')
      ->sort('start_datetime', 'ASC')
      ->accessCheck(FALSE)
      ->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

}
