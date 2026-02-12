<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generación de templates con IA (Sprint C4.2).
 *
 * Genera landing pages completas a partir de un prompt de texto,
 * adaptadas a la vertical y tono de marca del tenant.
 *
 * PATRÓN:
 * - Usa `\Drupal::service('ai.provider')` para LLM.
 * - ContentGroundingService para enriquecer con contenido real.
 * - TenantBrandVoiceService para adaptar tono por vertical.
 * - Log via CopilotQueryLoggerService.
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §11
 */
class AiTemplateGeneratorService
{

    /**
     * Logger del servicio.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   Factoría de loggers.
     */
    public function __construct(
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_page_builder.ai_template');
    }

    /**
     * Genera un template completo de landing page con IA.
     *
     * @param string $prompt
     *   Instrucciones del usuario sobre qué generar.
     * @param string $vertical
     *   Vertical de la plataforma (empleabilidad, emprendimiento, etc.).
     * @param string $tone
     *   Tono deseado (formal, cercano, tecnico, inspirador).
     * @param array $sections
     *   Secciones a generar (héroe, features, pricing, cta, etc.).
     *
     * @return array
     *   Array con:
     *   - html: string HTML del template generado.
     *   - css: string CSS del template.
     *   - sections: array de secciones individuales.
     *   - provider: string nombre del proveedor usado.
     */
    public function generateTemplate(
        string $prompt,
        string $vertical = 'generica',
        string $tone = 'profesional',
        array $sections = ['hero', 'features', 'cta'],
    ): array {
        try {
            return $this->generateWithAI($prompt, $vertical, $tone, $sections);
        } catch (\Exception $e) {
            $this->logger->warning(
                'Generación de template con IA falló: @error',
                ['@error' => $e->getMessage()]
            );
            return $this->generateFallbackTemplate($prompt, $vertical, $sections);
        }
    }

    /**
     * Genera un template usando el LLM configurado.
     *
     * @param string $prompt
     *   Instrucciones del usuario.
     * @param string $vertical
     *   Vertical activa.
     * @param string $tone
     *   Tono deseado.
     * @param array $sections
     *   Secciones solicitadas.
     *
     * @return array
     *   Template generado.
     */
    protected function generateWithAI(
        string $prompt,
        string $vertical,
        string $tone,
        array $sections,
    ): array {
        /** @var \Drupal\ai\AiProviderPluginManager $aiProvider */
        $aiProvider = \Drupal::service('ai.provider');

        $defaults = $aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
            throw new \RuntimeException('Sin proveedor IA configurado para chat.');
        }

        $provider = $aiProvider->createInstance($defaults['provider_id']);
        $modelId = $defaults['model_id'];

        // Obtener contexto de grounding si está disponible.
        $groundingContext = $this->getGroundingContext($prompt, $vertical);

        // Obtener brand voice si está disponible.
        $brandVoice = $this->getBrandVoice($vertical);

        $fullPrompt = $this->buildTemplatePrompt(
            $prompt,
            $vertical,
            $tone,
            $sections,
            $groundingContext,
            $brandVoice
        );

        $systemPrompt = 'Eres un diseñador web y desarrollador frontend experto. '
            . 'Genera HTML y CSS de alta calidad para landing pages profesionales. '
            . 'Responde SIEMPRE en formato JSON válido con la estructura indicada. '
            . 'El HTML debe ser semántico, responsivo y accesible. '
            . 'Los estilos CSS deben usar clases con prefijo "jaraba-" y variables CSS del sistema.';

        $chatInput = new \Drupal\ai\OperationType\Chat\ChatInput([
            new \Drupal\ai\OperationType\Chat\ChatMessage('system', $systemPrompt),
            new \Drupal\ai\OperationType\Chat\ChatMessage('user', $fullPrompt),
        ]);

        $configuration = ['temperature' => 0.7]; // Creatividad moderada.
        $response = $provider->chat($chatInput, $modelId, $configuration);
        $responseText = $response->getNormalized()->getText();

        // Log.
        $this->logAIQuery('template_generation', $fullPrompt, $responseText);

        return $this->parseTemplateResponse($responseText);
    }

    /**
     * Construye el prompt para generación de template.
     *
     * @param string $prompt
     *   Instrucciones del usuario.
     * @param string $vertical
     *   Vertical activa.
     * @param string $tone
     *   Tono deseado.
     * @param array $sections
     *   Secciones a generar.
     * @param string $groundingContext
     *   Contexto de contenido real.
     * @param string $brandVoice
     *   Directrices de brand voice.
     *
     * @return string
     *   Prompt completo.
     */
    protected function buildTemplatePrompt(
        string $prompt,
        string $vertical,
        string $tone,
        array $sections,
        string $groundingContext,
        string $brandVoice,
    ): string {
        $sectionsStr = implode(', ', $sections);
        $toneLabels = [
            'formal' => 'Formal y profesional',
            'cercano' => 'Cercano y amigable',
            'tecnico' => 'Técnico y preciso',
            'inspirador' => 'Inspirador y motivacional',
            'profesional' => 'Profesional y claro',
        ];
        $toneLabel = $toneLabels[$tone] ?? $toneLabels['profesional'];

        $verticalLabels = [
            'empleabilidad' => 'Empleo e inserción laboral',
            'emprendimiento' => 'Emprendimiento e innovación',
            'agroconecta' => 'Agricultura y comercio rural',
            'formacion' => 'Formación y educación',
            'generica' => 'General / corporativa',
        ];
        $verticalLabel = $verticalLabels[$vertical] ?? $verticalLabels['generica'];

        $result = "Genera una landing page completa para la vertical \"{$verticalLabel}\".\n\n";
        $result .= "INSTRUCCIONES DEL USUARIO:\n{$prompt}\n\n";
        $result .= "TONO: {$toneLabel}\n";
        $result .= "SECCIONES REQUERIDAS: {$sectionsStr}\n\n";

        if ($brandVoice) {
            $result .= "BRAND VOICE:\n{$brandVoice}\n\n";
        }

        if ($groundingContext) {
            $result .= "CONTEXTO REAL DE CONTENIDO (para personalizar):\n{$groundingContext}\n\n";
        }

        $result .= "REGLAS:\n";
        $result .= "- Usa clases CSS con prefijo 'jaraba-' (ej: jaraba-hero, jaraba-features)\n";
        $result .= "- Estructura semántica HTML5 (header, main, section, footer)\n";
        $result .= "- Diseño responsivo (usar flexbox/grid, media queries)\n";
        $result .= "- Accesible (alt text, ARIA labels, contraste)\n";
        $result .= "- Colores corporativos: #233D63 (principal), #00A9A5 (acento), #F5A623 (highlight)\n";
        $result .= "- Sin dependencias externas (no Bootstrap, no jQuery)\n";
        $result .= "- Contenido realista, no lorem ipsum\n\n";

        $result .= "FORMATO DE RESPUESTA (JSON estricto):\n";
        $result .= <<<'JSON'
{
  "html": "<section class='jaraba-hero'>...</section>...",
  "css": ".jaraba-hero { ... }",
  "sections": [
    {
      "id": "hero",
      "html": "<section class='jaraba-hero'>...</section>",
      "label": "Hero Section"
    }
  ]
}
JSON;

        return $result;
    }

    /**
     * Parsea la respuesta del LLM.
     *
     * @param string $responseText
     *   Texto de respuesta.
     *
     * @return array
     *   Template parseado.
     */
    protected function parseTemplateResponse(string $responseText): array
    {
        // Extraer JSON.
        $json = $responseText;
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $responseText, $matches)) {
            $json = $matches[1];
        }

        $parsed = json_decode(trim($json), TRUE);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            $this->logger->warning(
                'No se pudo parsear template IA: @error — raw: @raw',
                [
                    '@error' => json_last_error_msg(),
                    '@raw' => mb_substr($responseText, 0, 500),
                ]
            );
            return [
                'html' => '',
                'css' => '',
                'sections' => [],
                'provider' => 'ai_error',
            ];
        }

        return [
            'html' => $parsed['html'] ?? '',
            'css' => $parsed['css'] ?? '',
            'sections' => $parsed['sections'] ?? [],
            'provider' => 'ai',
        ];
    }

    /**
     * Genera un template placeholder cuando la IA no está disponible.
     *
     * @param string $prompt
     *   Instrucciones del usuario.
     * @param string $vertical
     *   Vertical activa.
     * @param array $sections
     *   Secciones solicitadas.
     *
     * @return array
     *   Template con estructura HTML básica.
     */
    protected function generateFallbackTemplate(
        string $prompt,
        string $vertical,
        array $sections,
    ): array {
        $generatedSections = [];
        $allHtml = '';
        $allCss = '';

        foreach ($sections as $section) {
            $sectionHtml = $this->generateFallbackSection($section, $prompt, $vertical);
            $generatedSections[] = [
                'id' => $section,
                'html' => $sectionHtml,
                'label' => ucfirst($section),
            ];
            $allHtml .= $sectionHtml . "\n";
        }

        $allCss = $this->generateFallbackCss();

        return [
            'html' => $allHtml,
            'css' => $allCss,
            'sections' => $generatedSections,
            'provider' => 'fallback',
        ];
    }

    /**
     * Genera HTML de una sección individual como fallback.
     *
     * @param string $sectionType
     *   Tipo de sección.
     * @param string $prompt
     *   Instrucciones originales.
     * @param string $vertical
     *   Vertical activa.
     *
     * @return string
     *   HTML de la sección.
     */
    protected function generateFallbackSection(
        string $sectionType,
        string $prompt,
        string $vertical,
    ): string {
        $title = $prompt ? mb_substr($prompt, 0, 60) : 'Tu título aquí';

        return match ($sectionType) {
            'hero' => <<<HTML
<section class="jaraba-hero jaraba-section">
  <div class="jaraba-hero__content">
    <h1 class="jaraba-hero__title">{$title}</h1>
    <p class="jaraba-hero__subtitle">Describe tu propuesta de valor aquí.</p>
    <a href="#" class="jaraba-btn jaraba-btn--primary">Comenzar ahora</a>
  </div>
</section>
HTML,
            'features' => <<<HTML
<section class="jaraba-features jaraba-section">
  <h2 class="jaraba-section__title">Características principales</h2>
  <div class="jaraba-features__grid">
    <div class="jaraba-feature-card">
      <h3>Característica 1</h3>
      <p>Descripción de la primera característica.</p>
    </div>
    <div class="jaraba-feature-card">
      <h3>Característica 2</h3>
      <p>Descripción de la segunda característica.</p>
    </div>
    <div class="jaraba-feature-card">
      <h3>Característica 3</h3>
      <p>Descripción de la tercera característica.</p>
    </div>
  </div>
</section>
HTML,
            'pricing' => <<<HTML
<section class="jaraba-pricing jaraba-section">
  <h2 class="jaraba-section__title">Planes y precios</h2>
  <div class="jaraba-pricing__grid">
    <div class="jaraba-pricing-card">
      <h3>Básico</h3>
      <p class="jaraba-pricing-card__price">Gratis</p>
      <p>Para empezar</p>
    </div>
    <div class="jaraba-pricing-card jaraba-pricing-card--featured">
      <h3>Pro</h3>
      <p class="jaraba-pricing-card__price">29€/mes</p>
      <p>Para profesionales</p>
    </div>
  </div>
</section>
HTML,
            'cta' => <<<HTML
<section class="jaraba-cta jaraba-section">
  <h2 class="jaraba-cta__title">¿Listo para empezar?</h2>
  <p class="jaraba-cta__text">Únete a miles de profesionales que ya confían en nosotros.</p>
  <a href="#" class="jaraba-btn jaraba-btn--primary jaraba-btn--lg">Empezar gratis</a>
</section>
HTML,
            'testimonials' => <<<HTML
<section class="jaraba-testimonials jaraba-section">
  <h2 class="jaraba-section__title">Lo que dicen nuestros usuarios</h2>
  <div class="jaraba-testimonials__grid">
    <blockquote class="jaraba-testimonial">
      <p>"Una plataforma excepcional que ha transformado nuestra forma de trabajar."</p>
      <cite>— Usuario satisfecho</cite>
    </blockquote>
  </div>
</section>
HTML,
            default => <<<HTML
<section class="jaraba-generic jaraba-section">
  <h2 class="jaraba-section__title">{$sectionType}</h2>
  <p>Contenido de la sección {$sectionType}.</p>
</section>
HTML,
        };
    }

    /**
     * Genera CSS base para las secciones de fallback.
     *
     * @return string
     *   CSS base.
     */
    protected function generateFallbackCss(): string
    {
        return <<<'CSS'
.jaraba-section {
  padding: 4rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
}
.jaraba-section__title {
  text-align: center;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 2rem;
  color: #233D63;
}
.jaraba-hero {
  text-align: center;
  background: linear-gradient(135deg, #233D63 0%, #00A9A5 100%);
  color: white;
  padding: 6rem 2rem;
  border-radius: 0 0 24px 24px;
}
.jaraba-hero__title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 1rem;
}
.jaraba-hero__subtitle {
  font-size: 1.25rem;
  opacity: 0.9;
  margin-bottom: 2rem;
}
.jaraba-btn {
  display: inline-block;
  padding: 0.75rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}
.jaraba-btn--primary {
  background: #F5A623;
  color: #233D63;
}
.jaraba-btn--primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245,166,35,0.4);
}
.jaraba-btn--lg {
  padding: 1rem 3rem;
  font-size: 1.1rem;
}
.jaraba-features__grid,
.jaraba-pricing__grid,
.jaraba-testimonials__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
}
.jaraba-feature-card,
.jaraba-pricing-card {
  padding: 2rem;
  border-radius: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
.jaraba-pricing-card--featured {
  border-color: #00A9A5;
  box-shadow: 0 8px 24px rgba(0,169,165,0.15);
}
.jaraba-pricing-card__price {
  font-size: 2rem;
  font-weight: 800;
  color: #00A9A5;
}
.jaraba-cta {
  text-align: center;
  background: #f0f9ff;
  border-radius: 24px;
}
.jaraba-cta__title {
  font-size: 2rem;
  font-weight: 700;
  color: #233D63;
}
.jaraba-testimonial {
  padding: 2rem;
  border-radius: 16px;
  background: white;
  border-left: 4px solid #00A9A5;
}
CSS;
    }

    /**
     * Obtiene contexto de grounding desde ContentGroundingService.
     *
     * @param string $prompt
     *   Prompt del usuario.
     * @param string $vertical
     *   Vertical activa.
     *
     * @return string
     *   Contexto de contenido real.
     */
    protected function getGroundingContext(string $prompt, string $vertical): string
    {
        try {
            if (\Drupal::hasService('jaraba_copilot_v2.content_grounding')) {
                /** @var \Drupal\jaraba_copilot_v2\Service\ContentGroundingService $grounding */
                $grounding = \Drupal::service('jaraba_copilot_v2.content_grounding');
                return $grounding->getContentContext($prompt, $vertical);
            }
        } catch (\Exception $e) {
            // Grounding no disponible, no es crítico.
        }
        return '';
    }

    /**
     * Obtiene las directrices de Brand Voice del tenant.
     *
     * @param string $vertical
     *   Vertical activa.
     *
     * @return string
     *   Directrices de brand voice.
     */
    protected function getBrandVoice(string $vertical): string
    {
        try {
            if (\Drupal::hasService('jaraba_ai_agents.brand_voice')) {
                /** @var \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService $brandVoice */
                $brandVoice = \Drupal::service('jaraba_ai_agents.brand_voice');
                return $brandVoice->getBrandVoice($vertical);
            }
        } catch (\Exception $e) {
            // Brand voice no disponible, no es crítico.
        }
        return '';
    }

    /**
     * Registra una query IA en el log del copilot.
     *
     * @param string $type
     *   Tipo de query.
     * @param string $prompt
     *   Prompt enviado.
     * @param string $response
     *   Respuesta recibida.
     */
    protected function logAIQuery(string $type, string $prompt, string $response): void
    {
        try {
            if (\Drupal::hasService('jaraba_copilot_v2.query_logger')) {
                /** @var \Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService $logger */
                $logger = \Drupal::service('jaraba_copilot_v2.query_logger');
                $logger->logQuery($type, $prompt, $response, [
                    'source' => 'page_builder_template_gen',
                ]);
            }
        } catch (\Exception $e) {
            // No crítico.
        }
    }

}
