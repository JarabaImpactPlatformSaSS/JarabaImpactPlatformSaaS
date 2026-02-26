<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Psr\Log\LoggerInterface;

/**
 * Smart Marketing Agent with Model Routing.
 *
 * Uses intelligent model selection for cost optimization.
 *
 * FIX-008: Constructor alineado con BaseAgent (6 args) + ModelRouterService.
 * services.yml inyecta 7 argumentos: aiProvider, configFactory, logger,
 * brandVoice, observability, modelRouter, promptBuilder.
 */
class SmartMarketingAgent extends SmartBaseAgent
{

    /**
     * Constructs a SmartMarketingAgent.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ModelRouterService $modelRouter,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'smart_marketing';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Smart Marketing Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Marketing con routing inteligente: usa modelos económicos para tareas simples, premium para complejas.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'social_post' => [
                'label' => 'Crear Post para Redes Sociales',
                'description' => 'Genera contenido optimizado para redes sociales.',
                'requires' => ['product_name', 'platform', 'objective'],
                'complexity' => 'low',
            ],
            'email_promo' => [
                'label' => 'Email de Marketing',
                'description' => 'Crea emails promocionales efectivos.',
                'requires' => ['product_name', 'objective', 'offer_details'],
                'complexity' => 'medium',
            ],
            'ad_copy' => [
                'label' => 'Copy para Anuncios',
                'description' => 'Genera textos para campañas publicitarias.',
                'requires' => ['product_name', 'platform', 'audience'],
                'complexity' => 'medium',
            ],
            'campaign_strategy' => [
                'label' => 'Estrategia de Campaña',
                'description' => 'Planifica campañas de marketing completas.',
                'requires' => ['brand_name', 'goals', 'budget', 'timeline'],
                'complexity' => 'high',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        return match ($action) {
            'social_post' => $this->generateSocialPost($context),
            'email_promo' => $this->generateEmailPromo($context),
            'ad_copy' => $this->generateAdCopy($context),
            'campaign_strategy' => $this->generateCampaignStrategy($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Generates a social media post (complexity: low → fast tier).
     */
    protected function generateSocialPost(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $platform = $context['platform'] ?? 'Instagram';
        $objective = $context['objective'] ?? 'Engagement';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Crear un post para {$platform}.
PRODUCTO: {$product}
OBJETIVO: {$objective}

FORMATO JSON:
{"content": "...", "hashtags": "...", "cta": "...", "visual_suggestion": "..."}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'social_post';
                $response['data']['platform'] = $platform;
            }
        }

        return $response;
    }

    /**
     * Generates a promotional email (complexity: medium → balanced tier).
     */
    protected function generateEmailPromo(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $objective = $context['objective'] ?? 'Ventas';
        $offerDetails = $context['offer_details'] ?? '';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Crear un email promocional efectivo.
PRODUCTO: {$product}
OBJETIVO: {$objective}
OFERTA: {$offerDetails}

FORMATO JSON:
{
  "subject": "...",
  "preview_text": "...",
  "body": "...",
  "cta_text": "...",
  "ps_line": "..."
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'email_promo';
            }
        }

        return $response;
    }

    /**
     * Generates ad copy (complexity: medium → balanced tier).
     */
    protected function generateAdCopy(array $context): array
    {
        $product = $context['product_name'] ?? 'producto';
        $platform = $context['platform'] ?? 'Meta Ads';
        $audience = $context['audience'] ?? 'público general';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Crear copy para anuncio en {$platform}.
PRODUCTO: {$product}
AUDIENCIA: {$audience}

FORMATO JSON:
{
  "headlines": ["...", "..."],
  "descriptions": ["...", "..."],
  "primary_text": "...",
  "cta_button": "..."
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'ad_copy';
            }
        }

        return $response;
    }

    /**
     * Generates campaign strategy (complexity: high → premium tier).
     */
    protected function generateCampaignStrategy(array $context): array
    {
        $brand = $context['brand_name'] ?? 'marca';
        $goals = $context['goals'] ?? '';
        $budget = $context['budget'] ?? '';
        $timeline = $context['timeline'] ?? '';

        $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Crear estrategia de campaña de marketing completa.
MARCA: {$brand}
OBJETIVOS: {$goals}
PRESUPUESTO: {$budget}
TIMELINE: {$timeline}

Analiza profundamente y proporciona una estrategia integral.

FORMATO JSON:
{
  "executive_summary": "...",
  "target_audience": {"primary": "...", "secondary": "..."},
  "channels": [{"channel": "...", "budget_allocation": "...", "kpis": [...]}],
  "content_calendar": [{"week": 1, "actions": [...]}],
  "success_metrics": [...],
  "risks_and_mitigation": [...]
}
EOT;

        // Force premium tier for complex strategy.
        $response = $this->callAiApi($prompt, ['require_quality' => TRUE]);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'campaign_strategy';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return "Eres un experto en marketing digital. Tono profesional pero cercano. Orientado a resultados.";
    }

}
