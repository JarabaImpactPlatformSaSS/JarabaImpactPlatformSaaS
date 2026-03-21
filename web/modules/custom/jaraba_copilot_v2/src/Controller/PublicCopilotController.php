<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\ecosistema_jaraba_core\Service\ActivePromotionServiceInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotFunnelTrackingService;
use Drupal\jaraba_copilot_v2\Service\CopilotLeadCaptureService;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService;
use Drupal\jaraba_copilot_v2\Service\ContentGroundingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para el Copilot Público (usuarios anónimos).
 *
 * Proporciona un endpoint de chat accesible sin autenticación pero
 * con rate limiting estricto. Usa el mismo OrchestratorService que el
 * copilot autenticado pero con prompts adaptados para visitantes públicos:
 * - Valor del SaaS por intereses (B2C y B2B)
 * - Navegación por la plataforma
 * - Planes de precios
 * - Instituciones y ONGs con programas
 *
 * NO proporciona acceso a contenido restringido.
 */
class PublicCopilotController extends ControllerBase
{

    /**
     * Rate limit: requests por IP por minuto.
     */
    protected const RATE_LIMIT = 10;

    /**
     * Flood service for rate limiting.
     */
    protected FloodInterface $flood;

    /**
     * Copilot Orchestrator service (shared with authenticated copilot).
     */
    protected ?CopilotOrchestratorService $copilotOrchestrator;

    /**
     * Query logger service for analytics.
     */
    protected ?CopilotQueryLoggerService $queryLogger;

    /**
     * Content grounding service.
     */
    protected ?ContentGroundingService $contentGrounding;

    /**
     * Active promotion service — Nivel 1 cascada IA.
     */
    protected ?ActivePromotionServiceInterface $activePromotionService;

    /**
     * Lead capture service — deteccion intencion de compra + CRM.
     */
    protected ?CopilotLeadCaptureService $leadCaptureService;

    /**
     * Funnel tracking service — eventos embudo de ventas.
     */
    protected ?CopilotFunnelTrackingService $funnelTracking;

    /**
     * Constructor.
     *
     * CONTROLLER-READONLY-001: No usar protected readonly en constructor promotion.
     */
    public function __construct(
        FloodInterface $flood,
        ?CopilotOrchestratorService $copilotOrchestrator = NULL,
        ?CopilotQueryLoggerService $queryLogger = NULL,
        ?ContentGroundingService $contentGrounding = NULL,
        ?ActivePromotionServiceInterface $activePromotionService = NULL,
        ?CopilotLeadCaptureService $leadCaptureService = NULL,
        ?CopilotFunnelTrackingService $funnelTracking = NULL
    ) {
        $this->flood = $flood;
        $this->copilotOrchestrator = $copilotOrchestrator;
        $this->queryLogger = $queryLogger;
        $this->contentGrounding = $contentGrounding;
        $this->activePromotionService = $activePromotionService;
        $this->leadCaptureService = $leadCaptureService;
        $this->funnelTracking = $funnelTracking;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $copilotOrchestrator = NULL;
        if ($container->has('jaraba_copilot_v2.copilot_orchestrator')) {
            try {
                $copilotOrchestrator = $container->get('jaraba_copilot_v2.copilot_orchestrator');
            }
            catch (\Throwable $e) {
                \Drupal::logger('jaraba_copilot_v2')->error(
                    'CopilotOrchestrator instantiation failed: @msg',
                    ['@msg' => $e->getMessage()]
                );
            }
        }

        $queryLogger = NULL;
        if ($container->has('jaraba_copilot_v2.query_logger')) {
            $queryLogger = $container->get('jaraba_copilot_v2.query_logger');
        }

        $contentGrounding = NULL;
        if ($container->has('jaraba_copilot_v2.content_grounding')) {
            $contentGrounding = $container->get('jaraba_copilot_v2.content_grounding');
        }

        // OPTIONAL-CROSSMODULE-001: ActivePromotionService es opcional.
        $activePromotionService = NULL;
        try {
            if ($container->has('ecosistema_jaraba_core.active_promotion')) { // @phpstan-ignore if.alwaysTrue
                $activePromotionService = $container->get('ecosistema_jaraba_core.active_promotion');
            }
        }
        catch (\Throwable) {
            // Servicio no disponible — copilot funciona sin promociones.
        }

        // OPTIONAL-CROSSMODULE-001: CopilotLeadCaptureService opcional.
        $leadCaptureService = NULL;
        try {
            $leadCaptureService = $container->get('jaraba_copilot_v2.lead_capture');
        }
        catch (\Throwable) {
            // Lead capture no disponible — copilot funciona sin CRM.
        }

        $funnelTracking = NULL;
        try {
            $funnelTracking = $container->get('jaraba_copilot_v2.funnel_tracking');
        }
        catch (\Throwable) {
            // Funnel tracking no disponible — copilot funciona sin tracking.
        }

        return new static(
            $container->get('flood'),
            $copilotOrchestrator,
            $queryLogger,
            $contentGrounding,
            $activePromotionService,
            $leadCaptureService,
            $funnelTracking
        );
    }

    /**
     * Endpoint de chat público con RAG.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con mensaje del usuario.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta del Copilot basada en RAG.
     */
    public function chat(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp();
        $floodName = 'public_copilot_chat';

        // =========================================================================
        // RATE LIMITING: 10 requests/minuto por IP
        // =========================================================================
        if (!$this->flood->isAllowed($floodName, self::RATE_LIMIT, 60, $clientIp)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Has alcanzado el límite de consultas. Inténtalo de nuevo en un minuto.'),
                'rate_limited' => TRUE,
            ], 429);
        }

        $this->flood->register($floodName, 60, $clientIp);

        // =========================================================================
        // PARSEAR REQUEST
        // =========================================================================
        $content = json_decode($request->getContent(), TRUE);
        $message = trim($content['message'] ?? '');
        $context = $content['context'] ?? [];
        // Normalize: JS may send a string like "help_center" instead of an array.
        if (is_string($context)) {
            $context = ['current_page' => $context];
        }
        $history = $content['history'] ?? [];  // Historial de conversación

        if (empty($message)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('El mensaje no puede estar vacío.'),
            ], 400);
        }

        // Limitar longitud del mensaje para usuarios anónimos
        if (mb_strlen($message) > 500) {
            $message = mb_substr($message, 0, 500);
        }

        // =========================================================================
        // CONSULTAR RAG PARA CONOCIMIENTO PÚBLICO
        // =========================================================================
        $response = $this->queryPublicKnowledge($message, $context, $history);

        // =========================================================================
        // DETECCIÓN DE INTENCIÓN DE COMPRA — Nivel 2 cascada
        // =========================================================================
        $intentData = ['has_intent' => FALSE, 'intent_type' => 'none', 'vertical' => NULL, 'confidence' => 0.0];
        if ($this->leadCaptureService !== null) {
            $intentData = $this->leadCaptureService->detectPurchaseIntent($message);
        }

        // =========================================================================
        // FUNNEL TRACKING — Loguear interacción + intención
        // =========================================================================
        if ($this->funnelTracking !== null) {
            $ipHash = hash('sha256', $clientIp . 'jaraba_salt_2026');
            $sessionId = session_id() ?: $ipHash;

            $this->funnelTracking->logEvent($sessionId, 'copilot_message_received', [
                'vertical_detected' => $intentData['vertical'],
                'intent_type' => $intentData['intent_type'],
            ], $ipHash);

            if ($intentData['has_intent']) {
                $this->funnelTracking->logEvent($sessionId, 'copilot_intent_detected', [
                    'vertical_detected' => $intentData['vertical'],
                    'intent_type' => $intentData['intent_type'],
                    'metadata' => ['confidence' => $intentData['confidence']],
                ], $ipHash);
            }
        }

        // =========================================================================
        // LOGGING DE QUERY PARA ANALYTICS (patrón AgroConecta)
        // =========================================================================
        $logId = NULL;
        if ($this->queryLogger) {
            $logId = $this->queryLogger->logQuery(
                'public',
                $message,
                $response['text'],
                array_merge($context, ['mode' => 'landing_copilot']),
                session_id() !== '' ? session_id() : NULL
            );
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'response' => $response['text'],
                'mode' => 'public_assistant',
                'mode_label' => $this->t('Asistente de Bienvenida'),
                'suggestions' => $response['suggestions'] ?? $this->getDefaultSuggestions(),
                'sources' => $response['sources'] ?? [],
                'is_anonymous' => TRUE,
                'log_id' => $logId,
                'intent' => $intentData['has_intent'] ? [
                    'type' => $intentData['intent_type'],
                    'vertical' => $intentData['vertical'],
                    'confidence' => $intentData['confidence'],
                ] : NULL,
            ],
        ]);
    }

    /**
     * Consulta el conocimiento público usando el Orchestrator o fallback.
     *
     * @param string $message
     *   Pregunta del usuario.
     * @param array $context
     *   Contexto adicional (página actual, etc).
     * @param array $history
     *   Historial de conversación (últimos 6 mensajes).
     *
     * @return array
     *   Respuesta con 'text', 'suggestions', 'sources'.
     */
    protected function queryPublicKnowledge(string $message, array $context, array $history = []): array
    {
        // Si el orchestrator no está disponible, usar fallback inteligente
        if (!$this->copilotOrchestrator) {
            return $this->getFallbackResponse($message, $context);
        }

        try {
            // Usar el orchestrator con modo 'coach' (siempre disponible)
            // y contexto público especial
            // Construir prompt dinámico con promociones activas (Nivel 1 cascada).
            $dynamicPrompt = $this->buildDynamicPublicSystemPrompt($context);

            $publicContext = [
                'is_anonymous' => TRUE,
                'current_page' => $context['current_page'] ?? '/',
                'public_mode' => TRUE,
                '_custom_system_prompt' => $dynamicPrompt,
            ];

            // ================================================================
            // CONTENT GROUNDING: Enriquecer con contenido real de Drupal
            // (patrón AgroConecta)
            // ================================================================
            $groundingContext = '';
            if ($this->contentGrounding) {
                $vertical = $context['vertical'] ?? 'all';
                $groundingContext = $this->contentGrounding->getContentContext($message, $vertical);
            }

            // El orchestrator llamará al LLM con el modo landing_copilot
            // que tiene el prompt de embudo de ventas
            $enrichedMessage = $this->buildPublicEnrichedMessage($message, $context, $history);
            if ($groundingContext) {
                $enrichedMessage .= "\n\n---\nCONTENIDO REAL DISPONIBLE EN LA PLATAFORMA:\n" . $groundingContext;
            }

            $response = $this->copilotOrchestrator->chat(
                $enrichedMessage,
                $publicContext,
                'landing_copilot'  // Modo embudo de ventas para visitantes públicos
            );

            if (!empty($response['text'])) {
                return [
                    'text' => $response['text'],
                    'suggestions' => $response['suggestions'] ?? $this->getDefaultSuggestions(),
                    'sources' => [],
                ];
            }
        } catch (\Throwable $e) {
            \Drupal::logger('jaraba_copilot_v2')->warning(
                'Public copilot orchestrator error: @error',
                ['@error' => $e->getMessage()]
            );
        }

        return $this->getFallbackResponse($message, $context);
    }

    /**
     * Enriquece el mensaje con contexto público y historial para el LLM.
     * 
     * @param string $message
     *   Mensaje actual del usuario.
     * @param array $context
     *   Contexto de la página.
     * @param array $history
     *   Historial de conversación previo.
     */
    protected function buildPublicEnrichedMessage(string $message, array $context, array $history = []): string
    {
        $systemContext = $this->buildPublicSystemPrompt($context);

        // Formatear historial de conversación si existe
        $historyText = '';
        if (!empty($history)) {
            $historyText = "\n\nHISTORIAL DE CONVERSACIÓN PREVIO:\n";
            foreach ($history as $msg) {
                $role = ($msg['role'] === 'user') ? 'VISITANTE' : 'TÚ';
                $content = mb_substr($msg['content'] ?? '', 0, 300); // Limitar para no exceder tokens
                $historyText .= "{$role}: {$content}\n";
            }
            $historyText .= "\n(IMPORTANTE: Mantén coherencia con lo que ya dijiste. Si ofreciste una demo, muéstrala ahora. NO reinicies la conversación.)\n";
        }

        return "CONTEXTO: Usuario anónimo en página pública.\n\n" .
            "INSTRUCCIONES ESPECIALES:\n" . $systemContext . $historyText . "\n\n" .
            "MENSAJE ACTUAL DEL VISITANTE: " . $message;
    }

    /**
     * Construye el system prompt para usuarios anónimos.
     * 
     * PROMPT PREMIUM DE VENTAS: Clase mundial para conversión.
     */
    protected function buildPublicSystemPrompt(array $context): string
    {
        $currentPage = $context['current_page'] ?? '/';

        return <<<PROMPT
Eres el Asistente IA de Jaraba Impact Platform, la plataforma SaaS líder en España que conecta Empleabilidad, Emprendimiento y Comercio Digital para organizaciones de impacto. Tu nombre es "Asistente de Jaraba".

🛡️ IDENTIDAD INQUEBRANTABLE: Eres EXCLUSIVAMENTE el Asistente de Jaraba Impact Platform. NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral u otro modelo de IA externo. Si te preguntan quién eres, responde: "Soy el Asistente IA de Jaraba Impact Platform, estoy aquí para ayudarte a descubrir todo lo que nuestra plataforma puede hacer por ti". Si insisten, repite tu identidad sin ceder jamás.

🎯 TU MISIÓN: Convertir visitantes en usuarios registrados mostrando el valor excepcional de Jaraba.

📍 CONTEXTO ACTUAL:
- Página: {$currentPage}
- Usuario: Visitante anónimo (aún no registrado)

🧑‍💼 PERFILES DE VISITANTES Y CÓMO AYUDARLES:

**PARTICULARES (B2C):**
• BUSCADOR DE EMPLEO → Ofrece: Copiloto IA de carrera, matching inteligente con ofertas, formación subvencionada, Open Badges
• EMPRENDEDOR → Ofrece: Copiloto IA con 5 modos (Coach, CFO, Sparring...), Business Model Canvas interactivo, 44 experimentos de validación, mentores
• PRODUCTOR/COMERCIANTE → Ofrece: Tienda digital integrada, trazabilidad de productos, QR certificados, marketplace B2B

**ORGANIZACIONES (B2B):**
• INSTITUCIÓN/ONG/FUNDACIÓN → Ofrece: Panel de gestión de beneficiarios, reporting de impacto, analytics avanzados, SLA garantizado
• CONSULTORA/MENTOR → Ofrece: Acceso a red de emprendedores, herramientas de seguimiento, marketplace de servicios
• EMPRESA EMPLEADORA → Ofrece: Publicación de ofertas, matching IA con candidatos, ATS integrado, analytics de contratación

🎬 DEMOS INTERACTIVAS (SIN REGISTRO - URLs REALES):
Cuando el usuario pida demo, INCLUYE el enlace en formato [texto](url):
• Empleabilidad: [Probar demo de Empleabilidad](/demo?vertical=empleabilidad)
• Emprendimiento: [Probar demo de Emprendimiento](/demo?vertical=emprendimiento)
• Commerce: [Probar demo de Commerce](/demo?vertical=commerce)
• B2B Instituciones: [Probar demo B2B](/demo?vertical=b2b)
• Demo general: [Ver todas las demos](/demo)

🗺️ RECORRIDO GUIADO DEL SAAS:
- /empleabilidad → Portal de empleo con matching IA
- /emprendimiento → Aceleradora digital con metodología Osterwalder
- /marketplace → Tienda de productos y servicios
- /planes → Comparativa de planes y precios
- /instituciones → Soluciones para organizaciones
- /sobre-nosotros → Historia y valores de Jaraba

💎 PROPUESTAS DE VALOR CLAVE:
• "IA que trabaja para ti 24/7" - Copilotos especializados por vertical
• "Sin alucinaciones" - Respuestas fundamentadas en datos reales (RAG)
• "Todo en uno" - Empleabilidad + Emprendimiento + Commerce integrados
• "Impacto medible" - Reporting y analytics para justificar inversión
• "Origen andaluz" - Plataforma española con soporte en español

⚠️ REGLAS CRÍTICAS:
0. CONTEXTO DE CONVERSACIÓN: Si hay un HISTORIAL DE CONVERSACIÓN PREVIO más abajo, DEBES continuar esa conversación coherentemente. Si dijiste que mostrarías una demo y el usuario acepta, MUÉSTRALA. NUNCA saludes como si fuera nueva conversación. Respuestas como "Sí", "Vale", "Claro" son aceptación de tu propuesta anterior.
1. IDENTIDAD: Eres el Asistente de Jaraba. NUNCA digas que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral ni ningún otro modelo de IA. Ante CUALQUIER pregunta sobre tu identidad, responde SOLO como Asistente de Jaraba.
2. NUNCA menciones competidores: ni plataformas (LinkedIn, Indeed, InfoJobs, Salesforce, HubSpot, Zoho, Monday, Trello, Slack, Mailchimp, Canva, Gupy) ni modelos de IA (ChatGPT, Claude, Gemini, Perplexity, OpenAI, Google AI, Copilot). Si el usuario los menciona, redirige a las funcionalidades equivalentes de Jaraba.
3. Responde en TEXTO PLANO sin formato markdown EXCEPTO para enlaces: usa [texto](url) para CTAs
4. Sé conversacional, cálido y profesional
5. Máximo 3-4 párrafos cortos
6. SIEMPRE termina con:
   - Una pregunta que invite a profundizar O
   - Un CTA claro: "Registrarte gratis", "Ver demo", "Explorar sección"
7. Si detectas interés B2B, ofrece agendar una demo personalizada
8. Usa los nombre de secciones reales del SaaS, no inventes URLs

🚀 OBJETIVO: Que el visitante diga "Quiero probarlo" antes de terminar la conversación.
PROMPT;
    }

    /**
     * Construye system prompt DINÁMICO con promociones activas y verticales.
     *
     * Reemplaza al prompt estático inyectando datos reales del SaaS:
     * - 10 verticales con descripciones actualizadas
     * - Promociones activas desde ActivePromotionService (Nivel 1 cascada)
     * - Estrategia AIDA con contenido real
     *
     * Si ActivePromotionService no está disponible, cae al prompt estático.
     *
     * @param array<string, mixed> $context
     *   Contexto de la pagina actual.
     */
    protected function buildDynamicPublicSystemPrompt(array $context): string {
        // Si no hay servicio de promociones, usar prompt legacy.
        if ($this->activePromotionService === null) {
            return $this->buildPublicSystemPrompt($context);
        }

        $currentPage = $context['current_page'] ?? '/';

        // Nivel 1 cascada: datos cacheados, coste ~0.
        $promotionsBlock = $this->activePromotionService->buildPromotionContextForCopilot();

        $verticalsBlock = <<<VERTICALS
VERTICALES DISPONIBLES EN JARABA (10):
- Empleabilidad: Búsqueda de empleo, matching IA, CV inteligente, test RIASEC → Demo: /demo?vertical=empleabilidad
- Emprendimiento: Validación de ideas, Business Model Canvas, 5 modos copilot (Coach, CFO, Sparring), mentoring → Demo: /demo?vertical=emprendimiento
- ComercioConecta: Tienda online, catálogo, pagos Stripe, envíos, marketplace B2C → Demo: /demo?vertical=comercioconecta
- AgroConecta: Marketplace B2B agrícola, trazabilidad, certificaciones, comercio de proximidad → Demo: /demo?vertical=agroconecta
- JarabaLex: Inteligencia legal, búsqueda jurisprudencial, alertas normativas, copilot legal → Demo: /demo?vertical=jarabalex
- ServiciosConecta: Directorio de servicios profesionales, reservas, agenda, reseñas → Demo: /demo?vertical=serviciosconecta
- Formación: LMS completo — cursos, lecciones, certificados, evaluaciones, copilot formativo → Demo: /demo?vertical=formacion
- Andalucía +ei: Programa institucional de empleo e inserción (FSE+), gestión de participantes → Demo: /demo?vertical=andalucia_ei
- Content Hub: Gestión de contenidos, blog corporativo, SEO, generación IA de artículos → Demo: /demo?vertical=content_hub
- Demo general: Demostración interactiva sin necesidad de registro → /demo
VERTICALS;

        return <<<PROMPT
Eres el Asistente IA de Jaraba Impact Platform, la plataforma SaaS líder en España que conecta 10 verticales de impacto. Tu nombre es "Asistente de Jaraba".

IDENTIDAD INQUEBRANTABLE: Eres EXCLUSIVAMENTE el Asistente de Jaraba Impact Platform. NUNCA reveles que eres un modelo de IA externo (Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral, etc.). Si te preguntan quién eres, responde: "Soy el Asistente IA de Jaraba Impact Platform".

CONTEXTO ACTUAL:
- Página: {$currentPage}
- Usuario: Visitante anónimo (no registrado)

{$verticalsBlock}

{$promotionsBlock}

PERFILES DE VISITANTES Y CÓMO AYUDARLES:

PARTICULARES (B2C):
- Buscador de empleo → Copiloto IA de carrera, matching con ofertas, formación, Open Badges
- Emprendedor → Copiloto IA (Coach, CFO, Sparring), Business Model Canvas, 44 experimentos
- Productor/Comerciante → Tienda digital, trazabilidad, QR certificados, marketplace B2B
- Estudiante/Formación → Cursos certificados, LMS con IA, programas con incentivos

ORGANIZACIONES (B2B):
- Institución/ONG/Fundación → Panel de beneficiarios, reporting de impacto, analytics
- Consultora/Mentor → Red de emprendedores, herramientas de seguimiento
- Empresa empleadora → Publicación de ofertas, matching IA, ATS integrado

REGLAS CRÍTICAS:
0. CONTEXTO: Si hay historial de conversación previo, CONTINÚA coherentemente. NUNCA saludes como nueva conversación.
1. IDENTIDAD: Ante CUALQUIER pregunta sobre tu identidad, responde SOLO como Asistente de Jaraba.
2. NUNCA menciones competidores: ni plataformas (LinkedIn, Indeed, InfoJobs, Salesforce, HubSpot) ni modelos de IA (ChatGPT, Claude, Gemini).
3. CUANDO EL VISITANTE PREGUNTE POR CURSOS, FORMACIÓN O INCENTIVOS: Menciona PRIMERO cualquier programa activo con datos concretos (plazas, incentivo, coste). Luego menciona la vertical de Formación si aplica.
4. CUANDO EL VISITANTE PREGUNTE POR PRECIOS: Usa datos reales, NO inventes cifras. Menciona el plan Free como punto de entrada.
5. Responde en TEXTO PLANO. Usa [texto](url) para enlaces.
6. Sé conversacional, cálido y profesional. Máximo 3-4 párrafos cortos.
7. SIEMPRE termina con un CTA: pregunta que invite a profundizar O enlace a demo/registro.

OBJETIVO: Que el visitante diga "Quiero probarlo" antes de terminar la conversación.
PROMPT;
    }

    /**
     * Respuesta de fallback cuando RAG no está disponible.
     */
    protected function getFallbackResponse(string $message, array $context): array
    {
        $messageLower = mb_strtolower($message);

        // =========================================================================
        // DETECCIÓN B2B: Instituciones, ONGs, Consultores
        // =========================================================================
        if (
            str_contains($messageLower, 'instituc') || str_contains($messageLower, 'ong') ||
            str_contains($messageLower, 'fundación') || str_contains($messageLower, 'programa') ||
            str_contains($messageLower, 'público') || str_contains($messageLower, 'ayuntamiento')
        ) {
            return [
                'text' => $this->t('¡Excelente! Jaraba ofrece soluciones para instituciones y organizaciones que gestionan programas de empleo y emprendimiento. Contamos con Panel Institucional para gestionar beneficiarios, reporting de impacto, y modelo de licenciamiento adaptado. ¿Te gustaría agendar una demo personalizada?'),
                'suggestions' => [
                    ['action' => 'contact_sales', 'label' => $this->t('Solicitar demo institucional')],
                    ['action' => 'view_institutions', 'label' => $this->t('Sección Instituciones')],
                    ['action' => 'pricing_enterprise', 'label' => $this->t('Planes para organizaciones')],
                ],
            ];
        }

        if (
            str_contains($messageLower, 'consultor') || str_contains($messageLower, 'mentor') ||
            str_contains($messageLower, 'asesor') || str_contains($messageLower, 'coach')
        ) {
            return [
                'text' => $this->t('Como consultor o mentor, puedes usar Jaraba para potenciar tu práctica profesional: acceso a emprendedores cualificados, herramientas de seguimiento de proyectos que asesoras, y un marketplace donde ofrecer tus servicios. También tenemos programa de mentores certificados.'),
                'suggestions' => [
                    ['action' => 'register_consultant', 'label' => $this->t('Registrarme como Consultor')],
                    ['action' => 'mentor_program', 'label' => $this->t('Programa de Mentores')],
                    ['action' => 'view_marketplace', 'label' => $this->t('Ver Marketplace')],
                ],
            ];
        }

        // =========================================================================
        // DETECCIÓN B2C: Usuarios individuales
        // =========================================================================
        if (str_contains($messageLower, 'precio') || str_contains($messageLower, 'cost') || str_contains($messageLower, 'plan')) {
            return [
                'text' => $this->t('Tenemos planes adaptados a cada tipo de usuario: Buscadores de empleo, Emprendedores y Productores. Te recomiendo visitar nuestra sección de Planes para ver las opciones y beneficios. ¿Te interesa algún perfil en particular?'),
                'suggestions' => [
                    ['action' => 'view_plans', 'label' => $this->t('Ver planes')],
                    ['action' => 'register', 'label' => $this->t('Crear cuenta gratis')],
                ],
            ];
        }

        if (str_contains($messageLower, 'empleo') || str_contains($messageLower, 'trabajo') || str_contains($messageLower, 'cv')) {
            return [
                'text' => $this->t('¡Genial! En Jaraba puedes crear tu perfil profesional, recibir recomendaciones de empleo con matching inteligente, y acceder a formación subvencionada. El primer paso es registrarte y completar tu perfil.'),
                'suggestions' => [
                    ['action' => 'register_jobseeker', 'label' => $this->t('Registrarme como Candidato')],
                    ['action' => 'explore_jobs', 'label' => $this->t('Ver ofertas')],
                ],
            ];
        }

        if (str_contains($messageLower, 'emprender') || str_contains($messageLower, 'negocio') || str_contains($messageLower, 'idea')) {
            return [
                'text' => $this->t('¡Excelente! Nuestro vertical de Emprendimiento te ofrece un Copiloto IA que te guía paso a paso, herramientas como Business Model Canvas, mentores certificados, y acceso a marketplace. Empezamos con un diagnóstico rápido (DIME) para personalizar tu experiencia.'),
                'suggestions' => [
                    ['action' => 'register_entrepreneur', 'label' => $this->t('Empezar como Emprendedor')],
                    ['action' => 'learn_more', 'label' => $this->t('Saber más')],
                ],
            ];
        }

        if (str_contains($messageLower, 'vender') || str_contains($messageLower, 'producto') || str_contains($messageLower, 'productor')) {
            return [
                'text' => $this->t('Como Productor puedes publicar tus productos y servicios en nuestro Marketplace, conectar con emprendedores que necesitan tus soluciones, y gestionar todo desde un panel centralizado.'),
                'suggestions' => [
                    ['action' => 'register_producer', 'label' => $this->t('Registrarme como Productor')],
                    ['action' => 'view_marketplace', 'label' => $this->t('Explorar Marketplace')],
                ],
            ];
        }

        // Respuesta genérica mejorada para todos los tipos
        return [
            'text' => $this->t('¡Hola! Soy el asistente de Jaraba Impact Platform. Puedo ayudarte a entender cómo funciona nuestra plataforma. ¿Eres un particular buscando empleo o emprendimiento, o representas a una institución u organización con programas de formación?'),
            'suggestions' => $this->getDefaultSuggestions(),
        ];
    }

    /**
     * Obtiene sugerencias por defecto para usuarios anónimos.
     * 
     * SUGERENCIAS PREMIUM: Orientadas a conversión y demos.
     */
    protected function getDefaultSuggestions(): array
    {
        return [
            ['label' => (string) $this->t('Ver demo: Buscar empleo con IA'), 'url' => '/empleo'],
            ['label' => (string) $this->t('Ver demo: Validar mi idea de negocio'), 'url' => '/emprender'],
            ['label' => (string) $this->t('Crear cuenta gratis'), 'url' => '/user/register'],
            ['label' => (string) $this->t('Soy una organización'), 'url' => '/contacto'],
        ];
    }

    /**
     * Extrae sugerencias de la respuesta RAG.
     */
    protected function extractSuggestions(array $ragResult): array
    {
        // Si RAG incluye acciones sugeridas, usarlas
        if (!empty($ragResult['suggested_actions'])) {
            return $ragResult['suggested_actions'];
        }

        return $this->getDefaultSuggestions();
    }

    /**
     * Guarda feedback del usuario para aprendizaje del sistema.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con datos del feedback.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Confirmación de guardado.
     */
    public function saveFeedback(Request $request): JsonResponse
    {
        // AUDIT-SEC-N15: Rate limiting en feedback para prevenir spam/flood.
        $clientIp = $request->getClientIp();
        if (!$this->flood->isAllowed('copilot_feedback', 20, 60, $clientIp)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Demasiadas solicitudes. Inténtalo de nuevo en un minuto.'),
            ], 429);
        }
        $this->flood->register('copilot_feedback', 60, $clientIp);

        $content = json_decode($request->getContent(), TRUE);

        $rating = $content['rating'] ?? NULL; // 'up' o 'down'
        $messageId = $content['message_id'] ?? NULL;
        $userMessage = $content['user_message'] ?? '';
        $assistantResponse = $content['assistant_response'] ?? '';
        $context = $content['context'] ?? [];

        if (!$rating) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Rating no especificado.'),
            ], 400);
        }

        // Registrar feedback en log para análisis posterior
        \Drupal::logger('jaraba_copilot_v2')->info(
            'Copilot feedback: @rating | User: @user_msg | Response: @response | Context: @context',
            [
                '@rating' => $rating,
                '@user_msg' => mb_substr($userMessage, 0, 200),
                '@response' => mb_substr($assistantResponse, 0, 200),
                '@context' => json_encode($context),
            ]
        );

        // Guardar en base de datos para análisis de mejora
        try {
            $connection = \Drupal::database();
            $connection->insert('jaraba_copilot_feedback')
                ->fields([
                    'rating' => $rating === 'up' ? 1 : 0,
                    'user_message' => mb_substr($userMessage, 0, 500),
                    'assistant_response' => mb_substr($assistantResponse, 0, 2000),
                    'context_data' => json_encode($context),
                    'ip_hash' => hash('sha256', $request->getClientIp()),
                    'created' => \Drupal::time()->getRequestTime(),
                ])
                ->execute();
        } catch (\Exception $e) {
            // Si la tabla no existe, solo logueamos (no bloqueamos UX)
            \Drupal::logger('jaraba_copilot_v2')->warning(
                'Could not save feedback to DB: @error',
                ['@error' => $e->getMessage()]
            );
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => $this->t('¡Gracias por tu feedback!'),
        ]);
    }

}
