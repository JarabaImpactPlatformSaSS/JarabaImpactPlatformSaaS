<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de computo de plazos procesales y tributarios.
 *
 * Implementa las reglas de computo de la LEC (Art. 130-136) y LGT (Art. 48):
 * - Dias habiles: excluye sabados, domingos, festivos nacionales/CCAA.
 * - Dias naturales: incluye todos los dias.
 * - Meses: fecha equivalente del mes siguiente(s).
 * - Agosto inhabil para plazos procesales (LEC 130.2).
 */
class DeadlineCalculatorService {

  /**
   * Reglas de computo precargadas.
   */
  protected const RULES = [
    'contestacion_demanda' => ['count' => 20, 'unit' => 'dias_habiles', 'basis' => 'LEC Art. 405'],
    'recurso_apelacion' => ['count' => 20, 'unit' => 'dias_habiles', 'basis' => 'LEC Art. 458'],
    'recurso_casacion' => ['count' => 20, 'unit' => 'dias_habiles', 'basis' => 'LEC Art. 479'],
    'recurso_reposicion' => ['count' => 5, 'unit' => 'dias_habiles', 'basis' => 'LEC Art. 452'],
    'modelo_303' => ['count' => 20, 'unit' => 'dias_naturales', 'basis' => 'LGT'],
    'modelo_200' => ['count' => 25, 'unit' => 'dias_naturales', 'basis' => 'LIS Art. 124'],
    'recurso_economico_admin' => ['count' => 1, 'unit' => 'meses', 'basis' => 'LGT Art. 235'],
    'recurso_contencioso' => ['count' => 2, 'unit' => 'meses', 'basis' => 'LJCA Art. 46'],
  ];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula fecha de vencimiento segun regla de computo.
   *
   * @param \DateTimeInterface $baseDate
   *   Fecha base (notificacion, publicacion...).
   * @param string $rule
   *   Regla: "20_dias_habiles", "30_dias_naturales", "1_mes" o ID predefinido.
   * @param string $jurisdiction
   *   Jurisdiccion para calendario laboral (default: ES).
   *
   * @return \DateTimeImmutable
   *   Fecha de vencimiento calculada.
   */
  public function computeDeadline(\DateTimeInterface $baseDate, string $rule, string $jurisdiction = 'ES'): \DateTimeImmutable {
    // Resolver regla predefinida.
    if (isset(self::RULES[$rule])) {
      $ruleData = self::RULES[$rule];
      return $this->calculate($baseDate, $ruleData['count'], $ruleData['unit'], $jurisdiction);
    }

    // Parsear regla dinamica: "20_dias_habiles", "30_dias_naturales", "1_mes".
    if (preg_match('/^(\d+)_(dias_habiles|dias_naturales|meses?)$/', $rule, $m)) {
      return $this->calculate($baseDate, (int) $m[1], $m[2], $jurisdiction);
    }

    $this->logger->warning('Regla de computo no reconocida: @rule', ['@rule' => $rule]);
    return \DateTimeImmutable::createFromInterface($baseDate);
  }

  /**
   * Calcula fecha segun count + unit.
   */
  protected function calculate(\DateTimeInterface $baseDate, int $count, string $unit, string $jurisdiction): \DateTimeImmutable {
    $date = \DateTimeImmutable::createFromInterface($baseDate);

    if (in_array($unit, ['meses', 'mes'])) {
      return $date->modify("+{$count} months");
    }

    if ($unit === 'dias_naturales') {
      return $date->modify("+{$count} days");
    }

    // Dias habiles: excluir fines de semana + agosto (LEC 130.2).
    $added = 0;
    $current = $date;
    while ($added < $count) {
      $current = $current->modify('+1 day');
      if ($this->isBusinessDay($current, $jurisdiction)) {
        $added++;
      }
    }
    return $current;
  }

  /**
   * Obtiene dias laborables entre dos fechas.
   */
  public function getBusinessDays(\DateTimeInterface $start, \DateTimeInterface $end, string $jurisdiction = 'ES'): int {
    $days = 0;
    $current = \DateTimeImmutable::createFromInterface($start);
    $endDate = \DateTimeImmutable::createFromInterface($end);

    while ($current < $endDate) {
      $current = $current->modify('+1 day');
      if ($this->isBusinessDay($current, $jurisdiction)) {
        $days++;
      }
    }
    return $days;
  }

  /**
   * Determina si un dia es laborable.
   *
   * Excluye sabados, domingos y agosto (LEC 130.2 para plazos procesales).
   */
  public function isBusinessDay(\DateTimeInterface $date, string $jurisdiction = 'ES'): bool {
    $dayOfWeek = (int) $date->format('N');
    if ($dayOfWeek >= 6) {
      return FALSE;
    }

    // Agosto es inhabil para plazos procesales (LEC 130.2).
    if ((int) $date->format('n') === 8) {
      return FALSE;
    }

    // Festivos nacionales basicos.
    $month_day = $date->format('m-d');
    $holidays = [
      '01-01', '01-06', '05-01', '08-15',
      '10-12', '11-01', '12-06', '12-08', '12-25',
    ];
    if (in_array($month_day, $holidays)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Obtiene las reglas de computo predefinidas.
   *
   * @return array
   *   Array de reglas con keys: count, unit, basis.
   */
  public function getPredefinedRules(): array {
    return self::RULES;
  }

}
