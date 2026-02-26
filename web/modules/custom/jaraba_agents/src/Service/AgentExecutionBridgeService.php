<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Agent\AgentInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bridge between AutonomousAgent entities and SmartBaseAgent services (FIX-030).
 *
 * Maps AutonomousAgent.agent_type to concrete service IDs in jaraba_ai_agents,
 * resolves the agent, establishes tenant context, and executes the action.
 */
class AgentExecutionBridgeService
{

    /**
     * Default agent type to service ID mapping.
     *
     * @var array<string, string>
     */
    protected const DEFAULT_MAPPING = [
        'marketing' => 'jaraba_ai_agents.smart_marketing_agent',
        'smart_marketing' => 'jaraba_ai_agents.smart_marketing_agent',
        'storytelling' => 'jaraba_ai_agents.storytelling_agent',
        'customer_experience' => 'jaraba_ai_agents.customer_experience_agent',
        'support' => 'jaraba_ai_agents.support_agent',
        'producer_copilot' => 'jaraba_ai_agents.producer_copilot_agent',
        'sales' => 'jaraba_ai_agents.sales_agent',
        'merchant_copilot' => 'jaraba_ai_agents.merchant_copilot_agent',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Executes an agent action by resolving the agent type to a service.
     *
     * @param string $agentType
     *   The AutonomousAgent.agent_type value.
     * @param string $action
     *   The action to execute.
     * @param array $context
     *   Execution context including input_data, tenant_id, vertical.
     *
     * @return array
     *   Execution result with success, data, and error keys.
     */
    public function execute(string $agentType, string $action, array $context): array
    {
        $serviceId = $this->resolveServiceId($agentType);

        if (!$serviceId) {
            $this->logger->error('No service mapping for agent type @type', ['@type' => $agentType]);
            return [
                'success' => FALSE,
                'error' => "No service mapping for agent type: {$agentType}",
            ];
        }

        if (!\Drupal::hasService($serviceId)) {
            $this->logger->error('Service @service not found for agent type @type', [
                '@service' => $serviceId,
                '@type' => $agentType,
            ]);
            return [
                'success' => FALSE,
                'error' => "Service not found: {$serviceId}",
            ];
        }

        try {
            $agent = \Drupal::service($serviceId);

            if (!$agent instanceof AgentInterface) {
                return [
                    'success' => FALSE,
                    'error' => "Service {$serviceId} does not implement AgentInterface.",
                ];
            }

            // Set tenant context if available.
            $tenantId = $context['tenant_id'] ?? NULL;
            $vertical = $context['vertical'] ?? 'general';
            if ($tenantId) {
                $agent->setTenantContext((string) $tenantId, $vertical);
            }

            // Execute.
            $inputData = $context['input_data'] ?? $context;
            $result = $agent->execute($action, $inputData);

            $this->logger->info('Bridge executed @type/@action: success=@success', [
                '@type' => $agentType,
                '@action' => $action,
                '@success' => ($result['success'] ?? FALSE) ? 'true' : 'false',
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Bridge execution failed for @type/@action: @msg', [
                '@type' => $agentType,
                '@action' => $action,
                '@msg' => $e->getMessage(),
            ]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolves agent type to service ID.
     *
     * First checks config YAML, then falls back to default mapping.
     *
     * @param string $agentType
     *   The agent type.
     *
     * @return string|null
     *   Service ID or NULL if not found.
     */
    protected function resolveServiceId(string $agentType): ?string
    {
        // Check config-based mapping first.
        $config = $this->configFactory->get('jaraba_agents.agent_type_mapping');
        $configMapping = $config->get('mapping') ?? [];

        if (isset($configMapping[$agentType])) {
            return $configMapping[$agentType];
        }

        // Fall back to hardcoded defaults.
        return self::DEFAULT_MAPPING[$agentType] ?? NULL;
    }

    /**
     * Gets all available agent type mappings.
     *
     * @return array<string, string>
     *   Map of agent_type => service_id.
     */
    public function getAvailableMappings(): array
    {
        $config = $this->configFactory->get('jaraba_agents.agent_type_mapping');
        $configMapping = $config->get('mapping') ?? [];

        return array_merge(self::DEFAULT_MAPPING, $configMapping);
    }

}
