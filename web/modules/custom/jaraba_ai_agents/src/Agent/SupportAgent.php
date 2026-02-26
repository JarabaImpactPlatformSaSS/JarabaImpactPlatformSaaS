<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * Agente de Soporte al Cliente.
 *
 * Especializado en gestionar soporte técnico y de ayuda:
 * respuestas a FAQs, tickets de soporte y artículos del centro de ayuda.
 *
 * FIX-035: Migrated Gen 1 -> Gen 2. Now extends SmartBaseAgent with
 * model routing, tool use, provider fallback, and context window management.
 */
class SupportAgent extends SmartBaseAgent
{

    /**
     * Constructs a SupportAgent.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ModelRouterService $modelRouter,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'support';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Agente de Soporte';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Gestiona soporte al cliente: respuestas a FAQs, tickets de soporte y documentación de ayuda.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'faq_answer' => [
                'label' => 'Respuesta FAQ',
                'description' => 'Genera respuestas para preguntas frecuentes.',
                'requires' => ['question'],
                'optional' => ['context', 'related_docs'],
            ],
            'ticket_response' => [
                'label' => 'Respuesta a Ticket',
                'description' => 'Crea respuestas para tickets de soporte.',
                'requires' => ['ticket_content', 'category'],
                'optional' => ['priority', 'previous_interactions'],
            ],
            'help_article' => [
                'label' => 'Artículo de Ayuda',
                'description' => 'Genera artículos para el centro de ayuda.',
                'requires' => ['topic', 'steps'],
                'optional' => ['screenshots_locations', 'common_errors'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        return match ($action) {
            'faq_answer' => $this->generateFaqAnswer($context),
            'ticket_response' => $this->generateTicketResponse($context),
            'help_article' => $this->generateHelpArticle($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Genera una respuesta FAQ.
     */
    protected function generateFaqAnswer(array $context): array
    {
        $question = $context['question'] ?? '';
        $additionalContext = $context['context'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear respuesta para FAQ.

PREGUNTA: {$question}
CONTEXTO ADICIONAL: {$additionalContext}

REQUISITOS:
- Respuesta clara y concisa
- Estructura escaneable
- Incluir pasos si es procedimiento
- Tono amigable pero profesional

FORMATO DE RESPUESTA (JSON):
{
  "question": "Pregunta reformulada si es necesario",
  "short_answer": "Respuesta breve (1-2 oraciones)",
  "detailed_answer": "Respuesta detallada",
  "steps": ["Paso 1", "Paso 2"],
  "related_topics": ["Tema relacionado 1", "Tema relacionado 2"],
  "still_need_help": "Texto de ayuda adicional"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'faq_answer';
            }
        }

        return $response;
    }

    /**
     * Genera una respuesta a ticket de soporte.
     */
    protected function generateTicketResponse(array $context): array
    {
        $ticketContent = $context['ticket_content'] ?? '';
        $category = $context['category'] ?? 'general';
        $priority = $context['priority'] ?? 'normal';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Responder ticket de soporte.

CONTENIDO DEL TICKET: {$ticketContent}
CATEGORÍA: {$category}
PRIORIDAD: {$priority}

REQUISITOS:
- Reconocer el problema del cliente
- Proporcionar solución o pasos a seguir
- Ofrecer alternativas si es necesario
- Indicar tiempos de respuesta si aplica

FORMATO DE RESPUESTA (JSON):
{
  "greeting": "Saludo personalizado",
  "acknowledgment": "Reconocimiento del problema",
  "solution": "Solución o pasos a seguir",
  "alternatives": ["Alternativa 1", "Alternativa 2"],
  "next_steps": "Siguientes pasos",
  "closing": "Cierre",
  "estimated_resolution": "Tiempo estimado si aplica"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'ticket_response';
            }
        }

        return $response;
    }

    /**
     * Genera un artículo de ayuda.
     */
    protected function generateHelpArticle(array $context): array
    {
        $topic = $context['topic'] ?? '';
        $steps = $context['steps'] ?? '';
        $commonErrors = $context['common_errors'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear artículo de ayuda.

TEMA: {$topic}
PASOS DEL PROCESO: {$steps}
ERRORES COMUNES: {$commonErrors}

REQUISITOS:
- Título claro y descriptivo
- Estructura con encabezados
- Pasos numerados
- Sección de resolución de problemas
- SEO optimizado

FORMATO DE RESPUESTA (JSON):
{
  "title": "Título del artículo",
  "meta_description": "Descripción SEO",
  "intro": "Introducción breve",
  "prerequisites": ["Requisito 1", "Requisito 2"],
  "steps": [
    {"title": "Paso 1", "content": "Descripción", "tip": "Consejo opcional"}
  ],
  "troubleshooting": [
    {"problem": "Problema", "solution": "Solución"}
  ],
  "related_articles": ["Artículo 1", "Artículo 2"]
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'help_article';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return <<<EOT
Eres un agente de soporte experto, paciente y orientado a soluciones.

ESTILO:
- Claro y directo
- Paciente y comprensivo
- Técnicamente preciso
- Accesible para todos los niveles

PRINCIPIOS:
- Resolver en el primer contacto
- Anticipar preguntas de seguimiento
- Proporcionar recursos adicionales
- Mantener tono positivo
EOT;
    }

}
