<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente entre modelos Python ML y heuristicos PHP.
 *
 * ESTRUCTURA:
 *   Capa de abstraccion que permite invocar modelos predictivos
 *   implementados en Python (via proc_open) con fallback automatico
 *   a heuristicos PHP cuando Python no esta disponible o falla.
 *   Comunica con los scripts Python mediante JSON stdin/stdout.
 *
 * LOGICA:
 *   1. Verifica si Python esta habilitado y disponible (config).
 *   2. Si disponible: invoca el script Python con features como JSON
 *      en stdin, lee resultado JSON de stdout, con timeout de 30s.
 *   3. Si no disponible o falla: ejecuta heuristico PHP equivalente.
 *   4. Registra siempre la fuente ('python' o 'heuristic') y el
 *      tiempo de ejecucion en milisegundos.
 *
 * RELACIONES:
 *   - Consume: config.factory (python_enabled, python_path, scripts_path).
 *   - Consumido por: ChurnPredictorService, LeadScorerService (futuro).
 */
class PredictionBridgeService {

  /**
   * Timeout por defecto para ejecucion de scripts Python (segundos).
   */
  protected const PYTHON_TIMEOUT_SECONDS = 30;

  /**
   * Construye el servicio puente de prediccion.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_predictive.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Fabrica de configuracion para acceder a jaraba_predictive.settings.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Ejecuta un modelo predictivo (Python o heuristico PHP).
   *
   * ESTRUCTURA:
   *   Metodo principal que decide la ruta de ejecucion y retorna
   *   el resultado unificado independientemente de la fuente.
   *
   * LOGICA:
   *   1. Si Python esta disponible: invoca proc_open con el script.
   *   2. Envia features como JSON via stdin del proceso.
   *   3. Lee respuesta JSON de stdout con timeout.
   *   4. Si falla o Python no disponible: usa heuristico PHP.
   *   5. Registra tiempo de ejecucion y fuente.
   *
   * RELACIONES:
   *   - Lee: config (python_enabled, python_path, scripts_path).
   *   - Ejecuta: scripts Python o heuristicos internos.
   *
   * @param string $modelName
   *   Nombre del modelo a ejecutar (sin extension .py).
   * @param array $features
   *   Array asociativo de features de entrada para el modelo.
   *
   * @return array
   *   Array con claves:
   *   - 'result': array con la prediccion del modelo.
   *   - 'source': 'python' o 'heuristic'.
   *   - 'execution_time_ms': float tiempo de ejecucion en milisegundos.
   */
  public function executePythonModel(string $modelName, array $features): array {
    $startTime = microtime(TRUE);

    if ($this->isPythonAvailable()) {
      try {
        $result = $this->invokeScript($modelName, $features);

        $executionTimeMs = (microtime(TRUE) - $startTime) * 1000;

        $this->logger->info('Python model @model executed successfully in @time ms.', [
          '@model' => $modelName,
          '@time' => round($executionTimeMs, 2),
        ]);

        return [
          'result' => $result,
          'source' => 'python',
          'execution_time_ms' => round($executionTimeMs, 2),
        ];
      }
      catch (\Exception $e) {
        $this->logger->warning('Python model @model failed: @message. Falling back to heuristic.', [
          '@model' => $modelName,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // --- Fallback a heuristico PHP ---
    $result = $this->executeHeuristic($modelName, $features);
    $executionTimeMs = (microtime(TRUE) - $startTime) * 1000;

    $this->logger->info('Heuristic model @model executed in @time ms.', [
      '@model' => $modelName,
      '@time' => round($executionTimeMs, 2),
    ]);

    return [
      'result' => $result,
      'source' => 'heuristic',
      'execution_time_ms' => round($executionTimeMs, 2),
    ];
  }

  /**
   * Verifica si Python esta disponible y habilitado.
   *
   * ESTRUCTURA:
   *   Metodo de verificacion de disponibilidad del motor Python.
   *
   * LOGICA:
   *   1. Comprueba que python_enabled es TRUE en config.
   *   2. Comprueba que el binario Python existe en python_path.
   *
   * RELACIONES:
   *   - Lee: config (python_enabled, python_path).
   *
   * @return bool
   *   TRUE si Python esta habilitado y el binario existe.
   */
  public function isPythonAvailable(): bool {
    $config = $this->configFactory->get('jaraba_predictive.settings');
    $pythonEnabled = (bool) $config->get('python_enabled');

    if (!$pythonEnabled) {
      return FALSE;
    }

    $pythonPath = $config->get('python_path') ?? '/usr/bin/python3';

    return file_exists($pythonPath) && is_executable($pythonPath);
  }

  /**
   * Obtiene informacion sobre un modelo disponible.
   *
   * ESTRUCTURA:
   *   Metodo informativo que retorna metadatos del modelo.
   *
   * LOGICA:
   *   Verifica existencia del script Python y retorna info:
   *   nombre, ruta, disponibilidad, tipo de fallback.
   *
   * RELACIONES:
   *   - Lee: config (scripts_path).
   *   - Lee: filesystem (existencia del script).
   *
   * @param string $modelName
   *   Nombre del modelo (sin extension .py).
   *
   * @return array
   *   Array con 'name', 'script_path', 'script_exists',
   *   'python_available', 'fallback_type'.
   */
  public function getModelInfo(string $modelName): array {
    $config = $this->configFactory->get('jaraba_predictive.settings');
    $scriptsPath = $config->get('scripts_path') ?? 'modules/custom/jaraba_predictive/scripts';
    $scriptFile = $scriptsPath . '/' . $modelName . '.py';

    return [
      'name' => $modelName,
      'script_path' => $scriptFile,
      'script_exists' => file_exists($scriptFile),
      'python_available' => $this->isPythonAvailable(),
      'fallback_type' => 'php_heuristic',
      'timeout_seconds' => self::PYTHON_TIMEOUT_SECONDS,
      'model_version' => $config->get('model_version') ?? 'heuristic_v1',
    ];
  }

  /**
   * Invoca un script Python via proc_open con JSON stdin/stdout.
   *
   * ESTRUCTURA: Metodo interno de ejecucion de proceso Python.
   * LOGICA: Abre proceso con proc_open, envia features como JSON
   *   a stdin, lee JSON de stdout, respeta timeout de 30s.
   *
   * @param string $modelName
   *   Nombre del modelo (sin extension .py).
   * @param array $features
   *   Features de entrada.
   *
   * @return array
   *   Resultado decodificado del JSON de salida del script.
   *
   * @throws \RuntimeException
   *   Si el proceso falla, el timeout expira o la salida no es JSON valido.
   */
  protected function invokeScript(string $modelName, array $features): array {
    $config = $this->configFactory->get('jaraba_predictive.settings');
    $pythonPath = $config->get('python_path') ?? '/usr/bin/python3';
    $scriptsPath = $config->get('scripts_path') ?? 'modules/custom/jaraba_predictive/scripts';
    $scriptFile = $scriptsPath . '/' . $modelName . '.py';

    if (!file_exists($scriptFile)) {
      throw new \RuntimeException("Script no encontrado: {$scriptFile}");
    }

    $descriptors = [
      0 => ['pipe', 'r'],  // stdin
      1 => ['pipe', 'w'],  // stdout
      2 => ['pipe', 'w'],  // stderr
    ];

    $process = proc_open(
      [$pythonPath, $scriptFile],
      $descriptors,
      $pipes,
    );

    if (!is_resource($process)) {
      throw new \RuntimeException("No se pudo iniciar el proceso Python para el modelo: {$modelName}");
    }

    // Enviar features como JSON via stdin.
    $inputJson = json_encode($features, JSON_THROW_ON_ERROR);
    fwrite($pipes[0], $inputJson);
    fclose($pipes[0]);

    // Leer stdout con timeout.
    stream_set_timeout($pipes[1], self::PYTHON_TIMEOUT_SECONDS);
    $output = stream_get_contents($pipes[1]);
    $streamMeta = stream_get_meta_data($pipes[1]);
    fclose($pipes[1]);

    // Leer stderr.
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($streamMeta['timed_out'] ?? FALSE) {
      throw new \RuntimeException("Timeout de {self::PYTHON_TIMEOUT_SECONDS}s excedido para el modelo: {$modelName}");
    }

    if ($exitCode !== 0) {
      throw new \RuntimeException("El proceso Python termino con codigo {$exitCode}. Stderr: {$errorOutput}");
    }

    if (empty($output)) {
      throw new \RuntimeException("El proceso Python no produjo salida para el modelo: {$modelName}");
    }

    $result = json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($result)) {
      throw new \RuntimeException("La salida del modelo Python no es un JSON valido: {$output}");
    }

    return $result;
  }

  /**
   * Ejecuta un heuristico PHP como fallback.
   *
   * ESTRUCTURA: Metodo interno de fallback heuristico.
   * LOGICA: Implementa logica simplificada en PHP que emula
   *   el comportamiento basico del modelo Python.
   *
   * @param string $modelName
   *   Nombre del modelo.
   * @param array $features
   *   Features de entrada.
   *
   * @return array
   *   Resultado del heuristico con estructura similar al modelo Python.
   */
  protected function executeHeuristic(string $modelName, array $features): array {
    return match ($modelName) {
      'churn_model' => $this->churnHeuristic($features),
      'lead_scoring_model' => $this->leadScoringHeuristic($features),
      'forecast_model' => $this->forecastHeuristic($features),
      default => [
        'prediction' => 0.5,
        'confidence' => 0.3,
        'model' => 'generic_heuristic',
        'warning' => "No hay heuristico especifico para el modelo: {$modelName}",
      ],
    };
  }

  /**
   * Heuristico PHP para prediccion de churn.
   *
   * ESTRUCTURA: Metodo interno de heuristico especifico.
   * LOGICA: Calcula score ponderado a partir de features clave.
   *
   * @param array $features
   *   Features del tenant.
   *
   * @return array
   *   Resultado con prediction, risk_level, confidence.
   */
  protected function churnHeuristic(array $features): array {
    $inactivity = (float) ($features['days_since_last_login'] ?? 0);
    $paymentFailures = (int) ($features['payment_failure_count'] ?? 0);
    $supportTickets = (int) ($features['support_ticket_count'] ?? 0);
    $featureAdoption = (float) ($features['feature_adoption_rate'] ?? 0.5);

    $score = 0.0;
    $score += min(40, ($inactivity / 30) * 40);
    $score += min(25, $paymentFailures * 12.5);
    $score += min(15, $supportTickets * 5);
    $score += max(0, (1 - $featureAdoption) * 20);

    $score = max(0, min(100, $score));

    return [
      'prediction' => round($score / 100, 2),
      'risk_score' => (int) round($score),
      'risk_level' => match (TRUE) {
        $score >= 85 => 'critical',
        $score >= 60 => 'high',
        $score >= 30 => 'medium',
        default => 'low',
      },
      'confidence' => 0.65,
      'model' => 'churn_heuristic_php',
    ];
  }

  /**
   * Heuristico PHP para lead scoring.
   *
   * ESTRUCTURA: Metodo interno de heuristico especifico.
   * LOGICA: Evalua engagement, activacion e intent.
   *
   * @param array $features
   *   Features del lead.
   *
   * @return array
   *   Resultado con prediction, qualification, confidence.
   */
  protected function leadScoringHeuristic(array $features): array {
    $engagement = (float) ($features['engagement_score'] ?? 0);
    $activation = (float) ($features['activation_score'] ?? 0);
    $intent = (float) ($features['intent_score'] ?? 0);

    $total = ($engagement * 0.4) + ($activation * 0.35) + ($intent * 0.25);
    $total = max(0, min(100, $total));

    return [
      'prediction' => round($total / 100, 2),
      'total_score' => (int) round($total),
      'qualification' => match (TRUE) {
        $total >= 75 => 'sales_ready',
        $total >= 50 => 'hot',
        $total >= 25 => 'warm',
        default => 'cold',
      },
      'confidence' => 0.60,
      'model' => 'lead_scoring_heuristic_php',
    ];
  }

  /**
   * Heuristico PHP para forecasting.
   *
   * ESTRUCTURA: Metodo interno de heuristico especifico.
   * LOGICA: Calcula media movil con trend lineal simple.
   *
   * @param array $features
   *   Features historicos.
   *
   * @return array
   *   Resultado con predicted_value, lower_bound, upper_bound.
   */
  protected function forecastHeuristic(array $features): array {
    $historicalValues = (array) ($features['historical_values'] ?? []);

    if (empty($historicalValues)) {
      return [
        'predicted_value' => 0.0,
        'lower_bound' => 0.0,
        'upper_bound' => 0.0,
        'confidence' => 0.20,
        'model' => 'forecast_heuristic_php',
      ];
    }

    $n = count($historicalValues);
    $lastValue = end($historicalValues);
    $mean = array_sum($historicalValues) / $n;

    // Trend: diferencia entre la media de la segunda mitad y la primera.
    $halfN = max(1, (int) ($n / 2));
    $firstHalf = array_slice($historicalValues, 0, $halfN);
    $secondHalf = array_slice($historicalValues, $halfN);

    $firstMean = !empty($firstHalf) ? array_sum($firstHalf) / count($firstHalf) : 0;
    $secondMean = !empty($secondHalf) ? array_sum($secondHalf) / count($secondHalf) : 0;

    $trend = $secondMean - $firstMean;
    $predicted = $lastValue + $trend;
    $margin = abs($predicted) * 0.15;

    return [
      'predicted_value' => round(max(0, $predicted), 2),
      'lower_bound' => round(max(0, $predicted - $margin), 2),
      'upper_bound' => round($predicted + $margin, 2),
      'confidence' => min(0.70, 0.30 + ($n * 0.03)),
      'model' => 'forecast_heuristic_php',
    ];
  }

}
