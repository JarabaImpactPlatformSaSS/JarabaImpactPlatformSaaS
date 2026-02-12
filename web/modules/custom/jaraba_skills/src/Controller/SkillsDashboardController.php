<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\jaraba_skills\Entity\AiSkill;
use Drupal\jaraba_skills\Entity\AiSkillRevision;
use Drupal\jaraba_skills\Service\SkillManager;
use Drupal\jaraba_skills\Service\SkillRevisionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller para el dashboard frontend de AI Skills.
 *
 * Implementa el patrón "Frontend Page" con template limpio
 * y slide-panel para CRUD sin abandonar la página.
 */
class SkillsDashboardController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SkillManager $skillManager,
        protected RendererInterface $renderer,
        protected SkillRevisionService $revisionService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_skills.skill_manager'),
            $container->get('renderer'),
            $container->get('jaraba_skills.revision_service'),
        );
    }

    /**
     * Dashboard principal de habilidades IA.
     *
     * Ruta: /skills
     */
    public function dashboard(): array
    {
        $storage = $this->entityTypeManager()->getStorage('ai_skill');
        $skills = $storage->loadMultiple();

        // Agrupar por tipo.
        $grouped = [
            'core' => [],
            'vertical' => [],
            'agent' => [],
            'tenant' => [],
        ];

        foreach ($skills as $skill) {
            /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
            $type = $skill->getSkillType();
            if (isset($grouped[$type])) {
                $grouped[$type][] = $skill;
            }
        }

        $statistics = $this->skillManager->getStatistics();

        return [
            '#theme' => 'skills_dashboard',
            '#skills' => $grouped,
            '#statistics' => $statistics,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_theme/slide-panel',
                ],
            ],
        ];
    }

    /**
     * Formulario de añadir skill (AJAX para slide-panel).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   HTML del form o JSON de éxito.
     */
    public function add(Request $request): array|Response
    {
        $entity = $this->entityTypeManager()->getStorage('ai_skill')->create();
        $form = $this->entityFormBuilder()->getForm($entity, 'add');

        // Para AJAX...
        if ($request->isXmlHttpRequest()) {
            // PASO 1: Renderizar el form - esto EJECUTA el submit handler si es POST.
            $html = (string) $this->renderer->render($form);

            // PASO 2: Después del render, verificar si el form fue procesado exitosamente.
            $form_state = $form['#form_state'] ?? NULL;

            if ($request->isMethod('POST') && $form_state) {
                // Verificar si hay redirect (signo de éxito) y la entidad tiene ID.
                $redirect = $form_state->getRedirect();
                $has_errors = $form_state->hasAnyErrors();

                if ($redirect && !$has_errors && $entity->id()) {
                    return new JsonResponse([
                        'success' => TRUE,
                        'message' => $this->t('Habilidad "@title" creada correctamente.', [
                            '@title' => $entity->label(),
                        ]),
                        'entity_id' => $entity->id(),
                    ]);
                }
            }

            // GET o POST con errores → devolver HTML del form.
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Request normal → redirect al dashboard.
        return $this->redirect('jaraba_skills.dashboard');
    }

    /**
     * Formulario de editar skill (AJAX para slide-panel).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     * @param \Drupal\jaraba_skills\Entity\AiSkill $ai_skill
     *   La skill a editar.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   HTML del form o JSON de éxito.
     */
    public function edit(Request $request, AiSkill $ai_skill): array|Response
    {
        // Para AJAX...
        if ($request->isXmlHttpRequest()) {
            // Si es POST, procesar el form directamente.
            if ($request->isMethod('POST')) {
                // Obtener valores del form POST.
                $form_values = $request->request->all();

                // Validar el CSRF token primero.
                $form_id = $form_values['form_id'] ?? '';
                if ($form_id !== 'ai_skill_edit_form') {
                    return new JsonResponse([
                        'success' => FALSE,
                        'message' => $this->t('Invalid form submission.'),
                    ], 400);
                }

                // Actualizar la entidad directamente con los valores del form.
                $name = $form_values['name'][0]['value'] ?? '';
                $content = $form_values['content'][0]['value'] ?? '';
                $skill_type = $form_values['skill_type'] ?? 'core';
                $priority = (int) ($form_values['priority'][0]['value'] ?? 100);
                $is_active = isset($form_values['is_active']['value']) ? (bool) $form_values['is_active']['value'] : TRUE;

                if (empty($name)) {
                    return new JsonResponse([
                        'success' => FALSE,
                        'message' => $this->t('El nombre es obligatorio.'),
                    ], 400);
                }

                try {
                    $ai_skill->set('name', $name);
                    $ai_skill->set('content', $content);
                    $ai_skill->set('skill_type', $skill_type);
                    $ai_skill->set('priority', $priority);
                    $ai_skill->set('is_active', $is_active);
                    $ai_skill->save();

                    return new JsonResponse([
                        'success' => TRUE,
                        'message' => $this->t('Habilidad "@title" actualizada correctamente.', [
                            '@title' => $ai_skill->label(),
                        ]),
                    ]);
                } catch (\Exception $e) {
                    \Drupal::logger('jaraba_skills')->error('Error saving skill: @message', [
                        '@message' => $e->getMessage(),
                    ]);
                    return new JsonResponse([
                        'success' => FALSE,
                        'message' => $this->t('Error al guardar la habilidad.'),
                    ], 500);
                }
            }

            // GET → devolver HTML del form.
            $form = $this->entityFormBuilder()->getForm($ai_skill, 'edit');
            $html = (string) $this->renderer->render($form);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Request normal → redirect al dashboard.
        return $this->redirect('jaraba_skills.dashboard');
    }

    /**
     * Historial de revisiones de un skill (AJAX para slide-panel).
     */
    public function revisionHistory(Request $request, AiSkill $ai_skill): array|Response
    {
        $revisions = $this->revisionService->getRevisions((int) $ai_skill->id());

        $build = [
            '#theme' => 'skills_revision_history',
            '#skill' => $ai_skill,
            '#revisions' => $revisions,
        ];

        if ($request->isXmlHttpRequest()) {
            $html = (string) $this->renderer->render($build);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return $build;
    }

    /**
     * Restaura un skill a una revisión anterior.
     */
    public function restoreRevision(Request $request, AiSkill $ai_skill, AiSkillRevision $ai_skill_revision): JsonResponse
    {
        try {
            // Crear revisión del estado actual antes de restaurar.
            $this->revisionService->createRevision($ai_skill, $this->t('Antes de restaurar a v@num', [
                '@num' => $ai_skill_revision->getRevisionNumber(),
            ]));

            // Restaurar.
            $success = $this->revisionService->restoreRevision((int) $ai_skill_revision->id());

            if ($success) {
                return new JsonResponse([
                    'success' => TRUE,
                    'message' => $this->t('Habilidad restaurada a la versión @num correctamente.', [
                        '@num' => $ai_skill_revision->getRevisionNumber(),
                    ]),
                ]);
            }

            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al restaurar la habilidad.'),
            ], 500);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_skills')->error('Error restoring revision: @message', [
                '@message' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al restaurar la habilidad.'),
            ], 500);
        }
    }

    /**
     * Test Console para probar prompts de skills.
     */
    public function testConsole(Request $request): array|Response
    {
        $storage = $this->entityTypeManager()->getStorage('ai_skill');
        $skills = $storage->loadByProperties(['is_active' => TRUE]);

        $build = [
            '#theme' => 'skills_test_console',
            '#skills' => $skills,
        ];

        if ($request->isXmlHttpRequest()) {
            $html = (string) $this->renderer->render($build);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return $build;
    }

    /**
     * Procesa un test de skill contra la IA.
     */
    public function processTest(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $skillId = $data['skill_id'] ?? NULL;
        $message = $data['message'] ?? '';

        if (empty($skillId) || empty($message)) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Skill y mensaje son requeridos.'),
            ], 400);
        }

        try {
            $skill = $this->entityTypeManager()->getStorage('ai_skill')->load($skillId);
            if (!$skill) {
                return new JsonResponse([
                    'success' => FALSE,
                    'message' => $this->t('Skill no encontrada.'),
                ], 404);
            }

            // Construir prompt con skill.
            $systemPrompt = $skill->getContent();

            // Usar el agente orquestador si existe.
            if (\Drupal::hasService('jaraba_ai_agents.agent_orchestrator')) {
                $orchestrator = \Drupal::service('jaraba_ai_agents.agent_orchestrator');
                $response = $orchestrator->executeAgent('copilot', 'chat', [
                    'message' => $message,
                    'system_prompt_override' => $systemPrompt,
                ]);

                return new JsonResponse([
                    'success' => TRUE,
                    'response' => $response['response'] ?? 'Sin respuesta',
                    'tokens_used' => $response['tokens_used'] ?? 0,
                ]);
            }

            // Fallback: simular respuesta.
            return new JsonResponse([
                'success' => TRUE,
                'response' => $this->t('(Simulación) El skill "@name" fue aplicado correctamente. El mensaje de prueba fue: @message', [
                    '@name' => $skill->label(),
                    '@message' => $message,
                ]),
                'tokens_used' => 0,
                'simulated' => TRUE,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard de analytics de uso de skills.
     *
     * Ruta: /skills/analytics
     */
    public function analytics(): array
    {
        // Obtener servicio de uso.
        $usageService = \Drupal::service('jaraba_skills.usage_service');

        // Estadísticas generales.
        $stats = $usageService->getUsageStats();

        // Top skills.
        $topSkills = $usageService->getTopSkills(10);

        // Datos por día (últimos 30 días).
        $dailyData = $usageService->getUsageByDay(30);

        return [
            '#theme' => 'skills_analytics',
            '#stats' => $stats,
            '#top_skills' => $topSkills,
            '#daily_data' => $dailyData,
            '#attached' => [
                'library' => ['jaraba_skills/skills.dashboard'],
            ],
            '#cache' => ['max-age' => 300], // Cache 5 minutos.
        ];
    }

    /**
     * Endpoint AJAX para datos de analytics.
     *
     * Ruta: /skills/analytics/data
     */
    public function analyticsData(Request $request): JsonResponse
    {
        try {
            $usageService = \Drupal::service('jaraba_skills.usage_service');

            $filters = [
                'skill_id' => $request->query->get('skill_id'),
                'tenant_id' => $request->query->get('tenant_id'),
                'date_from' => $request->query->get('date_from')
                    ? strtotime($request->query->get('date_from'))
                    : NULL,
                'date_to' => $request->query->get('date_to')
                    ? strtotime($request->query->get('date_to'))
                    : NULL,
            ];

            $filters = array_filter($filters);

            $stats = $usageService->getUsageStats($filters);
            $topSkills = $usageService->getTopSkills(10, $filters);
            $dailyData = $usageService->getUsageByDay(30, $filters);

            return new JsonResponse([
                'success' => TRUE,
                'stats' => $stats,
                'top_skills' => $topSkills,
                'daily_data' => $dailyData,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}

