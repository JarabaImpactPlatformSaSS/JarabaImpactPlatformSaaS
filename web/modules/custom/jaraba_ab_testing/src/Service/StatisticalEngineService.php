<?php

namespace Drupal\jaraba_ab_testing\Service;

use Psr\Log\LoggerInterface;

/**
 * Motor estadístico centralizado para A/B testing.
 *
 * ESTRUCTURA:
 * Servicio puro de cálculo estadístico sin dependencias de entidades ni
 * de base de datos. Recibe datos numéricos y devuelve resultados de análisis.
 * Implementa Z-score (dos proporciones), chi-cuadrado, intervalos de
 * confianza, cálculo de tamaño muestral, detección de ganador y estimación
 * de tiempo hasta significancia estadística.
 *
 * LÓGICA:
 * Todas las fórmulas usan estadística frecuentista clásica:
 * - Z-score: pooled standard error para comparar dos proporciones.
 * - Confianza: función de distribución normal acumulada (Phi) mediante
 *   aproximación de la función de error (erf) de Abramowitz & Stegun.
 * - Chi-cuadrado: test de independencia para múltiples variantes.
 * - Tamaño muestral: fórmula de Lehr para dos proporciones con potencia.
 * El umbral de significancia por defecto es 95% (alpha = 0.05).
 *
 * RELACIONES:
 * - StatisticalEngineService -> LoggerInterface (dependencia)
 * - StatisticalEngineService <- ExperimentAggregatorService (consumido por)
 * - StatisticalEngineService <- ABTestingDashboardController (consumido por)
 * - StatisticalEngineService <- ABTestingApiController (consumido por)
 *
 * @package Drupal\jaraba_ab_testing\Service
 */
class StatisticalEngineService {

  /**
   * Canal de log dedicado para el módulo de A/B testing.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del motor estadístico.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar cálculos y errores.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Calcula el Z-score entre las tasas de conversión de control y variante.
   *
   * LÓGICA:
   * Usa la proporción pooled (combinada) para calcular el error estándar.
   * La fórmula es:
   *   p_pool = (x1 + x2) / (n1 + n2)
   *   SE = sqrt(p_pool * (1 - p_pool) * (1/n1 + 1/n2))
   *   Z = (p2 - p1) / SE
   *
   * Un Z-score positivo indica que la variante supera al control.
   * Un Z-score > 1.96 corresponde a confianza > 95%.
   *
   * @param int $control_visitors
   *   Número total de visitantes en el grupo control (n1).
   * @param int $control_conversions
   *   Número de conversiones en el grupo control (x1).
   * @param int $variant_visitors
   *   Número total de visitantes en la variante (n2).
   * @param int $variant_conversions
   *   Número de conversiones en la variante (x2).
   *
   * @return float
   *   Z-score de la diferencia. Positivo si la variante es mejor.
   *   Retorna 0.0 si los datos son insuficientes para el cálculo.
   */
  public function calculateZScore(int $control_visitors, int $control_conversions, int $variant_visitors, int $variant_conversions): float {
    // Validar que hay suficientes datos para calcular.
    if ($control_visitors <= 0 || $variant_visitors <= 0) {
      return 0.0;
    }

    // Tasas de conversión individuales.
    $p1 = $control_conversions / $control_visitors;
    $p2 = $variant_conversions / $variant_visitors;

    // Proporción pooled (combinada).
    $p_pool = ($control_conversions + $variant_conversions) / ($control_visitors + $variant_visitors);

    // Error estándar pooled.
    $se = sqrt($p_pool * (1.0 - $p_pool) * (1.0 / $control_visitors + 1.0 / $variant_visitors));

    // Evitar división por cero cuando ambas tasas son iguales o 0/100%.
    if ($se <= 0.0) {
      return 0.0;
    }

    // Z-score: positivo si la variante supera al control.
    $z = ($p2 - $p1) / $se;

    return round($z, 6);
  }

  /**
   * Convierte un Z-score a nivel de confianza porcentual (0-100).
   *
   * LÓGICA:
   * Calcula la probabilidad acumulada de la distribución normal estándar
   * (Phi) usando la función de error (erf). Para un test bilateral:
   *   confianza = Phi(|z|) - Phi(-|z|) = 2 * Phi(|z|) - 1
   *
   * La aproximación de erf usa el método de Abramowitz & Stegun
   * (fórmula 7.1.26) con precisión de ~1.5e-7, suficiente para
   * A/B testing donde se trabaja con umbrales de 90%, 95% y 99%.
   *
   * @param float $z_score
   *   Z-score (puede ser positivo o negativo).
   *
   * @return float
   *   Nivel de confianza como porcentaje (0.0 a 100.0).
   *   Ejemplo: Z=1.96 retorna ~95.0, Z=2.58 retorna ~99.0.
   */
  public function zScoreToConfidence(float $z_score): float {
    // Usar valor absoluto para test bilateral.
    $z = abs($z_score);

    // Phi(z) = 0.5 * (1 + erf(z / sqrt(2)))
    $phi = 0.5 * (1.0 + $this->erf($z / M_SQRT2));

    // Confianza bilateral: 2 * Phi(z) - 1
    $confidence = (2.0 * $phi - 1.0) * 100.0;

    return round(min(100.0, max(0.0, $confidence)), 2);
  }

  /**
   * Análisis completo de un experimento: compara cada variante contra el control.
   *
   * LÓGICA:
   * 1. Identifica la variante de control (is_control = true o la primera).
   * 2. Para cada variante no-control, calcula:
   *    - Tasa de conversión (conversion_rate).
   *    - Z-score contra el control (z_score).
   *    - Nivel de confianza (confidence).
   *    - Si es estadísticamente significativo (is_significant).
   *    - Lift: mejora porcentual sobre el control.
   * 3. Determina si hay un ganador claro y genera una recomendación.
   *
   * El umbral de confianza por defecto es 0.95 (95%).
   *
   * @param array $variants
   *   Array de variantes, cada una con las claves:
   *   - 'id' (int): ID de la variante.
   *   - 'name' (string): Nombre de la variante.
   *   - 'is_control' (bool): Si es la variante de control.
   *   - 'visitors' (int): Número de visitantes.
   *   - 'conversions' (int): Número de conversiones.
   *   - 'traffic_percentage' (float): Porcentaje de tráfico asignado.
   * @param float $confidence_threshold
   *   Umbral mínimo de confianza (0.0 a 1.0). Por defecto 0.95.
   *
   * @return array
   *   Array con:
   *   - 'variants' (array): Resultados por variante con analysis.
   *   - 'winner_variant_id' (int|null): ID de la variante ganadora o null.
   *   - 'has_winner' (bool): Si se ha detectado un ganador significativo.
   *   - 'recommended_action' (string): Acción recomendada en texto.
   */
  public function analyzeExperiment(array $variants, float $confidence_threshold = 0.95): array {
    // Convertir umbral de ratio a porcentaje para comparación.
    $threshold_pct = $confidence_threshold * 100.0;

    // Identificar la variante de control.
    $control = NULL;
    foreach ($variants as $v) {
      if (!empty($v['is_control'])) {
        $control = $v;
        break;
      }
    }

    // Si no hay control explícito, usar la primera variante.
    if ($control === NULL && !empty($variants)) {
      $control = reset($variants);
    }

    // Si no hay variantes, retornar resultado vacío.
    if ($control === NULL) {
      return [
        'variants' => [],
        'winner_variant_id' => NULL,
        'has_winner' => FALSE,
        'recommended_action' => 'No hay variantes para analizar.',
      ];
    }

    $control_visitors = (int) ($control['visitors'] ?? 0);
    $control_conversions = (int) ($control['conversions'] ?? 0);
    $control_rate = $control_visitors > 0
      ? ($control_conversions / $control_visitors) * 100.0
      : 0.0;

    $results = [];
    $best_variant_id = NULL;
    $best_confidence = 0.0;
    $best_lift = -PHP_FLOAT_MAX;

    foreach ($variants as $v) {
      $v_id = (int) ($v['id'] ?? 0);
      $v_visitors = (int) ($v['visitors'] ?? 0);
      $v_conversions = (int) ($v['conversions'] ?? 0);
      $v_rate = $v_visitors > 0
        ? ($v_conversions / $v_visitors) * 100.0
        : 0.0;
      $is_control = !empty($v['is_control']) || ($v === $control);

      $variant_result = [
        'id' => $v_id,
        'name' => $v['name'] ?? '',
        'is_control' => $is_control,
        'visitors' => $v_visitors,
        'conversions' => $v_conversions,
        'conversion_rate' => round($v_rate, 4),
        'z_score' => 0.0,
        'confidence' => 0.0,
        'is_significant' => FALSE,
        'lift' => 0.0,
      ];

      // Solo calcular Z-score/confianza para variantes no-control.
      if (!$is_control) {
        $z = $this->calculateZScore(
          $control_visitors,
          $control_conversions,
          $v_visitors,
          $v_conversions
        );

        $confidence = $this->zScoreToConfidence($z);

        // Lift: mejora porcentual sobre el control.
        $lift = $control_rate > 0.0
          ? (($v_rate - $control_rate) / $control_rate) * 100.0
          : 0.0;

        $variant_result['z_score'] = round($z, 4);
        $variant_result['confidence'] = round($confidence, 2);
        $variant_result['is_significant'] = ($confidence >= $threshold_pct);
        $variant_result['lift'] = round($lift, 2);

        // Rastrear la mejor variante significativa con lift positivo.
        if ($variant_result['is_significant'] && $lift > 0.0 && $confidence > $best_confidence) {
          $best_variant_id = $v_id;
          $best_confidence = $confidence;
          $best_lift = $lift;
        }
      }

      $results[] = $variant_result;
    }

    // Determinar si hay un ganador y generar recomendación.
    $has_winner = ($best_variant_id !== NULL);
    $recommended_action = $this->generateRecommendation(
      $has_winner,
      $best_variant_id,
      $best_confidence,
      $best_lift,
      $results
    );

    return [
      'variants' => $results,
      'winner_variant_id' => $best_variant_id,
      'has_winner' => $has_winner,
      'recommended_action' => $recommended_action,
    ];
  }

  /**
   * Calcula el tamaño muestral mínimo para la potencia deseada.
   *
   * LÓGICA:
   * Usa la fórmula para comparar dos proporciones con test bilateral:
   *   n = (Z_alpha/2 + Z_beta)^2 * (p1*(1-p1) + p2*(1-p2)) / (p1 - p2)^2
   *
   * Donde:
   * - p1 = tasa de conversión base (baseline_rate).
   * - p2 = p1 * (1 + MDE), la tasa esperada con el efecto.
   * - Z_alpha = valor Z para el nivel de confianza (bilateral).
   * - Z_beta = valor Z para la potencia estadística.
   *
   * @param float $baseline_rate
   *   Tasa de conversión base (0.0 a 1.0). Ejemplo: 0.05 = 5%.
   * @param float $minimum_detectable_effect
   *   Efecto mínimo detectable como proporción relativa (0.0 a 1.0).
   *   Ejemplo: 0.10 = detectar una mejora del 10% sobre la base.
   * @param float $confidence
   *   Nivel de confianza deseado (0.0 a 1.0). Por defecto 0.95.
   * @param float $power
   *   Potencia estadística deseada (0.0 a 1.0). Por defecto 0.80.
   *
   * @return int
   *   Tamaño muestral mínimo por grupo (control + cada variante).
   *   Retorna 0 si los parámetros no son válidos.
   */
  public function calculateMinimumSampleSize(float $baseline_rate, float $minimum_detectable_effect, float $confidence = 0.95, float $power = 0.80): int {
    // Validar parámetros.
    if ($baseline_rate <= 0.0 || $baseline_rate >= 1.0) {
      return 0;
    }
    if ($minimum_detectable_effect <= 0.0) {
      return 0;
    }
    if ($confidence <= 0.0 || $confidence >= 1.0) {
      return 0;
    }
    if ($power <= 0.0 || $power >= 1.0) {
      return 0;
    }

    // Tasa esperada con el efecto mínimo detectable.
    $p1 = $baseline_rate;
    $p2 = $p1 * (1.0 + $minimum_detectable_effect);

    // Si p2 >= 1.0, no es posible (la tasa no puede superar 100%).
    if ($p2 >= 1.0) {
      $this->logger->warning('Tamaño muestral: p2 (@p2) >= 1.0 con baseline @baseline y MDE @mde.', [
        '@p2' => $p2,
        '@baseline' => $baseline_rate,
        '@mde' => $minimum_detectable_effect,
      ]);
      return 0;
    }

    // Z-scores para alfa bilateral y beta.
    $z_alpha = $this->inverseNormalCDF(1.0 - (1.0 - $confidence) / 2.0);
    $z_beta = $this->inverseNormalCDF($power);

    // Varianzas individuales.
    $var1 = $p1 * (1.0 - $p1);
    $var2 = $p2 * (1.0 - $p2);

    // Diferencia al cuadrado.
    $diff_sq = ($p1 - $p2) * ($p1 - $p2);

    if ($diff_sq <= 0.0) {
      return 0;
    }

    // Fórmula de tamaño muestral.
    $n = (($z_alpha + $z_beta) ** 2) * ($var1 + $var2) / $diff_sq;

    return (int) ceil($n);
  }

  /**
   * Calcula el test chi-cuadrado para múltiples variantes.
   *
   * LÓGICA:
   * Construye una tabla de contingencia 2xK (conversiones vs no-conversiones
   * para K variantes) y calcula el estadístico chi-cuadrado:
   *   X^2 = SUM((O - E)^2 / E)
   *
   * Donde O = frecuencia observada y E = frecuencia esperada bajo la
   * hipótesis nula de independencia. Los grados de libertad son (K-1).
   * El p-valor se calcula con la distribución chi-cuadrado.
   *
   * @param array $variants
   *   Array de variantes, cada una con:
   *   - 'visitors' (int): Total de visitantes.
   *   - 'conversions' (int): Total de conversiones.
   *
   * @return array
   *   Array con:
   *   - 'chi_squared' (float): Estadístico chi-cuadrado.
   *   - 'degrees_of_freedom' (int): Grados de libertad (K-1).
   *   - 'p_value' (float): P-valor del test.
   *   - 'is_significant' (bool): Si p_value < 0.05.
   */
  public function chiSquaredTest(array $variants): array {
    $k = count($variants);

    // Necesitamos al menos 2 variantes para el test.
    if ($k < 2) {
      return [
        'chi_squared' => 0.0,
        'degrees_of_freedom' => 0,
        'p_value' => 1.0,
        'is_significant' => FALSE,
      ];
    }

    // Totales marginales.
    $total_visitors = 0;
    $total_conversions = 0;

    foreach ($variants as $v) {
      $total_visitors += (int) ($v['visitors'] ?? 0);
      $total_conversions += (int) ($v['conversions'] ?? 0);
    }

    // Si no hay visitantes, no se puede calcular.
    if ($total_visitors <= 0) {
      return [
        'chi_squared' => 0.0,
        'degrees_of_freedom' => $k - 1,
        'p_value' => 1.0,
        'is_significant' => FALSE,
      ];
    }

    $total_non_conversions = $total_visitors - $total_conversions;
    $chi_sq = 0.0;

    foreach ($variants as $v) {
      $n = (int) ($v['visitors'] ?? 0);
      $c = (int) ($v['conversions'] ?? 0);
      $nc = $n - $c;

      if ($n <= 0) {
        continue;
      }

      // Frecuencias esperadas bajo H0 (independencia).
      $e_conv = ($n * $total_conversions) / $total_visitors;
      $e_non_conv = ($n * $total_non_conversions) / $total_visitors;

      // Sumar al chi-cuadrado (evitar división por cero).
      if ($e_conv > 0.0) {
        $chi_sq += (($c - $e_conv) ** 2) / $e_conv;
      }
      if ($e_non_conv > 0.0) {
        $chi_sq += (($nc - $e_non_conv) ** 2) / $e_non_conv;
      }
    }

    $df = $k - 1;
    $p_value = $this->chiSquaredPValue($chi_sq, $df);

    return [
      'chi_squared' => round($chi_sq, 4),
      'degrees_of_freedom' => $df,
      'p_value' => round($p_value, 6),
      'is_significant' => ($p_value < 0.05),
    ];
  }

  /**
   * Estima los días restantes para alcanzar significancia estadística.
   *
   * LÓGICA:
   * Calcula cuántos visitantes adicionales se necesitan para alcanzar
   * el tamaño muestral requerido y divide por la tasa diaria.
   * Si la tasa diaria es 0, retorna -1 (no se puede estimar).
   *
   * @param int $current_visitors
   *   Visitantes actuales acumulados.
   * @param int $daily_rate
   *   Tasa de visitantes diarios estimada.
   * @param int $required_sample
   *   Tamaño muestral requerido para significancia.
   *
   * @return int
   *   Días restantes estimados. 0 si ya se alcanzó el tamaño.
   *   -1 si no se puede estimar (daily_rate = 0).
   */
  public function estimateDaysToSignificance(int $current_visitors, int $daily_rate, int $required_sample): int {
    // Ya se alcanzó el tamaño muestral.
    if ($current_visitors >= $required_sample) {
      return 0;
    }

    // No se puede estimar sin tasa diaria.
    if ($daily_rate <= 0) {
      return -1;
    }

    $remaining = $required_sample - $current_visitors;

    return (int) ceil($remaining / $daily_rate);
  }

  /**
   * Aproximación de la función de error (erf) de Gauss.
   *
   * LÓGICA:
   * Usa la aproximación de Abramowitz & Stegun (fórmula 7.1.26)
   * con 5 coeficientes. Precisión: |error| < 1.5e-7.
   *
   * erf(x) = 1 - (a1*t + a2*t^2 + a3*t^3 + a4*t^4 + a5*t^5) * exp(-x^2)
   * donde t = 1 / (1 + 0.3275911 * |x|)
   *
   * @param float $x
   *   Valor de entrada.
   *
   * @return float
   *   Valor de erf(x), entre -1 y 1.
   */
  protected function erf(float $x): float {
    // Coeficientes de Abramowitz & Stegun.
    $a1 = 0.254829592;
    $a2 = -0.284496736;
    $a3 = 1.421413741;
    $a4 = -1.453152027;
    $a5 = 1.061405429;
    $p = 0.3275911;

    $sign = ($x >= 0) ? 1 : -1;
    $x = abs($x);

    $t = 1.0 / (1.0 + $p * $x);
    $t2 = $t * $t;
    $t3 = $t2 * $t;
    $t4 = $t3 * $t;
    $t5 = $t4 * $t;

    $y = 1.0 - ($a1 * $t + $a2 * $t2 + $a3 * $t3 + $a4 * $t4 + $a5 * $t5) * exp(-$x * $x);

    return $sign * $y;
  }

  /**
   * Calcula la inversa de la distribución normal acumulada (quantile).
   *
   * LÓGICA:
   * Usa la aproximación racional de Peter Acklam para la inversa de
   * Phi (probit function). Precisión relativa: ~1.15e-9 en el rango
   * central. Suficiente para calcular Z_alpha y Z_beta.
   *
   * @param float $p
   *   Probabilidad acumulada (0 < p < 1).
   *
   * @return float
   *   Z-score correspondiente a la probabilidad p.
   */
  protected function inverseNormalCDF(float $p): float {
    // Coeficientes de la aproximación racional de Acklam.
    $a = [
      -3.969683028665376e+01,
       2.209460984245205e+02,
      -2.759285104469687e+02,
       1.383577518672690e+02,
      -3.066479806614716e+01,
       2.506628277459239e+00,
    ];

    $b = [
      -5.447609879822406e+01,
       1.615858368580409e+02,
      -1.556989798598866e+02,
       6.680131188771972e+01,
      -1.328068155288572e+01,
    ];

    $c = [
      -7.784894002430293e-03,
      -3.223964580411365e-01,
      -2.400758277161838e+00,
      -2.549732539343734e+00,
       4.374664141464968e+00,
       2.938163982698783e+00,
    ];

    $d = [
       7.784695709041462e-03,
       3.224671290700398e-01,
       2.445134137142996e+00,
       3.754408661907416e+00,
    ];

    // Punto de corte bajo.
    $p_low = 0.02425;
    // Punto de corte alto.
    $p_high = 1.0 - $p_low;

    if ($p < $p_low) {
      // Aproximación racional para la cola inferior.
      $q = sqrt(-2.0 * log($p));
      return ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5])
        / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0);
    }
    elseif ($p <= $p_high) {
      // Aproximación racional para la región central.
      $q = $p - 0.5;
      $r = $q * $q;
      return ((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q
        / ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1.0);
    }
    else {
      // Aproximación racional para la cola superior.
      $q = sqrt(-2.0 * log(1.0 - $p));
      return -((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5])
        / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0);
    }
  }

  /**
   * Calcula el p-valor de la distribución chi-cuadrado.
   *
   * LÓGICA:
   * Usa la función gamma incompleta regularizada para calcular
   * P(X > chi_sq) donde X ~ chi^2(df).
   *
   * Para grados de libertad bajos (típico en A/B testing con 2-5 variantes),
   * se usa la serie de la función gamma incompleta inferior.
   *
   * @param float $chi_sq
   *   Estadístico chi-cuadrado.
   * @param int $df
   *   Grados de libertad.
   *
   * @return float
   *   P-valor (probabilidad de observar un valor >= chi_sq bajo H0).
   */
  protected function chiSquaredPValue(float $chi_sq, int $df): float {
    if ($chi_sq <= 0.0 || $df <= 0) {
      return 1.0;
    }

    // P-valor = 1 - CDF(chi_sq, df) = 1 - regularizedGammaP(df/2, chi_sq/2)
    $a = $df / 2.0;
    $x = $chi_sq / 2.0;

    $gamma_p = $this->regularizedGammaP($a, $x);

    return max(0.0, 1.0 - $gamma_p);
  }

  /**
   * Función gamma incompleta inferior regularizada P(a, x).
   *
   * LÓGICA:
   * P(a, x) = gamma(a, x) / Gamma(a)
   * Usa expansión en serie para x < a+1, fracción continua en caso contrario.
   *
   * @param float $a
   *   Parámetro de forma.
   * @param float $x
   *   Límite superior de integración.
   *
   * @return float
   *   Valor de la función gamma incompleta regularizada.
   */
  protected function regularizedGammaP(float $a, float $x): float {
    if ($x < 0.0 || $a <= 0.0) {
      return 0.0;
    }

    if ($x === 0.0) {
      return 0.0;
    }

    // Usar expansión en serie si x < a + 1.
    if ($x < $a + 1.0) {
      return $this->gammaPSeries($a, $x);
    }

    // Usar fracción continua en caso contrario.
    return 1.0 - $this->gammaPContinuedFraction($a, $x);
  }

  /**
   * Expansión en serie para la gamma incompleta regularizada.
   *
   * LÓGICA:
   * P(a, x) = exp(-x) * x^a / Gamma(a) * SUM(x^n / (a * (a+1) * ... * (a+n)))
   *
   * @param float $a
   *   Parámetro de forma.
   * @param float $x
   *   Límite superior.
   *
   * @return float
   *   Valor de P(a, x) por serie.
   */
  protected function gammaPSeries(float $a, float $x): float {
    $max_iterations = 200;
    $epsilon = 1.0e-10;

    $log_gamma_a = $this->logGamma($a);

    $sum = 1.0 / $a;
    $term = 1.0 / $a;

    for ($n = 1; $n <= $max_iterations; $n++) {
      $term *= $x / ($a + $n);
      $sum += $term;

      if (abs($term) < abs($sum) * $epsilon) {
        break;
      }
    }

    return $sum * exp(-$x + $a * log($x) - $log_gamma_a);
  }

  /**
   * Fracción continua para la gamma incompleta complementaria Q(a, x).
   *
   * LÓGICA:
   * Q(a, x) = 1 - P(a, x) calculada por fracción continua de Lentz.
   *
   * @param float $a
   *   Parámetro de forma.
   * @param float $x
   *   Límite superior.
   *
   * @return float
   *   Valor de Q(a, x).
   */
  protected function gammaPContinuedFraction(float $a, float $x): float {
    $max_iterations = 200;
    $epsilon = 1.0e-10;
    $tiny = 1.0e-30;

    $log_gamma_a = $this->logGamma($a);

    // Algoritmo de Lentz modificado.
    $f = $tiny;
    $c = $tiny;
    $d = 1.0 / ($x + 1.0 - $a);
    $f = $d;

    for ($n = 1; $n <= $max_iterations; $n++) {
      $an = -$n * ($n - $a);
      $bn = $x + 2.0 * $n + 1.0 - $a;

      $d = $bn + $an * $d;
      if (abs($d) < $tiny) {
        $d = $tiny;
      }
      $d = 1.0 / $d;

      $c = $bn + $an / $c;
      if (abs($c) < $tiny) {
        $c = $tiny;
      }

      $delta = $c * $d;
      $f *= $delta;

      if (abs($delta - 1.0) < $epsilon) {
        break;
      }
    }

    return $f * exp(-$x + $a * log($x) - $log_gamma_a);
  }

  /**
   * Logaritmo de la función gamma (Stirling + Lanczos).
   *
   * LÓGICA:
   * Usa la aproximación de Lanczos con g=7 y 9 coeficientes.
   * Precisión: ~15 dígitos significativos.
   *
   * @param float $x
   *   Valor positivo.
   *
   * @return float
   *   log(Gamma(x)).
   */
  protected function logGamma(float $x): float {
    $coef = [
      0.99999999999980993,
      676.5203681218851,
      -1259.1392167224028,
      771.32342877765313,
      -176.61502916214059,
      12.507343278686905,
      -0.13857109526572012,
      9.9843695780195716e-6,
      1.5056327351493116e-7,
    ];

    if ($x < 0.5) {
      // Reflexión: Gamma(x) * Gamma(1-x) = pi / sin(pi*x)
      return log(M_PI / sin(M_PI * $x)) - $this->logGamma(1.0 - $x);
    }

    $x -= 1.0;
    $a = $coef[0];
    $t = $x + 7.5;

    for ($i = 1; $i < 9; $i++) {
      $a += $coef[$i] / ($x + $i);
    }

    return 0.5 * log(2.0 * M_PI) + ($x + 0.5) * log($t) - $t + log($a);
  }

  /**
   * Genera la recomendación de acción basada en los resultados del análisis.
   *
   * LÓGICA:
   * Evalúa si hay un ganador significativo y genera un texto de recomendación
   * con el contexto del lift y la confianza alcanzada. Si no hay ganador,
   * sugiere continuar recopilando datos.
   *
   * @param bool $has_winner
   *   Si se detectó un ganador significativo.
   * @param int|null $winner_id
   *   ID de la variante ganadora.
   * @param float $confidence
   *   Nivel de confianza del ganador.
   * @param float $lift
   *   Lift porcentual del ganador sobre el control.
   * @param array $results
   *   Datos de todas las variantes para contexto.
   *
   * @return string
   *   Texto de recomendación.
   */
  protected function generateRecommendation(bool $has_winner, ?int $winner_id, float $confidence, float $lift, array $results): string {
    if (!$has_winner) {
      // Verificar si hay suficientes datos.
      $total_visitors = 0;
      foreach ($results as $r) {
        $total_visitors += $r['visitors'] ?? 0;
      }

      if ($total_visitors < 100) {
        return (string) t('Datos insuficientes. Se necesitan al menos 100 visitantes por variante para obtener resultados fiables. Continuar recopilando tráfico.');
      }

      return (string) t('No se ha detectado un ganador estadísticamente significativo. Se recomienda continuar el experimento para acumular más datos o considerar aumentar el efecto mínimo detectable.');
    }

    // Encontrar el nombre del ganador.
    $winner_name = '';
    foreach ($results as $r) {
      if (($r['id'] ?? 0) === $winner_id) {
        $winner_name = $r['name'] ?? '';
        break;
      }
    }

    return (string) t('Ganador detectado: @name con un lift de @lift% y @confidence% de confianza. Se recomienda implementar esta variante como la nueva versión por defecto y detener el experimento.', [
      '@name' => $winner_name,
      '@lift' => number_format($lift, 1),
      '@confidence' => number_format($confidence, 1),
    ]);
  }

}
