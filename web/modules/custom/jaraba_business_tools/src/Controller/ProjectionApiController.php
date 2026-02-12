<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Financial Projection API endpoints.
 */
class ProjectionApiController extends ControllerBase
{

    /**
     * Lists projections for current user.
     */
    public function list(): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('financial_projection');
        $ids = $storage->getQuery()
            ->condition('user_id', $this->currentUser()->id())
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->execute();

        $projections = [];
        foreach ($storage->loadMultiple($ids) as $projection) {
            $projections[] = $this->serializeProjection($projection);
        }

        return new JsonResponse(['data' => $projections]);
    }

    /**
     * Creates a new financial projection.
     */
    public function createProjection(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], 400);
        }

        $storage = $this->entityTypeManager()->getStorage('financial_projection');
        $projection = $storage->create([
            'user_id' => $this->currentUser()->id(),
            'title' => $data['title'],
            'scenario' => $data['scenario'] ?? 'realistic',
            'period_months' => $data['period_months'] ?? 12,
            'initial_investment' => $data['initial_investment'] ?? 0,
            'monthly_fixed_costs' => $data['monthly_fixed_costs'] ?? 0,
            'monthly_variable_costs' => $data['monthly_variable_costs'] ?? 0,
            'canvas_id' => $data['canvas_id'] ?? NULL,
        ]);
        $projection->save();

        return new JsonResponse([
            'data' => $this->serializeProjection($projection),
            'message' => 'Projection created successfully',
        ], 201);
    }

    /**
     * Gets a single projection.
     */
    public function get(int $id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('financial_projection');
        $projection = $storage->load($id);

        if (!$projection) {
            return new JsonResponse(['error' => 'Projection not found'], 404);
        }

        if ($projection->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse(['data' => $this->serializeProjection($projection)]);
    }

    /**
     * Updates a projection.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('financial_projection');
        $projection = $storage->load($id);

        if (!$projection) {
            return new JsonResponse(['error' => 'Projection not found'], 404);
        }

        if ($projection->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), TRUE);

        $allowed_fields = [
            'title',
            'scenario',
            'period_months',
            'initial_investment',
            'monthly_fixed_costs',
            'monthly_variable_costs',
            'projected_revenue_m1',
            'revenue_growth_rate',
            'notes',
        ];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $projection->set($field, $data[$field]);
            }
        }

        $projection->save();

        return new JsonResponse([
            'data' => $this->serializeProjection($projection),
            'message' => 'Projection updated',
        ]);
    }

    /**
     * Calculates projection metrics.
     */
    public function calculate(int $id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('financial_projection');
        $projection = $storage->load($id);

        if (!$projection) {
            return new JsonResponse(['error' => 'Projection not found'], 404);
        }

        if ($projection->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $period = $projection->getPeriodMonths();
        $initial = $projection->getInitialInvestment();
        $fixed_costs = $projection->getMonthlyFixedCosts();
        $variable_costs = $projection->getMonthlyVariableCosts();
        $m1_revenue = (float) $projection->get('projected_revenue_m1')->value;
        $growth_rate = (float) $projection->get('revenue_growth_rate')->value / 100;

        $monthly_data = [];
        $cumulative_profit = -$initial;
        $breakeven_month = NULL;

        for ($month = 1; $month <= $period; $month++) {
            $revenue = $m1_revenue * pow(1 + $growth_rate, $month - 1);
            $total_costs = $fixed_costs + $variable_costs;
            $profit = $revenue - $total_costs;
            $cumulative_profit += $profit;

            $monthly_data[] = [
                'month' => $month,
                'revenue' => round($revenue, 2),
                'costs' => round($total_costs, 2),
                'profit' => round($profit, 2),
                'cumulative' => round($cumulative_profit, 2),
            ];

            if ($breakeven_month === NULL && $cumulative_profit >= 0) {
                $breakeven_month = $month;
            }
        }

        $total_revenue = array_sum(array_column($monthly_data, 'revenue'));
        $total_costs = ($fixed_costs + $variable_costs) * $period + $initial;
        $roi = $initial > 0 ? (($total_revenue - $total_costs) / $initial) * 100 : 0;

        $projection->set('calculated_roi', round($roi, 2));
        $projection->set('breakeven_month', $breakeven_month);
        $projection->save();

        return new JsonResponse([
            'data' => [
                'projection_id' => (int) $projection->id(),
                'period_months' => $period,
                'roi' => round($roi, 2),
                'breakeven_month' => $breakeven_month,
                'total_revenue' => round($total_revenue, 2),
                'total_costs' => round($total_costs, 2),
                'net_profit' => round($total_revenue - $total_costs, 2),
                'monthly_data' => $monthly_data,
            ],
        ]);
    }

    /**
     * Serializes a projection entity.
     */
    protected function serializeProjection($projection): array
    {
        return [
            'id' => (int) $projection->id(),
            'uuid' => $projection->uuid(),
            'title' => $projection->label(),
            'scenario' => $projection->getScenario(),
            'period_months' => $projection->getPeriodMonths(),
            'initial_investment' => $projection->getInitialInvestment(),
            'monthly_fixed_costs' => $projection->getMonthlyFixedCosts(),
            'monthly_variable_costs' => $projection->getMonthlyVariableCosts(),
            'roi' => $projection->getRoi(),
            'breakeven_month' => $projection->getBreakevenMonth(),
            'canvas_id' => $projection->get('canvas_id')->target_id,
            'created' => $projection->get('created')->value,
            'changed' => $projection->getChangedTime(),
        ];
    }

}
