<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;

/**
 * Service for calculating SROI (Social Return on Investment) metrics.
 *
 * Implements methodology for measuring and quantifying social impact
 * of entrepreneurship programs for stakeholders and funders.
 */
class SroiCalculatorService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The database connection.
     */
    protected Connection $database;

    /**
     * Financial proxy values for social outcomes (EUR).
     */
    protected const PROXY_VALUES = [
        'job_created' => 25000,          // Annual economic value per job
        'job_preserved' => 18000,        // Economic value of preserved job
        'business_formalized' => 5000,   // Value of formal business registration
        'digital_skill_acquired' => 800, // Per skill gained
        'revenue_generated' => 0.15,     // 15% social value multiplier
        'carbon_saved_kg' => 0.05,       // EUR per kg CO2 saved
    ];

    /**
     * SDG (Sustainable Development Goals) mappings.
     */
    protected const SDG_MAPPINGS = [
        'jobs' => [8, 1],           // SDG 8: Decent Work, SDG 1: No Poverty
        'business' => [8, 9],       // SDG 8: Decent Work, SDG 9: Industry
        'digital' => [4, 9],        // SDG 4: Education, SDG 9: Industry
        'gender' => [5],            // SDG 5: Gender Equality
        'sustainability' => [12, 13], // SDG 12: Responsible Consumption, SDG 13: Climate Action
    ];

    /**
     * Constructs a new SroiCalculatorService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
    }

    /**
     * Calculates SROI for a specific program or time period.
     *
     * @param array $options
     *   Options: start_date, end_date, tenant_id, cohort_id
     *
     * @return array
     *   SROI calculation results
     */
    public function calculateSroi(array $options = []): array
    {
        $outcomes = $this->gatherOutcomes($options);
        $inputs = $this->gatherInputs($options);

        $socialValue = $this->calculateSocialValue($outcomes);
        $deadweight = $this->calculateDeadweight($outcomes);
        $attribution = $this->calculateAttribution($outcomes);

        $netSocialValue = $socialValue * (1 - $deadweight) * $attribution;
        $totalInvestment = $inputs['total_investment'];

        $sroiRatio = $totalInvestment > 0 ? $netSocialValue / $totalInvestment : 0;

        return [
            'sroi_ratio' => round($sroiRatio, 2),
            'interpretation' => $this->interpretSroi($sroiRatio),
            'social_value_created' => round($netSocialValue, 2),
            'total_investment' => round($totalInvestment, 2),
            'outcomes' => $outcomes,
            'inputs' => $inputs,
            'adjustments' => [
                'deadweight' => $deadweight,
                'attribution' => $attribution,
            ],
            'sdg_alignment' => $this->calculateSdgAlignment($outcomes),
            'calculated_at' => date('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * Gathers outcomes from various sources.
     */
    protected function gatherOutcomes(array $options): array
    {
        $outcomes = [
            'jobs_created' => 0,
            'jobs_preserved' => 0,
            'businesses_formalized' => 0,
            'digital_skills_acquired' => 0,
            'revenue_generated' => 0,
            'entrepreneurs_graduated' => 0,
            'mentoring_hours' => 0,
        ];

        // Count completed diagnostics (proxy for active entrepreneurs)
        $diagnosticStorage = $this->entityTypeManager->getStorage('business_diagnostic');
        $query = $diagnosticStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'completed');

        if (!empty($options['start_date'])) {
            $query->condition('created', strtotime($options['start_date']), '>=');
        }
        if (!empty($options['end_date'])) {
            $query->condition('created', strtotime($options['end_date']), '<=');
        }
        if (!empty($options['tenant_id'])) {
            $query->condition('tenant_id', $options['tenant_id']);
        }

        $outcomes['entrepreneurs_graduated'] = $query->count()->execute();

        // Count path completions (skills acquired)
        $pathStorage = $this->entityTypeManager->getStorage('path_enrollment');
        $completedPaths = $pathStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'completed')
            ->count()
            ->execute();

        $outcomes['digital_skills_acquired'] = $completedPaths * 3; // ~3 skills per path

        // Count mentoring sessions
        $sessionStorage = $this->entityTypeManager->getStorage('mentoring_session');
        $sessions = $sessionStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'completed')
            ->count()
            ->execute();

        $outcomes['mentoring_hours'] = $sessions * 1.5; // ~1.5 hours per session

        // Estimate jobs (placeholder - would connect to actual tracking)
        $outcomes['jobs_created'] = (int) ($outcomes['entrepreneurs_graduated'] * 0.3);
        $outcomes['businesses_formalized'] = (int) ($outcomes['entrepreneurs_graduated'] * 0.4);

        return $outcomes;
    }

    /**
     * Gathers program inputs (investments).
     */
    protected function gatherInputs(array $options): array
    {
        // Placeholder - would connect to actual financial tracking
        $defaultInvestment = 50000; // Default annual program cost

        return [
            'total_investment' => $options['investment'] ?? $defaultInvestment,
            'staff_costs' => $defaultInvestment * 0.5,
            'infrastructure_costs' => $defaultInvestment * 0.2,
            'materials_costs' => $defaultInvestment * 0.1,
            'overhead_costs' => $defaultInvestment * 0.2,
        ];
    }

    /**
     * Calculates total social value from outcomes.
     */
    protected function calculateSocialValue(array $outcomes): float
    {
        $value = 0;

        $value += $outcomes['jobs_created'] * self::PROXY_VALUES['job_created'];
        $value += $outcomes['jobs_preserved'] * self::PROXY_VALUES['job_preserved'];
        $value += $outcomes['businesses_formalized'] * self::PROXY_VALUES['business_formalized'];
        $value += $outcomes['digital_skills_acquired'] * self::PROXY_VALUES['digital_skill_acquired'];
        $value += $outcomes['revenue_generated'] * self::PROXY_VALUES['revenue_generated'];

        return $value;
    }

    /**
     * Calculates deadweight (what would have happened anyway).
     */
    protected function calculateDeadweight(array $outcomes): float
    {
        // Conservative estimate: 20% deadweight
        return 0.20;
    }

    /**
     * Calculates attribution (how much is due to this program).
     */
    protected function calculateAttribution(array $outcomes): float
    {
        // Conservative estimate: 70% attribution
        return 0.70;
    }

    /**
     * Interprets SROI ratio for reporting.
     */
    protected function interpretSroi(float $ratio): string
    {
        if ($ratio < 1) {
            return 'Por cada 1€ invertido, se genera ' . number_format($ratio, 2, ',', '.') . '€ de valor social. La inversión no está cubriendo su coste social.';
        } elseif ($ratio < 2) {
            return 'Por cada 1€ invertido, se genera ' . number_format($ratio, 2, ',', '.') . '€ de valor social. Retorno social positivo.';
        } elseif ($ratio < 5) {
            return 'Por cada 1€ invertido, se genera ' . number_format($ratio, 2, ',', '.') . '€ de valor social. Excelente retorno social.';
        } else {
            return 'Por cada 1€ invertido, se genera ' . number_format($ratio, 2, ',', '.') . '€ de valor social. Impacto excepcional.';
        }
    }

    /**
     * Calculates SDG alignment scores.
     */
    protected function calculateSdgAlignment(array $outcomes): array
    {
        $sdgScores = [];

        // SDG 1 & 8: Job creation impacts
        if ($outcomes['jobs_created'] > 0 || $outcomes['jobs_preserved'] > 0) {
            $sdgScores[1] = min(100, ($outcomes['jobs_created'] + $outcomes['jobs_preserved']) * 5);
            $sdgScores[8] = min(100, ($outcomes['jobs_created'] + $outcomes['jobs_preserved']) * 5);
        }

        // SDG 4: Skills/education
        if ($outcomes['digital_skills_acquired'] > 0) {
            $sdgScores[4] = min(100, $outcomes['digital_skills_acquired'] * 3);
        }

        // SDG 9: Industry/innovation
        if ($outcomes['businesses_formalized'] > 0) {
            $sdgScores[9] = min(100, $outcomes['businesses_formalized'] * 4);
        }

        return $sdgScores;
    }

    /**
     * Generates impact report for funders.
     */
    public function generateImpactReport(array $options = []): array
    {
        $sroi = $this->calculateSroi($options);

        return [
            'executive_summary' => [
                'sroi_ratio' => $sroi['sroi_ratio'],
                'social_value' => $sroi['social_value_created'],
                'investment' => $sroi['total_investment'],
                'interpretation' => $sroi['interpretation'],
            ],
            'outcomes_detail' => $sroi['outcomes'],
            'sdg_contribution' => $sroi['sdg_alignment'],
            'methodology' => [
                'framework' => 'SROI Standard',
                'proxy_values' => self::PROXY_VALUES,
                'deadweight' => $sroi['adjustments']['deadweight'] * 100 . '%',
                'attribution' => $sroi['adjustments']['attribution'] * 100 . '%',
            ],
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

}
