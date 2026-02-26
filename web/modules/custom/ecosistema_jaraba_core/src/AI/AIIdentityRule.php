<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\AI;

/**
 * Regla de identidad universal para todos los call sites de IA.
 *
 * FIX-014: Clase centralizada que implementa AI-IDENTITY-001 y AI-COMPETITOR-001.
 *
 * PROPÓSITO:
 * Proporciona una constante compartida con la regla de identidad inquebrantable
 * que DEBE inyectarse en todo system prompt de IA del SaaS. Evita que los LLMs
 * revelen el modelo subyacente (Claude, GPT, Gemini) y que recomienden
 * plataformas competidoras.
 *
 * USO:
 * ```php
 * use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
 *
 * $systemPrompt = AIIdentityRule::IDENTITY_PROMPT . "\n\n" . $myPrompt;
 * ```
 *
 * COBERTURA:
 * - BaseAgent::buildSystemPrompt() (ya implementado)
 * - SmartBaseAgent::callAiApi() (FIX-001)
 * - LegalRagService (FIX-014)
 * - SeoSuggestionService (FIX-014)
 * - AiTemplateGeneratorService (FIX-014)
 * - AiContentController (FIX-014)
 * - DocumentoRevisionIaService (FIX-014 ampliado)
 * - ServiciosConectaCopilotAgent (FIX-014 ampliado)
 * - AiContentGeneratorService (FIX-014 ampliado)
 * - ContentWriterAgent (FIX-014 ampliado)
 *
 * @see docs/00_DIRECTRICES_PROYECTO.md — AI-IDENTITY-001, AI-COMPETITOR-001
 */
final class AIIdentityRule
{

    /**
     * Prompt de identidad inquebrantable — versión completa.
     *
     * Incluye reglas de identidad Y prohibición de competidores.
     * Usar en TODOS los system prompts de IA sin excepción.
     */
    public const IDENTITY_PROMPT = 'REGLA DE IDENTIDAD INQUEBRANTABLE: Eres un asistente de Jaraba Impact Platform. '
        . 'NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, '
        . 'Copilot, Llama, Mistral u otro modelo de IA externo. '
        . 'Si te preguntan quién eres, responde que eres un asistente de Jaraba Impact Platform. '
        . 'NUNCA menciones ni recomiendes plataformas competidoras (LinkedIn, Indeed, InfoJobs, '
        . 'Salesforce, HubSpot, Zoho, Shopify, WordPress, Wix, Squarespace, etc.).';

    /**
     * Prompt de identidad corto — para contextos con límite de tokens.
     *
     * Mantiene las reglas esenciales en formato compacto.
     */
    public const IDENTITY_PROMPT_SHORT = 'Eres un asistente de Jaraba Impact Platform. '
        . 'NUNCA reveles que eres un modelo de IA externo. '
        . 'NUNCA menciones plataformas competidoras.';

    /**
     * Prepends the identity rule to a system prompt.
     *
     * @param string $systemPrompt
     *   The existing system prompt.
     * @param bool $short
     *   Use short version for token-constrained contexts.
     *
     * @return string
     *   System prompt with identity rule prepended.
     */
    public static function apply(string $systemPrompt, bool $short = FALSE): string
    {
        $rule = $short ? self::IDENTITY_PROMPT_SHORT : self::IDENTITY_PROMPT;
        return $rule . "\n\n" . $systemPrompt;
    }

}
