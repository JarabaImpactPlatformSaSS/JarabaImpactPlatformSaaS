<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_crm\Service\CompanyService;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\jaraba_crm\Service\OpportunityService;
use Drupal\jaraba_crm\Service\ActivityService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el dashboard y pipeline del CRM.
 */
class CrmDashboardController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected CompanyService $companyService,
        protected ContactService $contactService,
        protected OpportunityService $opportunityService,
        protected ActivityService $activityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_crm.company'),
            $container->get('jaraba_crm.contact'),
            $container->get('jaraba_crm.opportunity'),
            $container->get('jaraba_crm.activity'),
        );
    }

    /**
     * Muestra el dashboard principal del CRM.
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        // TODO: Obtener tenant_id del contexto actual.
        $tenantId = NULL;

        return [
            '#theme' => 'crm_dashboard',
            '#companies_count' => $this->companyService->count($tenantId),
            '#contacts_count' => $this->contactService->count($tenantId),
            '#opportunities_count' => $this->opportunityService->count($tenantId),
            '#pipeline_value' => $this->opportunityService->getPipelineValue($tenantId),
            '#weighted_value' => $this->opportunityService->getWeightedPipelineValue($tenantId),
            '#recent_activities' => $this->activityService->getRecent($tenantId, 10),
            '#top_contacts' => $this->contactService->getTopEngaged(5, $tenantId),
            '#closing_soon' => $this->opportunityService->getClosingSoon(30, $tenantId),
            '#cache' => [
                'max-age' => 0,
            ],
        ];
    }

    /**
     * Muestra el pipeline Kanban de oportunidades.
     *
     * @return array
     *   Render array del pipeline.
     */
    public function pipeline(): array
    {
        $tenantId = NULL;
        $stageLabels = jaraba_crm_get_opportunity_stage_values();
        $pipelineData = $this->opportunityService->getByStage($tenantId);

        // Construir estructura de stages con oportunidades y valores.
        $stages = [];
        foreach ($stageLabels as $stageKey => $label) {
            $stageOpportunities = $pipelineData[$stageKey] ?? [];
            $stageValue = 0;
            foreach ($stageOpportunities as $opp) {
                $stageValue += (float) ($opp['value'] ?? 0);
            }
            $stages[$stageKey] = [
                'label' => $label,
                'opportunities' => $stageOpportunities,
                'total_value' => $stageValue,
            ];
        }

        return [
            '#theme' => 'crm_pipeline',
            '#stages' => $stages,
            '#total_value' => $this->opportunityService->getPipelineValue($tenantId),
            '#cache' => [
                'max-age' => 0,
            ],
        ];
    }

}
