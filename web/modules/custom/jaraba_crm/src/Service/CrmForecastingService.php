<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de forecasting y metricas de ventas del CRM.
 *
 * Calcula previsiones de ventas, tasas de conversion,
 * tamano medio de deals y ciclo de ventas promedio.
 */
class CrmForecastingService {

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected OpportunityService $opportunityService,
    protected PipelineStageService $pipelineStageService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el forecast general del pipeline.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Datos de forecast: total, weighted, by_stage, by_month.
   */
  public function getForecast(int $tenantId): array {
    try {
      $stages = $this->pipelineStageService->getStagesForTenant($tenantId);
      $byStage = [];

      foreach ($stages as $stage) {
        $machineName = $stage->get('machine_name')->value;
        $stageId = (int) $stage->id();

        // Obtener oportunidades en esta etapa.
        $ids = $this->entityTypeManager->getStorage('crm_opportunity')->getQuery()
          ->accessCheck(TRUE)
          ->condition('tenant_id', $tenantId)
          ->condition('stage', $machineName)
          ->execute();

        $opportunities = $ids ? $this->entityTypeManager->getStorage('crm_opportunity')->loadMultiple($ids) : [];

        $stageTotal = 0.0;
        $stageWeighted = 0.0;
        $count = count($opportunities);

        foreach ($opportunities as $opp) {
          $value = (float) ($opp->get('value')->value ?? 0);
          $probability = (float) ($stage->get('default_probability')->value ?? 50);
          $stageTotal += $value;
          $stageWeighted += $value * ($probability / 100);
        }

        $byStage[] = [
          'stage_id' => $stageId,
          'stage_name' => $stage->get('name')->value,
          'count' => $count,
          'total' => round($stageTotal, 2),
          'weighted' => round($stageWeighted, 2),
        ];
      }

      $totalValue = $this->opportunityService->getPipelineValue($tenantId);
      $weightedValue = $this->opportunityService->getWeightedPipelineValue($tenantId);

      return [
        'total_pipeline' => round($totalValue, 2),
        'weighted_pipeline' => round($weightedValue, 2),
        'by_stage' => $byStage,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en forecast: @error', ['@error' => $e->getMessage()]);
      return [
        'total_pipeline' => 0,
        'weighted_pipeline' => 0,
        'by_stage' => [],
      ];
    }
  }

  /**
   * Calcula el pipeline ponderado por probabilidad.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Valor ponderado total.
   */
  public function getWeightedPipeline(int $tenantId): float {
    return $this->opportunityService->getWeightedPipelineValue($tenantId);
  }

  /**
   * Calcula la tasa de conversion (won / total cerradas).
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Porcentaje de conversion (0-100).
   */
  public function getWinRate(int $tenantId): float {
    try {
      $wonCount = $this->opportunityService->count($tenantId, 'won');
      $lostCount = $this->opportunityService->count($tenantId, 'lost');
      $totalClosed = $wonCount + $lostCount;

      if ($totalClosed === 0) {
        return 0.0;
      }

      return round(($wonCount / $totalClosed) * 100, 2);
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando win rate: @error', ['@error' => $e->getMessage()]);
      return 0.0;
    }
  }

  /**
   * Calcula el tamano medio de deals ganados.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Valor medio en euros.
   */
  public function getAvgDealSize(int $tenantId): float {
    try {
      $ids = $this->entityTypeManager->getStorage('crm_opportunity')->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('stage', 'won')
        ->execute();

      if (empty($ids)) {
        return 0.0;
      }

      $opportunities = $this->entityTypeManager->getStorage('crm_opportunity')->loadMultiple($ids);
      $total = 0.0;

      foreach ($opportunities as $opp) {
        $total += (float) ($opp->get('value')->value ?? 0);
      }

      return round($total / count($opportunities), 2);
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando avg deal size: @error', ['@error' => $e->getMessage()]);
      return 0.0;
    }
  }

  /**
   * Calcula el ciclo de ventas promedio en dias.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return float
   *   Dias promedio desde creacion hasta cierre (won).
   */
  public function getSalesCycleAvg(int $tenantId): float {
    try {
      $ids = $this->entityTypeManager->getStorage('crm_opportunity')->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->condition('stage', 'won')
        ->execute();

      if (empty($ids)) {
        return 0.0;
      }

      $opportunities = $this->entityTypeManager->getStorage('crm_opportunity')->loadMultiple($ids);
      $totalDays = 0;
      $count = 0;

      foreach ($opportunities as $opp) {
        $created = $opp->get('created')->value;
        $changed = $opp->get('changed')->value;
        if ($created && $changed) {
          $days = ($changed - $created) / 86400;
          $totalDays += $days;
          $count++;
        }
      }

      return $count > 0 ? round($totalDays / $count, 1) : 0.0;
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando sales cycle: @error', ['@error' => $e->getMessage()]);
      return 0.0;
    }
  }

}
