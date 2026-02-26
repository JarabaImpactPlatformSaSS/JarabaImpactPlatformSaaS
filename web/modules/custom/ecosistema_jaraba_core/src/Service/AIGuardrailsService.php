<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de guardrails para prompts de IA.
 *
 * PROPÓSITO:
 * Implementa validación y sanitización bidireccional de prompts IA:
 * - INPUT: Valida prompts de usuario (PII, jailbreak, rate limit)
 * - INTERMEDIATE: Sanitiza contenido RAG y tool outputs (prompt injection indirecto)
 * - OUTPUT: Enmascara PII en respuestas del LLM
 *
 * GAP-03: Defensa contra prompt injection indirecto — contenido malicioso
 * embebido en documentos RAG o resultados de tools puede manipular al LLM.
 * sanitizeRagContent() y sanitizeToolOutput() neutralizan instrucciones
 * embebidas ANTES de inyectarlas en el system prompt.
 *
 * @see JAILBREAK-DETECT-001
 * @see AI-GUARDRAILS-PII-001
 * @see OUTPUT-PII-MASK-001
 */
class AIGuardrailsService
{

    /**
     * Acciones de guardrail.
     */
    public const ACTION_ALLOW = 'allow';
    public const ACTION_MODIFY = 'modify';
    public const ACTION_BLOCK = 'block';
    public const ACTION_FLAG = 'flag';

    /**
     * Patrones prohibidos.
     */
    protected const BLOCKED_PATTERNS = [
        // Inyección de prompts.
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/disregard\s+(all\s+)?above/i',
        '/forget\s+(everything|all)/i',
        '/you\s+are\s+now\s+a/i',
        '/pretend\s+you\s+are/i',
        '/act\s+as\s+if/i',
        // Contenido malicioso.
        '/generate\s+(malware|virus|hack)/i',
        '/create\s+(fake|fraudulent)/i',
        // PII sensible.
        '/\b\d{3}-\d{2}-\d{4}\b/', // SSN
        '/\b\d{16}\b/', // Credit card
        // FIX-028: PII españoles en blocked patterns (alto riesgo).
        '/\bES\d{2}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{2}[\s-]?\d{10}\b/', // IBAN ES
    ];

    /**
     * Límites de tokens/caracteres.
     */
    protected const LIMITS = [
        'max_prompt_length' => 10000,
        'max_tokens_estimate' => 4000,
        'min_prompt_length' => 3,
    ];

    /**
     * Patrones de prompt injection indirecto (GAP-03).
     *
     * Detectan instrucciones maliciosas embebidas en contenido recuperado via
     * RAG (chunks de documentos) o resultados de tools. Bilingues ES/EN.
     *
     * Diferencia con BLOCKED_PATTERNS y checkJailbreak():
     * - BLOCKED_PATTERNS / checkJailbreak() se aplican al INPUT del usuario → BLOCK
     * - INDIRECT_INJECTION_PATTERNS se aplican a contenido INTERMEDIO → SANITIZE
     *
     * La accion es SANITIZE (neutralizar, no bloquear), porque el contenido
     * proviene de documentos legitimos que pueden contener frases coincidentes.
     */
    protected const INDIRECT_INJECTION_PATTERNS = [
        // EN: Override de instrucciones del sistema.
        '/ignore\s+(?:all\s+)?(?:previous|above|prior|earlier)\s+(?:instructions|rules|constraints|guidelines)/i',
        '/(?:new|updated|revised)\s+(?:instructions?|rules?|objective|task|goal)\s*:/i',
        '/(?:system|admin|root|sudo)\s+(?:override|access|command|mode)/i',
        '/from\s+(?:now|this\s+point)\s+(?:on|forward),?\s+(?:you|ignore)/i',
        '/(?:forget|disregard)\s+(?:everything|all|the\s+above)/i',
        // EN: Inyeccion de rol/identidad.
        '/(?:you\s+are|become|switch\s+to)\s+(?:now|a|an|the)\s+(?:different|new|evil|unrestricted)/i',
        '/(?:enter|activate|enable)\s+(?:DAN|jailbreak|unrestricted|developer)\s+mode/i',
        // EN: Extraccion de prompt del sistema.
        '/(?:output|print|display|show|repeat|reveal)\s+(?:your|the|all)\s+(?:system|initial|original|hidden)\s+(?:prompt|instructions|message)/i',
        // EN: XML/Markdown injection (delimitadores de prompt).
        '/<\/?(?:system|instruction|command|override|admin|prompt|rules)>/i',
        '/```(?:system|prompt|instruction|override|admin)/i',
        '/\[(?:SYSTEM|INST|ADMIN)\]/i',
        // ES: Override de instrucciones.
        '/(?:ignora|olvida|descarta)\s+(?:todas?\s+)?(?:las\s+)?(?:instrucciones|reglas|anteriores|previas|de\s+arriba)/i',
        '/(?:nuevas?|actualizadas?)\s+(?:instrucciones|reglas|objetivo)\s*:/i',
        '/a\s+partir\s+de\s+ahora,?\s+(?:ignora|eres|cambia|actua)/i',
        // ES: Inyeccion de rol/identidad.
        '/(?:ahora\s+)?(?:eres|conviertete\s+en|actua\s+como)\s+(?:un|una|el|la)\s+(?:diferente|nuevo|malvado|libre)/i',
        '/(?:activa|entra\s+en)\s+modo\s+(?:DAN|libre|sin\s+restricciones|desarrollador)/i',
        // ES: Extraccion de prompt.
        '/(?:muestra|repite|revela|imprime)\s+(?:tu|el|las)\s+(?:prompt|instrucciones|mensaje)\s+(?:de\s+sistema|inicial|original|oculto)/i',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected ?ConfigFactoryInterface $configFactory = NULL,
        protected ?LoggerInterface $logger = NULL,
    ) {
    }

    /**
     * Valida un prompt y devuelve resultado de guardrail.
     */
    public function validate(string $prompt, array $context = []): array
    {
        $result = [
            'original_prompt' => $prompt,
            'processed_prompt' => $prompt,
            'action' => self::ACTION_ALLOW,
            'violations' => [],
            'warnings' => [],
            'score' => 100,
        ];

        // Check 1: Longitud.
        $lengthCheck = $this->checkLength($prompt);
        if ($lengthCheck['violation']) {
            $result['violations'][] = $lengthCheck;
            $result['action'] = self::ACTION_BLOCK;
            $result['score'] -= 50;
        }

        // Check 2: Patrones prohibidos.
        $patternCheck = $this->checkBlockedPatterns($prompt);
        if (!empty($patternCheck['matches'])) {
            $result['violations'][] = $patternCheck;
            $result['action'] = self::ACTION_BLOCK;
            $result['score'] -= 100;
        }

        // Check 3: PII.
        $piiCheck = $this->checkPII($prompt);
        if ($piiCheck['found']) {
            $result['warnings'][] = $piiCheck;
            $result['processed_prompt'] = $piiCheck['sanitized'];
            if ($result['action'] === self::ACTION_ALLOW) {
                $result['action'] = self::ACTION_MODIFY;
            }
            $result['score'] -= 20;
        }

        // Check 4: Rate limiting por usuario/tenant.
        $rateLimitCheck = $this->checkRateLimit($context);
        if ($rateLimitCheck['exceeded']) {
            $result['violations'][] = $rateLimitCheck;
            $result['action'] = self::ACTION_BLOCK;
            $result['score'] -= 30;
        }

        // Check 5: Contenido sospechoso (heurístico).
        $suspiciousCheck = $this->checkSuspiciousContent($prompt);
        if ($suspiciousCheck['suspicious']) {
            $result['warnings'][] = $suspiciousCheck;
            if ($result['action'] === self::ACTION_ALLOW) {
                $result['action'] = self::ACTION_FLAG;
            }
            $result['score'] -= 10;
        }

        // FIX-043: Jailbreak detection.
        $jailbreakCheck = $this->checkJailbreak($prompt);
        if ($jailbreakCheck['detected']) {
            $result['violations'][] = $jailbreakCheck;
            $result['action'] = self::ACTION_BLOCK;
            $result['score'] -= 100;
        }

        $result['score'] = max(0, $result['score']);

        // Logging.
        $this->logValidation($result, $context);

        return $result;
    }

    /**
     * Verifica longitud del prompt.
     */
    protected function checkLength(string $prompt): array
    {
        $length = strlen($prompt);

        if ($length < self::LIMITS['min_prompt_length']) {
            return [
                'type' => 'length',
                'violation' => TRUE,
                'message' => 'Prompt too short',
                'details' => ['length' => $length, 'min' => self::LIMITS['min_prompt_length']],
            ];
        }

        if ($length > self::LIMITS['max_prompt_length']) {
            return [
                'type' => 'length',
                'violation' => TRUE,
                'message' => 'Prompt too long',
                'details' => ['length' => $length, 'max' => self::LIMITS['max_prompt_length']],
            ];
        }

        return ['type' => 'length', 'violation' => FALSE];
    }

    /**
     * Verifica patrones bloqueados.
     */
    protected function checkBlockedPatterns(string $prompt): array
    {
        $matches = [];

        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $prompt, $m)) {
                $matches[] = [
                    'pattern' => $pattern,
                    'match' => $m[0],
                ];
            }
        }

        return [
            'type' => 'blocked_pattern',
            'matches' => $matches,
            'message' => empty($matches) ? NULL : 'Blocked patterns detected',
        ];
    }

    /**
     * Detecta y sanitiza PII.
     */
    protected function checkPII(string $prompt): array
    {
        $piiPatterns = [
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            'phone' => '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
            'ssn' => '/\b\d{3}-\d{2}-\d{4}\b/',
            'credit_card' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
            // FIX-028: PII españoles.
            'dni' => '/\b\d{8}[A-Za-z]\b/',
            'nie' => '/\b[XYZxyz]\d{7}[A-Za-z]\b/',
            'iban_es' => '/\bES\d{2}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{2}[\s-]?\d{10}\b/',
            'nif_cif' => '/\b[A-HJ-NP-SUVW]\d{7}[A-J0-9]\b/',
            'phone_es' => '/\b(?:\+34|0034)[\s-]?\d{9}\b/',
        ];

        $found = [];
        $sanitized = $prompt;

        foreach ($piiPatterns as $type => $pattern) {
            if (preg_match_all($pattern, $prompt, $matches)) {
                $found[$type] = count($matches[0]);
                // Sanitizar reemplazando con placeholders.
                $sanitized = preg_replace($pattern, '[REDACTED_' . strtoupper($type) . ']', $sanitized);
            }
        }

        return [
            'type' => 'pii',
            'found' => !empty($found),
            'details' => $found,
            'sanitized' => $sanitized,
        ];
    }

    /**
     * Verifica rate limiting.
     */
    protected function checkRateLimit(array $context): array
    {
        $tenantId = $context['tenant_id'] ?? 'anonymous';
        $userId = $context['user_id'] ?? 0;

        // Límite: 100 requests por hora por tenant.
        $oneHourAgo = time() - 3600;

        $count = $this->database->select('ai_guardrail_logs', 'gl')
            ->condition('tenant_id', $tenantId)
            ->condition('created', $oneHourAgo, '>')
            ->countQuery()
            ->execute()
            ->fetchField();

        $limit = $context['rate_limit'] ?? 100;

        return [
            'type' => 'rate_limit',
            'exceeded' => $count >= $limit,
            'current' => (int) $count,
            'limit' => $limit,
        ];
    }

    /**
     * Detecta contenido sospechoso.
     */
    protected function checkSuspiciousContent(string $prompt): array
    {
        $suspiciousIndicators = 0;

        // Demasiados caracteres especiales.
        $specialCharRatio = preg_match_all('/[^a-zA-Z0-9\s]/', $prompt) / max(1, strlen($prompt));
        if ($specialCharRatio > 0.3) {
            $suspiciousIndicators++;
        }

        // Muchas repeticiones.
        if (preg_match('/(.)\1{10,}/', $prompt)) {
            $suspiciousIndicators++;
        }

        // Muchos saltos de línea.
        if (substr_count($prompt, "\n") > 50) {
            $suspiciousIndicators++;
        }

        return [
            'type' => 'suspicious',
            'suspicious' => $suspiciousIndicators >= 2,
            'indicators' => $suspiciousIndicators,
        ];
    }

    /**
     * Registra validación para auditoría.
     */
    protected function logValidation(array $result, array $context): void
    {
        $this->database->insert('ai_guardrail_logs')
            ->fields([
                    'tenant_id' => $context['tenant_id'] ?? 'anonymous',
                    'user_id' => $context['user_id'] ?? 0,
                    'action' => $result['action'],
                    'score' => $result['score'],
                    'violations_count' => count($result['violations']),
                    'details' => json_encode([
                        'violations' => $result['violations'],
                        'warnings' => $result['warnings'],
                    ]),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene estadísticas de guardrails.
     */
    public function getStats(?string $tenantId = NULL, int $days = 7): array
    {
        $since = time() - ($days * 24 * 60 * 60);

        $query = $this->database->select('ai_guardrail_logs', 'gl')
            ->fields('gl', ['action'])
            ->condition('created', $since, '>');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $query->addExpression('COUNT(*)', 'count');
        $query->groupBy('action');

        $results = $query->execute()->fetchAllKeyed();

        $total = array_sum($results);

        return [
            'period_days' => $days,
            'total_requests' => $total,
            'by_action' => $results,
            'block_rate' => $total > 0 ? round(($results['block'] ?? 0) / $total * 100, 2) : 0,
            'avg_score' => $this->getAverageScore($tenantId, $since),
        ];
    }

    /**
     * Obtiene score promedio.
     */
    protected function getAverageScore(?string $tenantId, int $since): float
    {
        $query = $this->database->select('ai_guardrail_logs', 'gl')
            ->condition('created', $since, '>');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $query->addExpression('AVG(score)', 'avg');
        $result = $query->execute()->fetchField();

        return round((float) $result, 1);
    }

    /**
     * Detects jailbreak and prompt injection attempts (FIX-043).
     *
     * Bilingual (ES/EN) pattern detection for:
     * - Prompt injection ("ignore previous", "you are now")
     * - Role-play attacks ("DAN mode", "pretend you are")
     * - System prompt extraction ("repeat your instructions")
     * - Identity manipulation ("eres ChatGPT", "act as GPT")
     *
     * @param string $prompt
     *   The user prompt.
     *
     * @return array
     *   Result with detected flag and matched patterns.
     */
    protected function checkJailbreak(string $prompt): array
    {
        $jailbreakPatterns = [
            // EN: Prompt injection.
            '/ignore\s+(all\s+)?(previous|prior|above)\s+(instructions|rules|prompts)/i',
            '/disregard\s+(all\s+)?(your|the|above|previous)\s+(instructions|rules|guidelines)/i',
            '/override\s+(your|all|the)\s+(instructions|rules|safety)/i',
            // EN: Role-play attacks.
            '/you\s+are\s+now\s+(a|an|the|DAN|evil|unrestricted)/i',
            '/pretend\s+(you\s+are|to\s+be)\s+(a|an|the|DAN)/i',
            '/DAN\s+mode/i',
            '/jailbreak/i',
            '/\bDAN\b/',
            // EN: System prompt extraction.
            '/repeat\s+(your|the|all)\s+(instructions|system\s+prompt|rules)/i',
            '/show\s+(me\s+)?(your|the)\s+(system|initial)\s+(prompt|message|instructions)/i',
            '/what\s+(are|were)\s+your\s+(initial|original|system)\s+(instructions|prompt)/i',
            // EN: Identity manipulation.
            '/you\s+are\s+(ChatGPT|GPT|Claude|Gemini|Llama|Mistral)/i',
            '/act\s+as\s+(ChatGPT|GPT|Claude|an?\s+AI\s+without\s+restrictions)/i',
            // ES: Inyeccion de prompts.
            '/ignora\s+(todas?\s+)?(las\s+)?(instrucciones|reglas)\s+(anteriores|previas)/i',
            '/olvida\s+(todas?\s+)?(las\s+)?(instrucciones|reglas|lo\s+anterior)/i',
            '/no\s+sigas\s+(las|tus)\s+(instrucciones|reglas)/i',
            // ES: Ataques de rol.
            '/ahora\s+eres\s+(un|una|el|la|DAN)/i',
            '/finge\s+(que\s+)?(eres|ser)\s+(un|una|DAN|libre)/i',
            '/modo\s+(DAN|sin\s+restricciones|libre)/i',
            // ES: Extraccion de prompt.
            '/repite\s+(tu|el)\s+(prompt|mensaje)\s+(de\s+sistema|inicial)/i',
            '/muestra\s+(tu|el)\s+prompt\s+(de\s+sistema|inicial|original)/i',
            // ES: Manipulacion de identidad.
            '/eres\s+(ChatGPT|GPT|Claude|Gemini|Llama|Mistral)/i',
            '/actua\s+como\s+(ChatGPT|GPT|Claude|Gemini|una?\s+IA\s+sin\s+restricciones)/i',
        ];

        $matches = [];

        foreach ($jailbreakPatterns as $pattern) {
            if (preg_match($pattern, $prompt, $m)) {
                $matches[] = [
                    'pattern' => $pattern,
                    'match' => $m[0],
                ];
            }
        }

        return [
            'type' => 'jailbreak',
            'detected' => !empty($matches),
            'matches' => $matches,
            'message' => empty($matches) ? NULL : 'Jailbreak/prompt injection attempt detected.',
        ];
    }

    /**
     * Masks PII in LLM output text (FIX-044).
     *
     * Scans the LLM response and replaces detected PII with
     * [DATO PROTEGIDO] placeholder. Reuses the same PII patterns
     * from checkPII().
     *
     * @param string $text
     *   The LLM output text.
     *
     * @return string
     *   Text with PII masked.
     */
    public function maskOutputPII(string $text): string
    {
        $piiPatterns = [
            'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
            'phone_us' => '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
            'ssn' => '/\b\d{3}-\d{2}-\d{4}\b/',
            'credit_card' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
            // Spanish PII.
            'dni' => '/\b\d{8}[A-Za-z]\b/',
            'nie' => '/\b[XYZxyz]\d{7}[A-Za-z]\b/',
            'iban_es' => '/\bES\d{2}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{2}[\s-]?\d{10}\b/',
            'nif_cif' => '/\b[A-HJ-NP-SUVW]\d{7}[A-J0-9]\b/',
            'phone_es' => '/\b(?:\+34|0034)[\s-]?\d{9}\b/',
        ];

        $masked = $text;
        foreach ($piiPatterns as $pattern) {
            $masked = preg_replace($pattern, '[DATO PROTEGIDO]', $masked);
        }

        return $masked;
    }

    /**
     * Sanitiza contenido RAG antes de inyectarlo en el system prompt (GAP-03).
     *
     * Escanea cada chunk de documento recuperado de Qdrant buscando patrones
     * de prompt injection indirecto. A diferencia de validate() que BLOQUEA
     * input del usuario, este metodo NEUTRALIZA el contenido sospechoso
     * reemplazandolo con un placeholder, ya que los documentos pueden contener
     * coincidencias parciales legitimas.
     *
     * Cada chunk sanitizado conserva su estructura (title, url, score, etc.)
     * — solo se modifica chunk_text y se marca is_sanitized=TRUE.
     *
     * @param array $chunks
     *   Array de chunks RAG. Cada chunk es un array asociativo con al menos
     *   'chunk_text' (string). Estructura tipica:
     *   - chunk_text: Texto del fragmento del documento.
     *   - title: Titulo del documento.
     *   - url: URL del documento.
     *   - score: Puntuacion de relevancia.
     *
     * @return array
     *   Los mismos chunks con chunk_text sanitizado donde se detecto injection.
     *
     * @see JAILBREAK-DETECT-001
     * @see AI-GUARDRAILS-PII-001
     */
    public function sanitizeRagContent(array $chunks): array
    {
        $sanitizedChunks = [];

        foreach ($chunks as $chunk) {
            $text = $chunk['chunk_text'] ?? '';

            if (empty($text)) {
                $sanitizedChunks[] = $chunk;
                continue;
            }

            $detections = $this->scanForIndirectInjection($text);

            if (!empty($detections)) {
                // Neutralizar cada patron encontrado.
                $sanitizedText = $text;
                foreach ($detections as $detection) {
                    $sanitizedText = preg_replace(
                        $detection['pattern'],
                        '[CONTENIDO NEUTRALIZADO POR GUARDRAILS]',
                        $sanitizedText
                    );
                }

                $chunk['chunk_text'] = $sanitizedText;
                $chunk['is_sanitized'] = TRUE;
                $chunk['sanitization_count'] = count($detections);

                $this->logIndirectInjection('rag_content', $detections, $chunk['title'] ?? 'unknown');
            }

            // Adicionalmente, enmascarar PII en el contenido RAG.
            $chunk['chunk_text'] = $this->maskOutputPII($chunk['chunk_text']);

            $sanitizedChunks[] = $chunk;
        }

        return $sanitizedChunks;
    }

    /**
     * Sanitiza el output de un tool antes de inyectarlo en el prompt (GAP-03).
     *
     * Los resultados de herramientas (ToolRegistry::execute()) pueden contener
     * datos de fuentes externas (APIs, base de datos, busquedas web) que podrian
     * incluir instrucciones de prompt injection embebidas.
     *
     * @param string $toolOutput
     *   El resultado del tool como string (JSON serializado o texto plano).
     *
     * @return string
     *   El resultado sanitizado.
     *
     * @see TOOL-USE-AGENT-001
     */
    public function sanitizeToolOutput(string $toolOutput): string
    {
        if (empty($toolOutput)) {
            return $toolOutput;
        }

        $detections = $this->scanForIndirectInjection($toolOutput);

        $sanitized = $toolOutput;
        if (!empty($detections)) {
            foreach ($detections as $detection) {
                $sanitized = preg_replace(
                    $detection['pattern'],
                    '[CONTENIDO NEUTRALIZADO POR GUARDRAILS]',
                    $sanitized
                );
            }

            $this->logIndirectInjection('tool_output', $detections);
        }

        // Enmascarar PII tambien en outputs de tools.
        return $this->maskOutputPII($sanitized);
    }

    /**
     * Escanea texto buscando patrones de prompt injection indirecto.
     *
     * @param string $text
     *   Texto a escanear (chunk RAG, tool output, memoria de agente).
     *
     * @return array
     *   Array de detecciones, cada una con 'pattern', 'match', 'severity'.
     */
    protected function scanForIndirectInjection(string $text): array
    {
        $detections = [];

        foreach (self::INDIRECT_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $detections[] = [
                    'pattern' => $pattern,
                    'match' => $matches[0],
                    'severity' => $this->classifyInjectionSeverity($matches[0]),
                ];
            }
        }

        return $detections;
    }

    /**
     * Clasifica la severidad de una deteccion de injection indirecto.
     *
     * @param string $match
     *   El texto coincidente.
     *
     * @return string
     *   'critical', 'high', o 'medium'.
     */
    protected function classifyInjectionSeverity(string $match): string
    {
        $loweredMatch = mb_strtolower($match);

        // Critico: intentos directos de override del sistema.
        if (str_contains($loweredMatch, 'system') || str_contains($loweredMatch, 'admin')
            || str_contains($loweredMatch, 'override') || str_contains($loweredMatch, 'sudo')) {
            return 'critical';
        }

        // Alto: inyeccion de instrucciones o identidad.
        if (str_contains($loweredMatch, 'instrucciones') || str_contains($loweredMatch, 'instructions')
            || str_contains($loweredMatch, 'eres') || str_contains($loweredMatch, 'you are')) {
            return 'high';
        }

        return 'medium';
    }

    /**
     * Loguea un intento de prompt injection indirecto.
     *
     * @param string $source
     *   Origen: 'rag_content', 'tool_output', 'agent_memory'.
     * @param array $detections
     *   Array de detecciones.
     * @param string $contextLabel
     *   Etiqueta de contexto (titulo del documento, tool_id, etc.).
     */
    protected function logIndirectInjection(string $source, array $detections, string $contextLabel = ''): void
    {
        if ($this->logger) {
            $this->logger->warning('Indirect prompt injection detected in @source: @count pattern(s). Context: @context. Matches: @matches', [
                '@source' => $source,
                '@count' => count($detections),
                '@context' => $contextLabel ?: 'N/A',
                '@matches' => implode(', ', array_column($detections, 'match')),
            ]);
        }

        // Registrar en tabla de guardrail logs para auditoria.
        try {
            $this->database->insert('ai_guardrail_logs')
                ->fields([
                    'tenant_id' => 'system',
                    'user_id' => 0,
                    'action' => self::ACTION_MODIFY,
                    'score' => 50,
                    'violations_count' => count($detections),
                    'details' => json_encode([
                        'type' => 'indirect_injection',
                        'source' => $source,
                        'context' => $contextLabel,
                        'detections' => $detections,
                    ]),
                    'created' => time(),
                ])
                ->execute();
        } catch (\Exception $e) {
            // No bloquear por fallo de logging.
        }
    }

}
