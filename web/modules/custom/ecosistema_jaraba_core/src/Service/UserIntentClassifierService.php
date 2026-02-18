<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de clasificación de intención del usuario con IA.
 *
 * PROPÓSITO:
 * Analiza el comportamiento del usuario durante el registro y onboarding
 * para predecir su intención y personalizar la experiencia.
 *
 * Q2 2026 - Sprint 5-6: Predictive Onboarding
 */
class UserIntentClassifierService
{

    /**
     * Tipos de intención detectables.
     */
    public const INTENT_EXPLORER = 'explorer';
    public const INTENT_BUYER = 'buyer';
    public const INTENT_SELLER = 'seller';
    public const INTENT_ENTERPRISE = 'enterprise';
    public const INTENT_RESEARCHER = 'researcher';

    /**
     * Señales de comportamiento.
     */
    protected const SIGNAL_WEIGHTS = [
        'visited_pricing' => 2.0,
        'visited_features' => 1.5,
        'visited_marketplace' => 1.0,
        'visited_docs' => 1.5,
        'clicked_signup' => 3.0,
        'clicked_demo' => 2.5,
        'viewed_products' => 1.0,
        'time_on_site' => 0.5,
        'pages_viewed' => 0.3,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Clasifica la intención del usuario basándose en señales.
     *
     * @param array $signals
     *   Señales de comportamiento del usuario.
     *
     * @return array
     *   Clasificación con intent, confidence y recommendations.
     */
    public function classifyIntent(array $signals): array
    {
        $scores = [
            self::INTENT_EXPLORER => 0,
            self::INTENT_BUYER => 0,
            self::INTENT_SELLER => 0,
            self::INTENT_ENTERPRISE => 0,
            self::INTENT_RESEARCHER => 0,
        ];

        // Calcular scores basados en señales.
        foreach ($signals as $signal => $value) {
            $weight = self::SIGNAL_WEIGHTS[$signal] ?? 1.0;
            $this->updateScores($scores, $signal, $value, $weight);
        }

        // Normalizar scores.
        $total = array_sum($scores) ?: 1;
        foreach ($scores as &$score) {
            $score = round($score / $total * 100, 1);
        }

        // Determinar intención principal.
        arsort($scores);
        $primaryIntent = array_key_first($scores);
        $confidence = $scores[$primaryIntent];

        return [
            'intent' => $primaryIntent,
            'confidence' => $confidence,
            'scores' => $scores,
            'recommendations' => $this->getRecommendations($primaryIntent),
            'next_steps' => $this->getNextSteps($primaryIntent),
        ];
    }

    /**
     * Actualiza scores basándose en una señal específica.
     */
    protected function updateScores(array &$scores, string $signal, $value, float $weight): void
    {
        $signalValue = is_bool($value) ? ($value ? 1 : 0) : (float) $value;

        switch ($signal) {
            case 'visited_pricing':
            case 'clicked_signup':
                $scores[self::INTENT_SELLER] += $signalValue * $weight * 2;
                $scores[self::INTENT_BUYER] += $signalValue * $weight;
                break;

            case 'clicked_demo':
                $scores[self::INTENT_ENTERPRISE] += $signalValue * $weight * 2;
                $scores[self::INTENT_SELLER] += $signalValue * $weight;
                break;

            case 'visited_marketplace':
            case 'viewed_products':
                $scores[self::INTENT_BUYER] += $signalValue * $weight * 2;
                $scores[self::INTENT_EXPLORER] += $signalValue * $weight;
                break;

            case 'visited_docs':
                $scores[self::INTENT_RESEARCHER] += $signalValue * $weight * 2;
                $scores[self::INTENT_ENTERPRISE] += $signalValue * $weight;
                break;

            case 'time_on_site':
                if ($value > 120) {
                    $scores[self::INTENT_RESEARCHER] += $weight;
                } elseif ($value > 60) {
                    $scores[self::INTENT_EXPLORER] += $weight;
                }
                break;

            case 'pages_viewed':
                if ($value > 10) {
                    $scores[self::INTENT_RESEARCHER] += $weight * 2;
                } elseif ($value > 5) {
                    $scores[self::INTENT_EXPLORER] += $weight;
                }
                break;

            default:
                $scores[self::INTENT_EXPLORER] += $signalValue * $weight * 0.5;
        }
    }

    /**
     * Obtiene recomendaciones personalizadas por intención.
     */
    protected function getRecommendations(string $intent): array
    {
        $recommendations = [
            self::INTENT_EXPLORER => [
                'Explora nuestro marketplace de productos',
                'Descubre las tiendas del ecosistema',
                'Lee casos de éxito de productores',
            ],
            self::INTENT_BUYER => [
                'Productos destacados para ti',
                'Ofertas exclusivas del marketplace',
                'Crea tu lista de favoritos',
            ],
            self::INTENT_SELLER => [
                'Inicia tu prueba gratuita de 14 días',
                'Configura tu tienda en minutos',
                'Conecta con compradores directamente',
            ],
            self::INTENT_ENTERPRISE => [
                'Agenda una demo personalizada',
                'Conoce nuestras soluciones enterprise',
                'Habla con nuestro equipo de ventas',
            ],
            self::INTENT_RESEARCHER => [
                'Consulta nuestra documentación técnica',
                'Accede a la API del marketplace',
                'Lee sobre nuestra arquitectura',
            ],
        ];

        return $recommendations[$intent] ?? $recommendations[self::INTENT_EXPLORER];
    }

    /**
     * Obtiene los próximos pasos sugeridos.
     */
    protected function getNextSteps(string $intent): array
    {
        $steps = [
            self::INTENT_EXPLORER => [
                ['action' => 'browse_marketplace', 'label' => 'Explorar Marketplace', 'url' => '/marketplace'],
                ['action' => 'view_categories', 'label' => 'Ver Categorías', 'url' => '/marketplace/search'],
            ],
            self::INTENT_BUYER => [
                ['action' => 'create_account', 'label' => 'Crear Cuenta', 'url' => '/user/register'],
                ['action' => 'view_deals', 'label' => 'Ver Ofertas', 'url' => '/marketplace'],
            ],
            self::INTENT_SELLER => [
                ['action' => 'start_trial', 'label' => 'Empezar Prueba Gratis', 'url' => '/registro'],
                ['action' => 'view_plans', 'label' => 'Ver Planes', 'url' => '/planes'],
            ],
            self::INTENT_ENTERPRISE => [
                ['action' => 'request_demo', 'label' => 'Solicitar Demo', 'url' => '/demo'],
                ['action' => 'contact_sales', 'label' => 'Contactar Ventas', 'url' => '/contacto'],
            ],
            self::INTENT_RESEARCHER => [
                ['action' => 'view_docs', 'label' => 'Ver Documentación', 'url' => '/docs'],
                ['action' => 'api_reference', 'label' => 'API Reference', 'url' => '/api/v1/docs'], // AUDIT-CONS-N07: Added API versioning prefix.
            ],
        ];

        return $steps[$intent] ?? $steps[self::INTENT_EXPLORER];
    }

    /**
     * Registra las señales del usuario para análisis.
     */
    public function trackSignal(string $signal, $value, ?int $userId = NULL): void
    {
        $userId = $userId ?? $this->currentUser->id();
        $sessionId = session_id() ?: 'anonymous';

        $this->database->insert('user_intent_signals')
            ->fields([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'signal' => $signal,
                    'value' => json_encode($value),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene señales acumuladas de un usuario/sesión.
     */
    public function getAccumulatedSignals(?int $userId = NULL, ?string $sessionId = NULL): array
    {
        $userId = $userId ?? $this->currentUser->id();
        $sessionId = $sessionId ?? session_id();

        $query = $this->database->select('user_intent_signals', 'uis')
            ->fields('uis', ['signal', 'value'])
            ->condition('created', time() - 3600, '>'); // Última hora.

        if ($userId > 0) {
            $query->condition('user_id', $userId);
        } else {
            $query->condition('session_id', $sessionId);
        }

        $results = $query->execute()->fetchAll();

        $signals = [];
        foreach ($results as $row) {
            $value = json_decode($row->value, TRUE);
            if (isset($signals[$row->signal])) {
                $signals[$row->signal] = is_numeric($value)
                    ? $signals[$row->signal] + $value
                    : TRUE;
            } else {
                $signals[$row->signal] = $value;
            }
        }

        return $signals;
    }

}
