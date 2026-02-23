<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface;

/**
 * Servicio de generación de recomendaciones.
 *
 * Genera recomendaciones personalizadas basadas en los gaps
 * detectados en el diagnóstico empresarial.
 */
class RecommendationEngineService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected Connection $database;
    protected LoggerInterface $logger;

    /**
     * Catálogo de recomendaciones por área.
     */
    const RECOMMENDATIONS = [
        'online_presence' => [
            'low' => [
                'title' => 'Crear presencia web básica',
                'description' => 'Tu negocio necesita una página web para ser visible ante clientes potenciales.',
                'action' => 'Crear web responsive con información de contacto y servicios.',
                'quick_wins' => ['Perfil de Google My Business', 'Página de Facebook'],
            ],
            'medium' => [
                'title' => 'Optimizar presencia digital',
                'description' => 'Tienes presencia pero hay margen de mejora en SEO y contenido.',
                'action' => 'Mejorar SEO, añadir blog, optimizar fichas de productos.',
                'quick_wins' => ['Optimizar Google My Business', 'Añadir testimonios'],
            ],
        ],
        'digital_sales' => [
            'low' => [
                'title' => 'Activar canal de ventas online',
                'description' => 'No vendes online. Estás perdiendo el 18% de ingresos potenciales.',
                'action' => 'Implementar tienda online o catálogo con WhatsApp Business.',
                'quick_wins' => ['Catálogo en WhatsApp', 'Formulario de pedidos'],
            ],
            'medium' => [
                'title' => 'Optimizar conversión de ventas',
                'description' => 'Vendes online pero el ratio de conversión es bajo.',
                'action' => 'Mejorar checkout, añadir métodos de pago, optimizar fichas.',
                'quick_wins' => ['Añadir Bizum/PayPal', 'Fotos de producto mejoradas'],
            ],
        ],
        'digital_marketing' => [
            'low' => [
                'title' => 'Iniciar marketing digital básico',
                'description' => 'Sin marketing digital activo, dependes solo del boca a boca.',
                'action' => 'Crear estrategia de contenido y presencia en redes.',
                'quick_wins' => ['Publicar 2x semana en Instagram', 'Email de bienvenida'],
            ],
            'medium' => [
                'title' => 'Automatizar marketing',
                'description' => 'Tienes marketing pero es muy manual.',
                'action' => 'Implementar email automation y remarketing.',
                'quick_wins' => ['Secuencia email post-compra', 'Pixel de Facebook'],
            ],
        ],
        'digital_operations' => [
            'low' => [
                'title' => 'Digitalizar operaciones básicas',
                'description' => 'Gestión manual de clientes, inventario y facturación.',
                'action' => 'Implementar herramientas de gestión (CRM, facturación).',
                'quick_wins' => ['CRM integrado en Jaraba', 'Gestión de facturas digital'],
            ],
            'medium' => [
                'title' => 'Integrar sistemas',
                'description' => 'Tienes varias herramientas pero no están conectadas.',
                'action' => 'Integrar CRM con tienda, automatizar flujos.',
                'quick_wins' => ['Automatización de flujos integrada', 'Dashboard unificado'],
            ],
        ],
        'automation_ai' => [
            'low' => [
                'title' => 'Introducir automatizaciones básicas',
                'description' => 'Todo es manual. Puedes ahorrar horas con automatizaciones simples.',
                'action' => 'Automatizar respuestas, confirmaciones, recordatorios.',
                'quick_wins' => ['Autorespuesta WhatsApp', 'Recordatorio de citas'],
            ],
            'medium' => [
                'title' => 'Explorar asistentes de IA',
                'description' => 'Tienes bases para aprovechar IA en atención y contenido.',
                'action' => 'Implementar chatbot, generación de contenido con IA.',
                'quick_wins' => ['Chatbot FAQ', 'IA para posts de redes'],
            ],
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->logger = $loggerFactory;
    }

    /**
     * Genera recomendaciones para un diagnóstico.
     *
     * @param \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $diagnostic
     *   El diagnóstico completado.
     * @param array $sectionScores
     *   Scores por sección.
     *
     * @return array
     *   Array de recomendaciones ordenadas por prioridad.
     */
    public function generateRecommendations(BusinessDiagnosticInterface $diagnostic, array $sectionScores): array
    {
        $recommendations = [];

        foreach (self::RECOMMENDATIONS as $area => $levels) {
            $score = $sectionScores[$area] ?? 0;

            // Determinar nivel
            $level = match (TRUE) {
                $score < 30 => 'low',
                $score < 60 => 'medium',
                default => NULL, // No necesita recomendación
            };

            if ($level && isset($levels[$level])) {
                $rec = $levels[$level];
                $recommendations[] = [
                    'area' => $area,
                    'level' => $level,
                    'score' => $score,
                    'title' => $rec['title'],
                    'description' => $rec['description'],
                    'action' => $rec['action'],
                    'quick_wins' => $rec['quick_wins'],
                    'priority' => $this->calculatePriority($area, $score, $diagnostic->getBusinessSector()),
                ];
            }
        }

        // Ordenar por prioridad
        usort($recommendations, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $recommendations;
    }

    /**
     * Calcula la prioridad de una recomendación.
     */
    protected function calculatePriority(string $area, float $score, string $sector): float
    {
        $weights = DiagnosticScoringService::SECTION_WEIGHTS;
        $modifiers = DiagnosticScoringService::SECTOR_MODIFIERS[$sector]
            ?? DiagnosticScoringService::SECTOR_MODIFIERS['otros'];

        $weight = $weights[$area] ?? 0.2;
        $modifier = $modifiers[$area] ?? 1.0;

        // Prioridad = (100 - score) * peso * modificador
        return (100 - $score) * $weight * $modifier;
    }

    /**
     * Obtiene los Quick Wins más impactantes.
     */
    public function getTopQuickWins(array $recommendations, int $limit = 5): array
    {
        $quickWins = [];

        foreach ($recommendations as $rec) {
            foreach ($rec['quick_wins'] as $win) {
                $quickWins[] = [
                    'action' => $win,
                    'area' => $rec['area'],
                    'priority' => $rec['priority'],
                ];
            }
        }

        // Ordenar y limitar
        usort($quickWins, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return array_slice($quickWins, 0, $limit);
    }

}
