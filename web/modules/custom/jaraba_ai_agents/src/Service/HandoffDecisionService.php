<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Psr\Log\LoggerInterface;

/**
 * Handoff Decision Service (FIX-040).
 *
 * Uses LLM (tier fast) to classify if a message should be handed off
 * to another agent. Connects to the existing HandoffManagerService
 * and AgentCollaborationService.
 */
class HandoffDecisionService
{

    /**
     * Agent capability descriptions for the classifier.
     *
     * @var array<string, string>
     */
    protected const AGENT_CAPABILITIES = [
        'smart_marketing' => 'Marketing digital: posts, emails, ads, campañas',
        'storytelling' => 'Narrativas de marca, historias de producto, páginas About',
        'customer_experience' => 'Post-venta: reseñas, seguimiento, quejas',
        'support' => 'Soporte técnico: FAQs, tickets, artículos de ayuda',
        'producer_copilot' => 'AgroConecta: producción, cultivos, trazabilidad',
        'sales' => 'Ventas B2B: propuestas, negociación, pipeline',
        'merchant_copilot' => 'ComercioConecta: ofertas, inventario, analytics de comercio',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected object $aiProvider,
        protected ModelRouterService $modelRouter,
        protected ?object $handoffManager = NULL,
        protected ?object $agentCollaboration = NULL,
        protected ?LoggerInterface $logger = NULL,
    ) {
    }

    /**
     * Determines if a message should be handed off to another agent.
     *
     * @param string $currentAgentId
     *   The current agent ID.
     * @param string $message
     *   The user message.
     * @param array $context
     *   Execution context.
     *
     * @return array
     *   Result with: should_handoff, target_agent_id, confidence, reason.
     */
    public function shouldHandoff(string $currentAgentId, string $message, array $context = []): array
    {
        // Build classification prompt.
        $capabilities = '';
        foreach (self::AGENT_CAPABILITIES as $id => $desc) {
            if ($id !== $currentAgentId) {
                $capabilities .= "- {$id}: {$desc}\n";
            }
        }

        $prompt = <<<EOT
Eres un clasificador de mensajes. Determina si el siguiente mensaje deberia ser redirigido a otro agente especializado.

Agente actual: {$currentAgentId}
Mensaje del usuario: "{$message}"

Agentes disponibles para handoff:
{$capabilities}

Responde en JSON:
{"should_handoff": true/false, "target_agent_id": "agent_id o null", "confidence": 0.0-1.0, "reason": "explicacion breve"}

REGLAS:
- Solo recomendar handoff si la confianza es > 0.7
- Si el mensaje es ambiguo, no hacer handoff (should_handoff: false)
- El agente actual puede manejar tareas generales de su dominio
EOT;

        try {
            // Route to fast tier for classification.
            $routing = $this->modelRouter->route('classification', $prompt, ['force_tier' => 'fast']);
            $provider = $this->aiProvider->createInstance($routing['provider_id']);

            $chatInput = new ChatInput([
                new ChatMessage('system', 'You are a message classifier. Respond only in JSON.'),
                new ChatMessage('user', $prompt),
            ]);

            $response = $provider->chat($chatInput, $routing['model_id'], ['temperature' => 0.1]);
            $text = $response->getNormalized()->getText();

            // Parse JSON response.
            $cleaned = preg_replace('/```(?:json)?\s*/is', '', $text);
            $cleaned = preg_replace('/\s*```/is', '', $cleaned);

            if (preg_match('/(\{[\s\S]*\})/m', $cleaned, $matches)) {
                $decoded = json_decode($matches[1], TRUE);
                if ($decoded) {
                    $result = [
                        'should_handoff' => (bool) ($decoded['should_handoff'] ?? FALSE),
                        'target_agent_id' => $decoded['target_agent_id'] ?? NULL,
                        'confidence' => (float) ($decoded['confidence'] ?? 0),
                        'reason' => $decoded['reason'] ?? '',
                    ];

                    // Execute handoff if recommended and services available.
                    if ($result['should_handoff'] && $result['confidence'] > 0.7 && $result['target_agent_id']) {
                        $this->executeHandoff($currentAgentId, $result['target_agent_id'], $message, $context);
                    }

                    return $result;
                }
            }

        } catch (\Exception $e) {
            $this->logger?->warning('Handoff decision failed: @msg', ['@msg' => $e->getMessage()]);
        }

        // Default: no handoff.
        return [
            'should_handoff' => FALSE,
            'target_agent_id' => NULL,
            'confidence' => 0,
            'reason' => 'Classification failed or unavailable.',
        ];
    }

    /**
     * Executes the actual handoff via existing services.
     */
    protected function executeHandoff(string $fromAgent, string $toAgent, string $message, array $context): void
    {
        try {
            if ($this->agentCollaboration && method_exists($this->agentCollaboration, 'handoff')) {
                $this->agentCollaboration->handoff($fromAgent, $toAgent, $message, $context);
                $this->logger?->info('Handoff executed: @from -> @to', ['@from' => $fromAgent, '@to' => $toAgent]);
            }
            elseif ($this->handoffManager && method_exists($this->handoffManager, 'handoff')) {
                $this->handoffManager->handoff($fromAgent, $toAgent, $message, $context);
            }
        } catch (\Exception $e) {
            $this->logger?->error('Handoff execution failed: @msg', ['@msg' => $e->getMessage()]);
        }
    }

}
