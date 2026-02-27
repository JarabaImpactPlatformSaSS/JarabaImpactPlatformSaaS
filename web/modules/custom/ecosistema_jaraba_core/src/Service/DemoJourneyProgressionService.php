<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Servicio de progresión de journey para conversión demo → registro.
 *
 * S4-04: Evalúa el estado del usuario en la demo y genera nudges
 * proactivos para guiar la conversión a cuenta real.
 *
 * Las reglas se evalúan por prioridad (0 = máxima) y los nudges
 * descartados no vuelven a mostrarse en la misma sesión.
 *
 * Q1 2027 - Sprint 4: Elevación y Page Builder
 */
class DemoJourneyProgressionService
{

    use StringTranslationTrait;

    /**
     * Reglas proactivas de conversión ordenadas por prioridad.
     */
    protected const PROACTIVE_RULES = [
        'first_value_reached' => [
            'state' => 'activation',
            'condition' => 'ttfv_calculated',
            'priority' => 1,
            'channel' => 'fab_expand',
        ],
        'ai_story_generated' => [
            'state' => 'engagement',
            'condition' => 'story_generated',
            'priority' => 2,
            'channel' => 'fab_dot',
        ],
        'session_expiring' => [
            'state' => 'conversion',
            'condition' => 'session_75_pct_elapsed',
            'priority' => 0,
            'channel' => 'fab_expand',
        ],
        'multiple_actions' => [
            'state' => 'engagement',
            'condition' => 'actions_count_gte_5',
            'priority' => 3,
            'channel' => 'fab_dot',
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * S7-07: Disclosure levels para desbloqueo progresivo de funciones.
     *
     * Nivel 1 (initial): Dashboard + métricas.
     * Nivel 2 (1 acción): Productos + AI storytelling.
     * Nivel 3 (3 acciones): AI playground.
     * Nivel 4 (TTFV reached): Full features + prompt de conversión.
     */
    protected const DISCLOSURE_LEVELS = [
        1 => ['dashboard', 'metrics'],
        2 => ['products', 'storytelling'],
        3 => ['ai_playground'],
        4 => ['full_features', 'conversion_prompt'],
    ];

    /**
     * Calcula el nivel de disclosure actual de una sesión.
     *
     * S7-07: Progressive disclosure — desbloqueo gradual.
     *
     * @return array{level: int, unlocked: string[], next_actions_needed: int}
     */
    public function getDisclosureLevel(string $sessionId): array
    {
        $session = $this->getSessionData($sessionId);
        if (!$session) {
            return ['level' => 1, 'unlocked' => self::DISCLOSURE_LEVELS[1], 'next_actions_needed' => 1];
        }

        $actionCount = count($session['actions'] ?? []);
        $hasTtfv = !empty($session['ttfv_seconds']);

        if ($hasTtfv || $actionCount >= 5) {
            $level = 4;
            $actionsNeeded = 0;
        }
        elseif ($actionCount >= 3) {
            $level = 3;
            $actionsNeeded = 5 - $actionCount;
        }
        elseif ($actionCount >= 1) {
            $level = 2;
            $actionsNeeded = 3 - $actionCount;
        }
        else {
            $level = 1;
            $actionsNeeded = 1;
        }

        // Acumular secciones desbloqueadas.
        $unlocked = [];
        for ($i = 1; $i <= $level; $i++) {
            $unlocked = array_merge($unlocked, self::DISCLOSURE_LEVELS[$i]);
        }

        return [
            'level' => $level,
            'unlocked' => $unlocked,
            'next_actions_needed' => $actionsNeeded,
        ];
    }

    /**
     * Evalúa las reglas de nudge y devuelve los aplicables.
     *
     * @param string $sessionId
     *   ID de la sesión demo.
     *
     * @return array
     *   Array de nudges aplicables, ordenados por prioridad.
     */
    public function evaluateNudges(string $sessionId): array
    {
        $session = $this->getSessionData($sessionId);
        if (!$session) {
            return [];
        }

        $nudges = [];
        $dismissedNudges = $session['dismissed_nudges'] ?? [];

        foreach (self::PROACTIVE_RULES as $ruleId => $rule) {
            // Saltar nudges ya descartados.
            if (in_array($ruleId, $dismissedNudges, TRUE)) {
                continue;
            }

            // Evaluar condición.
            if (!$this->evaluateCondition($rule['condition'], $session)) {
                continue;
            }

            $nudges[] = [
                'id' => $ruleId,
                'state' => $rule['state'],
                'message' => $this->getNudgeMessage($ruleId),
                'cta_label' => $this->getNudgeCta($ruleId),
                'cta_url' => $this->getNudgeUrl($session),
                'channel' => $rule['channel'],
                'priority' => $rule['priority'],
            ];
        }

        // Ordenar por prioridad (0 = máxima).
        usort($nudges, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $nudges;
    }

    /**
     * Descarta un nudge para una sesión.
     *
     * @param string $sessionId
     *   ID de la sesión demo.
     * @param string $nudgeId
     *   ID de la regla de nudge a descartar.
     */
    public function dismissNudge(string $sessionId, string $nudgeId): void
    {
        // S8-03: Validar que el nudgeId existe en las reglas conocidas.
        if (!isset(self::PROACTIVE_RULES[$nudgeId])) {
            return;
        }

        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data'])
                ->condition('session_id', $sessionId)
                ->execute()
                ->fetchField();

            if (!$row) {
                return;
            }

            $data = json_decode($row, TRUE) ?: [];
            $data['dismissed_nudges'][] = $nudgeId;
            $data['dismissed_nudges'] = array_unique($data['dismissed_nudges']);

            $this->database->update('demo_sessions')
                ->fields(['session_data' => json_encode($data, JSON_UNESCAPED_UNICODE)])
                ->condition('session_id', $sessionId)
                ->execute();
        }
        catch (\Exception $e) {
            // Silencioso.
        }
    }

    /**
     * Evalúa una condición contra los datos de sesión.
     */
    protected function evaluateCondition(string $condition, array $session): bool
    {
        return match ($condition) {
            'ttfv_calculated' => !empty($session['ttfv_seconds']),
            'story_generated' => $this->hasAction($session, 'generate_story'),
            'session_75_pct_elapsed' => $this->isSessionExpiring($session),
            'actions_count_gte_5' => count($session['actions'] ?? []) >= 5,
            default => FALSE,
        };
    }

    /**
     * Comprueba si se ha realizado una acción específica.
     */
    protected function hasAction(array $session, string $actionId): bool
    {
        foreach ($session['actions'] ?? [] as $action) {
            if (($action['action'] ?? '') === $actionId) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Comprueba si la sesión ha consumido >= 75% de su TTL.
     */
    protected function isSessionExpiring(array $session): bool
    {
        $created = $session['created'] ?? 0;
        $expires = $session['expires'] ?? 0;
        if ($created === 0 || $expires === 0) {
            return FALSE;
        }

        $totalTtl = $expires - $created;
        $elapsed = time() - $created;

        return $totalTtl > 0 && ($elapsed / $totalTtl) >= 0.75;
    }

    /**
     * Obtiene datos de sesión de la base de datos.
     */
    protected function getSessionData(string $sessionId): ?array
    {
        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data'])
                ->condition('session_id', $sessionId)
                ->execute()
                ->fetchField();

            return $row ? (json_decode($row, TRUE) ?: NULL) : NULL;
        }
        catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Obtiene el mensaje traducido del nudge.
     */
    protected function getNudgeMessage(string $ruleId): string
    {
        return match ($ruleId) {
            'first_value_reached' => (string) $this->t('Has descubierto el valor de la plataforma. Crea tu cuenta gratis.'),
            'ai_story_generated' => (string) $this->t('La IA puede generar contenido personalizado para tu negocio real.'),
            'session_expiring' => (string) $this->t('Tu sesión demo expira pronto. Guarda tu progreso creando una cuenta.'),
            'multiple_actions' => (string) $this->t('Estás aprovechando la demo al máximo. Imagina lo que puedes hacer con una cuenta real.'),
            default => (string) $this->t('Crea tu cuenta para acceder a todas las funcionalidades.'),
        };
    }

    /**
     * Obtiene la etiqueta CTA del nudge.
     */
    protected function getNudgeCta(string $ruleId): string
    {
        return match ($ruleId) {
            'first_value_reached' => (string) $this->t('Crear cuenta'),
            'ai_story_generated' => (string) $this->t('Probarlo con mis datos'),
            'session_expiring' => (string) $this->t('Guardar y registrarme'),
            'multiple_actions' => (string) $this->t('Crear cuenta gratis'),
            default => (string) $this->t('Registrarme'),
        };
    }

    /**
     * Genera URL de registro basada en el vertical de la sesión.
     *
     * ROUTE-LANGPREFIX-001: Generada via Url::fromRoute().
     */
    protected function getNudgeUrl(array $session): string
    {
        $vertical = $session['profile']['vertical'] ?? 'emprendimiento';
        return Url::fromRoute('ecosistema_jaraba_core.onboarding.register', [
            'vertical' => $vertical,
        ])->toString();
    }

}
