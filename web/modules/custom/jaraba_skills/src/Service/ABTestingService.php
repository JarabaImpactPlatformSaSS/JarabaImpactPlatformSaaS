<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de A/B Testing para habilidades IA.
 *
 * Permite ejecutar experimentos entre variantes de skills para medir
 * cuál produce mejores resultados.
 *
 * FLUJO:
 * 1. Crear variantes del skill con mismo experiment_id (ej: "tone_test_2026")
 * 2. Asignar experiment_variant diferente (A, B, control, tratamiento)
 * 3. Al resolver skills, selectVariant() elige una variante aleatoriamente
 * 4. El tracking registra qué variante se usó para análisis posterior
 */
class ABTestingService
{

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Selecciona una variante aleatoria de un experimento.
     *
     * @param string $experimentId
     *   ID del experimento.
     * @param array $context
     *   Contexto de resolución (para filtrar por vertical, tenant, etc.).
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkill|null
     *   La variante seleccionada, o NULL si no hay variantes.
     */
    public function selectVariant(string $experimentId, array $context = []): ?object
    {
        if (empty($experimentId)) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('ai_skill');

        // Buscar todas las variantes activas del experimento.
        $query = $storage->getQuery()
            ->condition('experiment_id', $experimentId)
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE);

        // Filtrar por contexto si aplica.
        if (!empty($context['vertical'])) {
            $query->condition('vertical_id', $context['vertical']);
        }
        if (!empty($context['tenant_id'])) {
            $query->condition('tenant_id', $context['tenant_id']);
        }

        $ids = $query->execute();

        if (empty($ids)) {
            $this->logger->debug('No variants found for experiment @id', [
                '@id' => $experimentId,
            ]);
            return NULL;
        }

        // Selección aleatoria ponderada (por ahora equiprobable).
        $randomId = array_rand(array_flip($ids));

        /** @var \Drupal\jaraba_skills\Entity\AiSkill $variant */
        $variant = $storage->load($randomId);

        $this->logger->debug('Selected variant @variant for experiment @id', [
            '@variant' => $variant->get('experiment_variant')->value ?? 'unknown',
            '@id' => $experimentId,
        ]);

        return $variant;
    }

    /**
     * Obtiene todas las variantes de un experimento.
     *
     * @param string $experimentId
     *   ID del experimento.
     *
     * @return array
     *   Array de skills que participan en el experimento.
     */
    public function getExperimentVariants(string $experimentId): array
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill');

        $ids = $storage->getQuery()
            ->condition('experiment_id', $experimentId)
            ->accessCheck(FALSE)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Lista todos los experimentos activos.
     *
     * @return array
     *   Array de experiment_id => número de variantes.
     */
    public function getActiveExperiments(): array
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill');

        // Query para obtener skill con experiment_id no vacío.
        $ids = $storage->getQuery()
            ->condition('experiment_id', '', '<>')
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE)
            ->execute();

        $skills = $storage->loadMultiple($ids);
        $experiments = [];

        foreach ($skills as $skill) {
            $expId = $skill->get('experiment_id')->value ?? '';
            if (!empty($expId)) {
                if (!isset($experiments[$expId])) {
                    $experiments[$expId] = [
                        'id' => $expId,
                        'variants' => [],
                        'total' => 0,
                    ];
                }
                $variant = $skill->get('experiment_variant')->value ?? 'default';
                $experiments[$expId]['variants'][] = [
                    'skill_id' => $skill->id(),
                    'name' => $skill->label(),
                    'variant' => $variant,
                ];
                $experiments[$expId]['total']++;
            }
        }

        return $experiments;
    }

    /**
     * Obtiene estadísticas de un experimento.
     *
     * @param string $experimentId
     *   ID del experimento.
     *
     * @return array
     *   Estadísticas por variante (invocaciones, éxito, latencia promedio).
     */
    public function getExperimentStats(string $experimentId): array
    {
        $usageStorage = $this->entityTypeManager->getStorage('ai_skill_usage');
        $skillStorage = $this->entityTypeManager->getStorage('ai_skill');

        // Obtener skills del experimento.
        $skillIds = $skillStorage->getQuery()
            ->condition('experiment_id', $experimentId)
            ->accessCheck(FALSE)
            ->execute();

        if (empty($skillIds)) {
            return [];
        }

        $skills = $skillStorage->loadMultiple($skillIds);
        $stats = [];

        foreach ($skills as $skill) {
            $skillId = $skill->id();
            $variant = $skill->get('experiment_variant')->value ?? 'default';

            // Obtener usos de este skill.
            $query = $usageStorage->getQuery()
                ->condition('skill_id', $skillId)
                ->accessCheck(FALSE);
            $usageIds = $query->execute();
            $usages = $usageStorage->loadMultiple($usageIds);

            $totalLatency = 0;
            $successCount = 0;

            foreach ($usages as $usage) {
                $totalLatency += (int) ($usage->get('latency_ms')->value ?? 0);
                if ($usage->get('success')->value) {
                    $successCount++;
                }
            }

            $totalUsages = count($usages);
            $avgLatency = $totalUsages > 0 ? round($totalLatency / $totalUsages) : 0;
            $successRate = $totalUsages > 0 ? round(($successCount / $totalUsages) * 100, 1) : 0;

            $stats[$variant] = [
                'skill_id' => $skillId,
                'skill_name' => $skill->label(),
                'variant' => $variant,
                'invocations' => $totalUsages,
                'success_count' => $successCount,
                'success_rate' => $successRate,
                'avg_latency_ms' => $avgLatency,
            ];
        }

        return $stats;
    }

}
