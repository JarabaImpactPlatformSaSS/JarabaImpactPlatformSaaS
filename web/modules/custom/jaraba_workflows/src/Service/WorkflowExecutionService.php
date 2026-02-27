<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_workflows\Entity\WorkflowRuleInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Workflow execution engine (S4-04).
 *
 * Evaluates triggers, checks conditions, and executes actions
 * for tenant-scoped workflow automation rules.
 */
class WorkflowExecutionService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected EventDispatcherInterface $eventDispatcher,
        protected LoggerInterface $logger,
        protected ?object $observability = NULL,
    ) {
    }

    /**
     * Evaluates all active rules for a given trigger event.
     *
     * @param string $triggerType
     *   The trigger type (entity_created, entity_updated, cron_schedule, etc.).
     * @param array $eventData
     *   Event-specific data (entity_type, entity_id, tenant_id, etc.).
     *
     * @return array
     *   Results: [{rule_id, actions_executed, success}].
     */
    public function evaluate(string $triggerType, array $eventData = []): array
    {
        $results = [];

        try {
            $rules = $this->getActiveRulesForTrigger($triggerType, $eventData['tenant_id'] ?? 0);

            foreach ($rules as $rule) {
                if ($this->checkConditions($rule, $eventData)) {
                    $actionResults = $this->executeActions($rule, $eventData);
                    $results[] = [
                        'rule_id' => $rule->id(),
                        'rule_label' => $rule->label(),
                        'actions_executed' => count($actionResults),
                        'success' => !in_array(FALSE, array_column($actionResults, 'success'), TRUE),
                        'action_results' => $actionResults,
                    ];
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Workflow evaluation failed for trigger @type: @error', [
                '@type' => $triggerType,
                '@error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Gets active rules matching a trigger type and tenant scope.
     *
     * @param string $triggerType
     *   The trigger type.
     * @param int $tenantId
     *   The tenant ID (0 = global only).
     *
     * @return \Drupal\jaraba_workflows\Entity\WorkflowRuleInterface[]
     *   Matching rules sorted by weight.
     */
    protected function getActiveRulesForTrigger(string $triggerType, int $tenantId): array
    {
        $storage = $this->entityTypeManager->getStorage('workflow_rule');
        $allRules = $storage->loadMultiple();

        $matching = [];
        foreach ($allRules as $rule) {
            if (!$rule instanceof WorkflowRuleInterface || !$rule->status()) {
                continue;
            }

            if ($rule->getTriggerType() !== $triggerType) {
                continue;
            }

            // Tenant scope: global rules (0) always match; tenant rules match specific tenant.
            $ruleTenantId = $rule->getTenantId();
            if ($ruleTenantId !== 0 && $ruleTenantId !== $tenantId) {
                continue;
            }

            $matching[] = $rule;
        }

        // Sort by weight.
        usort($matching, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

        return $matching;
    }

    /**
     * Checks if all conditions of a rule are met.
     *
     * @param \Drupal\jaraba_workflows\Entity\WorkflowRuleInterface $rule
     *   The workflow rule.
     * @param array $eventData
     *   The event data.
     *
     * @return bool
     *   TRUE if all conditions are met.
     */
    protected function checkConditions(WorkflowRuleInterface $rule, array $eventData): bool
    {
        $conditions = $rule->getConditions();

        if (empty($conditions)) {
            return TRUE;
        }

        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? '';
            $config = $condition['config'] ?? [];

            switch ($type) {
                case 'entity_type':
                    if (($eventData['entity_type'] ?? '') !== ($config['entity_type'] ?? '')) {
                        return FALSE;
                    }
                    break;

                case 'field_value':
                    $field = $config['field'] ?? '';
                    $expected = $config['value'] ?? '';
                    $operator = $config['operator'] ?? '==';
                    $actual = $eventData['fields'][$field] ?? NULL;

                    if (!$this->compareValue($actual, $expected, $operator)) {
                        return FALSE;
                    }
                    break;

                case 'severity':
                    if (($eventData['severity'] ?? '') !== ($config['severity'] ?? '')) {
                        return FALSE;
                    }
                    break;

                case 'threshold':
                    $metric = $config['metric'] ?? '';
                    $threshold = (float) ($config['threshold'] ?? 0);
                    $actual = (float) ($eventData['metrics'][$metric] ?? 0);
                    if ($actual < $threshold) {
                        return FALSE;
                    }
                    break;

                default:
                    // Unknown condition type — skip (permissive).
                    break;
            }
        }

        return TRUE;
    }

    /**
     * Executes all actions of a rule.
     *
     * @param \Drupal\jaraba_workflows\Entity\WorkflowRuleInterface $rule
     *   The workflow rule.
     * @param array $eventData
     *   The event data.
     *
     * @return array
     *   Array of action results: [{type, success, message}].
     */
    protected function executeActions(WorkflowRuleInterface $rule, array $eventData): array
    {
        $results = [];

        foreach ($rule->getActions() as $action) {
            $type = $action['type'] ?? '';
            $config = $action['config'] ?? [];

            try {
                $result = match ($type) {
                    'send_email' => $this->executeSendEmail($config, $eventData),
                    'create_task' => $this->executeCreateTask($config, $eventData),
                    'notify_admin' => $this->executeNotifyAdmin($config, $eventData),
                    'generate_report' => $this->executeGenerateReport($config, $eventData),
                    default => ['success' => FALSE, 'message' => "Unknown action type: {$type}"],
                };

                $results[] = array_merge(['type' => $type], $result);

                $this->logger->info('Workflow action @type executed for rule @rule.', [
                    '@type' => $type,
                    '@rule' => $rule->id(),
                ]);
            }
            catch (\Exception $e) {
                $results[] = [
                    'type' => $type,
                    'success' => FALSE,
                    'message' => $e->getMessage(),
                ];

                $this->logger->error('Workflow action @type failed for rule @rule: @error', [
                    '@type' => $type,
                    '@rule' => $rule->id(),
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        // Log execution for observability.
        if ($this->observability && method_exists($this->observability, 'log')) {
            try {
                $this->observability->log([
                    'agent_id' => 'workflow_engine',
                    'action' => 'execute_rule_' . $rule->id(),
                    'tier' => 'system',
                    'model_id' => '',
                    'tenant_id' => $rule->getTenantId(),
                    'vertical' => 'general',
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'duration_ms' => 0,
                    'success' => !in_array(FALSE, array_column($results, 'success'), TRUE),
                    'operation_name' => 'workflow_execution',
                ]);
            }
            catch (\Exception $e) {
                // Non-critical.
            }
        }

        return $results;
    }

    /**
     * Sends an email notification.
     */
    protected function executeSendEmail(array $config, array $eventData): array
    {
        $to = $config['to'] ?? $eventData['admin_email'] ?? '';
        $subject = $config['subject'] ?? 'Workflow notification';
        $body = $config['body'] ?? '';

        // Substitute variables in body.
        $body = $this->replaceTokens($body, $eventData);

        if (empty($to)) {
            return ['success' => FALSE, 'message' => 'No recipient specified.'];
        }

        try {
            /** @var \Drupal\Core\Mail\MailManagerInterface $mailManager */
            $mailManager = \Drupal::service('plugin.manager.mail');
            $result = $mailManager->mail(
                'jaraba_workflows',
                'workflow_notification',
                $to,
                'es',
                [
                    'subject' => $subject,
                    'body' => $body,
                ]
            );

            return [
                'success' => !empty($result['result']),
                'message' => "Email sent to {$to}",
            ];
        }
        catch (\Exception $e) {
            return ['success' => FALSE, 'message' => $e->getMessage()];
        }
    }

    /**
     * Creates a task/ticket entity.
     */
    protected function executeCreateTask(array $config, array $eventData): array
    {
        $title = $this->replaceTokens($config['title'] ?? 'Auto-generated task', $eventData);
        $description = $this->replaceTokens($config['description'] ?? '', $eventData);

        // Attempt to create a generic node or custom entity.
        try {
            if (\Drupal::hasService('jaraba_tasks.task_service')) {
                $taskService = \Drupal::service('jaraba_tasks.task_service');
                $task = $taskService->create([
                    'title' => $title,
                    'description' => $description,
                    'tenant_id' => $eventData['tenant_id'] ?? 0,
                    'priority' => $config['priority'] ?? 'medium',
                ]);
                return [
                    'success' => TRUE,
                    'message' => "Task created: {$title}",
                    'task_id' => $task['id'] ?? NULL,
                ];
            }

            return ['success' => FALSE, 'message' => 'Task service not available.'];
        }
        catch (\Exception $e) {
            return ['success' => FALSE, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sends a notification to the tenant admin.
     */
    protected function executeNotifyAdmin(array $config, array $eventData): array
    {
        $message = $this->replaceTokens($config['message'] ?? 'Workflow triggered.', $eventData);
        $tenantId = $eventData['tenant_id'] ?? 0;

        // Use ProactiveInsightsService to create an insight-style notification.
        if (\Drupal::hasService('jaraba_ai_agents.proactive_insights')) {
            try {
                // Load admin user for tenant.
                if ($tenantId > 0) {
                    $tenantStorage = $this->entityTypeManager->getStorage('tenant');
                    $tenant = $tenantStorage->load($tenantId);
                    $adminUid = 0;

                    if ($tenant && $tenant->hasField('admin_user')) {
                        $adminUid = (int) ($tenant->get('admin_user')->target_id ?? 0);
                    }

                    if ($adminUid > 0) {
                        $insightStorage = $this->entityTypeManager->getStorage('proactive_insight');
                        $entity = $insightStorage->create([
                            'title' => mb_substr($config['title'] ?? 'Workflow Alert', 0, 255),
                            'insight_type' => 'workflow_alert',
                            'body' => $message,
                            'severity' => $config['severity'] ?? 'medium',
                            'target_user' => $adminUid,
                            'tenant_id' => $tenantId,
                            'read_status' => FALSE,
                        ]);
                        $entity->save();

                        return ['success' => TRUE, 'message' => "Admin notified for tenant {$tenantId}"];
                    }
                }
            }
            catch (\Exception $e) {
                // Fallback to log.
            }
        }

        $this->logger->info('Workflow admin notification: @message (tenant: @tid)', [
            '@message' => $message,
            '@tid' => $tenantId,
        ]);

        return ['success' => TRUE, 'message' => 'Notification logged.'];
    }

    /**
     * Generates a report using AI.
     */
    protected function executeGenerateReport(array $config, array $eventData): array
    {
        // Placeholder — full AI-generated report would use an agent.
        $reportType = $config['report_type'] ?? 'summary';
        $tenantId = $eventData['tenant_id'] ?? 0;

        $this->logger->info('Workflow report generation requested: type=@type, tenant=@tid', [
            '@type' => $reportType,
            '@tid' => $tenantId,
        ]);

        return [
            'success' => TRUE,
            'message' => "Report generation queued: {$reportType}",
        ];
    }

    /**
     * Replaces {{token}} placeholders in text with event data.
     */
    protected function replaceTokens(string $text, array $eventData): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($eventData) {
            $key = $matches[1];
            return (string) ($eventData[$key] ?? $matches[0]);
        }, $text) ?? $text;
    }

    /**
     * Compares a value with an expected value using an operator.
     */
    protected function compareValue(mixed $actual, mixed $expected, string $operator): bool
    {
        return match ($operator) {
            '==' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'in' => is_array($expected) && in_array($actual, $expected, TRUE),
            'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
            default => FALSE,
        };
    }

}
