<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

/**
 * Storytelling Agent for brand narratives.
 *
 * Creates brand stories, product narratives, and about pages.
 */
class StorytellingAgent extends BaseAgent
{

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'storytelling';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Storytelling Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Crea narrativas de marca, historias de producto y contenido sobre la empresa.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'brand_story' => [
                'label' => 'Historia de Marca',
                'description' => 'Genera la narrativa fundacional de la marca.',
                'requires' => ['brand_name', 'founding_context'],
                'optional' => ['values', 'mission', 'vision'],
            ],
            'product_story' => [
                'label' => 'Historia de Producto',
                'description' => 'Crea la narrativa detrás de un producto.',
                'requires' => ['product_name', 'origin'],
                'optional' => ['craftsmanship', 'uniqueness'],
            ],
            'about_page' => [
                'label' => 'Página Sobre Nosotros',
                'description' => 'Genera contenido para página About.',
                'requires' => ['company_name', 'team_info'],
                'optional' => ['milestones', 'culture'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute(string $action, array $context): array
    {
        $this->setCurrentAction($action);

        return match ($action) {
            'brand_story' => $this->generateBrandStory($context),
            'product_story' => $this->generateProductStory($context),
            'about_page' => $this->generateAboutPage($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Generates a brand story.
     */
    protected function generateBrandStory(array $context): array
    {
        $brandName = $context['brand_name'] ?? 'marca';
        $foundingContext = $context['founding_context'] ?? '';
        $values = $context['values'] ?? '';
        $mission = $context['mission'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear la historia de marca para {$brandName}.

CONTEXTO FUNDACIONAL: {$foundingContext}
VALORES: {$values}
MISIÓN: {$mission}

REQUISITOS:
- Narrativa emocional y auténtica
- Conexión con el público objetivo
- Destacar diferenciadores
- Tono inspirador

FORMATO DE RESPUESTA (JSON):
{
  "headline": "Título impactante",
  "tagline": "Lema de marca",
  "short_story": "Historia corta (100 palabras)",
  "full_story": "Historia completa (300-500 palabras)",
  "key_moments": ["Momento 1", "Momento 2"],
  "emotional_hook": "Gancho emocional principal"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'brand_story';
            }
        }

        return $response;
    }

    /**
     * Generates a product story.
     */
    protected function generateProductStory(array $context): array
    {
        $productName = $context['product_name'] ?? 'producto';
        $origin = $context['origin'] ?? '';
        $craftsmanship = $context['craftsmanship'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear la historia del producto {$productName}.

ORIGEN: {$origin}
ARTESANÍA/PROCESO: {$craftsmanship}

REQUISITOS:
- Narrativa que conecte emocionalmente
- Destacar el proceso y cuidado
- Generar valor percibido
- Diferenciación de competencia

FORMATO DE RESPUESTA (JSON):
{
  "title": "Título de la historia",
  "intro": "Párrafo introductorio",
  "journey": "El viaje del producto",
  "craftsmanship": "El arte detrás",
  "promise": "La promesa al cliente",
  "call_to_experience": "Invitación a probar"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'product_story';
            }
        }

        return $response;
    }

    /**
     * Generates an about page.
     */
    protected function generateAboutPage(array $context): array
    {
        $companyName = $context['company_name'] ?? 'empresa';
        $teamInfo = $context['team_info'] ?? '';
        $milestones = $context['milestones'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Crear contenido para página "Sobre Nosotros" de {$companyName}.

EQUIPO: {$teamInfo}
HITOS: {$milestones}

REQUISITOS:
- Estructura clara con secciones
- Humanizar la marca
- Mostrar credibilidad
- Optimizado para SEO

FORMATO DE RESPUESTA (JSON):
{
  "meta_title": "Título SEO",
  "meta_description": "Meta descripción",
  "hero_headline": "Titular principal",
  "hero_subheadline": "Subtítulo",
  "our_story": "Sección nuestra historia",
  "our_mission": "Sección misión",
  "our_values": ["Valor 1", "Valor 2"],
  "team_intro": "Introducción al equipo",
  "cta_section": "Llamada a la acción final"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'about_page';
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
Eres un storyteller experto especializado en narrativas de marca.

ESTILO:
- Emotivo pero auténtico
- Evocador de imágenes
- Conecta con valores humanos
- Crea memorabilidad

PRINCIPIOS:
- Mostrar, no solo contar
- Buscar el conflicto/tensión narrativa
- Resolución inspiradora
- Voz única y diferenciada
EOT;
    }

}
