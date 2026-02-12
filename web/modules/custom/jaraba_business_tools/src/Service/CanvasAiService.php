<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for AI-powered canvas analysis.
 */
class CanvasAiService
{

    /**
     * The config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * The HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * The logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new CanvasAiService.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        ClientInterface $httpClient,
        $loggerFactory
    ) {
        $this->configFactory = $configFactory;
        $this->httpClient = $httpClient;
        $this->logger = $loggerFactory->get('jaraba_business_tools');
    }

    /**
     * Analyzes canvas for coherence and suggestions.
     */
    public function analyzeCanvas(array $canvasData): array
    {
        $prompt = $this->buildAnalysisPrompt($canvasData);

        try {
            $response = $this->callAiApi($prompt);
            return $this->parseAnalysisResponse($response);
        } catch (\Exception $e) {
            $this->logger->error('AI analysis failed: @error', ['@error' => $e->getMessage()]);
            return $this->getFallbackAnalysis($canvasData);
        }
    }

    /**
     * Builds the AI analysis prompt.
     */
    protected function buildAnalysisPrompt(array $canvasData): string
    {
        $sector = $canvasData['canvas']['sector'] ?? 'general';
        $stage = $canvasData['canvas']['business_stage'] ?? 'idea';

        $blocksJson = json_encode($canvasData['blocks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Analiza el siguiente Business Model Canvas para un negocio del sector "{$sector}" en fase de "{$stage}".

CANVAS:
{$blocksJson}

Evalúa y responde en JSON con esta estructura exacta:
{
  "coherence_score": 0-100,
  "gaps": [
    {"block": "block_type", "severity": "critical|important|minor", "message": "descripción del gap"}
  ],
  "incoherences": [
    {"blocks": ["block1", "block2"], "message": "descripción de la incoherencia"}
  ],
  "suggestions": {
    "customer_segments": ["sugerencia 1", "sugerencia 2"],
    "value_propositions": ["sugerencia 1"],
    ...
  },
  "summary": "Resumen ejecutivo de 2-3 líneas sobre el estado del canvas"
}

Criterios de coherencia:
- Propuesta de valor resuelve problemas del segmento
- Canales alcanzan a los segmentos definidos
- Modelo de ingresos coherente con el valor ofrecido
- Recursos soportan las actividades clave
- Socios cubren gaps de recursos
- Costes reflejan actividades y recursos

Sé constructivo y específico con las sugerencias para el sector {$sector}.
PROMPT;
    }

    /**
     * Calls the AI API (Claude/OpenAI compatible).
     */
    protected function callAiApi(string $prompt): string
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $apiKey = $config->get('anthropic_api_key') ?? $config->get('openai_api_key');
        $apiEndpoint = $config->get('ai_endpoint') ?? 'https://api.anthropic.com/v1/messages';

        if (empty($apiKey)) {
            throw new \RuntimeException('AI API key not configured');
        }

        $response = $this->httpClient->request('POST', $apiEndpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
            'timeout' => 30,
        ]);

        $body = json_decode($response->getBody()->getContents(), TRUE);
        return $body['content'][0]['text'] ?? '';
    }

    /**
     * Parses the AI response.
     */
    protected function parseAnalysisResponse(string $response): array
    {
        // Extract JSON from response.
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $data = json_decode($matches[0], TRUE);
            if ($data) {
                return $data;
            }
        }

        throw new \RuntimeException('Could not parse AI response');
    }

    /**
     * Provides fallback analysis when AI is unavailable.
     */
    protected function getFallbackAnalysis(array $canvasData): array
    {
        $blocks = $canvasData['blocks'] ?? [];
        $canvas = $canvasData['canvas'] ?? [];
        $sector = $canvas['sector'] ?? 'general';
        $gaps = [];
        $suggestions = [];
        $filledCount = 0;
        $totalItems = 0;

        $criticalBlocks = ['customer_segments', 'value_propositions', 'revenue_streams'];
        $importantBlocks = ['channels', 'customer_relationships', 'key_resources'];
        $allBlocks = array_merge($criticalBlocks, $importantBlocks, ['key_activities', 'key_partners', 'cost_structure']);

        foreach ($allBlocks as $type) {
            $items = $blocks[$type]['items'] ?? [];
            $itemCount = count($items);
            $totalItems += $itemCount;

            if ($itemCount === 0) {
                $severity = in_array($type, $criticalBlocks) ? 'critical' : 'important';
                $gaps[] = [
                    'block' => $type,
                    'severity' => $severity,
                    'message' => $this->getBlockLabel($type) . ' está vacío. ' . $this->getBlockHint($type),
                ];
                // Add default suggestions for empty blocks
                $defaultSuggestions = $this->getDefaultSuggestions($type, $sector);
                if (!empty($defaultSuggestions)) {
                    $suggestions[$type] = $defaultSuggestions;
                }
            } elseif ($itemCount < 2 && in_array($type, $criticalBlocks)) {
                $gaps[] = [
                    'block' => $type,
                    'severity' => 'minor',
                    'message' => $this->getBlockLabel($type) . ' tiene pocos elementos. Considera ampliar.',
                ];
            }

            if ($itemCount > 0) {
                $filledCount++;
            }
        }

        // Coherence based on filled blocks and items
        $blockScore = ($filledCount / 9) * 50;
        $itemScore = min(50, ($totalItems / 20) * 50);
        $coherenceScore = round($blockScore + $itemScore);

        // Generate contextual summary
        $summaryParts = [];
        if ($filledCount < 5) {
            $summaryParts[] = 'Tu canvas está en fase inicial.';
        } elseif ($filledCount < 8) {
            $summaryParts[] = 'Buen progreso en tu canvas.';
        } else {
            $summaryParts[] = 'Canvas casi completo.';
        }

        if (!empty($gaps)) {
            $criticalGaps = array_filter($gaps, fn($g) => $g['severity'] === 'critical');
            if (count($criticalGaps) > 0) {
                $summaryParts[] = 'Prioriza: ' . implode(', ', array_map(fn($g) => $this->getBlockLabel($g['block']), array_slice($criticalGaps, 0, 2))) . '.';
            }
        }

        if ($totalItems > 15) {
            $summaryParts[] = 'Revisa que haya coherencia entre bloques.';
        }

        return [
            'coherence_score' => $coherenceScore,
            'gaps' => $gaps,
            'incoherences' => $this->detectBasicIncoherences($blocks),
            'suggestions' => $suggestions,
            'summary' => implode(' ', $summaryParts),
            'stats' => [
                'filled_blocks' => $filledCount,
                'total_items' => $totalItems,
            ],
            'fallback' => TRUE,
        ];
    }

    /**
     * Gets a hint for how to fill a block.
     */
    protected function getBlockHint(string $type): string
    {
        $hints = [
            'customer_segments' => 'Define quiénes son tus clientes ideales.',
            'value_propositions' => 'Describe qué problema resuelves y qué valor aportas.',
            'channels' => 'Indica cómo llegas a tus clientes.',
            'customer_relationships' => 'Define cómo interactúas con tus clientes.',
            'revenue_streams' => 'Especifica cómo generas ingresos.',
            'key_resources' => 'Lista los recursos esenciales para operar.',
            'key_activities' => 'Describe las actividades fundamentales.',
            'key_partners' => 'Identifica aliados estratégicos.',
            'cost_structure' => 'Detalla tus principales costes.',
        ];
        return $hints[$type] ?? '';
    }

    /**
     * Detects basic incoherences between blocks.
     */
    protected function detectBasicIncoherences(array $blocks): array
    {
        $incoherences = [];

        // Check value proposition vs customer segments
        $hasSegments = !empty($blocks['customer_segments']['items']);
        $hasValue = !empty($blocks['value_propositions']['items']);
        $hasChannels = !empty($blocks['channels']['items']);
        $hasRevenue = !empty($blocks['revenue_streams']['items']);
        $hasCosts = !empty($blocks['cost_structure']['items']);

        if ($hasValue && !$hasSegments) {
            $incoherences[] = [
                'blocks' => ['value_propositions', 'customer_segments'],
                'message' => 'Tienes propuesta de valor pero no has definido para quién.',
            ];
        }

        if ($hasSegments && !$hasChannels) {
            $incoherences[] = [
                'blocks' => ['customer_segments', 'channels'],
                'message' => 'Has definido segmentos pero no cómo llegarás a ellos.',
            ];
        }

        if ($hasRevenue && !$hasCosts) {
            $incoherences[] = [
                'blocks' => ['revenue_streams', 'cost_structure'],
                'message' => 'Tienes ingresos definidos pero no estructura de costes.',
            ];
        }

        return $incoherences;
    }

    /**
     * Gets human-readable block label.
     */
    protected function getBlockLabel(string $type): string
    {
        $labels = [
            'customer_segments' => 'Segmentos de Clientes',
            'value_propositions' => 'Propuesta de Valor',
            'channels' => 'Canales',
            'customer_relationships' => 'Relaciones con Clientes',
            'revenue_streams' => 'Fuentes de Ingresos',
            'key_resources' => 'Recursos Clave',
            'key_activities' => 'Actividades Clave',
            'key_partners' => 'Socios Clave',
            'cost_structure' => 'Estructura de Costes',
        ];

        return $labels[$type] ?? $type;
    }

    /**
     * Generates suggestions for a specific block.
     */
    public function generateBlockSuggestions(string $blockType, string $sector, array $existingItems): array
    {
        $prompt = $this->buildBlockSuggestionsPrompt($blockType, $sector, $existingItems);

        try {
            $response = $this->callAiApi($prompt);
            $data = $this->parseAnalysisResponse($response);
            return $data['suggestions'] ?? [];
        } catch (\Exception $e) {
            $this->logger->warning('Block suggestions failed: @error', ['@error' => $e->getMessage()]);
            return $this->getDefaultSuggestions($blockType, $sector);
        }
    }

    /**
     * Builds prompt for block-specific suggestions.
     */
    protected function buildBlockSuggestionsPrompt(string $blockType, string $sector, array $existingItems): string
    {
        $label = $this->getBlockLabel($blockType);
        $existing = implode(', ', array_column($existingItems, 'text'));

        return <<<PROMPT
Para el bloque "{$label}" de un Business Model Canvas del sector "{$sector}":

Contenido actual: {$existing}

Sugiere 3-5 elementos adicionales relevantes para este bloque y sector.
Responde en JSON: {"suggestions": ["sugerencia 1", "sugerencia 2", ...]}
PROMPT;
    }

    /**
     * Gets default suggestions by sector and block.
     */
    protected function getDefaultSuggestions(string $blockType, string $sector): array
    {
        $defaults = [
            'comercio' => [
                'customer_segments' => ['Residentes del barrio', 'Turistas', 'Compradores online'],
                'value_propositions' => ['Producto local de calidad', 'Atención personalizada', 'Entrega rápida'],
                'channels' => ['Tienda física', 'WhatsApp Business', 'Instagram'],
                'customer_relationships' => ['Atención personalizada', 'Fidelización por puntos', 'Comunidad local'],
                'revenue_streams' => ['Venta directa', 'Suscripción mensual', 'Envíos a domicilio'],
                'key_resources' => ['Local comercial', 'Stock de productos', 'Sistema de gestión'],
                'key_activities' => ['Atención al cliente', 'Gestión de inventario', 'Marketing local'],
                'key_partners' => ['Proveedores locales', 'Empresa de reparto', 'Asociación de comerciantes'],
                'cost_structure' => ['Alquiler', 'Personal', 'Inventario', 'Marketing'],
            ],
            'servicios' => [
                'customer_segments' => ['Pymes locales', 'Autónomos', 'Particulares'],
                'value_propositions' => ['Expertise especializado', 'Cercanía', 'Flexibilidad'],
                'channels' => ['Web profesional', 'LinkedIn', 'Recomendación'],
                'customer_relationships' => ['Consultoría personalizada', 'Soporte continuo', 'Formación'],
                'revenue_streams' => ['Honorarios por proyecto', 'Iguala mensual', 'Consultoría premium'],
                'key_resources' => ['Conocimiento experto', 'Herramientas profesionales', 'Red de contactos'],
                'key_activities' => ['Prestación del servicio', 'Networking', 'Actualización profesional'],
                'key_partners' => ['Otros profesionales', 'Asociaciones sectoriales', 'Plataformas de freelance'],
                'cost_structure' => ['Formación', 'Herramientas', 'Marketing', 'Oficina'],
            ],
            'agro' => [
                'customer_segments' => ['Consumidores conscientes', 'Restaurantes km0', 'Tiendas gourmet'],
                'value_propositions' => ['Producto artesanal', 'Trazabilidad total', 'Historia del productor'],
                'channels' => ['Venta directa', 'Mercados locales', 'Plataformas de venta directa'],
                'customer_relationships' => ['Visitas a la finca', 'Newsletter de temporada', 'Club de productores'],
                'revenue_streams' => ['Venta de productos', 'Cestas de temporada', 'Experiencias gastronómicas'],
                'key_resources' => ['Tierras', 'Conocimiento agrícola', 'Certificaciones ecológicas'],
                'key_activities' => ['Cultivo/producción', 'Gestión de calidad', 'Comercialización directa'],
                'key_partners' => ['Cooperativas', 'Distribuidores ecológicos', 'Certificadoras'],
                'cost_structure' => ['Semillas/insumos', 'Mano de obra', 'Transporte', 'Certificaciones'],
            ],
            'tecnologia' => [
                'customer_segments' => ['Startups', 'Pymes digitales', 'Corporaciones'],
                'value_propositions' => ['Solución escalable', 'Integración fácil', 'Soporte 24/7'],
                'channels' => ['Web/SaaS', 'Marketplace', 'Partners tecnológicos'],
                'customer_relationships' => ['Onboarding automatizado', 'Customer Success', 'Comunidad de usuarios'],
                'revenue_streams' => ['Suscripción mensual', 'Pago por uso', 'Licencias enterprise'],
                'key_resources' => ['Plataforma tecnológica', 'Equipo de desarrollo', 'Infraestructura cloud'],
                'key_activities' => ['Desarrollo de producto', 'Soporte técnico', 'Mejora continua'],
                'key_partners' => ['Cloud providers', 'Integradores', 'Resellers'],
                'cost_structure' => ['Desarrollo', 'Infraestructura', 'Marketing digital', 'Soporte'],
            ],
            'hosteleria' => [
                'customer_segments' => ['Turistas', 'Locales', 'Empresas (eventos)'],
                'value_propositions' => ['Experiencia única', 'Producto de calidad', 'Ambiente acogedor'],
                'channels' => ['Local físico', 'Redes sociales', 'Plataformas de reservas'],
                'customer_relationships' => ['Servicio de calidad', 'Programa de fidelización', 'Eventos especiales'],
                'revenue_streams' => ['Consumos en local', 'Eventos privados', 'Delivery/take away'],
                'key_resources' => ['Local', 'Cocina equipada', 'Personal cualificado'],
                'key_activities' => ['Servicio al cliente', 'Gestión de cocina', 'Marketing experiencial'],
                'key_partners' => ['Proveedores de alimentos', 'Plataformas de delivery', 'Organizadores de eventos'],
                'cost_structure' => ['Alquiler', 'Personal', 'Materias primas', 'Suministros'],
            ],
            'formacion' => [
                'customer_segments' => ['Profesionales en activo', 'Empresas', 'Desempleados'],
                'value_propositions' => ['Formación práctica', 'Certificación oficial', 'Flexibilidad horaria'],
                'channels' => ['Plataforma online', 'Presencial', 'Blended learning'],
                'customer_relationships' => ['Tutorización', 'Comunidad de alumnos', 'Bolsa de empleo'],
                'revenue_streams' => ['Matrículas', 'Formación in-company', 'Suscripción premium'],
                'key_resources' => ['Contenido formativo', 'Plataforma LMS', 'Equipo docente'],
                'key_activities' => ['Diseño curricular', 'Impartición', 'Seguimiento de alumnos'],
                'key_partners' => ['Empresas para prácticas', 'Certificadoras', 'Universidades'],
                'cost_structure' => ['Docentes', 'Plataforma', 'Marketing', 'Materiales'],
            ],
            'general' => [
                'customer_segments' => ['Define tu cliente ideal', 'Segmento secundario'],
                'value_propositions' => ['Tu propuesta única de valor', 'Diferenciación'],
                'channels' => ['Canal principal', 'Canal digital', 'Canal presencial'],
                'customer_relationships' => ['Tipo de relación con clientes'],
                'revenue_streams' => ['Fuente de ingresos principal', 'Ingresos recurrentes'],
                'key_resources' => ['Recursos esenciales para operar'],
                'key_activities' => ['Actividades fundamentales'],
                'key_partners' => ['Aliados estratégicos'],
                'cost_structure' => ['Costes principales'],
            ],
        ];

        return $defaults[$sector][$blockType] ?? $defaults['general'][$blockType] ?? [];
    }

    /**
     * Generates a complete canvas based on a business description.
     */
    public function generateFullCanvas(string $description, string $sector = 'general'): array
    {
        $prompt = $this->buildGenerationPrompt($description, $sector);

        try {
            $response = $this->callAiApi($prompt);
            $data = $this->parseAnalysisResponse($response);
            return $data;
        } catch (\Exception $e) {
            $this->logger->warning('Canvas generation failed: @error', ['@error' => $e->getMessage()]);
            return $this->generateFallbackCanvas($description, $sector);
        }
    }

    /**
     * Builds the prompt for full canvas generation.
     */
    protected function buildGenerationPrompt(string $description, string $sector): string
    {
        return <<<PROMPT
Genera un Business Model Canvas completo para el siguiente negocio del sector "{$sector}":

DESCRIPCIÓN DEL NEGOCIO:
{$description}

Responde en JSON con esta estructura exacta (3-5 elementos por bloque):
{
  "title": "Nombre sugerido para el canvas",
  "blocks": {
    "customer_segments": ["segmento 1", "segmento 2", "segmento 3"],
    "value_propositions": ["propuesta 1", "propuesta 2"],
    "channels": ["canal 1", "canal 2", "canal 3"],
    "customer_relationships": ["relación 1", "relación 2"],
    "revenue_streams": ["fuente 1", "fuente 2"],
    "key_resources": ["recurso 1", "recurso 2", "recurso 3"],
    "key_activities": ["actividad 1", "actividad 2"],
    "key_partners": ["socio 1", "socio 2"],
    "cost_structure": ["coste 1", "coste 2", "coste 3"]
  }
}

Sé específico, realista y adaptado al sector indicado. Los elementos deben ser concisos (máximo 15 palabras cada uno).
PROMPT;
    }

    /**
     * Generates a fallback canvas when AI is unavailable.
     */
    protected function generateFallbackCanvas(string $description, string $sector): array
    {
        $defaults = $this->getDefaultSuggestions('customer_segments', $sector);

        return [
            'title' => 'Mi Modelo de Negocio',
            'blocks' => [
                'customer_segments' => $this->getDefaultSuggestions('customer_segments', $sector) ?: ['Clientes potenciales'],
                'value_propositions' => $this->getDefaultSuggestions('value_propositions', $sector) ?: ['Valor diferencial'],
                'channels' => $this->getDefaultSuggestions('channels', $sector) ?: ['Canal principal'],
                'customer_relationships' => ['Atención personalizada'],
                'revenue_streams' => ['Venta directa'],
                'key_resources' => ['Equipo', 'Tecnología'],
                'key_activities' => ['Operaciones', 'Marketing'],
                'key_partners' => ['Proveedores clave'],
                'cost_structure' => ['Costes operativos', 'Marketing'],
            ],
            'fallback' => TRUE,
        ];
    }

}

