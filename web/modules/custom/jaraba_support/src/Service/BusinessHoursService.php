<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Business hours calculation service.
 *
 * Manages business hour schedules and provides time calculations
 * that respect working hours, weekends, and holidays. Used by
 * SlaEngineService for deadline computation.
 */
final class BusinessHoursService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Checks if a given moment falls within business hours.
   *
   * @param string $scheduleId
   *   The business hours schedule entity ID.
   * @param \DateTimeInterface|null $at
   *   The moment to check. Defaults to current time.
   *
   * @return bool
   *   TRUE if the moment is within business hours.
   */
  public function isWithinBusinessHours(string $scheduleId, ?\DateTimeInterface $at = NULL): bool {
    try {
      $schedule = $this->loadSchedule($scheduleId);
      if (!$schedule) {
        $this->logger->warning('Business hours schedule @id not found — defaulting to TRUE.', [
          '@id' => $scheduleId,
        ]);
        return TRUE;
      }

      $tz = new \DateTimeZone($schedule->getTimezone());
      $now = $at
        ? (new \DateTimeImmutable('@' . $at->getTimestamp()))->setTimezone($tz)
        : new \DateTimeImmutable('now', $tz);

      // Check if today is a holiday.
      if ($this->isDateHoliday($schedule, $now)) {
        return FALSE;
      }

      // Get the day-of-week schedule.
      $dayName = strtolower($now->format('l'));
      $weeklySchedule = $schedule->getSchedule();

      if (!isset($weeklySchedule[$dayName])) {
        return FALSE;
      }

      $dayConfig = $weeklySchedule[$dayName];

      // Check if the day is marked as closed.
      if (!empty($dayConfig['closed'])) {
        return FALSE;
      }

      $openTime = $dayConfig['open'] ?? NULL;
      $closeTime = $dayConfig['close'] ?? NULL;

      if ($openTime === NULL || $closeTime === NULL) {
        return FALSE;
      }

      // Compare current time against open/close window.
      $currentTime = $now->format('H:i');

      return $currentTime >= $openTime && $currentTime < $closeTime;
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking business hours for schedule @id: @msg', [
        '@id' => $scheduleId,
        '@msg' => $e->getMessage(),
      ]);
      return TRUE;
    }
  }

  /**
   * Adds business hours to a start time, skipping non-working periods.
   *
   * @param string $scheduleId
   *   The business hours schedule entity ID.
   * @param \DateTimeInterface $from
   *   The start time.
   * @param int $hours
   *   Number of business hours to add.
   *
   * @return \DateTimeImmutable
   *   The resulting date/time after adding business hours.
   */
  public function addBusinessHours(string $scheduleId, \DateTimeInterface $from, int $hours): \DateTimeImmutable {
    try {
      $schedule = $this->loadSchedule($scheduleId);
      if (!$schedule) {
        $this->logger->warning('Business hours schedule @id not found — adding @hours calendar hours.', [
          '@id' => $scheduleId,
          '@hours' => $hours,
        ]);
        return \DateTimeImmutable::createFromInterface($from)->modify("+{$hours} hours");
      }

      $tz = new \DateTimeZone($schedule->getTimezone());
      $cursor = (new \DateTimeImmutable('@' . $from->getTimestamp()))->setTimezone($tz);
      $remainingMinutes = $hours * 60;
      $weeklySchedule = $schedule->getSchedule();

      // Safety: prevent infinite loops (max 365 days forward).
      $maxIterations = 365;
      $iterations = 0;

      while ($remainingMinutes > 0 && $iterations < $maxIterations) {
        $iterations++;
        $dayName = strtolower($cursor->format('l'));
        $dayConfig = $weeklySchedule[$dayName] ?? [];

        // Skip closed days and holidays.
        if (!empty($dayConfig['closed']) || !isset($dayConfig['open'], $dayConfig['close']) || $this->isDateHoliday($schedule, $cursor)) {
          // Advance to next day at 00:00.
          $cursor = $cursor->modify('+1 day')->setTime(0, 0, 0);
          continue;
        }

        $openTime = $dayConfig['open'];
        $closeTime = $dayConfig['close'];

        // Parse open/close into DateTime objects on this day.
        [$openH, $openM] = array_map('intval', explode(':', $openTime));
        [$closeH, $closeM] = array_map('intval', explode(':', $closeTime));

        $dayOpen = $cursor->setTime($openH, $openM, 0);
        $dayClose = $cursor->setTime($closeH, $closeM, 0);

        // If cursor is before the day opens, snap to open.
        if ($cursor < $dayOpen) {
          $cursor = $dayOpen;
        }

        // If cursor is at or past closing, move to next day.
        if ($cursor >= $dayClose) {
          $cursor = $cursor->modify('+1 day')->setTime(0, 0, 0);
          continue;
        }

        // Calculate available minutes until close.
        $availableMinutes = (int) (($dayClose->getTimestamp() - $cursor->getTimestamp()) / 60);

        if ($remainingMinutes <= $availableMinutes) {
          // All remaining time fits within this working day.
          $cursor = $cursor->modify("+{$remainingMinutes} minutes");
          $remainingMinutes = 0;
        }
        else {
          // Consume available time and move to next day.
          $remainingMinutes -= $availableMinutes;
          $cursor = $cursor->modify('+1 day')->setTime(0, 0, 0);
        }
      }

      if ($iterations >= $maxIterations) {
        $this->logger->error('addBusinessHours() exceeded max iterations for schedule @id — returning best estimate.', [
          '@id' => $scheduleId,
        ]);
      }

      return $cursor;
    }
    catch (\Exception $e) {
      $this->logger->error('Error adding business hours for schedule @id: @msg', [
        '@id' => $scheduleId,
        '@msg' => $e->getMessage(),
      ]);
      return \DateTimeImmutable::createFromInterface($from)->modify("+{$hours} hours");
    }
  }

  /**
   * Checks if a given date is a holiday in the schedule.
   *
   * @param string $scheduleId
   *   The business hours schedule entity ID.
   * @param \DateTimeInterface $date
   *   The date to check.
   *
   * @return bool
   *   TRUE if the date is a holiday.
   */
  public function isHoliday(string $scheduleId, \DateTimeInterface $date): bool {
    try {
      $schedule = $this->loadSchedule($scheduleId);
      if (!$schedule) {
        $this->logger->warning('Business hours schedule @id not found — defaulting to not a holiday.', [
          '@id' => $scheduleId,
        ]);
        return FALSE;
      }

      $tz = new \DateTimeZone($schedule->getTimezone());
      $dateInTz = (new \DateTimeImmutable('@' . $date->getTimestamp()))->setTimezone($tz);

      return $this->isDateHoliday($schedule, $dateInTz);
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking holiday for schedule @id: @msg', [
        '@id' => $scheduleId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Loads a business hours schedule config entity.
   *
   * @param string $scheduleId
   *   The schedule entity ID.
   *
   * @return \Drupal\jaraba_support\Entity\BusinessHoursSchedule|null
   *   The schedule entity or NULL if not found.
   */
  private function loadSchedule(string $scheduleId): ?object {
    try {
      $schedule = $this->entityTypeManager
        ->getStorage('business_hours_schedule')
        ->load($scheduleId);

      return $schedule;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load business hours schedule @id: @msg', [
        '@id' => $scheduleId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Checks if a date matches any holiday in the schedule.
   *
   * Internal helper that operates on already-timezone-converted dates.
   *
   * @param object $schedule
   *   The BusinessHoursSchedule entity.
   * @param \DateTimeImmutable $date
   *   The date to check, already in the schedule's timezone.
   *
   * @return bool
   *   TRUE if the date matches a holiday entry.
   */
  private function isDateHoliday(object $schedule, \DateTimeImmutable $date): bool {
    $dateString = $date->format('Y-m-d');
    $holidays = $schedule->getHolidays();

    foreach ($holidays as $holiday) {
      if (isset($holiday['date']) && $holiday['date'] === $dateString) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
