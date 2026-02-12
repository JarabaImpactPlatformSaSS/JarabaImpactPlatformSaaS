<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;

/**
 * Servicio de detección inteligente de modo del Copiloto.
 *
 * Implementa scoring por triggers según la especificación del Router Inteligente,
 * analizando el mensaje del usuario y su contexto para determinar el modo más
 * apropiado del Copiloto.
 *
 * Modos disponibles:
 * - coach: Apoyo emocional (síndrome impostor, bloqueos)
 * - consultor: Instrucciones paso a paso
 * - sparring: Simulación y roleplay
 * - cfo: Cálculos financieros
 * - fiscal: Normativa tributaria (RAG)
 * - laboral: Seguridad Social (RAG)
 * - devil: Cuestionamiento de hipótesis
 */
class ModeDetectorService
{

    /**
     * Database connection.
     */
    protected ?Connection $database;

    /**
     * Cache backend for triggers.
     */
    protected ?CacheBackendInterface $triggersCache;

    /**
     * Constructor.
     */
    public function __construct(?Connection $database = NULL, ?CacheBackendInterface $triggersCache = NULL) {
        $this->database = $database;
        $this->triggersCache = $triggersCache;
    }

    /**
     * Triggers por modo con pesos asociados.
     */
    const MODE_TRIGGERS = [
        'coach' => [
            // Emocionales
            ['word' => 'miedo', 'weight' => 10],
            ['word' => 'no puedo', 'weight' => 10],
            ['word' => 'agobio', 'weight' => 9],
            ['word' => 'agobiado', 'weight' => 9],
            ['word' => 'bloqueo', 'weight' => 9],
            ['word' => 'bloqueado', 'weight' => 9],
            ['word' => 'impostor', 'weight' => 12],
            ['word' => 'vergüenza', 'weight' => 8],
            ['word' => 'culpa', 'weight' => 7],
            ['word' => 'fracaso', 'weight' => 10],
            ['word' => 'hundido', 'weight' => 10],
            ['word' => 'ansiedad', 'weight' => 9],
            ['word' => 'estrés', 'weight' => 8],
            ['word' => 'desmotivado', 'weight' => 8],
            ['word' => 'inseguro', 'weight' => 8],
            ['word' => 'me siento', 'weight' => 5],
            ['word' => 'no sé si puedo', 'weight' => 10],
        ],
        'consultor' => [
            // Instrucciones
            ['word' => 'cómo hago', 'weight' => 8],
            ['word' => 'cómo puedo', 'weight' => 7],
            ['word' => 'paso a paso', 'weight' => 10],
            ['word' => 'tutorial', 'weight' => 10],
            ['word' => 'herramienta', 'weight' => 6],
            ['word' => 'configurar', 'weight' => 7],
            ['word' => 'crear', 'weight' => 5],
            ['word' => 'montar', 'weight' => 6],
            ['word' => 'instalar', 'weight' => 7],
            ['word' => 'explicame', 'weight' => 6],
            ['word' => 'explícame', 'weight' => 6],
            ['word' => 'qué necesito', 'weight' => 7],
            ['word' => 'checklist', 'weight' => 9],
            ['word' => 'pasos', 'weight' => 6],
        ],
        'sparring' => [
            // Simulación
            ['word' => 'qué te parece', 'weight' => 8],
            ['word' => 'valídame', 'weight' => 9],
            ['word' => 'validame', 'weight' => 9],
            ['word' => 'practica', 'weight' => 9],
            ['word' => 'practicar', 'weight' => 9],
            ['word' => 'simula', 'weight' => 10],
            ['word' => 'simular', 'weight' => 10],
            ['word' => 'feedback', 'weight' => 8],
            ['word' => 'cliente', 'weight' => 5],
            ['word' => 'pitch', 'weight' => 10],
            ['word' => 'inversor', 'weight' => 9],
            ['word' => 'roleplay', 'weight' => 12],
            ['word' => 'ensayar', 'weight' => 9],
            ['word' => 'presentación', 'weight' => 7],
            ['word' => 'objeción', 'weight' => 8],
            ['word' => 'convencer', 'weight' => 6],
        ],
        'cfo' => [
            // Finanzas
            ['word' => 'precio', 'weight' => 9],
            ['word' => 'cobrar', 'weight' => 9],
            ['word' => 'tarifa', 'weight' => 9],
            ['word' => 'margen', 'weight' => 10],
            ['word' => 'coste', 'weight' => 8],
            ['word' => 'costo', 'weight' => 8],
            ['word' => 'rentable', 'weight' => 10],
            ['word' => 'rentabilidad', 'weight' => 10],
            ['word' => 'euros', 'weight' => 6],
            ['word' => 'caro', 'weight' => 7],
            ['word' => 'barato', 'weight' => 7],
            ['word' => 'facturar', 'weight' => 8],
            ['word' => 'ingresos', 'weight' => 8],
            ['word' => 'gastos', 'weight' => 7],
            ['word' => 'break even', 'weight' => 12],
            ['word' => 'punto de equilibrio', 'weight' => 12],
            ['word' => 'unit economics', 'weight' => 12],
        ],
        'fiscal' => [
            // Tributario
            ['word' => 'hacienda', 'weight' => 12],
            ['word' => 'iva', 'weight' => 12],
            ['word' => 'irpf', 'weight' => 12],
            ['word' => 'modelo 303', 'weight' => 15],
            ['word' => 'modelo 130', 'weight' => 15],
            ['word' => 'modelo 036', 'weight' => 15],
            ['word' => 'modelo 037', 'weight' => 15],
            ['word' => 'factura', 'weight' => 8],
            ['word' => 'declaración', 'weight' => 9],
            ['word' => 'impuestos', 'weight' => 11],
            ['word' => 'aeat', 'weight' => 12],
            ['word' => 'verifactu', 'weight' => 15],
            ['word' => 'deducible', 'weight' => 10],
            ['word' => 'retención', 'weight' => 10],
            ['word' => 'alta censal', 'weight' => 12],
            ['word' => 'trimestre', 'weight' => 7],
        ],
        'laboral' => [
            // Seguridad Social
            ['word' => 'autónomo', 'weight' => 10],
            ['word' => 'cuota', 'weight' => 10],
            ['word' => 'reta', 'weight' => 12],
            ['word' => 'tarifa plana', 'weight' => 15],
            ['word' => 'seguridad social', 'weight' => 12],
            ['word' => 'cotización', 'weight' => 11],
            ['word' => 'cotizar', 'weight' => 10],
            ['word' => 'baja', 'weight' => 7],
            ['word' => 'alta autónomo', 'weight' => 12],
            ['word' => 'pluriactividad', 'weight' => 12],
            ['word' => 'maternidad', 'weight' => 10],
            ['word' => 'paternidad', 'weight' => 10],
            ['word' => 'incapacidad', 'weight' => 9],
            ['word' => 'paro', 'weight' => 8],
            ['word' => 'cese actividad', 'weight' => 12],
            ['word' => '80 euros', 'weight' => 15],
        ],
        'devil' => [
            // Cuestionamiento
            ['word' => 'estoy seguro', 'weight' => 9],
            ['word' => 'todos quieren', 'weight' => 10],
            ['word' => 'es obvio', 'weight' => 10],
            ['word' => 'sin duda', 'weight' => 9],
            ['word' => 'funcionará', 'weight' => 8],
            ['word' => 'es único', 'weight' => 9],
            ['word' => 'no hay competencia', 'weight' => 12],
            ['word' => 'todo el mundo', 'weight' => 8],
            ['word' => 'seguro que', 'weight' => 8],
            ['word' => 'cuestiona', 'weight' => 10],
            ['word' => 'desafía', 'weight' => 10],
            ['word' => 'ponme a prueba', 'weight' => 12],
            ['word' => 'crítica', 'weight' => 7],
        ],
        // === v3: Nuevos modos Osterwalder/Blank ===
        'vpc_designer' => [
            // Value Proposition Canvas
            ['word' => 'propuesta de valor', 'weight' => 15],
            ['word' => 'value proposition', 'weight' => 15],
            ['word' => 'vpc', 'weight' => 12],
            ['word' => 'diferencial', 'weight' => 10],
            ['word' => 'cliente objetivo', 'weight' => 9],
            ['word' => 'segmento', 'weight' => 8],
            ['word' => 'jobs to be done', 'weight' => 12],
            ['word' => 'trabajos del cliente', 'weight' => 10],
            ['word' => 'pains', 'weight' => 10],
            ['word' => 'dolores', 'weight' => 9],
            ['word' => 'gains', 'weight' => 10],
            ['word' => 'beneficios esperados', 'weight' => 9],
            ['word' => 'pain relievers', 'weight' => 12],
            ['word' => 'aliviadores', 'weight' => 9],
            ['word' => 'gain creators', 'weight' => 12],
            ['word' => 'generadores', 'weight' => 8],
            ['word' => 'encaje', 'weight' => 10],
            ['word' => 'fit', 'weight' => 8],
            ['word' => 'por qué elegirme', 'weight' => 10],
        ],
        'customer_discovery' => [
            // Customer Development - Blank/Dorf
            ['word' => 'entrevista', 'weight' => 9],
            ['word' => 'entrevistar', 'weight' => 9],
            ['word' => 'salir del edificio', 'weight' => 15],
            ['word' => 'sal del edificio', 'weight' => 15],
            ['word' => 'get out of the building', 'weight' => 15],
            ['word' => 'validar problema', 'weight' => 12],
            ['word' => 'validar hipótesis', 'weight' => 11],
            ['word' => 'customer discovery', 'weight' => 15],
            ['word' => 'descubrimiento', 'weight' => 8],
            ['word' => 'early adopter', 'weight' => 12],
            ['word' => 'primeros clientes', 'weight' => 10],
            ['word' => 'problema real', 'weight' => 10],
            ['word' => 'hablar con clientes', 'weight' => 11],
            ['word' => 'guión entrevista', 'weight' => 12],
            ['word' => 'preguntas abiertas', 'weight' => 9],
            ['word' => 'mom test', 'weight' => 12],
        ],
        'pattern_expert' => [
            // Business Model Patterns - Osterwalder
            ['word' => 'patrón de negocio', 'weight' => 12],
            ['word' => 'business pattern', 'weight' => 12],
            ['word' => 'modelo de negocio', 'weight' => 9],
            ['word' => 'freemium', 'weight' => 12],
            ['word' => 'suscripción', 'weight' => 10],
            ['word' => 'marketplace', 'weight' => 11],
            ['word' => 'long tail', 'weight' => 12],
            ['word' => 'multi-sided', 'weight' => 12],
            ['word' => 'plataforma', 'weight' => 8],
            ['word' => 'open business', 'weight' => 10],
            ['word' => 'navaja y cuchilla', 'weight' => 12],
            ['word' => 'razor blade', 'weight' => 12],
            ['word' => 'lock-in', 'weight' => 10],
            ['word' => 'recurrente', 'weight' => 9],
            ['word' => 'estrategia de monetización', 'weight' => 11],
        ],
        'pivot_advisor' => [
            // Pivots y Explore/Exploit - Osterwalder/Ries
            ['word' => 'pivotar', 'weight' => 15],
            ['word' => 'pivot', 'weight' => 15],
            ['word' => 'cambiar de dirección', 'weight' => 10],
            ['word' => 'no funciona', 'weight' => 9],
            ['word' => 'replantear', 'weight' => 10],
            ['word' => 'empezar de nuevo', 'weight' => 9],
            ['word' => 'cambiar modelo', 'weight' => 11],
            ['word' => 'explorar explotar', 'weight' => 12],
            ['word' => 'explore exploit', 'weight' => 12],
            ['word' => 'zoom in', 'weight' => 10],
            ['word' => 'zoom out', 'weight' => 10],
            ['word' => 'customer segment pivot', 'weight' => 12],
            ['word' => 'value capture pivot', 'weight' => 12],
            ['word' => 'channel pivot', 'weight' => 12],
            ['word' => 'señales de fracaso', 'weight' => 10],
            ['word' => 'métricas rojas', 'weight' => 11],
        ],
    ];

    /**
     * Modificadores de contexto por carril del emprendedor.
     */
    const CARRIL_MODIFIERS = [
        'IMPULSO' => ['coach' => 1.3, 'consultor' => 1.0],
        'LANZADERA' => ['consultor' => 1.2, 'cfo' => 1.1],
        'ACELERA' => ['cfo' => 1.3, 'sparring' => 1.2],
    ];

    /**
     * Detecta el modo apropiado basado en el mensaje y contexto.
     *
     * @param string $message
     *   Mensaje del usuario.
     * @param array $context
     *   Contexto del emprendedor (carril, fase, etc.).
     *
     * @return array
     *   Array con:
     *   - mode: string (el modo detectado)
     *   - score: float (puntuación del modo ganador)
     *   - confidence: string (high, medium, low)
     *   - all_scores: array (puntuaciones de todos los modos)
     */
    public function detectMode(string $message, array $context = []): array
    {
        $messageLower = mb_strtolower($message);
        $scores = [];

        // 1. Calcular puntuación base por triggers (BD con fallback a const)
        $triggers = $this->loadTriggersFromDb();
        foreach ($triggers as $mode => $modeTriggers) {
            $scores[$mode] = 0;
            foreach ($modeTriggers as $trigger) {
                if (mb_strpos($messageLower, $trigger['word']) !== FALSE) {
                    $scores[$mode] += $trigger['weight'];
                }
            }
        }

        // 2. Aplicar modificadores de contexto (carril)
        $carril = strtoupper($context['carril'] ?? '');
        if (isset(self::CARRIL_MODIFIERS[$carril])) {
            foreach (self::CARRIL_MODIFIERS[$carril] as $mode => $modifier) {
                if (isset($scores[$mode])) {
                    $scores[$mode] *= $modifier;
                }
            }
        }

        // 3. Boost para modo coach si detectamos emoción fuerte
        $emotionScore = $this->analyzeEmotion($message);
        if ($emotionScore > 0.7) {
            $scores['coach'] = ($scores['coach'] ?? 0) + 15;
        }

        // 4. Seleccionar modo con mayor puntuación
        arsort($scores);
        $modes = array_keys($scores);
        $topMode = $modes[0] ?? 'consultor';
        $topScore = $scores[$topMode] ?? 0;

        // 5. Determinar confianza
        $secondScore = $scores[$modes[1] ?? 'consultor'] ?? 0;
        $scoreDiff = $topScore - $secondScore;

        if ($topScore < 5) {
            $confidence = 'low';
            $topMode = 'consultor'; // Default si no hay señal clara
        } elseif ($scoreDiff > 10) {
            $confidence = 'high';
        } elseif ($scoreDiff > 5) {
            $confidence = 'medium';
        } else {
            $confidence = 'low';
        }

        return [
            'mode' => $topMode,
            'score' => round($topScore, 2),
            'confidence' => $confidence,
            'emotion_score' => round($emotionScore, 2),
            'all_scores' => array_map(fn($s) => round($s, 2), $scores),
        ];
    }

    /**
     * Analiza el nivel de emoción en el mensaje.
     *
     * @param string $message
     *   El mensaje a analizar.
     *
     * @return float
     *   Score de emoción entre 0 y 1.
     */
    protected function analyzeEmotion(string $message): float
    {
        $emotionIndicators = [
            // Indicadores fuertes
            'no puedo más' => 0.9,
            'estoy destrozado' => 0.95,
            'me rindo' => 0.9,
            'quiero dejarlo' => 0.85,
            'no sé qué hacer' => 0.7,
            'tengo miedo' => 0.8,
            'me siento fatal' => 0.85,
            'estoy agotado' => 0.75,
            'no valgo' => 0.9,
            // Indicadores moderados
            'preocupado' => 0.5,
            'nervioso' => 0.5,
            'ansioso' => 0.6,
            'frustrado' => 0.6,
            'cansado' => 0.4,
            'dudas' => 0.4,
            'confundido' => 0.4,
        ];

        $messageLower = mb_strtolower($message);
        $maxScore = 0;

        foreach ($emotionIndicators as $indicator => $score) {
            if (mb_strpos($messageLower, $indicator) !== FALSE) {
                $maxScore = max($maxScore, $score);
            }
        }

        // Boost adicional por signos de exclamación múltiples o mayúsculas extensas
        if (preg_match('/[!¡]{2,}/', $message)) {
            $maxScore = min(1, $maxScore + 0.1);
        }
        if (preg_match('/[A-Z]{4,}/', $message)) {
            $maxScore = min(1, $maxScore + 0.1);
        }

        return $maxScore;
    }

    /**
     * Carga triggers desde BD con cache (TTL 1h). Fallback al const si BD vacia.
     *
     * @return array
     *   Triggers agrupados por modo: ['mode' => [['word' => ..., 'weight' => ...], ...]].
     */
    public function loadTriggersFromDb(): array {
        // Intentar cache primero.
        if ($this->triggersCache) {
            $cached = $this->triggersCache->get('mode_triggers_all');
            if ($cached) {
                return $cached->data;
            }
        }

        // Intentar cargar desde BD.
        if ($this->database) {
            try {
                if ($this->database->schema()->tableExists('copilot_mode_triggers')) {
                    $results = $this->database->select('copilot_mode_triggers', 't')
                        ->fields('t', ['mode', 'trigger_word', 'weight'])
                        ->condition('active', 1)
                        ->orderBy('mode')
                        ->orderBy('weight', 'DESC')
                        ->execute()
                        ->fetchAll();

                    if (!empty($results)) {
                        $triggers = [];
                        foreach ($results as $row) {
                            $triggers[$row->mode][] = [
                                'word' => $row->trigger_word,
                                'weight' => (int) $row->weight,
                            ];
                        }

                        // Guardar en cache con TTL de 1 hora.
                        if ($this->triggersCache) {
                            $this->triggersCache->set('mode_triggers_all', $triggers, time() + 3600);
                        }
                        return $triggers;
                    }
                }
            }
            catch (\Exception $e) {
                // Fallback silencioso al const.
            }
        }

        // Fallback al const hardcodeado.
        return self::MODE_TRIGGERS;
    }

    /**
     * Obtiene los triggers disponibles para un modo.
     *
     * @param string $mode
     *   El modo del copiloto.
     *
     * @return array
     *   Lista de triggers del modo.
     */
    public function getTriggersForMode(string $mode): array
    {
        $triggers = $this->loadTriggersFromDb();
        return $triggers[$mode] ?? [];
    }

    /**
     * Obtiene todos los modos disponibles.
     *
     * @return array
     *   Lista de modos disponibles.
     */
    public function getAvailableModes(): array
    {
        $triggers = $this->loadTriggersFromDb();
        return array_keys($triggers);
    }

}
