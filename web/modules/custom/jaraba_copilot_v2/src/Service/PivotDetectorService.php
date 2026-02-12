<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio para detectar señales de pivot en el negocio del emprendedor.
 *
 * Según Ries (Lean Startup) y Osterwalder (Invincible Company),
 * existen 10 tipos principales de pivots que un emprendedor puede hacer.
 *
 * Este servicio analiza métricas, experimentos y conversaciones para
 * detectar cuándo es apropiado considerar un pivot.
 *
 * @see The Lean Startup (Eric Ries)
 * @see The Invincible Company (Osterwalder)
 */
class PivotDetectorService
{

    /**
     * Los 10 tipos de pivot según Ries/Osterwalder.
     */
    const PIVOT_TYPES = [
        'zoom_in' => [
            'name' => 'Zoom-in Pivot',
            'name_es' => 'Pivot de Enfoque',
            'description' => 'Una feature del producto se convierte en el producto completo.',
            'signals' => ['feature popular', 'una función destaca', 'los clientes solo usan'],
            'action' => 'Enfócate en esa única feature y hazla tu producto principal.',
        ],
        'zoom_out' => [
            'name' => 'Zoom-out Pivot',
            'name_es' => 'Pivot de Expansión',
            'description' => 'El producto actual se convierte en una feature de algo más grande.',
            'signals' => ['producto insuficiente', 'necesitan más', 'funcionalidad limitada'],
            'action' => 'Expande el producto para cubrir necesidades más amplias.',
        ],
        'customer_segment' => [
            'name' => 'Customer Segment Pivot',
            'name_es' => 'Pivot de Segmento',
            'description' => 'El producto resuelve un problema real pero para un segmento diferente.',
            'signals' => ['otro público compra', 'clientes inesperados', 'segmento diferente'],
            'action' => 'Reorienta marketing y producto hacia el nuevo segmento.',
        ],
        'customer_need' => [
            'name' => 'Customer Need Pivot',
            'name_es' => 'Pivot de Necesidad',
            'description' => 'Descubres que el cliente tiene un problema más importante que resolver.',
            'signals' => ['problema diferente', 'necesidad real distinta', 'no es el dolor principal'],
            'action' => 'Pivota hacia resolver el problema más urgente del cliente.',
        ],
        'platform' => [
            'name' => 'Platform Pivot',
            'name_es' => 'Pivot de Plataforma',
            'description' => 'Cambiar de aplicación a plataforma, o viceversa.',
            'signals' => ['quieren integrar', 'API pedida', 'ecosistema', 'marketplace'],
            'action' => 'Transforma tu app en plataforma con API abierta.',
        ],
        'business_architecture' => [
            'name' => 'Business Architecture Pivot',
            'name_es' => 'Pivot de Arquitectura',
            'description' => 'Cambiar de high margin/low volume a low margin/high volume, o viceversa.',
            'signals' => ['escala', 'mass market', 'nicho premium', 'volumen vs margen'],
            'action' => 'Reestructura precios y operaciones según el nuevo modelo.',
        ],
        'value_capture' => [
            'name' => 'Value Capture Pivot',
            'name_es' => 'Pivot de Monetización',
            'description' => 'Cambiar cómo capturas valor (modelo de ingresos).',
            'signals' => ['no pagan', 'modelo de pago incorrecto', 'freemium no convierte', 'pricing'],
            'action' => 'Experimenta con diferentes modelos: suscripción, transacción, etc.',
        ],
        'engine_of_growth' => [
            'name' => 'Engine of Growth Pivot',
            'name_es' => 'Pivot de Motor de Crecimiento',
            'description' => 'Cambiar la estrategia de crecimiento: viral, pagada, o sticky.',
            'signals' => ['crecimiento lento', 'CAC alto', 'no hay viralidad', 'retención baja'],
            'action' => 'Pivota entre motor viral, pagado, o de retención.',
        ],
        'channel' => [
            'name' => 'Channel Pivot',
            'name_es' => 'Pivot de Canal',
            'description' => 'Cambiar el canal de distribución o venta.',
            'signals' => ['canal no funciona', 'venta directa vs indirect', 'online vs offline'],
            'action' => 'Experimenta con canales alternativos de distribución.',
        ],
        'technology' => [
            'name' => 'Technology Pivot',
            'name_es' => 'Pivot Tecnológico',
            'description' => 'Usar tecnología diferente para entregar la misma solución.',
            'signals' => ['tecnología obsoleta', 'hay algo mejor', 'costes altos de desarrollo'],
            'action' => 'Migra a la nueva tecnología manteniendo la propuesta de valor.',
        ],
    ];

    /**
     * Señales rojas que indican necesidad de pivot.
     */
    const RED_FLAGS = [
        ['signal' => 'no hay tracción', 'severity' => 'high', 'weeks_without_progress' => 4],
        ['signal' => 'los clientes no vuelven', 'severity' => 'high', 'churn' => 0.15],
        ['signal' => 'CAC > LTV', 'severity' => 'critical', 'ratio' => 1.0],
        ['signal' => 'hipótesis invalidadas repetidamente', 'severity' => 'medium', 'count' => 3],
        ['signal' => 'feedback negativo consistente', 'severity' => 'medium', 'nps' => -10],
        ['signal' => 'métricas planas o en declive', 'severity' => 'high', 'trend' => 'declining'],
    ];

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Logger.
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Analiza la necesidad de pivotar basado en el contexto del emprendedor.
     *
     * @param array $context
     *   Contexto del emprendedor con métricas y experimentos.
     *
     * @return array
     *   Análisis de pivot con recomendaciones.
     */
    public function analyzePivotNeed(array $context): array
    {
        $redFlags = $this->detectRedFlags($context);
        $suggestedPivots = $this->suggestPivotTypes($context);

        $pivotScore = $this->calculatePivotScore($redFlags);

        return [
            'should_consider_pivot' => $pivotScore >= 50,
            'pivot_score' => $pivotScore,
            'urgency' => $this->determineUrgency($pivotScore),
            'red_flags' => $redFlags,
            'suggested_pivots' => $suggestedPivots,
            'recommendation' => $this->generateRecommendation($pivotScore, $redFlags, $suggestedPivots),
        ];
    }

    /**
     * Detecta señales rojas en el contexto.
     */
    protected function detectRedFlags(array $context): array
    {
        $detected = [];

        // Hipótesis invalidadas
        $invalidated = $context['hypothesis_stats']['invalidated'] ?? 0;
        if ($invalidated >= 3) {
            $detected[] = [
                'flag' => 'hipótesis invalidadas repetidamente',
                'severity' => 'medium',
                'value' => $invalidated,
            ];
        }

        // Experimentos fallidos
        $failedExperiments = $context['experiments_stats']['failed'] ?? 0;
        $totalExperiments = $context['experiments_stats']['total'] ?? 1;
        if ($totalExperiments > 0 && ($failedExperiments / $totalExperiments) > 0.7) {
            $detected[] = [
                'flag' => 'mayoría de experimentos fallidos',
                'severity' => 'high',
                'value' => round(($failedExperiments / $totalExperiments) * 100) . '%',
            ];
        }

        // Field exits sin validación positiva
        $fieldExits = $context['field_exits_count'] ?? 0;
        $positiveValidation = $context['positive_validations'] ?? 0;
        if ($fieldExits >= 10 && $positiveValidation < 2) {
            $detected[] = [
                'flag' => 'muchas entrevistas sin validación positiva',
                'severity' => 'high',
                'value' => "{$positiveValidation}/{$fieldExits}",
            ];
        }

        return $detected;
    }

    /**
     * Sugiere tipos de pivot basados en el contexto.
     */
    protected function suggestPivotTypes(array $context): array
    {
        $suggestions = [];

        // Analizar texto de feedback/notas si está disponible
        $notes = $context['recent_notes'] ?? '';
        $notesLower = mb_strtolower($notes);

        foreach (self::PIVOT_TYPES as $pivotId => $pivot) {
            $matchScore = 0;
            foreach ($pivot['signals'] as $signal) {
                if (mb_strpos($notesLower, $signal) !== FALSE) {
                    $matchScore += 1;
                }
            }

            if ($matchScore > 0) {
                $suggestions[$pivotId] = [
                    'pivot' => $pivot,
                    'match_score' => $matchScore,
                ];
            }
        }

        uasort($suggestions, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

        return array_slice($suggestions, 0, 3, TRUE);
    }

    /**
     * Calcula puntuación de pivot (0-100).
     */
    protected function calculatePivotScore(array $redFlags): int
    {
        $score = 0;

        foreach ($redFlags as $flag) {
            switch ($flag['severity']) {
                case 'critical':
                    $score += 40;
                    break;

                case 'high':
                    $score += 25;
                    break;

                case 'medium':
                    $score += 15;
                    break;
            }
        }

        return min(100, $score);
    }

    /**
     * Determina urgencia del pivot.
     */
    protected function determineUrgency(int $score): string
    {
        if ($score >= 80) {
            return 'critical';
        }
        if ($score >= 50) {
            return 'high';
        }
        if ($score >= 25) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Genera recomendación textual.
     */
    protected function generateRecommendation(int $score, array $redFlags, array $suggestedPivots): string
    {
        if ($score < 25) {
            return 'Continúa con tu estrategia actual. Las métricas no muestran señales de alerta significativas.';
        }

        if ($score >= 80) {
            $pivotNames = array_map(
                fn($p) => $p['pivot']['name_es'],
                array_slice($suggestedPivots, 0, 2, TRUE)
            );
            $pivotList = implode(' o ', $pivotNames) ?: 'cualquier dirección alternativa';
            return "⚠️ ALERTA CRÍTICA: Considera seriamente pivotar hacia {$pivotList}. Múltiples señales indican que la estrategia actual no está funcionando.";
        }

        if ($score >= 50) {
            return 'Hay señales moderadas de que deberías considerar ajustes significativos. Realiza más experimentos para confirmar antes de pivotar.';
        }

        return 'Algunas señales menores. Monitorea de cerca en las próximas semanas.';
    }

    /**
     * Genera resumen para prompt del Copiloto.
     */
    public function getPivotSummaryForPrompt(array $context): string
    {
        $analysis = $this->analyzePivotNeed($context);

        if ($analysis['pivot_score'] < 25) {
            return 'Sin señales de pivot necesario.';
        }

        $flagCount = count($analysis['red_flags']);
        return "Señales de pivot: {$flagCount} red flags, urgencia {$analysis['urgency']}. Score: {$analysis['pivot_score']}/100.";
    }

    /**
     * Obtiene todos los tipos de pivot.
     */
    public function getAllPivotTypes(): array
    {
        return self::PIVOT_TYPES;
    }

}
