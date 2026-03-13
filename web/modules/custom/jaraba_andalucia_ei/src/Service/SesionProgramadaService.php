<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dominio para sesiones programadas.
 *
 * Gestiona la calendarización de sesiones, expansión de recurrencia
 * Outlook-style, y consultas relacionadas con la programación.
 *
 * Recurrence engine supports: daily (every N days / weekdays only),
 * weekly (every N weeks on selected days), monthly (day of month or
 * ordinal weekday), yearly (fixed date or ordinal weekday).
 * Range: count, end_date, or no_end (type-aware safety limits).
 * Backward-compatible with legacy {frequency, count} patterns.
 */
class SesionProgramadaService {

  /**
   * Day key → ISO-8601 numeric (1=Mon…7=Sun).
   */
  private const DAY_MAP = [
    'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4,
    'fri' => 5, 'sat' => 6, 'sun' => 7,
  ];

  /**
   * Reverse: ISO numeric → day key.
   */
  private const DAY_MAP_REVERSE = [
    1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu',
    5 => 'fri', 6 => 'sat', 7 => 'sun',
  ];

  /**
   * Type-aware safety limits for "no_end" range.
   *
   * Each value represents ~1 year of occurrences for that frequency.
   */
  private const NO_END_LIMITS = [
    'daily' => 365,
    'weekly' => 52,
    'monthly' => 24,
    'yearly' => 10,
  ];

  /**
   * Absolute ceiling for generated sessions (any type, any range).
   */
  private const MAX_ABSOLUTE = 365;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene sesiones programadas de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string|null $estado
   *   Filtrar por estado.
   * @param string|null $desde
   *   Fecha mínima (Y-m-d).
   * @param string|null $hasta
   *   Fecha máxima (Y-m-d).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones ordenadas por fecha ASC.
   */
  public function getSesionesPorTenant(int $tenantId, ?string $estado = NULL, ?string $desde = NULL, ?string $hasta = NULL): array {
    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('fecha', 'ASC');

    if ($estado !== NULL) {
      $query->condition('estado', $estado);
    }
    if ($desde !== NULL) {
      $query->condition('fecha', $desde, '>=');
    }
    if ($hasta !== NULL) {
      $query->condition('fecha', $hasta, '<=');
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Obtiene sesiones futuras filtradas por tenant y/o acción formativa.
   *
   * TENANT-001: El tenantId es obligatorio cuando se consulta desde contexto
   * de dashboard o listados. El accionFormativaId es opcional para filtrar
   * por una acción concreta.
   *
   * @param int $tenantId
   *   ID del tenant (TENANT-001 compliance).
   * @param int|null $limit
   *   Máximo de sesiones a devolver (NULL = sin límite).
   * @param int|null $accionFormativaId
   *   Filtrar por acción formativa específica (NULL = todas).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones futuras ordenadas por fecha ASC.
   */
  public function getSesionesFuturas(int $tenantId, ?int $limit = NULL, ?int $accionFormativaId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
    $hoy = date('Y-m-d');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('fecha', $hoy, '>=')
      ->condition('estado', ['cancelada', 'aplazada'], 'NOT IN')
      ->sort('fecha', 'ASC');

    if ($accionFormativaId !== NULL) {
      $query->condition('accion_formativa_id', $accionFormativaId);
    }
    if ($limit !== NULL) {
      $query->range(0, $limit);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[] */
    return $storage->loadMultiple($ids);
  }

  /**
   * Expande la recurrencia de una sesión padre (Outlook-style).
   *
   * Soporta: daily, weekly (días seleccionados), monthly (día del mes u
   * ordinal weekday), yearly (fecha fija u ordinal weekday).
   * Rango: count, end_date, no_end (type-aware limits).
   * Backward-compatible con legacy {frequency, count}.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesionPadre
   *   La sesión padre con recurrencia.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones hijas generadas.
   */
  public function expandirRecurrencia(SesionProgramadaEiInterface $sesionPadre): array {
    if (!$sesionPadre->isRecurrente()) {
      return [];
    }

    $patronJson = $sesionPadre->get('recurrencia_patron')->value;
    if (empty($patronJson)) {
      return [];
    }

    try {
      $patron = json_decode($patronJson, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      $this->logger->warning('Patrón de recurrencia inválido para sesión @id.', [
        '@id' => $sesionPadre->id(),
      ]);
      return [];
    }

    // Backward compatibility: convert legacy {frequency, count}.
    if (isset($patron['frequency']) && !isset($patron['type'])) {
      $patron = $this->convertLegacyPattern($patron);
    }

    $fechas = $this->calcularFechasRecurrencia(
      $sesionPadre->get('fecha')->value,
      $patron,
    );
    if (empty($fechas)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');

    // Bulk dedup: load all existing child dates in a single query.
    $fechasExistentes = $this->getExistingChildDates(
      $storage,
      (int) $sesionPadre->id(),
    );

    $sesionesGeneradas = [];
    $sequence = 2;

    foreach ($fechas as $fecha) {
      if (isset($fechasExistentes[$fecha])) {
        $sequence++;
        continue;
      }

      try {
        /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $sesionHija */
        $sesionHija = $storage->create([
          'titulo' => $sesionPadre->getTitulo() . ' (' . $sequence . ')',
          'tenant_id' => $sesionPadre->get('tenant_id')->target_id,
          'uid' => $sesionPadre->getOwnerId(),
          'accion_formativa_id' => $sesionPadre->get('accion_formativa_id')->target_id,
          'tipo_sesion' => $sesionPadre->getTipoSesion(),
          'fase_programa' => $sesionPadre->getFasePrograma(),
          'fecha' => $fecha,
          'hora_inicio' => $sesionPadre->getHoraInicio(),
          'hora_fin' => $sesionPadre->getHoraFin(),
          'modalidad' => $sesionPadre->getModalidad(),
          'lugar_descripcion' => $sesionPadre->get('lugar_descripcion')->value,
          'lugar_url' => $sesionPadre->get('lugar_url')->value,
          'facilitador_id' => $sesionPadre->get('facilitador_id')->target_id,
          'facilitador_nombre' => $sesionPadre->get('facilitador_nombre')->value,
          'max_plazas' => $sesionPadre->getMaxPlazas(),
          'estado' => 'programada',
          'es_recurrente' => FALSE,
          'sesion_padre_id' => $sesionPadre->id(),
        ]);

        $sesionHija->save();
        $sesionesGeneradas[] = $sesionHija;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Error generando sesión hija fecha @fecha para padre @id: @msg', [
          '@fecha' => $fecha,
          '@id' => $sesionPadre->id(),
          '@msg' => $e->getMessage(),
        ]);
      }

      $sequence++;
    }

    $this->logger->info('Expansión de recurrencia: sesión @id generó @count sesiones hijas.', [
      '@id' => $sesionPadre->id(),
      '@count' => count($sesionesGeneradas),
    ]);

    return $sesionesGeneradas;
  }

  /**
   * Obtiene sesiones para una participante (su carril + comunes).
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $carril
   *   Carril de la participante.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface[]
   *   Sesiones aplicables, futuras, con plazas.
   */
  public function getSesionesParaParticipante(int $tenantId, string $carril): array {
    $sesiones = $this->getSesionesPorTenant($tenantId, 'programada', date('Y-m-d'));
    return array_filter($sesiones, function (SesionProgramadaEiInterface $sesion) use ($carril) {
      if (!$sesion->hayPlazasDisponibles()) {
        return FALSE;
      }
      $accionId = $sesion->get('accion_formativa_id')->target_id;
      if ($accionId) {
        try {
          $accion = $this->entityTypeManager->getStorage('accion_formativa_ei')->load($accionId);
          if ($accion) {
            $carrilAccion = $accion->get('carril')->value ?? 'comun';
            return $carrilAccion === $carril || $carrilAccion === 'comun';
          }
        }
        catch (\Throwable) {
          // PRESAVE-RESILIENCE-001.
        }
      }
      return TRUE;
    });
  }

  /**
   * Converts legacy {frequency, count} to Outlook-style schema.
   *
   * @param array<string, mixed> $legacy
   *   Legacy pattern.
   *
   * @return array<string, mixed>
   *   New-format pattern.
   */
  private function convertLegacyPattern(array $legacy): array {
    $frequencyMap = [
      'weekly' => ['type' => 'weekly', 'interval' => 1],
      'biweekly' => ['type' => 'weekly', 'interval' => 2],
      'monthly' => ['type' => 'monthly', 'interval' => 1, 'monthly_type' => 'day_of_month'],
    ];
    $base = $frequencyMap[$legacy['frequency'] ?? 'weekly'] ?? $frequencyMap['weekly'];
    $base['range_type'] = 'count';
    $base['count'] = min((int) ($legacy['count'] ?? 4), self::MAX_ABSOLUTE);
    return $base;
  }

  /**
   * Loads existing child session dates in bulk (single query).
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity storage.
   * @param int $padreId
   *   Parent session ID.
   *
   * @return array<string, true>
   *   Lookup map of Y-m-d dates that already have child sessions.
   */
  private function getExistingChildDates(EntityStorageInterface $storage, int $padreId): array {
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('sesion_padre_id', $padreId)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $existing = [];
    /** @var \Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface $child */
    foreach ($storage->loadMultiple($ids) as $child) {
      $fecha = $child->get('fecha')->value;
      if ($fecha) {
        $existing[$fecha] = TRUE;
      }
    }

    return $existing;
  }

  /**
   * Calcula fechas de recurrencia según patrón Outlook-style.
   *
   * @param string $fechaBase
   *   Fecha de la sesión padre (Y-m-d).
   * @param array<string, mixed> $patron
   *   Patrón de recurrencia.
   *
   * @return string[]
   *   Array de fechas Y-m-d, cronológicamente ordenadas.
   */
  private function calcularFechasRecurrencia(string $fechaBase, array $patron): array {
    $tipo = $patron['type'] ?? 'weekly';
    $rangeType = $patron['range_type'] ?? 'count';

    // Type-aware limit for "no_end".
    $maxCount = match ($rangeType) {
      'count' => min((int) ($patron['count'] ?? 10), self::MAX_ABSOLUTE),
      'no_end' => self::NO_END_LIMITS[$tipo] ?? 52,
      'end_date' => self::MAX_ABSOLUTE,
      default => 10,
    };

    $endDate = ($rangeType === 'end_date' && !empty($patron['end_date']))
      ? new \DateTimeImmutable($patron['end_date'])
      : NULL;

    $base = new \DateTimeImmutable($fechaBase);

    return match ($tipo) {
      'daily' => $this->calcularDiaria($base, $patron, $maxCount, $endDate),
      'weekly' => $this->calcularSemanal($base, $patron, $maxCount, $endDate),
      'monthly' => $this->calcularMensual($base, $patron, $maxCount, $endDate),
      'yearly' => $this->calcularAnual($base, $patron, $maxCount, $endDate),
      default => [],
    };
  }

  /**
   * Calcula fechas para recurrencia diaria.
   *
   * @return string[]
   */
  private function calcularDiaria(\DateTimeImmutable $base, array $patron, int $maxCount, ?\DateTimeImmutable $endDate): array {
    $interval = max(1, (int) ($patron['interval'] ?? 1));
    $weekdaysOnly = (bool) ($patron['daily_weekdays_only'] ?? FALSE);
    $fechas = [];
    $current = $base;
    // Safety: prevent runaway loops (weekdays_only + large interval could
    // skip many dates). Cap iterations at 4x maxCount.
    $maxIterations = $maxCount * 4;
    $iterations = 0;

    while (count($fechas) < $maxCount && $iterations < $maxIterations) {
      $current = $current->modify("+{$interval} days");
      $iterations++;

      if ($endDate !== NULL && $current > $endDate) {
        break;
      }

      if ($weekdaysOnly && (int) $current->format('N') > 5) {
        continue;
      }

      $fechas[] = $current->format('Y-m-d');
    }

    return $fechas;
  }

  /**
   * Calcula fechas para recurrencia semanal.
   *
   * Algorithm: iterate through weeks starting from base date's week.
   * For each scheduled week, check all target days. Skip days <= base.
   * Advance by `interval` weeks between scheduled weeks.
   *
   * @return string[]
   */
  private function calcularSemanal(\DateTimeImmutable $base, array $patron, int $maxCount, ?\DateTimeImmutable $endDate): array {
    $interval = max(1, (int) ($patron['interval'] ?? 1));
    $daysOfWeek = $patron['days_of_week'] ?? [];

    // Fallback: if no days selected, use base date's day.
    if (empty($daysOfWeek)) {
      $baseDowKey = self::DAY_MAP_REVERSE[(int) $base->format('N')] ?? 'mon';
      $daysOfWeek = [$baseDowKey];
    }

    // Convert to numeric and sort for chronological output.
    $targetDays = [];
    foreach ($daysOfWeek as $d) {
      if (isset(self::DAY_MAP[$d])) {
        $targetDays[] = self::DAY_MAP[$d];
      }
    }
    sort($targetDays);

    if (empty($targetDays)) {
      return [];
    }

    $fechas = [];
    // Monday of base date's week.
    $baseDow = (int) $base->format('N');
    $weekStart = $base->modify('-' . ($baseDow - 1) . ' days');
    $weekNumber = 0;
    // Safety: max 10 years of weeks.
    $maxWeekNumber = 520;

    while (count($fechas) < $maxCount && $weekNumber <= $maxWeekNumber) {
      $currentWeekStart = $weekStart->modify('+' . ($weekNumber * 7) . ' days');

      foreach ($targetDays as $targetDow) {
        $candidate = $currentWeekStart->modify('+' . ($targetDow - 1) . ' days');

        // Skip the base date itself and anything before it.
        if ($candidate <= $base) {
          continue;
        }
        if ($endDate !== NULL && $candidate > $endDate) {
          return $fechas;
        }

        $fechas[] = $candidate->format('Y-m-d');
        if (count($fechas) >= $maxCount) {
          return $fechas;
        }
      }

      $weekNumber += $interval;
    }

    return $fechas;
  }

  /**
   * Calcula fechas para recurrencia mensual.
   *
   * Uses arithmetic month computation to avoid PHP's modify("+N months")
   * overflow (e.g., Jan 31 + 1 month = March 3 in PHP, but we need Feb 28).
   *
   * @return string[]
   */
  private function calcularMensual(\DateTimeImmutable $base, array $patron, int $maxCount, ?\DateTimeImmutable $endDate): array {
    $interval = max(1, (int) ($patron['interval'] ?? 1));
    $monthlyType = $patron['monthly_type'] ?? 'day_of_month';
    $baseYear = (int) $base->format('Y');
    $baseMonth = (int) $base->format('n');
    $fechas = [];

    for ($i = 1; count($fechas) < $maxCount && $i <= 400; $i++) {
      // Arithmetic month computation: avoids PHP modify() overflow.
      $totalMonths = ($baseYear * 12 + $baseMonth - 1) + ($i * $interval);
      $targetYear = intdiv($totalMonths, 12);
      $targetMonth = ($totalMonths % 12) + 1;

      if ($monthlyType === 'day_of_month') {
        $dayOfMonth = (int) ($patron['day_of_month'] ?? (int) $base->format('j'));
        // Clamp to actual month length.
        $maxDay = $this->daysInMonth($targetYear, $targetMonth);
        $day = min($dayOfMonth, $maxDay);
        $candidate = new \DateTimeImmutable(
          sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $day),
        );
      }
      else {
        $ordinal = $patron['weekday_ordinal'] ?? 'first';
        $weekday = $patron['weekday'] ?? 'mon';
        $candidate = $this->resolveOrdinalWeekday($targetYear, $targetMonth, $ordinal, $weekday);
        if ($candidate === NULL) {
          continue;
        }
      }

      if ($endDate !== NULL && $candidate > $endDate) {
        break;
      }
      if ($candidate > $base) {
        $fechas[] = $candidate->format('Y-m-d');
      }
    }

    return $fechas;
  }

  /**
   * Calcula fechas para recurrencia anual.
   *
   * @return string[]
   */
  private function calcularAnual(\DateTimeImmutable $base, array $patron, int $maxCount, ?\DateTimeImmutable $endDate): array {
    $yearlyType = $patron['yearly_type'] ?? 'date';
    $yearlyMonth = (int) ($patron['yearly_month'] ?? (int) $base->format('n'));
    $fechas = [];

    for ($i = 1; count($fechas) < $maxCount && $i <= 50; $i++) {
      $year = (int) $base->format('Y') + $i;

      if ($yearlyType === 'date') {
        $yearlyDay = (int) ($patron['yearly_day'] ?? (int) $base->format('j'));
        $maxDay = $this->daysInMonth($year, $yearlyMonth);
        $day = min($yearlyDay, $maxDay);
        $candidate = new \DateTimeImmutable(
          sprintf('%04d-%02d-%02d', $year, $yearlyMonth, $day),
        );
      }
      else {
        $ordinal = $patron['yearly_ordinal'] ?? 'first';
        $weekday = $patron['yearly_weekday'] ?? 'mon';
        $candidate = $this->resolveOrdinalWeekday($year, $yearlyMonth, $ordinal, $weekday);
        if ($candidate === NULL) {
          continue;
        }
      }

      if ($endDate !== NULL && $candidate > $endDate) {
        break;
      }
      if ($candidate > $base) {
        $fechas[] = $candidate->format('Y-m-d');
      }
    }

    return $fechas;
  }

  /**
   * Resolves ordinal weekday (e.g., "second tuesday of March 2026").
   *
   * Pure arithmetic — no ext-calendar dependency, no relative date strings.
   *
   * @param int $year
   *   Year.
   * @param int $month
   *   Month (1-12).
   * @param string $ordinal
   *   first|second|third|fourth|last.
   * @param string $weekday
   *   mon|tue|wed|thu|fri|sat|sun.
   *
   * @return \DateTimeImmutable|null
   *   The resolved date, or NULL if invalid (e.g., 5th Monday doesn't exist).
   */
  private function resolveOrdinalWeekday(int $year, int $month, string $ordinal, string $weekday): ?\DateTimeImmutable {
    $targetDow = self::DAY_MAP[$weekday] ?? 1;

    if ($ordinal === 'last') {
      // Start from last day of month and walk backward.
      $lastDayNum = $this->daysInMonth($year, $month);
      $lastDay = new \DateTimeImmutable(
        sprintf('%04d-%02d-%02d', $year, $month, $lastDayNum),
      );
      $lastDow = (int) $lastDay->format('N');
      $diff = $lastDow - $targetDow;
      if ($diff < 0) {
        $diff += 7;
      }
      return $lastDay->modify("-{$diff} days");
    }

    $ordinalMap = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4];
    $nth = $ordinalMap[$ordinal] ?? 1;

    // Find first occurrence of target weekday in the month.
    $firstOfMonth = new \DateTimeImmutable(
      sprintf('%04d-%02d-01', $year, $month),
    );
    $firstDow = (int) $firstOfMonth->format('N');
    $daysUntilFirst = ($targetDow - $firstDow + 7) % 7;
    $firstOccurrence = $firstOfMonth->modify("+{$daysUntilFirst} days");

    // Add (nth - 1) weeks.
    $weeksToAdd = ($nth - 1) * 7;
    $result = $firstOccurrence->modify("+{$weeksToAdd} days");

    // Verify still in same month (e.g., "fifth Monday" may overflow).
    if ((int) $result->format('n') !== $month) {
      return NULL;
    }

    return $result;
  }

  /**
   * Returns the number of days in a given month.
   *
   * Pure PHP — no ext-calendar dependency.
   *
   * @param int $year
   *   Year.
   * @param int $month
   *   Month (1-12).
   *
   * @return int
   *   Number of days (28-31).
   */
  private function daysInMonth(int $year, int $month): int {
    return (int) (new \DateTimeImmutable(
      sprintf('%04d-%02d-01', $year, $month),
    ))->format('t');
  }

}
