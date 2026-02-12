<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión y resolución de habilidades IA.
 *
 * Resuelve habilidades jerárquicamente: Core → Vertical → Agent → Tenant.
 */
class SkillManager
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected SkillUsageService $usageService,
    ) {
    }

    /**
     * Resuelve las habilidades aplicables a un contexto dado.
     *
     * @param array $context
     *   Contexto de resolución:
     *   - 'vertical': ID de la vertical (opcional)
     *   - 'agent_type': Tipo de agente (opcional)
     *   - 'tenant_id': ID del tenant (opcional)
     *
     * @return array
     *   Array de habilidades resueltas ordenadas por prioridad.
     */
    public function resolveSkills(array $context = []): array
    {
        $skills = [];
        $storage = $this->entityTypeManager->getStorage('ai_skill');

        // 1. Core Skills (siempre aplican).
        $coreSkills = $storage->loadByProperties([
            'skill_type' => 'core',
            'is_active' => TRUE,
        ]);
        $skills = array_merge($skills, $coreSkills);

        // 2. Vertical Skills.
        if (!empty($context['vertical'])) {
            $verticalSkills = $storage->loadByProperties([
                'skill_type' => 'vertical',
                'vertical_id' => $context['vertical'],
                'is_active' => TRUE,
            ]);
            $skills = array_merge($skills, $verticalSkills);
        }

        // 3. Agent Skills.
        if (!empty($context['agent_type'])) {
            $agentSkills = $storage->loadByProperties([
                'skill_type' => 'agent',
                'agent_type' => $context['agent_type'],
                'is_active' => TRUE,
            ]);
            $skills = array_merge($skills, $agentSkills);
        }

        // 4. Tenant Skills.
        if (!empty($context['tenant_id'])) {
            $tenantSkills = $storage->loadByProperties([
                'skill_type' => 'tenant',
                'tenant_id' => $context['tenant_id'],
                'is_active' => TRUE,
            ]);
            $skills = array_merge($skills, $tenantSkills);
        }

        // Ordenar por prioridad (mayor primero).
        usort($skills, function ($a, $b) {
            $priorityA = (int) ($a->get('priority')->value ?? 0);
            $priorityB = (int) ($b->get('priority')->value ?? 0);
            return $priorityB <=> $priorityA;
        });

        return $skills;
    }

    /**
     * Genera la sección XML de skills para inyectar en el prompt del LLM.
     *
     * Registra automáticamente el uso de cada skill resuelto para analytics.
     *
     * @param array $context
     *   Contexto de resolución.
     * @param array $metrics
     *   Métricas adicionales de la invocación (opcional):
     *   - 'latency_ms': Latencia en ms
     *   - 'tokens_input': Tokens de entrada
     *   - 'tokens_output': Tokens de salida
     *   - 'model': Modelo LLM usado
     *   - 'success': Si fue exitoso
     *
     * @return string
     *   Sección <skills> para el prompt.
     */
    public function generatePromptSection(array $context = [], array $metrics = []): string
    {
        $skills = $this->resolveSkills($context);

        if (empty($skills)) {
            return '';
        }

        $output = "<skills>\n";
        foreach ($skills as $skill) {
            /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
            $name = $skill->label();
            $type = $skill->getSkillType();
            $content = $skill->getContent();

            $output .= "<skill name=\"{$name}\" type=\"{$type}\">\n";
            $output .= trim($content) . "\n";
            $output .= "</skill>\n";

            // Registrar uso de cada skill.
            $this->usageService->recordUsage(
                (int) $skill->id(),
                $context,
                $metrics
            );
        }
        $output .= "</skills>";

        $this->logger->debug('Generated skills section with @count skills for context: @context', [
            '@count' => count($skills),
            '@context' => json_encode($context),
        ]);

        return $output;
    }

    /**
     * Obtiene estadísticas de uso de skills.
     *
     * @return array
     *   Estadísticas por tipo.
     */
    public function getStatistics(): array
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill');
        $stats = [
            'total' => 0,
            'by_type' => [
                'core' => 0,
                'vertical' => 0,
                'agent' => 0,
                'tenant' => 0,
            ],
            'active' => 0,
        ];

        $skills = $storage->loadMultiple();
        $stats['total'] = count($skills);

        foreach ($skills as $skill) {
            /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
            $type = $skill->getSkillType();
            if (isset($stats['by_type'][$type])) {
                $stats['by_type'][$type]++;
            }
            if ($skill->isActive()) {
                $stats['active']++;
            }
        }

        return $stats;
    }

    /**
     * Verifica si un tenant puede crear más skills según su plan.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Array con:
     *   - 'allowed': bool, si puede crear más skills.
     *   - 'current': int, número actual de skills del tenant.
     *   - 'limit': int, límite máximo según el plan (-1 = ilimitado).
     *   - 'message': string, mensaje para mostrar al usuario.
     */
    public function canTenantCreateSkill(int $tenantId): array
    {
        // Cargar tenant y su plan.
        $tenantStorage = $this->entityTypeManager->getStorage('tenant');
        /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null $tenant */
        $tenant = $tenantStorage->load($tenantId);

        if (!$tenant) {
            return [
                'allowed' => FALSE,
                'current' => 0,
                'limit' => 0,
                'message' => 'Tenant no encontrado.',
            ];
        }

        $plan = $tenant->getSubscriptionPlan();
        $limit = $plan ? (int) $plan->getLimit('max_custom_skills', -1) : -1;

        // -1 significa ilimitado.
        if ($limit === -1) {
            return [
                'allowed' => TRUE,
                'current' => 0,
                'limit' => -1,
                'message' => '',
            ];
        }

        // Contar skills actuales del tenant.
        $skillStorage = $this->entityTypeManager->getStorage('ai_skill');
        $query = $skillStorage->getQuery()
            ->condition('skill_type', 'tenant')
            ->condition('tenant_id', $tenantId)
            ->accessCheck(FALSE);
        $current = (int) $query->count()->execute();

        $allowed = $current < $limit;

        return [
            'allowed' => $allowed,
            'current' => $current,
            'limit' => $limit,
            'message' => $allowed
                ? ''
                : "Has alcanzado el límite de {$limit} habilidades personalizadas de tu plan. Actualiza tu plan para crear más.",
        ];
    }

}
