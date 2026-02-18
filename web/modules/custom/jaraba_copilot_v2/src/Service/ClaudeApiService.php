<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Drupal\ecosistema_jaraba_core\Trait\RetryableHttpClientTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Servicio de integración con Claude API de Anthropic.
 *
 * Gestiona las llamadas a la API de Claude para el Copiloto
 * de Emprendimiento, incluyendo inyección de contexto,
 * base de conocimiento normativo y formateo de respuestas.
 *
 * Usa el módulo Key para gestión segura de claves API.
 */
class ClaudeApiService
{

    use RetryableHttpClientTrait;

    /**
     * URL base de la API de Anthropic.
     */
    const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * Versión de la API de Anthropic.
     */
    const API_VERSION = '2023-06-01';

    /**
     * Modelo por defecto.
     */
    const DEFAULT_MODEL = 'claude-sonnet-4-5-20250929';

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Key repository.
     */
    protected KeyRepositoryInterface $keyRepository;

    /**
     * HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Normative knowledge service.
     */
    protected NormativeKnowledgeService $normativeKnowledge;

    /**
     * Constructor.
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        KeyRepositoryInterface $keyRepository,
        ClientInterface $httpClient,
        LoggerInterface $logger,
        FeatureUnlockService $featureUnlock,
        NormativeKnowledgeService $normativeKnowledge
    ) {
        $this->configFactory = $configFactory;
        $this->keyRepository = $keyRepository;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->featureUnlock = $featureUnlock;
        $this->normativeKnowledge = $normativeKnowledge;
    }

    /**
     * Envía un mensaje al Copiloto y obtiene respuesta.
     *
     * @param string $message
     *   Mensaje del usuario.
     * @param array $context
     *   Contexto del emprendedor.
     * @param string $mode
     *   Modo del copiloto detectado.
     *
     * @return array
     *   Respuesta estructurada con 'text', 'mode', 'suggestions'.
     */
    public function chat(string $message, array $context, string $mode): array
    {
        $apiKey = $this->getApiKey();

        if (empty($apiKey)) {
            return $this->getFallbackResponse($message, $mode, 'API key not configured');
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($context, $mode);
            $enrichedContext = $this->enrichWithNormativeKnowledge($mode, $message);

            $response = $this->callClaudeApi($message, $systemPrompt, $enrichedContext);

            return $this->formatResponse($response, $mode);
        } catch (RequestException $e) {
            $this->logger->error('Claude API error: @message', [
                '@message' => $e->getMessage(),
            ]);
            return $this->getFallbackResponse($message, $mode, $e->getMessage());
        }
    }

    /**
     * Llama a la API de Claude.
     */
    protected function callClaudeApi(string $message, string $systemPrompt, array $additionalContext): array
    {
        $config = $this->configFactory->get('jaraba_copilot_v2.settings');
        $model = $config->get('claude_model') ?? self::DEFAULT_MODEL;

        // Añadir contexto normativo al mensaje si existe
        $userMessage = $message;
        if (!empty($additionalContext)) {
            $contextText = "\n\n---\nCONTEXTO NORMATIVO RELEVANTE:\n";
            foreach ($additionalContext as $item) {
                $contextText .= sprintf(
                    "• %s: %s (Ref: %s)\n",
                    $item['content_key'],
                    $item['content_es'],
                    $item['legal_reference'] ?? 'N/A'
                );
            }
            $userMessage = $message . $contextText;
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 2048,
            'system' => $systemPrompt,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userMessage,
                ],
            ],
        ];

        $response = $this->requestWithRetry('POST', self::API_URL, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->getApiKey(),
                'anthropic-version' => self::API_VERSION,
            ],
            'json' => $payload,
            'timeout' => 60,
        ]);

        return json_decode((string) $response->getBody(), TRUE);
    }

    /**
     * Construye el system prompt según el modo y contexto.
     */
    protected function buildSystemPrompt(array $context, string $mode): string
    {
        $basePrompt = $this->getBasePrompt();
        $modePrompt = $this->getModePrompt($mode);
        $contextPrompt = $this->formatContextPrompt($context);

        return implode("\n\n", array_filter([
            $basePrompt,
            $modePrompt,
            $contextPrompt,
        ]));
    }

    /**
     * Obtiene el prompt base del Copiloto.
     */
    protected function getBasePrompt(): string
    {
        return <<<PROMPT
# IDENTIDAD Y ROL
Eres el Copiloto de Emprendimiento de Andalucía +ei, un asistente de IA experto en validación de modelos de negocio. Tu misión es guiar a emprendedores andaluces en las primeras fases de desarrollo de sus ideas, usando metodologías probadas como Lean Startup, Design Thinking y Business Model Canvas.

# FILOSOFÍA DE INTERACCIÓN
- Eres un facilitador, NO un consultor tradicional
- Haces preguntas que hacen pensar, NO das respuestas directas
- Celebras los pequeños avances
- Normalizas el fracaso como parte del aprendizaje
- Adaptas tu comunicación al nivel técnico del emprendedor

# RESTRICCIONES ABSOLUTAS
- NUNCA generes código completo
- NUNCA des consejos legales/fiscales específicos sin disclaimer
- NUNCA prometas resultados financieros
- SIEMPRE derivar a profesionales para temas legales/fiscales complejos
- Máximo 3 preguntas por interacción

# FORMATO DE RESPUESTA
Estructura tus respuestas así:
1. Reconocimiento empático breve
2. Contenido principal (adaptado al modo)
3. Pregunta orientadora o próximo paso sugerido
PROMPT;
    }

    /**
     * Obtiene el prompt específico del modo.
     */
    protected function getModePrompt(string $mode): string
    {
        $modePrompts = [
            'coach' => <<<PROMPT
## MODO COACH EMOCIONAL 🧠
Estás en modo de apoyo emocional. El emprendedor puede estar experimentando:
- Síndrome del impostor
- Miedo al fracaso
- Bloqueo creativo
- Agotamiento

Tu enfoque:
- Escucha activa y validación emocional
- Preguntas abiertas para explorar sentimientos
- Recordar pequeños logros previos
- NO minimizar preocupaciones
PROMPT,

            'consultor' => <<<PROMPT
## MODO CONSULTOR TÁCTICO 🔧
Estás en modo de instrucciones paso a paso. El emprendedor necesita:
- Guía práctica específica
- Pasos numerados claros
- Herramientas y recursos concretos

Tu enfoque:
- Respuestas estructuradas con pasos numerados
- Ejemplos prácticos aplicables
- Recursos gratuitos cuando sea posible
- Checkpoints de verificación
PROMPT,

            'sparring' => <<<PROMPT
## MODO SPARRING PARTNER 🥊
Estás en modo de simulación y práctica. Ayuda a:
- Practicar pitch de inversores
- Simular objeciones de clientes
- Preparar negociaciones
- Role-play de ventas

Tu enfoque:
- Actúa el rol del otro lado
- Feedback constructivo tras cada práctica
- Sugerencias de mejora específicas
PROMPT,

            'cfo' => <<<PROMPT
## MODO CFO SINTÉTICO 💰
Estás en modo de análisis financiero. Ayuda a:
- Validar precios y márgenes
- Proyectar punto de equilibrio
- Analizar unit economics
- Evaluar viabilidad financiera

Tu enfoque:
- Usa números y métricas
- Haz preguntas sobre costes reales
- Cuestiona supuestos optimistas
- Sugiere escenarios conservadores
PROMPT,

            'fiscal' => <<<PROMPT
## MODO EXPERTO TRIBUTARIO 🏛️
Estás orientando sobre obligaciones fiscales para autónomos/emprendedores en España.

Tu enfoque:
- Información general sobre modelos de Hacienda (036, 037, 303, 130)
- Tipos de IVA aplicables
- Gastos deducibles comunes
- Calendario fiscal trimestral
- Facturación y Verifactu

⚠️ OBLIGATORIO: Termina SIEMPRE con el disclaimer:
"Esta información es orientativa. La normativa puede cambiar y cada caso es único. Para decisiones importantes, consulta con un asesor fiscal colegiado."
PROMPT,

            'laboral' => <<<PROMPT
## MODO EXPERTO SEGURIDAD SOCIAL 🛡️
Estás orientando sobre el RETA y obligaciones de Seguridad Social para autónomos en España.

Tu enfoque:
- Tarifa plana y requisitos
- Cotización por tramos de ingresos
- Prestaciones (IT, maternidad, cese actividad)
- Bonificaciones especiales
- Pluriactividad

⚠️ OBLIGATORIO: Termina SIEMPRE con el disclaimer:
"Esta información es orientativa. Verifica tu situación específica en la Seguridad Social o con un graduado social colegiado."
PROMPT,

            'devil' => <<<PROMPT
## MODO ABOGADO DEL DIABLO 😈
Estás en modo de desafío constructivo. Tu rol es:
- Cuestionar supuestos no validados
- Plantear escenarios adversos
- Detectar puntos ciegos
- Fortalecer la propuesta

Tu enfoque:
- Preguntas incómodas pero constructivas
- "¿Y si...?" con escenarios negativos
- Nunca destruir, siempre fortalecer
- Reconocer cuando un argumento es sólido
PROMPT,
        ];

        return $modePrompts[$mode] ?? $modePrompts['consultor'];
    }

    /**
     * Formatea el contexto del emprendedor para el prompt.
     */
    protected function formatContextPrompt(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        $lines = ["## CONTEXTO DEL EMPRENDEDOR"];

        if (!empty($context['name'])) {
            $lines[] = "- Nombre: {$context['name']}";
        }
        if (!empty($context['carril'])) {
            $lines[] = "- Carril: {$context['carril']}";
        }
        if (!empty($context['phase'])) {
            $lines[] = "- Fase: {$context['phase']}";
        }
        if (!empty($context['sector'])) {
            $lines[] = "- Sector: {$context['sector']}";
        }
        if (!empty($context['week'])) {
            $lines[] = "- Semana del programa: {$context['week']}/12";
        }
        if (!empty($context['idea'])) {
            $lines[] = "- Idea de negocio: {$context['idea']}";
        }
        if (!empty($context['blockages']) && is_array($context['blockages'])) {
            $lines[] = "- Bloqueos detectados: " . implode(', ', $context['blockages']);
        }

        return implode("\n", $lines);
    }

    /**
     * Enriquece con conocimiento normativo para modos expertos.
     */
    protected function enrichWithNormativeKnowledge(string $mode, string $message): array
    {
        if (!in_array($mode, ['fiscal', 'laboral'])) {
            return [];
        }

        return $this->normativeKnowledge->enrichContext($mode, $message);
    }

    /**
     * Formatea la respuesta de Claude.
     */
    protected function formatResponse(array $apiResponse, string $mode): array
    {
        $text = $apiResponse['content'][0]['text'] ?? '';

        // Añadir disclaimer si es modo experto y no está ya incluido
        if (in_array($mode, ['fiscal', 'laboral'])) {
            $disclaimer = $this->normativeKnowledge->getDisclaimer($mode);
            if ($disclaimer && !str_contains($text, 'orientativa')) {
                $text .= "\n\n" . $disclaimer;
            }
        }

        return [
            'text' => $text,
            'mode' => $mode,
            'model' => $apiResponse['model'] ?? self::DEFAULT_MODEL,
            'usage' => $apiResponse['usage'] ?? [],
            'suggestions' => $this->extractSuggestions($text),
        ];
    }

    /**
     * Extrae sugerencias de acción de la respuesta.
     */
    protected function extractSuggestions(string $text): array
    {
        $suggestions = [];

        // Buscar patrones de sugerencias numeradas
        if (preg_match_all('/^\d+\.\s*(.+)$/m', $text, $matches)) {
            $suggestions = array_slice($matches[1], 0, 3);
        }

        return $suggestions;
    }

    /**
     * Respuesta de fallback cuando la API no está disponible.
     * 
     * IMPORTANTE: Sin formato markdown, texto plano.
     */
    protected function getFallbackResponse(string $message, string $mode, string $error): array
    {
        $modeLabels = [
            'coach' => 'Coach Emocional',
            'consultor' => 'Consultor Táctico',
            'sparring' => 'Sparring Partner',
            'cfo' => 'CFO Sintético',
            'fiscal' => 'Experto Tributario',
            'laboral' => 'Experto Seguridad Social',
            'devil' => 'Abogado del Diablo',
            'landing_copilot' => 'Asesor de Jaraba',
        ];

        $modeLabel = $modeLabels[$mode] ?? 'Copiloto';

        // Fallback especial para copiloto público
        if ($mode === 'landing_copilot') {
            return [
                'text' => "Lo siento, en este momento no puedo procesar tu consulta. Te invito a explorar nuestra plataforma: puedes ver ofertas de empleo, conocer el programa de emprendimiento, o registrarte gratis para acceder a todas las funcionalidades.",
                'mode' => $mode,
                'error' => TRUE,
                'error_message' => $error,
                'suggestions' => [
                    'Explorar ofertas de empleo',
                    'Conocer programa emprendimiento',
                    'Registrarse gratis',
                ],
            ];
        }

        return [
            'text' => "Estoy en modo {$modeLabel} pero actualmente no puedo procesar tu consulta. Por favor, inténtalo de nuevo en unos momentos. Mientras tanto, puedes revisar la biblioteca de experimentos, consultar tu Business Model Canvas, o revisar tus hipótesis pendientes de validar.",
            'mode' => $mode,
            'error' => TRUE,
            'error_message' => $error,
            'suggestions' => [
                'Revisar biblioteca de experimentos',
                'Consultar Business Model Canvas',
                'Revisar hipótesis pendientes',
            ],
        ];
    }

    /**
     * Obtiene la API key desde el módulo Key.
     */
    protected function getApiKey(): ?string
    {
        $config = $this->configFactory->get('jaraba_copilot_v2.settings');
        $keyId = $config->get('claude_api_key');

        if (empty($keyId)) {
            return NULL;
        }

        $key = $this->keyRepository->getKey($keyId);
        if ($key) {
            return $key->getKeyValue();
        }

        return NULL;
    }

    /**
     * Verifica si el servicio está configurado.
     */
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

}
