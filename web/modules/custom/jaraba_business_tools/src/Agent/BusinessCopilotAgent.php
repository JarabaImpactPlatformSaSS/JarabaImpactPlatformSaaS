<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Agent;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Business Copilot AI Agent for Entrepreneurs.
 *
 * Provides personalized business guidance, canvas analysis, 
 * competitive insights, and digitalization recommendations.
 */
class BusinessCopilotAgent
{

    use StringTranslationTrait;

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
    }

    /**
     * Gets agent metadata.
     */
    public function getAgentInfo(): array
    {
        return [
            'id' => 'business_copilot',
            'name' => $this->t('Copiloto de Negocio'),
            'description' => $this->t('Tu asistente IA para digitalización y estrategia empresarial'),
            'avatar' => 'business_coach',
            'color' => '#10B981',
            'icon' => 'briefcase',
            'capabilities' => [
                'canvas_analysis',
                'competitive_insights',
                'digitalization_advice',
                'financial_guidance',
                'mvp_coaching',
            ],
            'context' => 'entrepreneur',
            'version' => '1.0.0',
        ];
    }

    /**
     * Available actions for this agent.
     */
    public function getAvailableActions(): array
    {
        return [
            'analyze_canvas' => [
                'id' => 'analyze_canvas',
                'label' => $this->t('Analizar mi Canvas'),
                'description' => $this->t('Obtén sugerencias para mejorar tu modelo de negocio'),
                'icon' => 'grid',
                'requires_canvas' => TRUE,
            ],
            'suggest_value_proposition' => [
                'id' => 'suggest_value_proposition',
                'label' => $this->t('Mejorar propuesta de valor'),
                'description' => $this->t('Recibe ideas para una propuesta de valor más clara'),
                'icon' => 'star',
                'requires_canvas' => TRUE,
            ],
            'competitive_position' => [
                'id' => 'competitive_position',
                'label' => $this->t('Análisis competitivo'),
                'description' => $this->t('Descubre oportunidades frente a tu competencia'),
                'icon' => 'users',
                'requires_canvas' => TRUE,
            ],
            'digitalization_roadmap' => [
                'id' => 'digitalization_roadmap',
                'label' => $this->t('Ruta de digitalización'),
                'description' => $this->t('Plan paso a paso para digitalizar tu negocio'),
                'icon' => 'trending-up',
                'requires_diagnostic' => TRUE,
            ],
            'validate_hypothesis' => [
                'id' => 'validate_hypothesis',
                'label' => $this->t('Validar hipótesis'),
                'description' => $this->t('Diseña experimentos para validar tu idea'),
                'icon' => 'check-circle',
                'requires_canvas' => FALSE,
            ],
            'financial_check' => [
                'id' => 'financial_check',
                'label' => $this->t('Check financiero'),
                'description' => $this->t('Revisa la viabilidad económica de tu modelo'),
                'icon' => 'dollar-sign',
                'requires_canvas' => TRUE,
            ],
        ];
    }

    /**
     * Executes an agent action.
     */
    public function executeAction(string $actionId, array $context = []): array
    {
        $uid = $this->currentUser->id();

        return match ($actionId) {
            'analyze_canvas' => $this->analyzeCanvas($context),
            'suggest_value_proposition' => $this->suggestValueProposition($context),
            'competitive_position' => $this->analyzeCompetitivePosition($context),
            'digitalization_roadmap' => $this->generateDigitalizationRoadmap($context),
            'validate_hypothesis' => $this->designValidationExperiment($context),
            'financial_check' => $this->performFinancialCheck($context),
            default => [
                'success' => FALSE,
                'message' => $this->t('Acción no reconocida.'),
            ],
        };
    }

    /**
     * Analyzes business model canvas.
     */
    protected function analyzeCanvas(array $context): array
    {
        $canvas = $this->getLatestCanvas();

        if (!$canvas) {
            return [
                'success' => FALSE,
                'message' => $this->t('No tienes un Business Model Canvas todavía. ¿Quieres crear uno?'),
                'action' => [
                    'label' => $this->t('Crear Canvas'),
                    'url' => '/entrepreneur/canvas/new',
                ],
            ];
        }

        $completeness = (int) ($canvas->get('completeness_score')->value ?? 0);
        $insights = [];

        // Analyze each block
        $weakBlocks = [];
        $strongBlocks = [];

        // Value Propositions
        $valueProps = $this->getBlockItems($canvas, 'value_propositions');
        if (count($valueProps) < 2) {
            $weakBlocks[] = 'Propuestas de Valor';
            $insights[] = [
                'type' => 'warning',
                'block' => 'value_propositions',
                'message' => $this->t('Tu propuesta de valor necesita más desarrollo. ¿Qué problema específico resuelves mejor que nadie?'),
            ];
        } else {
            $strongBlocks[] = 'Propuestas de Valor';
        }

        // Customer Segments
        $segments = $this->getBlockItems($canvas, 'customer_segments');
        if (count($segments) < 1) {
            $weakBlocks[] = 'Segmentos de Cliente';
            $insights[] = [
                'type' => 'critical',
                'block' => 'customer_segments',
                'message' => $this->t('Sin clientes definidos, el modelo no funciona. ¿Quién paga por tu solución?'),
            ];
        }

        // Revenue Streams
        $revenue = $this->getBlockItems($canvas, 'revenue_streams');
        if (count($revenue) < 1) {
            $weakBlocks[] = 'Fuentes de Ingresos';
            $insights[] = [
                'type' => 'critical',
                'block' => 'revenue_streams',
                'message' => $this->t('¿Cómo vas a ganar dinero? Define al menos una fuente de ingresos clara.'),
            ];
        }

        // Generate summary
        $summary = '';
        if ($completeness >= 70) {
            $summary = $this->t('¡Tu Canvas está bastante completo! Ahora es momento de validar las hipótesis más arriesgadas.');
        } elseif ($completeness >= 40) {
            $summary = $this->t('Vas por buen camino. Concéntrate en los bloques marcados como críticos.');
        } else {
            $summary = $this->t('Tu Canvas necesita más trabajo. Empieza por definir clientes y propuesta de valor.');
        }

        return [
            'success' => TRUE,
            'summary' => $summary,
            'completeness' => $completeness,
            'insights' => $insights,
            'strong_blocks' => $strongBlocks,
            'weak_blocks' => $weakBlocks,
            'next_action' => [
                'label' => !empty($weakBlocks)
                    ? $this->t('Mejorar @block', ['@block' => $weakBlocks[0]])
                    : $this->t('Validar hipótesis'),
                'action_id' => !empty($weakBlocks) ? 'suggest_value_proposition' : 'validate_hypothesis',
            ],
        ];
    }

    /**
     * Suggests value proposition improvements.
     */
    protected function suggestValueProposition(array $context): array
    {
        $canvas = $this->getLatestCanvas();
        $diagnostic = $this->getLatestDiagnostic();

        $suggestions = [
            [
                'template' => 'Para [CLIENTE], que tiene [PROBLEMA], ofrezco [SOLUCIÓN] que [BENEFICIO ÚNICO].',
                'example' => 'Para pequeñas tiendas locales, que tienen dificultad para vender online, ofrezco una tienda digital lista en 24h que triplica su visibilidad.',
            ],
            [
                'template' => 'A diferencia de [ALTERNATIVA], nosotros [DIFERENCIADOR].',
                'example' => 'A diferencia de marketplaces genéricos, nosotros conectamos directamente productor y consumidor local.',
            ],
        ];

        $tips = [
            $this->t('Sé específico: "ahorra tiempo" es vago, "ahorra 2h al día" es concreto.'),
            $this->t('Habla de beneficios, no de características.'),
            $this->t('Incluye un elemento diferenciador único.'),
            $this->t('Usa el lenguaje de tu cliente, no jerga técnica.'),
        ];

        return [
            'success' => TRUE,
            'title' => $this->t('Cómo escribir una propuesta de valor irresistible'),
            'suggestions' => $suggestions,
            'tips' => $tips,
            'cta' => [
                'label' => $this->t('Editar mi Canvas'),
                'url' => $canvas ? '/entrepreneur/canvas/' . $canvas->id() : '/entrepreneur/canvas/new',
            ],
        ];
    }

    /**
     * Analyzes competitive position.
     */
    protected function analyzeCompetitivePosition(array $context): array
    {
        return [
            'success' => TRUE,
            'title' => $this->t('Análisis de Posicionamiento Competitivo'),
            'steps' => [
                [
                    'step' => 1,
                    'title' => $this->t('Identifica 3-5 competidores'),
                    'description' => $this->t('Incluye competidores directos, indirectos y sustitutos.'),
                ],
                [
                    'step' => 2,
                    'title' => $this->t('Analiza su presencia digital'),
                    'description' => $this->t('Web, redes sociales, reseñas, posicionamiento SEO.'),
                ],
                [
                    'step' => 3,
                    'title' => $this->t('Encuentra el hueco'),
                    'description' => $this->t('¿Qué necesidad no están cubriendo bien?'),
                ],
            ],
            'action' => [
                'label' => $this->t('Crear Análisis Competitivo'),
                'url' => '/entrepreneur/competitive-analysis/new',
            ],
        ];
    }

    /**
     * Generates digitalization roadmap.
     */
    protected function generateDigitalizationRoadmap(array $context): array
    {
        $diagnostic = $this->getLatestDiagnostic();
        $score = $diagnostic ? (int) ($diagnostic->get('total_score')->value ?? 0) : 0;

        $phases = [];

        if ($score < 30) {
            $phases = [
                ['name' => 'Presencia Básica', 'duration' => '2-4 semanas', 'tasks' => ['Google My Business', 'Perfil en redes', 'Web básica']],
                ['name' => 'Operaciones', 'duration' => '4-8 semanas', 'tasks' => ['Facturación digital', 'Gestión de clientes', 'Herramientas cloud']],
                ['name' => 'Ventas Online', 'duration' => '8-12 semanas', 'tasks' => ['Tienda online', 'Pagos digitales', 'Logística']],
            ];
        } elseif ($score < 60) {
            $phases = [
                ['name' => 'Optimización', 'duration' => '2-4 semanas', 'tasks' => ['SEO local', 'Automatización básica', 'Analítica']],
                ['name' => 'Marketing Digital', 'duration' => '4-6 semanas', 'tasks' => ['Email marketing', 'Contenido', 'Publicidad online']],
                ['name' => 'Escala', 'duration' => '6-12 semanas', 'tasks' => ['Integraciones', 'CRM', 'IA en procesos']],
            ];
        } else {
            $phases = [
                ['name' => 'Excelencia Digital', 'duration' => '4-8 semanas', 'tasks' => ['IA avanzada', 'Omnicanalidad', 'Data-driven']],
                ['name' => 'Innovación', 'duration' => 'Continuo', 'tasks' => ['Nuevos canales', 'Automatización total', 'Modelo escalable']],
            ];
        }

        return [
            'success' => TRUE,
            'current_score' => $score,
            'phases' => $phases,
            'next_step' => [
                'label' => $this->t('Ver Itinerario Completo'),
                'url' => '/entrepreneur/paths',
            ],
        ];
    }

    /**
     * Designs validation experiment.
     */
    protected function designValidationExperiment(array $context): array
    {
        return [
            'success' => TRUE,
            'title' => $this->t('Diseña un experimento de validación'),
            'methodology' => 'Lean Startup',
            'template' => [
                'hypothesis' => $this->t('Creemos que [CLIENTE] tiene el problema de [PROBLEMA]'),
                'test' => $this->t('Para validarlo, haremos [ACCIÓN]'),
                'metric' => $this->t('Sabremos que tenemos razón si [MÉTRICA] alcanza [UMBRAL]'),
                'timeframe' => $this->t('En un plazo de [TIEMPO]'),
            ],
            'examples' => [
                [
                    'hypothesis' => 'Las tiendas locales quieren vender online pero no saben cómo',
                    'test' => 'Ofreceremos demo gratuita a 20 tiendas por email',
                    'metric' => '5 o más solicitan la demo',
                    'timeframe' => '2 semanas',
                ],
            ],
            'action' => [
                'label' => $this->t('Crear Hipótesis MVP'),
                'url' => '/entrepreneur/mvp/new',
            ],
        ];
    }

    /**
     * Performs financial viability check.
     */
    protected function performFinancialCheck(array $context): array
    {
        $canvas = $this->getLatestCanvas();

        $questions = [
            [
                'question' => $this->t('¿Cuánto puede pagar tu cliente por tu solución?'),
                'hint' => $this->t('Investiga precio de alternativas y capacidad de pago.'),
            ],
            [
                'question' => $this->t('¿Cuántos clientes necesitas para cubrir costes?'),
                'hint' => $this->t('Punto de equilibrio = Costes fijos / Margen por venta'),
            ],
            [
                'question' => $this->t('¿Cuánto cuesta adquirir un cliente (CAC)?'),
                'hint' => $this->t('Incluye marketing, ventas, tiempo invertido.'),
            ],
            [
                'question' => $this->t('¿Cuánto vale un cliente a lo largo del tiempo (LTV)?'),
                'hint' => $this->t('LTV debe ser al menos 3x CAC para ser sostenible.'),
            ],
        ];

        return [
            'success' => TRUE,
            'title' => $this->t('Check de Viabilidad Financiera'),
            'questions' => $questions,
            'tools' => [
                [
                    'label' => $this->t('Calculadora de Proyecciones'),
                    'url' => '/entrepreneur/projections/new',
                ],
                [
                    'label' => $this->t('Plantilla de Punto de Equilibrio'),
                    'url' => '/entrepreneur/resources/break-even',
                ],
            ],
        ];
    }

    /**
     * Gets the latest canvas for current user.
     */
    protected function getLatestCanvas(): ?object
    {
        $storage = $this->entityTypeManager->getStorage('business_model_canvas');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('user_id', $this->currentUser->id())
            ->sort('changed', 'DESC')
            ->range(0, 1)
            ->execute();

        return !empty($ids) ? $storage->load(reset($ids)) : NULL;
    }

    /**
     * Gets the latest diagnostic for current user.
     */
    protected function getLatestDiagnostic(): ?object
    {
        $storage = $this->entityTypeManager->getStorage('business_diagnostic');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('user_id', $this->currentUser->id())
            ->sort('created', 'DESC')
            ->range(0, 1)
            ->execute();

        return !empty($ids) ? $storage->load(reset($ids)) : NULL;
    }

    /**
     * Gets items from a canvas block.
     */
    protected function getBlockItems($canvas, string $blockType): array
    {
        // Placeholder - would query CanvasBlock entities
        return [];
    }

    /**
     * Gets onboarding message for first interaction.
     */
    public function getOnboardingMessage(): array
    {
        $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
        $userName = $user ? $user->getDisplayName() : 'Emprendedor/a';

        $canvas = $this->getLatestCanvas();
        $diagnostic = $this->getLatestDiagnostic();

        $message = '';
        $suggestedAction = NULL;

        if (!$diagnostic) {
            $message = $this->t('¡Hola @name! Soy tu Copiloto de Negocio. Para darte recomendaciones personalizadas, empecemos con un diagnóstico rápido de tu situación digital.', [
                '@name' => $userName,
            ]);
            $suggestedAction = [
                'label' => $this->t('Hacer Diagnóstico'),
                'url' => '/entrepreneur/diagnostic/start',
            ];
        } elseif (!$canvas) {
            $message = $this->t('¡Hola @name! Ya tengo tu diagnóstico. El siguiente paso es crear tu Business Model Canvas para visualizar tu modelo de negocio.', [
                '@name' => $userName,
            ]);
            $suggestedAction = [
                'label' => $this->t('Crear Canvas'),
                'url' => '/entrepreneur/canvas/new',
            ];
        } else {
            $completeness = (int) ($canvas->get('completeness_score')->value ?? 0);
            $message = $this->t('¡Hola @name! Tu Canvas está al @percent%. ¿En qué te ayudo hoy?', [
                '@name' => $userName,
                '@percent' => $completeness,
            ]);
        }

        return [
            'message' => $message,
            'suggested_action' => $suggestedAction,
            'available_actions' => $this->getAvailableActions(),
        ];
    }

}
