<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * CRM Copilot Bridge — contextualiza el copilot con datos CRM del tenant.
 */
class CrmCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected OpportunityService $opportunityService,
    protected ContactService $contactService,
    protected CrmForecastingService $forecastingService,
    protected SalesPlaybookService $salesPlaybook,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'crm',
      'has_crm_data' => FALSE,
      'pipeline_summary' => [],
      'top_opportunities' => [],
      'recent_activities' => [],
      'forecast_snapshot' => [],
    ];

    try {
      $tenantId = $this->resolveTenantId($userId);
      if ($tenantId === 0) {
        return $context;
      }

      // Pipeline summary.
      $forecast = $this->forecastingService->getForecast($tenantId);
      $context['forecast_snapshot'] = [
        'total_pipeline' => $forecast['total_pipeline'] ?? 0,
        'weighted_pipeline' => $forecast['weighted_pipeline'] ?? 0,
        'win_rate' => $this->forecastingService->getWinRate($tenantId),
        'avg_deal_size' => $this->forecastingService->getAvgDealSize($tenantId),
        'avg_sales_cycle_days' => $this->forecastingService->getSalesCycleAvg($tenantId),
      ];

      // Top opportunities closing soon.
      $closingSoon = $this->opportunityService->getClosingSoon(30, $tenantId);
      $topOpps = [];
      foreach (array_slice($closingSoon, 0, 5) as $opp) {
        $playbook = $this->salesPlaybook->getNextAction($opp);
        $topOpps[] = [
          'title' => $opp->get('title')->value ?? '',
          'stage' => $opp->getStage(),
          'value' => (float) ($opp->get('value')->value ?? 0),
          'probability' => (int) ($opp->get('probability')->value ?? 50),
          'bant_score' => $opp->getBantScore(),
          'next_action' => $playbook['action'] ?? '',
          'next_action_priority' => $playbook['priority'] ?? 'medium',
        ];
      }
      $context['top_opportunities'] = $topOpps;

      // Recent activities.
      $activities = $this->entityTypeManager
        ->getStorage('crm_activity')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('activity_date', 'DESC')
        ->range(0, 5)
        ->execute();

      if ($activities !== []) {
        $activityEntities = $this->entityTypeManager
          ->getStorage('crm_activity')
          ->loadMultiple($activities);
        foreach ($activityEntities as $act) {
          if (!$act instanceof ContentEntityInterface) {
            continue;
          }
          $context['recent_activities'][] = [
            'type' => $act->get('type')->value ?? '',
            'subject' => $act->get('subject')->value ?? '',
            'date' => $act->get('activity_date')->value ?? '',
          ];
        }
      }

      // Contact count and pipeline count.
      $context['contacts_count'] = $this->contactService->count($tenantId);
      $context['opportunities_count'] = $this->opportunityService->count($tenantId);
      $context['has_crm_data'] = ($context['contacts_count'] > 0 || $context['opportunities_count'] > 0);

      // System prompt addition for CRM context.
      $context['_system_prompt_addition'] = $this->buildSystemPrompt($context);

    }
    catch (\Exception $e) {
      $this->logger->warning('CRM CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $tenantId = $this->resolveTenantId($userId);
      if ($tenantId === 0) {
        return NULL;
      }

      $contactCount = $this->contactService->count($tenantId);
      if ($contactCount === 0) {
        return [
          'message' => 'Aun no tienes contactos en tu CRM. Empieza a registrar tus leads para que el copilot pueda ayudarte con recomendaciones de venta personalizadas.',
          'cta' => [
            'label' => 'Ir al CRM',
            'route' => 'jaraba_crm.dashboard',
          ],
          'trigger' => 'no_crm_contacts',
        ];
      }

      $oppCount = $this->opportunityService->count($tenantId);
      if ($oppCount === 0) {
        return [
          'message' => 'Tienes contactos pero ninguna oportunidad de venta. Crea tu primera oportunidad para activar el pipeline inteligente.',
          'cta' => [
            'label' => 'Crear oportunidad',
            'route' => 'jaraba_crm.pipeline',
          ],
          'trigger' => 'no_opportunities',
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('CRM soft suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Builds CRM-specific system prompt.
   *
   * @param array<string, mixed> $context
   *   The CRM context data.
   */
  protected function buildSystemPrompt(array $context): string {
    $prompt = "Tienes acceso a datos CRM del usuario. ";

    if ($context['forecast_snapshot'] !== []) {
      $f = $context['forecast_snapshot'];
      $prompt .= sprintf(
        "Pipeline: %.0f EUR (ponderado: %.0f EUR). Win rate: %.0f%%. Deal medio: %.0f EUR. Ciclo venta medio: %.0f dias. ",
        $f['total_pipeline'],
        $f['weighted_pipeline'],
        $f['win_rate'],
        $f['avg_deal_size'],
        $f['avg_sales_cycle_days'],
      );
    }

    if ($context['top_opportunities'] !== []) {
      $prompt .= "Oportunidades prioritarias: ";
      foreach ($context['top_opportunities'] as $opp) {
        $prompt .= sprintf(
          "[%s — %s, %.0f EUR, BANT %d/4, accion: %s] ",
          $opp['title'],
          $opp['stage'],
          $opp['value'],
          $opp['bant_score'],
          $opp['next_action'],
        );
      }
    }

    $prompt .= "Usa estos datos para dar recomendaciones de venta contextualizadas. ";
    $prompt .= "Prioriza oportunidades con BANT alto y close date cercano. ";
    $prompt .= "NUNCA inventes datos de contactos o empresas que no existan.";

    return $prompt;
  }

  /**
   * Resolves tenant ID from user.
   */
  protected function resolveTenantId(int $userId): int {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if ($user === NULL) {
        return 0;
      }
      // Resolve via group membership.
      $memberships = $this->entityTypeManager
        ->getStorage('group_content')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_id', $userId)
        ->condition('type', '%group_membership', 'LIKE')
        ->range(0, 1)
        ->execute();

      if ($memberships !== []) {
        $gc = $this->entityTypeManager
          ->getStorage('group_content')
          ->load(reset($memberships));
        if ($gc instanceof GroupRelationshipInterface) {
          return (int) $gc->getGroup()->id();
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Tenant resolution error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return 0;
  }

}
