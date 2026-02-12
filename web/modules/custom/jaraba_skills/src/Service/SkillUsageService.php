<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_skills\Entity\AiSkillUsage;
use Psr\Log\LoggerInterface;

/**
 * Servicio para registrar y consultar el uso de AI Skills.
 *
 * Proporciona métricas de invocación, latencia, tokens y éxito
 * para analytics y control de costos.
 */
class SkillUsageService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Registra una invocación de skill.
     *
     * @param int $skillId
     *   ID del skill invocado.
     * @param array $context
     *   Contexto de la invocación (tenant_id, vertical, agent_type).
     * @param array $metrics
     *   Métricas (latency_ms, tokens_input, tokens_output, success, error_message, model).
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkillUsage
     *   La entidad de uso creada.
     */
    public function recordUsage(int $skillId, array $context = [], array $metrics = []): AiSkillUsage
    {
        try {
            /** @var \Drupal\jaraba_skills\Entity\AiSkillUsage $usage */
            $usage = $this->entityTypeManager
                ->getStorage('ai_skill_usage')
                ->create([
                    'skill_id' => $skillId,
                    'tenant_id' => $context['tenant_id'] ?? NULL,
                    'vertical' => $context['vertical'] ?? '',
                    'agent_type' => $context['agent_type'] ?? '',
                    'user_id' => $context['user_id'] ?? \Drupal::currentUser()->id(),
                    'latency_ms' => $metrics['latency_ms'] ?? 0,
                    'tokens_input' => $metrics['tokens_input'] ?? 0,
                    'tokens_output' => $metrics['tokens_output'] ?? 0,
                    'success' => $metrics['success'] ?? TRUE,
                    'error_message' => $metrics['error_message'] ?? '',
                    'model' => $metrics['model'] ?? '',
                ]);

            $usage->save();

            return $usage;
        } catch (\Exception $e) {
            $this->logger->error('Error registrando uso de skill @id: @error', [
                '@id' => $skillId,
                '@error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene estadísticas agregadas de uso.
     *
     * @param array $filters
     *   Filtros opcionales (skill_id, tenant_id, date_from, date_to).
     *
     * @return array
     *   Estadísticas agregadas.
     */
    public function getUsageStats(array $filters = []): array
    {
        $query = $this->entityTypeManager
            ->getStorage('ai_skill_usage')
            ->getQuery()
            ->accessCheck(TRUE);

        // Aplicar filtros.
        if (!empty($filters['skill_id'])) {
            $query->condition('skill_id', $filters['skill_id']);
        }
        if (!empty($filters['tenant_id'])) {
            $query->condition('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->condition('created', $filters['date_from'], '>=');
        }
        if (!empty($filters['date_to'])) {
            $query->condition('created', $filters['date_to'], '<=');
        }

        $ids = $query->execute();

        if (empty($ids)) {
            return [
                'total_invocations' => 0,
                'successful' => 0,
                'failed' => 0,
                'total_tokens_input' => 0,
                'total_tokens_output' => 0,
                'total_tokens' => 0,
                'avg_latency_ms' => 0,
                'success_rate' => 100,
            ];
        }

        $usages = $this->entityTypeManager
            ->getStorage('ai_skill_usage')
            ->loadMultiple($ids);

        $stats = [
            'total_invocations' => count($usages),
            'successful' => 0,
            'failed' => 0,
            'total_tokens_input' => 0,
            'total_tokens_output' => 0,
            'total_latency' => 0,
        ];

        foreach ($usages as $usage) {
            /** @var \Drupal\jaraba_skills\Entity\AiSkillUsage $usage */
            if ($usage->wasSuccessful()) {
                $stats['successful']++;
            } else {
                $stats['failed']++;
            }
            $stats['total_tokens_input'] += $usage->getTokensInput();
            $stats['total_tokens_output'] += $usage->getTokensOutput();
            $stats['total_latency'] += $usage->getLatencyMs();
        }

        return [
            'total_invocations' => $stats['total_invocations'],
            'successful' => $stats['successful'],
            'failed' => $stats['failed'],
            'total_tokens_input' => $stats['total_tokens_input'],
            'total_tokens_output' => $stats['total_tokens_output'],
            'total_tokens' => $stats['total_tokens_input'] + $stats['total_tokens_output'],
            'avg_latency_ms' => $stats['total_invocations'] > 0
                ? round($stats['total_latency'] / $stats['total_invocations'], 2)
                : 0,
            'success_rate' => $stats['total_invocations'] > 0
                ? round(($stats['successful'] / $stats['total_invocations']) * 100, 1)
                : 100,
        ];
    }

    /**
     * Obtiene las skills más usadas.
     *
     * @param int $limit
     *   Número máximo de resultados.
     * @param array $filters
     *   Filtros opcionales.
     *
     * @return array
     *   Array con skill_id => count ordenado por uso.
     */
    public function getTopSkills(int $limit = 10, array $filters = []): array
    {
        $connection = \Drupal::database();

        $query = $connection->select('ai_skill_usage', 'u')
            ->fields('u', ['skill_id'])
            ->groupBy('u.skill_id');

        $query->addExpression('COUNT(u.id)', 'usage_count');
        $query->addExpression('AVG(u.latency_ms)', 'avg_latency');
        $query->addExpression('SUM(u.tokens_input + u.tokens_output)', 'total_tokens');

        if (!empty($filters['tenant_id'])) {
            $query->condition('u.tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->condition('u.created', $filters['date_from'], '>=');
        }
        if (!empty($filters['date_to'])) {
            $query->condition('u.created', $filters['date_to'], '<=');
        }

        $query->orderBy('usage_count', 'DESC')
            ->range(0, $limit);

        $results = $query->execute()->fetchAll();

        // Cargamos los nombres de las skills.
        $skillStorage = $this->entityTypeManager->getStorage('ai_skill');
        $topSkills = [];

        foreach ($results as $row) {
            $skill = $skillStorage->load($row->skill_id);
            $topSkills[] = [
                'skill_id' => $row->skill_id,
                'skill_name' => $skill ? $skill->label() : 'Eliminada',
                'usage_count' => (int) $row->usage_count,
                'avg_latency_ms' => round((float) $row->avg_latency, 2),
                'total_tokens' => (int) $row->total_tokens,
            ];
        }

        return $topSkills;
    }

    /**
     * Obtiene el uso por día para gráficos.
     *
     * @param int $days
     *   Número de días hacia atrás.
     * @param array $filters
     *   Filtros opcionales.
     *
     * @return array
     *   Array de datos por día.
     */
    public function getUsageByDay(int $days = 30, array $filters = []): array
    {
        $connection = \Drupal::database();
        $dateFrom = strtotime("-{$days} days");

        $query = $connection->select('ai_skill_usage', 'u');
        $query->addExpression("DATE(FROM_UNIXTIME(u.created))", 'day');
        $query->addExpression('COUNT(u.id)', 'invocations');
        $query->addExpression('SUM(u.tokens_input + u.tokens_output)', 'tokens');
        $query->addExpression('AVG(u.latency_ms)', 'avg_latency');

        $query->condition('u.created', $dateFrom, '>=');

        if (!empty($filters['skill_id'])) {
            $query->condition('u.skill_id', $filters['skill_id']);
        }
        if (!empty($filters['tenant_id'])) {
            $query->condition('u.tenant_id', $filters['tenant_id']);
        }

        $query->groupBy('day')
            ->orderBy('day', 'ASC');

        $results = $query->execute()->fetchAll();

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'date' => $row->day,
                'invocations' => (int) $row->invocations,
                'tokens' => (int) $row->tokens,
                'avg_latency_ms' => round((float) $row->avg_latency, 2),
            ];
        }

        return $data;
    }

}
