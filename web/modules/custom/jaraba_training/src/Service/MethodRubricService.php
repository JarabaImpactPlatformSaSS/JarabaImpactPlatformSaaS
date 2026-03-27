<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio de rúbrica del Método Jaraba.
 *
 * Gestiona las 4 competencias (Pedir, Evaluar, Iterar, Integrar),
 * las 3 capas (Criterio, Supervisión IA, Posicionamiento), y el
 * cálculo del nivel global (1-4).
 *
 * CERT-08: Nivel global = mínimo de las 4 competencias.
 * CERT-03: Validación de completitud de portfolio antes de evaluación.
 */
class MethodRubricService {

  use StringTranslationTrait;

  /**
   * Las 4 competencias del Método Jaraba.
   */
  public const COMPETENCIES = ['pedir', 'evaluar', 'iterar', 'integrar'];

  /**
   * Las 3 capas del Método Jaraba.
   */
  public const LAYERS = ['criterio', 'supervision_ia', 'posicionamiento'];

  /**
   * Niveles de rúbrica (1-4).
   */
  public const LEVELS = [
    1 => 'novel',
    2 => 'aprendiz',
    3 => 'competente',
    4 => 'autonomo',
  ];

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el nivel global como el mínimo de las 4 competencias.
   *
   * CERT-08: Si una competencia es nivel 2 y las demás nivel 3,
   * el nivel global es 2.
   *
   * @param array<string, int> $scores
   *   Puntuaciones por competencia ['pedir' => 3, 'evaluar' => 2, ...].
   *
   * @return int
   *   Nivel global (1-4). 0 si faltan puntuaciones.
   */
  public function calculateOverallLevel(array $scores): int {
    $values = [];
    foreach (self::COMPETENCIES as $comp) {
      $score = $scores[$comp] ?? 0;
      if ($score < 1 || $score > 4) {
        return 0;
      }
      $values[] = $score;
    }
    return min($values);
  }

  /**
   * Detecta disparidad significativa entre competencias.
   *
   * Si hay más de 1 nivel de diferencia entre la mayor y la menor
   * puntuación, retorna un mensaje de advertencia.
   *
   * @param array<string, int> $scores
   *   Puntuaciones por competencia.
   *
   * @return string|null
   *   Mensaje de advertencia o NULL si no hay disparidad.
   */
  public function checkScoreDisparity(array $scores): ?string {
    $values = array_filter(
      array_map(fn($c) => $scores[$c] ?? 0, self::COMPETENCIES),
      fn($v) => $v > 0
    );
    if (count($values) < 2) {
      return NULL;
    }
    $diff = max($values) - min($values);
    if ($diff > 1) {
      return (string) $this->t(
        'Disparidad significativa detectada: @diff niveles entre la competencia más alta y la más baja. Considere reforzar las áreas más débiles.',
        ['@diff' => $diff]
      );
    }
    return NULL;
  }

  /**
   * Obtiene los indicadores observables para un nivel de una competencia.
   *
   * Los indicadores se cargan desde configuración YAML editable desde admin.
   * Fallback a indicadores por defecto si no hay config.
   *
   * @param string $competency
   *   Competencia (pedir, evaluar, iterar, integrar).
   * @param int $level
   *   Nivel (1-4).
   *
   * @return array<int, string>
   *   Lista de indicadores observables.
   */
  public function getIndicatorsForLevel(string $competency, int $level): array {
    $config = \Drupal::config('jaraba_training.rubric_config');
    $indicators = $config->get("indicators.$competency.$level");

    if (is_array($indicators) && $indicators !== []) {
      return $indicators;
    }

    return $this->getDefaultIndicators($competency, $level);
  }

  /**
   * Indicadores por defecto cuando no hay configuración YAML.
   */
  protected function getDefaultIndicators(string $competency, int $level): array {
    $defaults = [
      'pedir' => [
        1 => [(string) $this->t('Formula prompts básicos con asistencia')],
        2 => [(string) $this->t('Diseña prompts estructurados con contexto')],
        3 => [(string) $this->t('Crea flujos multi-prompt complejos')],
        4 => [(string) $this->t('Diseña sistemas de prompting para terceros')],
      ],
      'evaluar' => [
        1 => [(string) $this->t('Identifica errores evidentes en outputs')],
        2 => [(string) $this->t('Detecta sesgos y errores no obvios')],
        3 => [(string) $this->t('Aplica criterios profesionales de evaluación')],
        4 => [(string) $this->t('Define marcos de evaluación para equipos')],
      ],
      'iterar' => [
        1 => [(string) $this->t('Ajusta prompts de forma básica')],
        2 => [(string) $this->t('Itera con estrategia para mejorar resultados')],
        3 => [(string) $this->t('Optimiza flujos completos iterativamente')],
        4 => [(string) $this->t('Diseña ciclos de iteración automatizados')],
      ],
      'integrar' => [
        1 => [(string) $this->t('Usa un agente IA con asistencia')],
        2 => [(string) $this->t('Combina 2+ herramientas IA en una tarea')],
        3 => [(string) $this->t('Integra outputs de múltiples agentes')],
        4 => [(string) $this->t('Diseña workflows multi-agente para clientes')],
      ],
    ];

    return $defaults[$competency][$level] ?? [];
  }

}
