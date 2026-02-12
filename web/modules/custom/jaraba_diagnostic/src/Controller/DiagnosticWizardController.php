<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador del wizard de diagnóstico para emprendedores.
 *
 * Proporciona una experiencia guiada paso a paso para
 * completar el diagnóstico empresarial.
 */
class DiagnosticWizardController extends ControllerBase
{

    /**
     * Definición de secciones del diagnóstico.
     */
    const SECTIONS = [
        'online_presence' => [
            'title' => 'Presencia Online',
            'icon' => 'globe',
            'description' => 'Evalúa tu visibilidad digital actual',
            'questions' => [
                ['id' => 'has_website', 'text' => '¿Tu negocio tiene página web?', 'type' => 'boolean'],
                ['id' => 'website_responsive', 'text' => '¿Tu web se ve bien en móviles?', 'type' => 'boolean'],
                ['id' => 'google_business', 'text' => '¿Tienes perfil de Google My Business actualizado?', 'type' => 'boolean'],
                ['id' => 'social_profiles', 'text' => '¿Cuántas redes sociales usas activamente?', 'type' => 'scale_5', 'labels' => ['Ninguna', '1', '2-3', '4-5', 'Más de 5']],
                ['id' => 'seo_basic', 'text' => '¿Apareces en Google al buscar tu negocio?', 'type' => 'scale_5', 'labels' => ['No aparezco', 'Rara vez', 'A veces', 'Casi siempre', 'Siempre primero']],
            ],
        ],
        'digital_sales' => [
            'title' => 'Ventas Digitales',
            'icon' => 'shopping-cart',
            'description' => 'Evalúa tu capacidad de vender online',
            'questions' => [
                ['id' => 'sells_online', 'text' => '¿Vendes productos/servicios por Internet?', 'type' => 'boolean'],
                ['id' => 'ecommerce_platform', 'text' => '¿Tienes tienda online o catálogo digital?', 'type' => 'boolean'],
                ['id' => 'online_payments', 'text' => '¿Aceptas pagos online (tarjeta, Bizum, etc.)?', 'type' => 'boolean'],
                ['id' => 'digital_revenue_pct', 'text' => '¿Qué porcentaje de tus ventas viene de canales digitales?', 'type' => 'scale_5', 'labels' => ['0%', '1-10%', '11-30%', '31-60%', 'Más del 60%']],
                ['id' => 'delivery_logistics', 'text' => '¿Tienes logística de envíos organizada?', 'type' => 'scale_5', 'labels' => ['No envío', 'Manual y caótico', 'Parcialmente organizado', 'Bien organizado', 'Totalmente automatizado']],
            ],
        ],
        'digital_marketing' => [
            'title' => 'Marketing Digital',
            'icon' => 'megaphone',
            'description' => 'Evalúa tus estrategias de captación',
            'questions' => [
                ['id' => 'email_marketing', 'text' => '¿Usas email marketing (newsletters, campañas)?', 'type' => 'boolean'],
                ['id' => 'social_ads', 'text' => '¿Haces publicidad en redes sociales?', 'type' => 'boolean'],
                ['id' => 'google_ads', 'text' => '¿Usas Google Ads u otra publicidad de búsqueda?', 'type' => 'boolean'],
                ['id' => 'content_frequency', 'text' => '¿Con qué frecuencia publicas contenido?', 'type' => 'scale_5', 'labels' => ['Nunca', 'Mensual', 'Quincenal', 'Semanal', 'Diario']],
                ['id' => 'marketing_budget', 'text' => '¿Destinas presupuesto a marketing digital?', 'type' => 'scale_5', 'labels' => ['Nada', 'Menos de 100€/mes', '100-500€/mes', '500-2000€/mes', 'Más de 2000€/mes']],
            ],
        ],
        'digital_operations' => [
            'title' => 'Operaciones Digitales',
            'icon' => 'cog',
            'description' => 'Evalúa la digitalización de tus procesos',
            'questions' => [
                ['id' => 'uses_crm', 'text' => '¿Usas un CRM para gestionar clientes?', 'type' => 'boolean'],
                ['id' => 'digital_invoicing', 'text' => '¿Facturas de forma digital/automática?', 'type' => 'boolean'],
                ['id' => 'cloud_tools', 'text' => '¿Usas herramientas en la nube (Drive, Office 365, etc.)?', 'type' => 'boolean'],
                ['id' => 'inventory_digital', 'text' => '¿Gestionas inventario con software?', 'type' => 'scale_5', 'labels' => ['Todo manual', 'Excel básico', 'Software básico', 'Software integrado', 'ERP completo']],
                ['id' => 'team_collaboration', 'text' => '¿Usas herramientas de colaboración (Slack, Teams)?', 'type' => 'scale_5', 'labels' => ['Nada', 'WhatsApp solo', 'Email + WhatsApp', 'Herramientas básicas', 'Suite completa']],
            ],
        ],
        'automation_ai' => [
            'title' => 'Automatización e IA',
            'icon' => 'robot',
            'description' => 'Evalúa tu nivel de automatización',
            'questions' => [
                ['id' => 'chatbot', 'text' => '¿Tienes chatbot o respuestas automáticas?', 'type' => 'boolean'],
                ['id' => 'email_automation', 'text' => '¿Tienes emails automáticos (bienvenida, abandono)?', 'type' => 'boolean'],
                ['id' => 'uses_ai_tools', 'text' => '¿Usas herramientas de IA (ChatGPT, etc.)?', 'type' => 'boolean'],
                ['id' => 'process_automation', 'text' => '¿Qué nivel de automatización tienen tus procesos?', 'type' => 'scale_5', 'labels' => ['Todo manual', 'Muy poco', 'Algo', 'Bastante', 'Muy automatizado']],
                ['id' => 'data_analysis', 'text' => '¿Analizas datos para tomar decisiones?', 'type' => 'scale_5', 'labels' => ['Nunca', 'Rara vez', 'A veces', 'Frecuentemente', 'Siempre con datos']],
            ],
        ],
    ];

    /**
     * Página de inicio del wizard.
     *
     * /diagnostic/start
     */
    public function start(): array
    {
        return [
            '#theme' => 'diagnostic_wizard_start',
            '#sections' => self::SECTIONS,
            '#total_questions' => $this->countTotalQuestions(),
            '#estimated_time' => '5-7 minutos',
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => ['jaraba_diagnostic/wizard'],
            ],
        ];
    }

    /**
     * Crear diagnóstico y redirigir al primer paso.
     *
     * POST /diagnostic/start
     */
    public function createAndStart(Request $request): RedirectResponse
    {
        $data = $request->request->all();

        $storage = $this->entityTypeManager()->getStorage('business_diagnostic');
        $diagnostic = $storage->create([
            'business_name' => $data['business_name'] ?? 'Mi Negocio',
            'business_sector' => $data['business_sector'] ?? 'otros',
            'business_size' => $data['business_size'] ?? 'solo',
            'user_id' => $this->currentUser()->id(),
            'status' => 'in_progress',
        ]);
        $diagnostic->save();

        $firstSection = array_key_first(self::SECTIONS);
        $url = Url::fromRoute('jaraba_diagnostic.wizard.step', [
            'uuid' => $diagnostic->uuid(),
            'section' => $firstSection,
        ]);

        return new RedirectResponse($url->toString());
    }

    /**
     * Paso del wizard (sección específica).
     *
     * /diagnostic/{uuid}/step/{section}
     */
    public function step(string $uuid, string $section): array
    {
        $diagnostic = $this->loadDiagnosticByUuid($uuid);

        if (!isset(self::SECTIONS[$section])) {
            throw new NotFoundHttpException('Sección no encontrada');
        }

        $sectionData = self::SECTIONS[$section];
        $sectionKeys = array_keys(self::SECTIONS);
        $currentIndex = array_search($section, $sectionKeys);
        $totalSections = count($sectionKeys);

        $previousSection = $currentIndex > 0 ? $sectionKeys[$currentIndex - 1] : NULL;
        $nextSection = $currentIndex < $totalSections - 1 ? $sectionKeys[$currentIndex + 1] : NULL;

        // Cargar respuestas existentes para esta sección
        $existingAnswers = $this->loadSectionAnswers($diagnostic, $section);

        return [
            '#theme' => 'diagnostic_wizard_step',
            '#diagnostic' => $diagnostic,
            '#uuid' => $uuid,
            '#section' => $section,
            '#section_data' => $sectionData,
            '#current_step' => $currentIndex + 1,
            '#total_steps' => $totalSections,
            '#progress_percent' => round((($currentIndex) / $totalSections) * 100),
            '#previous_section' => $previousSection,
            '#next_section' => $nextSection,
            '#existing_answers' => $existingAnswers,
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => ['jaraba_diagnostic/wizard'],
                'drupalSettings' => [
                    'diagnosticWizard' => [
                        'uuid' => $uuid,
                        'section' => $section,
                        'apiEndpoint' => '/api/v1/diagnostics/' . $uuid . '/answers',
                    ],
                ],
            ],
        ];
    }

    /**
     * Página de resultados.
     *
     * /diagnostic/{uuid}/results
     */
    public function results(string $uuid): array
    {
        $diagnostic = $this->loadDiagnosticByUuid($uuid);

        // Calcular scores si aún no se han calculado
        if (!$diagnostic->getOverallScore()) {
            $this->calculateAndSaveScores($diagnostic);
        }

        // Obtener gaps prioritarios
        $priorityGaps = json_decode($diagnostic->get('priority_gaps')->value ?? '[]', TRUE);

        // Generar recomendaciones
        $recommendationService = \Drupal::service('jaraba_diagnostic.recommendation');
        $sectionScores = $this->getSectionScores($diagnostic);
        $recommendations = $recommendationService->generateRecommendations($diagnostic, $sectionScores);
        $quickWins = $recommendationService->getTopQuickWins($recommendations, 3);

        // Obtener path recomendado
        $recommendedPath = NULL;
        if ($pathId = $diagnostic->get('recommended_path_id')->target_id) {
            $recommendedPath = $this->entityTypeManager()
                ->getStorage('digitalization_path')
                ->load($pathId);
        }

        return [
            '#theme' => 'diagnostic_results',
            '#diagnostic' => $diagnostic,
            '#overall_score' => $diagnostic->getOverallScore(),
            '#maturity_level' => $diagnostic->getMaturityLevel(),
            '#maturity_label' => $this->getMaturityLabel($diagnostic->getMaturityLevel()),
            '#estimated_loss' => $diagnostic->getEstimatedLoss(),
            '#section_scores' => $sectionScores,
            '#sections_config' => self::SECTIONS,
            '#priority_gaps' => $priorityGaps,
            '#recommendations' => array_slice($recommendations, 0, 5),
            '#quick_wins' => $quickWins,
            '#recommended_path' => $recommendedPath,
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => ['jaraba_diagnostic/results'],
            ],
        ];
    }

    /**
     * Carga diagnóstico por UUID.
     */
    protected function loadDiagnosticByUuid(string $uuid): object
    {
        $storage = $this->entityTypeManager()->getStorage('business_diagnostic');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);
        $diagnostic = !empty($entities) ? reset($entities) : NULL;

        if (!$diagnostic) {
            throw new NotFoundHttpException('Diagnóstico no encontrado');
        }

        return $diagnostic;
    }

    /**
     * Cuenta el total de preguntas.
     */
    protected function countTotalQuestions(): int
    {
        $count = 0;
        foreach (self::SECTIONS as $section) {
            $count += count($section['questions']);
        }
        return $count;
    }

    /**
     * Carga respuestas existentes de una sección.
     */
    protected function loadSectionAnswers($diagnostic, string $section): array
    {
        // Por ahora retornar array vacío - las respuestas se guardan vía API
        return [];
    }

    /**
     * Obtiene scores por sección.
     */
    protected function getSectionScores($diagnostic): array
    {
        // Calcular desde respuestas almacenadas
        // Por ahora retornar valores demo
        return [
            'online_presence' => 45,
            'digital_sales' => 30,
            'digital_marketing' => 25,
            'digital_operations' => 40,
            'automation_ai' => 15,
        ];
    }

    /**
     * Calcula y guarda scores del diagnóstico.
     */
    protected function calculateAndSaveScores($diagnostic): void
    {
        $scoringService = \Drupal::service('jaraba_diagnostic.scoring');
        $sectionScores = $this->getSectionScores($diagnostic);
        $scoringService->calculateScores($diagnostic, $sectionScores);
        $diagnostic->save();
    }

    /**
     * Obtiene label legible del nivel de madurez.
     */
    protected function getMaturityLabel(string $level): string
    {
        return match ($level) {
            'analogico' => 'Analógico',
            'basico' => 'Básico',
            'conectado' => 'Conectado',
            'digitalizado' => 'Digitalizado',
            'inteligente' => 'Inteligente',
            default => $level,
        };
    }

}
