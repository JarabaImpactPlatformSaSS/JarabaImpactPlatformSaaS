<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Agente CFO Sintético para Emprendimiento Digital.
 *
 * PROPÓSITO:
 * Agente autónomo de IA que actúa como Director Financiero virtual,
 * proporcionando análisis financiero, proyecciones, y recomendaciones
 * automatizadas para emprendedores sin experiencia financiera.
 *
 * FUNCIONALIDADES:
 * - Análisis de flujo de caja
 * - Proyecciones de ingresos/gastos
 * - Alertas de tesorería
 * - Recomendaciones de ahorro fiscal (informativas, no asesoría)
 * - Dashboard de métricas clave
 *
 * BASADO EN:
 * - docs/tecnicos/20260115d-Ecosistema Jaraba_ Estrategia de Verticalización y Precios_Gemini.md
 *
 * @version 1.0.0
 */
class SyntheticCfoService
{

    /**
     * Niveles de alerta para métricas financieras.
     */
    public const ALERT_LEVELS = [
        'critical' => ['color' => 'red', 'icon' => '🚨', 'priority' => 1],
        'warning' => ['color' => 'orange', 'icon' => '⚠️', 'priority' => 2],
        'info' => ['color' => 'blue', 'icon' => 'ℹ️', 'priority' => 3],
        'positive' => ['color' => 'green', 'icon' => '✅', 'priority' => 4],
    ];

    /**
     * Categorías de gastos estándar.
     */
    public const EXPENSE_CATEGORIES = [
        'personal' => 'Nóminas y seguridad social',
        'suministros' => 'Luz, agua, telecomunicaciones',
        'alquiler' => 'Alquiler de local',
        'marketing' => 'Publicidad y promoción',
        'software' => 'Herramientas y suscripciones',
        'profesionales' => 'Asesoría, gestoría',
        'impuestos' => 'IVA, IRPF, IS',
        'otros' => 'Otros gastos',
    ];

    /**
     * The HTTP client for AI API calls.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The Multi-AI Provider Service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\MultiAiProviderService|null
     */
    protected $aiService;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_cfo');
    }

    /**
     * Sets the AI service (optional dependency).
     */
    public function setAiService($ai_service): void
    {
        $this->aiService = $ai_service;
    }

    /**
     * Analiza la salud financiera de un negocio.
     *
     * @param array $financial_data
     *   Datos financieros del periodo:
     *   - ingresos: float - Total ingresos del periodo
     *   - gastos: array - Desglose por categoría
     *   - cuentas_cobrar: float - Pendiente de cobrar
     *   - cuentas_pagar: float - Pendiente de pagar
     *   - saldo_banco: float - Saldo actual
     *
     * @return array
     *   Análisis completo con métricas, alertas y recomendaciones.
     */
    public function analyzeFinancialHealth(array $financial_data): array
    {
        $ingresos = $financial_data['ingresos'] ?? 0;
        $gastos_total = array_sum($financial_data['gastos'] ?? []);
        $cuentas_cobrar = $financial_data['cuentas_cobrar'] ?? 0;
        $cuentas_pagar = $financial_data['cuentas_pagar'] ?? 0;
        $saldo_banco = $financial_data['saldo_banco'] ?? 0;

        // Calcular métricas clave
        $beneficio_bruto = $ingresos - $gastos_total;
        $margen_bruto = $ingresos > 0 ? ($beneficio_bruto / $ingresos) * 100 : 0;
        $ratio_liquidez = $cuentas_pagar > 0 ? $saldo_banco / $cuentas_pagar : 999;
        $burn_rate = $gastos_total; // Mensual
        $runway_meses = $gastos_total > 0 ? $saldo_banco / $gastos_total : 999;

        // Generar alertas
        $alerts = $this->generateAlerts([
            'margen_bruto' => $margen_bruto,
            'ratio_liquidez' => $ratio_liquidez,
            'runway_meses' => $runway_meses,
            'saldo_banco' => $saldo_banco,
            'cuentas_cobrar' => $cuentas_cobrar,
        ]);

        // Generar recomendaciones
        $recommendations = $this->generateRecommendations($financial_data, $alerts);

        return [
            'periodo' => date('Y-m'),
            'resumen' => [
                'ingresos' => $ingresos,
                'gastos' => $gastos_total,
                'beneficio' => $beneficio_bruto,
                'margen_bruto' => round($margen_bruto, 1),
            ],
            'metricas' => [
                'ratio_liquidez' => round($ratio_liquidez, 2),
                'burn_rate' => $burn_rate,
                'runway_meses' => $runway_meses == 999 ? 'N/A' : round($runway_meses, 1),
                'endeudamiento' => $cuentas_pagar,
            ],
            'alertas' => $alerts,
            'recomendaciones' => $recommendations,
            'score_salud' => $this->calculateHealthScore($margen_bruto, $ratio_liquidez, $runway_meses),
            'generated_at' => date('c'),
        ];
    }

    /**
     * Genera alertas basadas en umbrales financieros.
     */
    protected function generateAlerts(array $metrics): array
    {
        $alerts = [];

        // Alerta de margen bajo
        if ($metrics['margen_bruto'] < 0) {
            $alerts[] = [
                'level' => 'critical',
                'message' => 'Estás operando con pérdidas este mes',
                'metric' => 'margen_bruto',
                'value' => $metrics['margen_bruto'],
            ];
        } elseif ($metrics['margen_bruto'] < 20) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Tu margen bruto es inferior al 20% recomendado',
                'metric' => 'margen_bruto',
                'value' => $metrics['margen_bruto'],
            ];
        }

        // Alerta de liquidez
        if ($metrics['ratio_liquidez'] < 1) {
            $alerts[] = [
                'level' => 'critical',
                'message' => 'No tienes liquidez suficiente para cubrir pagos pendientes',
                'metric' => 'ratio_liquidez',
                'value' => $metrics['ratio_liquidez'],
            ];
        } elseif ($metrics['ratio_liquidez'] < 1.5) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Tu liquidez está ajustada, considera acelerar cobros',
                'metric' => 'ratio_liquidez',
                'value' => $metrics['ratio_liquidez'],
            ];
        }

        // Alerta de runway
        if (is_numeric($metrics['runway_meses']) && $metrics['runway_meses'] < 3) {
            $alerts[] = [
                'level' => 'critical',
                'message' => "Solo tienes caja para {$metrics['runway_meses']} meses",
                'metric' => 'runway_meses',
                'value' => $metrics['runway_meses'],
            ];
        } elseif (is_numeric($metrics['runway_meses']) && $metrics['runway_meses'] < 6) {
            $alerts[] = [
                'level' => 'warning',
                'message' => 'Runway inferior a 6 meses, planifica financiación',
                'metric' => 'runway_meses',
                'value' => $metrics['runway_meses'],
            ];
        }

        // Alerta de cobros pendientes
        if ($metrics['cuentas_cobrar'] > $metrics['saldo_banco'] * 0.5) {
            $alerts[] = [
                'level' => 'info',
                'message' => 'Tienes mucho pendiente de cobrar, revisa la gestión de cobros',
                'metric' => 'cuentas_cobrar',
                'value' => $metrics['cuentas_cobrar'],
            ];
        }

        // Ordenar por prioridad
        usort(
            $alerts,
            fn($a, $b) =>
            self::ALERT_LEVELS[$a['level']]['priority'] <=> self::ALERT_LEVELS[$b['level']]['priority']
        );

        return $alerts;
    }

    /**
     * Genera recomendaciones automáticas basadas en el análisis.
     */
    protected function generateRecommendations(array $data, array $alerts): array
    {
        $recommendations = [];

        $gastos = $data['gastos'] ?? [];
        $total_gastos = array_sum($gastos);

        // Recomendaciones basadas en distribución de gastos
        if ($total_gastos > 0) {
            $pct_personal = (($gastos['personal'] ?? 0) / $total_gastos) * 100;
            if ($pct_personal > 60) {
                $recommendations[] = [
                    'area' => 'personal',
                    'titulo' => 'Optimizar costes de personal',
                    'descripcion' => 'Tu coste de personal supera el 60% del total. Considera automatizar procesos o externalizar.',
                    'ahorro_potencial' => round($gastos['personal'] * 0.1),
                ];
            }

            $pct_marketing = (($gastos['marketing'] ?? 0) / $total_gastos) * 100;
            if ($pct_marketing < 5) {
                $recommendations[] = [
                    'area' => 'marketing',
                    'titulo' => 'Aumentar inversión en marketing',
                    'descripcion' => 'Inviertes menos del 5% en marketing. Para crecer, considera aumentarlo al 10-15%.',
                    'ahorro_potencial' => 0,
                ];
            }
        }

        // Recomendaciones basadas en alertas
        foreach ($alerts as $alert) {
            if ($alert['metric'] === 'ratio_liquidez' && $alert['level'] === 'critical') {
                $recommendations[] = [
                    'area' => 'financiacion',
                    'titulo' => 'Buscar financiación urgente',
                    'descripcion' => 'Contacta con tu banco para una línea de crédito o considera factoring para adelantar cobros.',
                    'ahorro_potencial' => 0,
                ];
            }

            if ($alert['metric'] === 'cuentas_cobrar') {
                $recommendations[] = [
                    'area' => 'cobros',
                    'titulo' => 'Acelerar gestión de cobros',
                    'descripcion' => 'Implementa recordatorios automáticos y ofrece descuento por pronto pago.',
                    'ahorro_potencial' => round($data['cuentas_cobrar'] * 0.02),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Calcula un score de salud financiera 0-100.
     */
    protected function calculateHealthScore(float $margen, float $liquidez, $runway): int
    {
        $score = 0;

        // Margen (40 puntos)
        if ($margen >= 30) {
            $score += 40;
        } elseif ($margen >= 20) {
            $score += 30;
        } elseif ($margen >= 10) {
            $score += 20;
        } elseif ($margen >= 0) {
            $score += 10;
        }

        // Liquidez (35 puntos)
        if ($liquidez >= 2) {
            $score += 35;
        } elseif ($liquidez >= 1.5) {
            $score += 25;
        } elseif ($liquidez >= 1) {
            $score += 15;
        } elseif ($liquidez >= 0.5) {
            $score += 5;
        }

        // Runway (25 puntos)
        if (!is_numeric($runway) || $runway >= 12) {
            $score += 25;
        } elseif ($runway >= 6) {
            $score += 15;
        } elseif ($runway >= 3) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Genera una proyección de flujo de caja.
     *
     * @param array $historical_data
     *   Datos históricos de los últimos 3-6 meses.
     * @param int $months_ahead
     *   Meses a proyectar (default: 3).
     *
     * @return array
     *   Proyección mes a mes.
     */
    public function projectCashflow(array $historical_data, int $months_ahead = 3): array
    {
        // Calcular tendencias simples (media móvil)
        $ingresos_avg = array_sum(array_column($historical_data, 'ingresos')) / count($historical_data);
        $gastos_avg = array_sum(array_column($historical_data, 'gastos')) / count($historical_data);

        $last_month = end($historical_data);
        $saldo_actual = $last_month['saldo_banco'] ?? 0;

        $projections = [];
        $saldo = $saldo_actual;

        for ($i = 1; $i <= $months_ahead; $i++) {
            $ingresos_proyectado = $ingresos_avg * (1 + (rand(-10, 15) / 100)); // Variación aleatoria
            $gastos_proyectado = $gastos_avg * (1 + (rand(-5, 5) / 100));
            $saldo += ($ingresos_proyectado - $gastos_proyectado);

            $projections[] = [
                'mes' => date('Y-m', strtotime("+{$i} months")),
                'ingresos_estimado' => round($ingresos_proyectado),
                'gastos_estimado' => round($gastos_proyectado),
                'saldo_proyectado' => round($saldo),
                'es_proyeccion' => TRUE,
            ];
        }

        return $projections;
    }

    /**
     * Genera informe mensual en formato texto (para usar con IA).
     */
    public function generateMonthlyReportPrompt(array $analysis): string
    {
        $score = $analysis['score_salud'];
        $status = $score >= 70 ? 'saludable' : ($score >= 40 ? 'con áreas de mejora' : 'en riesgo');

        return "Genera un resumen ejecutivo de 150 palabras para un emprendedor con este análisis financiero:\n\n" .
            "- Ingresos: " . number_format($analysis['resumen']['ingresos'], 2) . "€\n" .
            "- Gastos: " . number_format($analysis['resumen']['gastos'], 2) . "€\n" .
            "- Beneficio: " . number_format($analysis['resumen']['beneficio'], 2) . "€\n" .
            "- Margen bruto: " . $analysis['resumen']['margen_bruto'] . "%\n" .
            "- Score salud financiera: {$score}/100 ({$status})\n" .
            "- Alertas activas: " . count($analysis['alertas']) . "\n\n" .
            "El tono debe ser cercano pero profesional. Menciona los puntos positivos primero, " .
            "luego las áreas de mejora con acciones concretas. No uses jerga financiera compleja.";
    }

}
