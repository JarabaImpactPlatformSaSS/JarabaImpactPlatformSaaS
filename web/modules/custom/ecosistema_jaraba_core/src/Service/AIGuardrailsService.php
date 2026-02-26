<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;

/**
 * Servicio de guardrails para prompts de IA.
 *
 * PROPÓSITO:
 * Implementa validación y sanitización de prompts antes de enviarlos
 * a los modelos de IA, previniendo abusos y asegurando calidad.
 *
 * Q3 2026 - Sprint 9-10: AI Operations
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
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
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

}
