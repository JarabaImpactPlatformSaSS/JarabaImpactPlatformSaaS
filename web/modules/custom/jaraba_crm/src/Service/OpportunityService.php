<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Entity\Opportunity;

/**
 * Servicio para gestión del pipeline de oportunidades.
 */
class OpportunityService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Obtiene el storage de oportunidades.
     */
    protected function getStorage()
    {
        return $this->entityTypeManager->getStorage('crm_opportunity');
    }

    /**
     * Crea una nueva oportunidad.
     */
    public function create(array $values): Opportunity
    {
        $opportunity = $this->getStorage()->create($values);
        $opportunity->save();
        return $opportunity;
    }

    /**
     * Carga una oportunidad por ID.
     */
    public function load(int $id): ?Opportunity
    {
        return $this->getStorage()->load($id);
    }

    /**
     * Obtiene oportunidades agrupadas por etapa (para Kanban).
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return array
     *   Array con etapas como keys y arrays de oportunidades como values.
     */
    public function getByStage(?int $tenantId = NULL): array
    {
        $stages = jaraba_crm_get_opportunity_stage_values();
        $pipeline = [];

        foreach (array_keys($stages) as $stage) {
            $pipeline[$stage] = [];
        }

        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        $opportunities = $ids ? $this->getStorage()->loadMultiple($ids) : [];

        foreach ($opportunities as $opportunity) {
            $stage = $opportunity->get('stage')->value;
            if (isset($pipeline[$stage])) {
                $pipeline[$stage][] = $opportunity;
            }
        }

        return $pipeline;
    }

    /**
     * Mueve una oportunidad a otra etapa.
     *
     * @param int $opportunityId
     *   ID de la oportunidad.
     * @param string $newStage
     *   Nueva etapa.
     *
     * @return bool
     *   TRUE si se movió correctamente.
     */
    public function moveToStage(int $opportunityId, string $newStage): bool
    {
        $opportunity = $this->load($opportunityId);
        if (!$opportunity) {
            return FALSE;
        }

        $stages = jaraba_crm_get_opportunity_stage_values();
        if (!isset($stages[$newStage])) {
            return FALSE;
        }

        $opportunity->set('stage', $newStage);
        $opportunity->save();
        return TRUE;
    }

    /**
     * Calcula el valor total del pipeline.
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     * @param string|null $stage
     *   Filtrar por etapa (opcional).
     *
     * @return float
     *   Valor total en euros.
     */
    public function getPipelineValue(?int $tenantId = NULL, ?string $stage = NULL): float
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        if ($stage) {
            $query->condition('stage', $stage);
        }

        $ids = $query->execute();
        if (empty($ids)) {
            return 0.0;
        }

        $opportunities = $this->getStorage()->loadMultiple($ids);
        $total = 0.0;

        foreach ($opportunities as $opportunity) {
            $value = (float) ($opportunity->get('value')->value ?? 0);
            $total += $value;
        }

        return $total;
    }

    /**
     * Calcula el valor ponderado del pipeline (valor * probabilidad).
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return float
     *   Valor ponderado total.
     */
    public function getWeightedPipelineValue(?int $tenantId = NULL): float
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        if (empty($ids)) {
            return 0.0;
        }

        $opportunities = $this->getStorage()->loadMultiple($ids);
        $total = 0.0;

        foreach ($opportunities as $opportunity) {
            $value = (float) ($opportunity->get('value')->value ?? 0);
            $probability = (int) ($opportunity->get('probability')->value ?? 50);
            $total += $value * ($probability / 100);
        }

        return $total;
    }

    /**
     * Obtiene oportunidades próximas a cerrar.
     *
     * @param int $days
     *   Días hacia adelante.
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return \Drupal\jaraba_crm\Entity\Opportunity[]
     *   Oportunidades ordenadas por fecha de cierre.
     */
    public function getClosingSoon(int $days = 30, ?int $tenantId = NULL): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('expected_close', date('Y-m-d'), '>=')
            ->condition('expected_close', date('Y-m-d', strtotime("+{$days} days")), '<=')
            ->sort('expected_close', 'ASC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Cuenta oportunidades.
     */
    public function count(?int $tenantId = NULL, ?string $stage = NULL): int
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->count();

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        if ($stage) {
            $query->condition('stage', $stage);
        }

        return (int) $query->execute();
    }

}
