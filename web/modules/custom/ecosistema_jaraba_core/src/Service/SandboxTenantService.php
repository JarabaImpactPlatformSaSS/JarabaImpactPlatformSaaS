<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Sandbox Tenant Service.
 *
 * Crea tenants temporales de "prueba" para demos sin registro.
 * Permite al usuario experimentar el producto completo antes de registrarse.
 *
 * CARACTERÍSTICAS:
 * - Sandbox temporal con TTL (Time-To-Live)
 * - Datos pre-populados realistas
 * - Conversión seamless a cuenta real
 * - Métricas de engagement
 */
class SandboxTenantService
{

    /**
     * TTL por defecto para sandbox (24 horas).
     */
    protected const SANDBOX_TTL = 86400;

    /**
     * Planes de demo disponibles.
     */
    protected const SANDBOX_TEMPLATES = [
        'agroconecta' => [
            'name' => 'AgroConecta Demo',
            'vertical' => 'agro',
            'products' => [
                ['name' => 'Aceite Virgen Extra', 'price' => 15.99, 'stock' => 50],
                ['name' => 'Queso Manchego Curado', 'price' => 24.99, 'stock' => 30],
                ['name' => 'Jamón Ibérico 50%', 'price' => 89.99, 'stock' => 10],
                ['name' => 'Miel de Romero', 'price' => 12.99, 'stock' => 45],
            ],
            'orders' => 12,
            'revenue' => 2340.50,
            'customers' => 28,
        ],
        'artesania' => [
            'name' => 'Artesanía Demo',
            'vertical' => 'crafts',
            'products' => [
                ['name' => 'Cerámica Tradicional', 'price' => 45.00, 'stock' => 20],
                ['name' => 'Tejido Artesanal', 'price' => 89.00, 'stock' => 15],
                ['name' => 'Joyería Hecha a Mano', 'price' => 120.00, 'stock' => 12],
            ],
            'orders' => 8,
            'revenue' => 1890.00,
            'customers' => 18,
        ],
        'gastronomia' => [
            'name' => 'Gastronomía Demo',
            'vertical' => 'food',
            'products' => [
                ['name' => 'Conservas Gourmet', 'price' => 8.99, 'stock' => 100],
                ['name' => 'Vino Reserva 2020', 'price' => 35.00, 'stock' => 60],
                ['name' => 'Paté Artesano', 'price' => 12.50, 'stock' => 40],
            ],
            'orders' => 25,
            'revenue' => 4200.00,
            'customers' => 45,
        ],
    ];

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * State service.
     */
    protected StateInterface $state;

    /**
     * Logger.
     */
    protected LoggerChannelFactoryInterface $loggerFactory;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        StateInterface $state,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->state = $state;
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * Crea un sandbox temporal para demo.
     *
     * @param string $template
     *   Template de sandbox (agroconecta, artesania, gastronomia).
     *
     * @return array
     *   Datos del sandbox creado.
     */
    public function createSandbox(string $template = 'agroconecta'): array
    {
        $sandboxId = $this->generateSandboxId();
        $templateData = self::SANDBOX_TEMPLATES[$template] ?? self::SANDBOX_TEMPLATES['agroconecta'];

        $sandbox = [
            'id' => $sandboxId,
            'template' => $template,
            'name' => $templateData['name'],
            'vertical' => $templateData['vertical'],
            'created_at' => time(),
            'expires_at' => time() + self::SANDBOX_TTL,
            'status' => 'active',
            'data' => [
                'products' => $templateData['products'],
                'metrics' => [
                    'orders' => $templateData['orders'],
                    'revenue' => $templateData['revenue'],
                    'customers' => $templateData['customers'],
                ],
                'ai_agents' => ['storytelling', 'marketing', 'customer_experience'],
            ],
            'engagement' => [
                'actions' => [],
                'pages_viewed' => [],
                'features_used' => [],
                'magic_moment_reached' => FALSE,
                'time_to_value' => NULL,
            ],
        ];

        // Guardar sandbox.
        $this->state->set("sandbox_{$sandboxId}", $sandbox);

        // Registrar en lista de sandboxes activos.
        $activeSandboxes = $this->state->get('active_sandboxes', []);
        $activeSandboxes[] = $sandboxId;
        $this->state->set('active_sandboxes', $activeSandboxes);

        $this->loggerFactory->get('sandbox')->info(
            '🎪 Sandbox created: @id (template: @template)',
            ['@id' => $sandboxId, '@template' => $template]
        );

        return $sandbox;
    }

    /**
     * Obtiene un sandbox por ID.
     *
     * @param string $sandboxId
     *   ID del sandbox.
     *
     * @return array|null
     *   Datos del sandbox o NULL si no existe/expiró.
     */
    public function getSandbox(string $sandboxId): ?array
    {
        $sandbox = $this->state->get("sandbox_{$sandboxId}");

        if (!$sandbox) {
            return NULL;
        }

        // Verificar expiración.
        if (time() > $sandbox['expires_at']) {
            $this->expireSandbox($sandboxId);
            return NULL;
        }

        return $sandbox;
    }

    /**
     * Actualiza el engagement de un sandbox.
     *
     * @param string $sandboxId
     *   ID del sandbox.
     * @param string $action
     *   Acción realizada.
     * @param array $metadata
     *   Metadata adicional.
     */
    public function trackEngagement(string $sandboxId, string $action, array $metadata = []): void
    {
        $sandbox = $this->getSandbox($sandboxId);

        if (!$sandbox) {
            return;
        }

        // Registrar acción.
        $sandbox['engagement']['actions'][] = [
            'action' => $action,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];

        // Detectar magic moment.
        if (!$sandbox['engagement']['magic_moment_reached']) {
            if ($this->checkMagicMoment($sandbox)) {
                $sandbox['engagement']['magic_moment_reached'] = TRUE;
                $sandbox['engagement']['time_to_value'] =
                    time() - $sandbox['created_at'];

                $this->loggerFactory->get('sandbox')->notice(
                    '✨ Magic moment reached for sandbox @id in @seconds seconds!',
                    ['@id' => $sandboxId, '@seconds' => $sandbox['engagement']['time_to_value']]
                );
            }
        }

        // Actualizar sandbox.
        $this->state->set("sandbox_{$sandboxId}", $sandbox);
    }

    /**
     * Verifica si se alcanzó el "magic moment".
     *
     * @param array $sandbox
     *   Datos del sandbox.
     *
     * @return bool
     *   TRUE si se alcanzó el magic moment.
     */
    protected function checkMagicMoment(array $sandbox): bool
    {
        $actions = array_column($sandbox['engagement']['actions'], 'action');

        // Magic moment = al menos 3 acciones clave.
        $keyActions = ['view_dashboard', 'view_product', 'use_ai_agent', 'view_analytics'];
        $matched = array_intersect($keyActions, $actions);

        return count($matched) >= 2;
    }

    /**
     * Convierte un sandbox a cuenta real.
     *
     * @param string $sandboxId
     *   ID del sandbox.
     * @param array $userData
     *   Datos del usuario (email, name, etc.).
     *
     * @return array
     *   Resultado de la conversión.
     */
    public function convertToAccount(string $sandboxId, array $userData): array
    {
        $sandbox = $this->getSandbox($sandboxId);

        if (!$sandbox) {
            return [
                'success' => FALSE,
                'error' => 'Sandbox not found or expired',
            ];
        }

        // Calcular tiempo de conversión.
        $conversionTime = time() - $sandbox['created_at'];

        // Crear cuenta real en Drupal.
        try {
            $email = $userData['email'] ?? '';
            $name = $userData['name'] ?? explode('@', $email)[0];

            // Verificar que el email no esté ya registrado.
            $existingUsers = $this->entityTypeManager->getStorage('user')
                ->loadByProperties(['mail' => $email]);

            if (!empty($existingUsers)) {
                return [
                    'success' => FALSE,
                    'error' => 'Email already registered',
                ];
            }

            // Crear el usuario.
            $user = $this->entityTypeManager->getStorage('user')->create([
                'name' => $name,
                'mail' => $email,
                'status' => 1,
                'pass' => $userData['password'] ?? \Drupal::service('password_generator')->generate(12),
            ]);
            $user->addRole('tenant_admin');
            $user->save();

            // Crear el Tenant asociado.
            $templateData = self::SANDBOX_TEMPLATES[$sandbox['template']] ?? self::SANDBOX_TEMPLATES['agroconecta'];
            $tenant = $this->entityTypeManager->getStorage('tenant')->create([
                'name' => $userData['company_name'] ?? $templateData['name'],
                'admin_user' => $user->id(),
                'status' => TRUE,
            ]);
            $tenant->save();

            $sandbox['user_id'] = (int) $user->id();
            $sandbox['tenant_entity_id'] = (int) $tenant->id();

        } catch (\Exception $e) {
            $this->loggerFactory->get('sandbox')->error(
                'Error creating account from sandbox @id: @error',
                ['@id' => $sandboxId, '@error' => $e->getMessage()]
            );
            return [
                'success' => FALSE,
                'error' => 'Failed to create account: ' . $e->getMessage(),
            ];
        }

        // Marcar sandbox como convertido.
        $sandbox['status'] = 'converted';
        $sandbox['converted_at'] = time();
        $sandbox['user_email'] = $userData['email'] ?? '';
        $this->state->set("sandbox_{$sandboxId}", $sandbox);

        $this->loggerFactory->get('sandbox')->notice(
            '🎉 Sandbox @id converted to account in @seconds seconds',
            ['@id' => $sandboxId, '@seconds' => $conversionTime]
        );

        return [
            'success' => TRUE,
            'sandbox_id' => $sandboxId,
            'conversion_time_seconds' => $conversionTime,
            'engagement_score' => count($sandbox['engagement']['actions']),
            'magic_moment_reached' => $sandbox['engagement']['magic_moment_reached'],
        ];
    }

    /**
     * Expira un sandbox.
     *
     * @param string $sandboxId
     *   ID del sandbox a expirar.
     */
    public function expireSandbox(string $sandboxId): void
    {
        $sandbox = $this->state->get("sandbox_{$sandboxId}");

        if ($sandbox) {
            $sandbox['status'] = 'expired';
            $this->state->set("sandbox_{$sandboxId}", $sandbox);
        }

        // Remover de lista activa.
        $activeSandboxes = $this->state->get('active_sandboxes', []);
        $activeSandboxes = array_filter($activeSandboxes, fn($id) => $id !== $sandboxId);
        $this->state->set('active_sandboxes', array_values($activeSandboxes));

        $this->loggerFactory->get('sandbox')->info(
            '⏰ Sandbox expired: @id',
            ['@id' => $sandboxId]
        );
    }

    /**
     * Limpia sandboxes expirados (ejecutar en cron).
     *
     * @return int
     *   Número de sandboxes limpiados.
     */
    public function cleanupExpiredSandboxes(): int
    {
        $activeSandboxes = $this->state->get('active_sandboxes', []);
        $cleaned = 0;

        foreach ($activeSandboxes as $sandboxId) {
            $sandbox = $this->state->get("sandbox_{$sandboxId}");

            if ($sandbox && time() > $sandbox['expires_at']) {
                $this->expireSandbox($sandboxId);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            $this->loggerFactory->get('sandbox')->info(
                '🧹 Cleaned @count expired sandboxes',
                ['@count' => $cleaned]
            );
        }

        return $cleaned;
    }

    /**
     * Obtiene estadísticas de sandboxes.
     *
     * @return array
     *   Estadísticas.
     */
    public function getStatistics(): array
    {
        $activeSandboxes = $this->state->get('active_sandboxes', []);
        $totalConversions = 0;
        $totalMagicMoments = 0;
        $avgTimeToValue = 0;
        $timeToValueSum = 0;
        $timeToValueCount = 0;

        // Buscar todos los sandboxes (activos y convertidos).
        foreach ($activeSandboxes as $sandboxId) {
            $sandbox = $this->state->get("sandbox_{$sandboxId}");

            if ($sandbox) {
                if ($sandbox['status'] === 'converted') {
                    $totalConversions++;
                }

                if ($sandbox['engagement']['magic_moment_reached']) {
                    $totalMagicMoments++;
                    if ($sandbox['engagement']['time_to_value']) {
                        $timeToValueSum += $sandbox['engagement']['time_to_value'];
                        $timeToValueCount++;
                    }
                }
            }
        }

        if ($timeToValueCount > 0) {
            $avgTimeToValue = round($timeToValueSum / $timeToValueCount);
        }

        return [
            'active_sandboxes' => count($activeSandboxes),
            'total_conversions' => $totalConversions,
            'conversion_rate' => count($activeSandboxes) > 0
                ? round(($totalConversions / count($activeSandboxes)) * 100, 1)
                : 0,
            'magic_moments_reached' => $totalMagicMoments,
            'avg_time_to_value_seconds' => $avgTimeToValue,
        ];
    }

    /**
     * Extiende el TTL de un sandbox.
     *
     * @param string $sandboxId
     *   ID del sandbox.
     * @param int $additionalSeconds
     *   Segundos adicionales.
     *
     * @return bool
     *   TRUE si se extendió.
     */
    public function extendSandbox(string $sandboxId, int $additionalSeconds = 3600): bool
    {
        $sandbox = $this->getSandbox($sandboxId);

        if (!$sandbox) {
            return FALSE;
        }

        $sandbox['expires_at'] += $additionalSeconds;
        $this->state->set("sandbox_{$sandboxId}", $sandbox);

        return TRUE;
    }

    /**
     * Obtiene templates disponibles.
     *
     * @return array
     *   Templates de sandbox.
     */
    public function getAvailableTemplates(): array
    {
        $templates = [];

        foreach (self::SANDBOX_TEMPLATES as $id => $data) {
            $templates[$id] = [
                'id' => $id,
                'name' => $data['name'],
                'vertical' => $data['vertical'],
                'products_count' => count($data['products']),
            ];
        }

        return $templates;
    }

    /**
     * Genera un ID único para sandbox.
     *
     * @return string
     *   ID único.
     */
    protected function generateSandboxId(): string
    {
        return 'sbx_' . bin2hex(random_bytes(8));
    }

}
