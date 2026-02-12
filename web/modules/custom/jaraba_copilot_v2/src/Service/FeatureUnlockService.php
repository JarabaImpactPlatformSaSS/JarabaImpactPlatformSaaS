<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Psr\Log\LoggerInterface;

/**
 * Servicio de desbloqueo progresivo de funcionalidades.
 *
 * Este servicio implementa el patrón "Desbloqueo Progresivo UX" donde
 * las funcionalidades se desbloquean según la semana del programa en
 * la que se encuentra el emprendedor.
 *
 * PRINCIPIO RECTOR: El emprendedor ve exactamente lo que necesita
 * cuando lo necesita. La plataforma "crece" con él.
 *
 * @see docs/tecnicos/aprendizajes/2026-01-21_desbloqueo_progresivo_ux.md
 */
class FeatureUnlockService
{

    /**
     * Mapa de desbloqueo: semana => array de features disponibles.
     *
     * Cada semana hereda las features de semanas anteriores.
     * El número indica la primera semana en que la feature está disponible.
     */
    const UNLOCK_MAP = [
        // Semana 0: Onboarding + Diagnóstico DIME
        0 => [
            'dime_test',
            'profile_basic',
            'carril_assignment',
        ],
        // Semanas 1-3: Inventario "Pájaro en Mano"
        1 => [
            'copilot_coach',
            'pills_1_3',
            'inventory_efectual',
            'kit_emocional',
        ],
        // Semanas 4-6: Validación de Propuesta de Valor
        4 => [
            'copilot_consultor',
            'copilot_sparring',
            'canvas_vpc',
            'canvas_bmc',
            'experiments_discovery',
            'hypotheses_basic',
            'pills_4_6',
        ],
        // Semanas 7-9: Modelo Financiero + Validación
        7 => [
            'copilot_cfo',
            'copilot_devil',
            'calculadora_precio',
            'proyecciones_financieras',
            'experiments_interest',
            'test_card',
            'learning_card',
            'bmc_dashboard',
            'pills_7_9',
        ],
        // Semanas 10-11: Mentoría + Ventas
        10 => [
            'mentoring_marketplace',
            'calendar_sessions',
            'experiments_preference',
            'circulo_responsabilidad',
            'pills_10_16',
        ],
        // Semana 12: Demo Day + Plan 90 Días
        12 => [
            'experiments_commitment',
            'demo_day',
            'plan_90_dias',
            'certificado',
            'club_alumni',
            'dashboard_full',
            'pills_17_20',
        ],
    ];

    /**
     * Mapa de modos del Copiloto por semana de disponibilidad.
     *
     * Los 7 modos cubren todas las necesidades del emprendedor:
     * - Emocionales: Coach (bloqueos, miedos)
     * - Estratégicos: Consultor, Sparring, Devil
     * - Financieros: CFO (precios, márgenes)
     * - Legales: Fiscal (Hacienda), Laboral (Seguridad Social)
     */
    const COPILOT_MODES = [
        'coach' => [
            'unlock_week' => 1,
            'label' => 'Coach Emocional',
            'icon' => '🧠',
            'triggers' => ['miedo', 'no puedo', 'agobio', 'bloqueo', 'impostor'],
            'description' => 'Escucha activa, normaliza y ofrece Kit Emocional',
        ],
        'consultor' => [
            'unlock_week' => 4,
            'label' => 'Consultor Táctico',
            'icon' => '🔧',
            'triggers' => ['cómo hago', 'qué herramienta', 'paso a paso', 'tutorial'],
            'description' => 'Instrucciones detalladas, plantillas y tutoriales',
        ],
        'sparring' => [
            'unlock_week' => 4,
            'label' => 'Sparring Partner',
            'icon' => '🥊',
            'triggers' => ['qué te parece', 'valídame', 'practica conmigo', 'simula'],
            'description' => 'Feedback honesto, simulación de cliente/inversor',
        ],
        'cfo' => [
            'unlock_week' => 7,
            'label' => 'CFO Sintético',
            'icon' => '💰',
            'triggers' => ['precio', 'cobrar', 'cuánto vale', 'margen', 'coste'],
            'description' => 'Calculadora de la Verdad, análisis financiero',
        ],
        'fiscal' => [
            'unlock_week' => 7,
            'label' => 'Experto Tributario',
            'icon' => '🏛️',
            'triggers' => ['hacienda', 'iva', 'irpf', 'factura', 'modelo', 'impuesto', 'declaración', 'trimestral', '303', '130', 'epígrafe', 'iae', 'deducir', 'gastos deducibles', 'verifactu', '036', '037'],
            'description' => 'Obligaciones tributarias, modelos de Hacienda, alta censal, facturas',
            'disclaimer' => 'Esta información es orientativa. Consulta con un asesor fiscal colegiado para tu caso específico.',
        ],
        'laboral' => [
            'unlock_week' => 7,
            'label' => 'Experto Seguridad Social',
            'icon' => '🛡️',
            'triggers' => ['autónomo', 'seguridad social', 'reta', 'cuota', 'bonificación', 'tarifa plana', 'baja', 'contrato', 'cotización', 'alta', 'pluriactividad', 'prestación', 'incapacidad', 'maternidad', 'cese actividad', 'jubilación'],
            'description' => 'RETA, cuota autónomos, tarifa plana, bonificaciones, prestaciones',
            'disclaimer' => 'Esta información es orientativa. Verifica tu situación en la Seguridad Social o con un graduado social.',
        ],
        'devil' => [
            'unlock_week' => 7,
            'label' => 'Abogado del Diablo',
            'icon' => '😈',
            'triggers' => ['estoy seguro', 'todos quieren', 'es obvio', 'no tiene competencia'],
            'description' => 'Preguntas incómodas, retos a suposiciones',
        ],
    ];

    /**
     * Mapa de tipos de experimentos por semana.
     */
    const EXPERIMENT_TYPES = [
        'DISCOVERY' => 4,
        'INTEREST' => 7,
        'PREFERENCE' => 10,
        'COMMITMENT' => 12,
    ];

    /**
     * Entity Type Manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Entity Type Manager para acceder a entidades.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   Cuenta del usuario actual.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger para registrar eventos.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $logger;
    }

    /**
     * Obtiene todas las features desbloqueadas para un perfil de emprendedor.
     *
     * @param object|null $profile
     *   Perfil del emprendedor con método getCurrentProgramWeek().
     *
     * @return array
     *   Array de features desbloqueadas.
     */
    public function getUnlockedFeatures(?object $profile): array
    {
        // Si no hay perfil, devolver features de semana 0 (mínimo acceso)
        if ($profile === NULL) {
            return self::UNLOCK_MAP[0] ?? [];
        }

        $weekNumber = $this->getProfileWeek($profile);
        $features = [];

        foreach (self::UNLOCK_MAP as $unlockWeek => $weekFeatures) {
            if ($weekNumber >= $unlockWeek) {
                $features = array_merge($features, $weekFeatures);
            }
        }

        return array_unique($features);
    }

    /**
     * Verifica si una feature está desbloqueada para un perfil.
     *
     * @param string $feature
     *   Identificador de la feature a verificar.
     * @param object|null $profile
     *   Perfil del emprendedor. Si es null, usa el usuario actual.
     *
     * @return bool
     *   TRUE si la feature está desbloqueada.
     */
    public function isFeatureUnlocked(string $feature, ?object $profile = NULL): bool
    {
        if ($profile === NULL) {
            $profile = $this->getCurrentUserProfile();
        }

        if ($profile === NULL) {
            // Si no hay perfil, solo permitir features de semana 0
            return in_array($feature, self::UNLOCK_MAP[0] ?? []);
        }

        return in_array($feature, $this->getUnlockedFeatures($profile));
    }

    /**
     * Obtiene la semana en que se desbloquea una feature.
     *
     * @param string $feature
     *   Identificador de la feature.
     *
     * @return int|null
     *   Número de semana o NULL si la feature no existe.
     */
    public function getFeatureUnlockWeek(string $feature): ?int
    {
        foreach (self::UNLOCK_MAP as $week => $features) {
            if (in_array($feature, $features)) {
                return (int) $week;
            }
        }
        return NULL;
    }

    /**
     * Verifica si un modo del Copiloto está disponible.
     *
     * @param string $mode
     *   Modo del copiloto (coach, consultor, sparring, cfo, fiscal, laboral, devil).
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return bool
     *   TRUE si el modo está disponible.
     */
    public function isCopilotModeAvailable(string $mode, ?object $profile = NULL): bool
    {
        $modeConfig = self::COPILOT_MODES[$mode] ?? NULL;
        if (!$modeConfig) {
            return FALSE;
        }
        $requiredWeek = $modeConfig['unlock_week'] ?? 999;
        $currentWeek = $this->getProfileWeek($profile ?? $this->getCurrentUserProfile());

        return $currentWeek >= $requiredWeek;
    }

    /**
     * Obtiene los modos del Copiloto disponibles para un perfil.
     *
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return array
     *   Array asociativo [mode => config + available].
     */
    public function getAvailableCopilotModes(?object $profile = NULL): array
    {
        $currentWeek = $this->getProfileWeek($profile ?? $this->getCurrentUserProfile());
        $modes = [];

        foreach (self::COPILOT_MODES as $mode => $config) {
            $unlockWeek = $config['unlock_week'] ?? 999;
            $modes[$mode] = array_merge($config, [
                'available' => $currentWeek >= $unlockWeek,
            ]);
        }

        return $modes;
    }

    /**
     * Verifica si un tipo de experimento está disponible.
     *
     * @param string $experimentType
     *   Tipo de experimento (DISCOVERY, INTEREST, PREFERENCE, COMMITMENT).
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return bool
     *   TRUE si el tipo está disponible.
     */
    public function isExperimentTypeAvailable(string $experimentType, ?object $profile = NULL): bool
    {
        $requiredWeek = self::EXPERIMENT_TYPES[$experimentType] ?? 999;
        $currentWeek = $this->getProfileWeek($profile ?? $this->getCurrentUserProfile());

        return $currentWeek >= $requiredWeek;
    }

    /**
     * Obtiene información de preview para una feature bloqueada.
     *
     * @param string $feature
     *   Identificador de la feature.
     *
     * @return array
     *   Array con información de la feature bloqueada.
     */
    public function getLockedFeatureInfo(string $feature): array
    {
        $unlockWeek = $this->getFeatureUnlockWeek($feature);

        $previews = [
            'copilot_coach' => t('Tu asistente emocional que te ayudará a superar bloqueos y miedos.'),
            'copilot_cfo' => t('Calcula precios, analiza rentabilidad y valida tu modelo financiero.'),
            'copilot_devil' => t('Un sparring que desafiará tus hipótesis para fortalecerlas.'),
            'canvas_bmc' => t('Diseña tu modelo de negocio completo con 9 bloques interactivos.'),
            'canvas_vpc' => t('Mapea el perfil de tu cliente y tu propuesta de valor.'),
            'experiments_discovery' => t('10 experimentos para descubrir si tu problema existe.'),
            'experiments_interest' => t('12 experimentos para medir el interés real del mercado.'),
            'experiments_commitment' => t('Preventa, cartas de intención y compromisos reales.'),
            'mentoring_marketplace' => t('Accede a mentores humanos expertos en tu sector.'),
            'demo_day' => t('Presenta tu proyecto ante inversores y expertos.'),
            'certificado' => t('Obtén tu certificado de participación firmado digitalmente.'),
        ];

        return [
            'feature' => $feature,
            'unlock_week' => $unlockWeek,
            'preview' => $previews[$feature] ?? t('Esta funcionalidad se desbloqueará más adelante.'),
            'message' => t('Disponible en la Semana @week', ['@week' => $unlockWeek]),
        ];
    }

    /**
     * Obtiene la semana actual del programa para un perfil.
     *
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return int
     *   Número de semana (0-12).
     */
    public function getProfileWeek(?object $profile): int
    {
        if ($profile === NULL) {
            return 0;
        }

        // Si el perfil tiene método getCurrentProgramWeek, usarlo
        if (method_exists($profile, 'getCurrentProgramWeek')) {
            return (int) $profile->getCurrentProgramWeek();
        }

        // Alternativamente, calcular desde fecha de inicio
        if (method_exists($profile, 'get') && $profile->hasField('program_start_date')) {
            $startDate = $profile->get('program_start_date')->value;
            if ($startDate) {
                $start = new DrupalDateTime($startDate);
                $now = new DrupalDateTime();
                $diff = $now->diff($start);
                $weeks = (int) floor($diff->days / 7);
                return min($weeks, 12);
            }
        }

        // Default: semana 0
        return 0;
    }

    /**
     * Obtiene el perfil del emprendedor para el usuario actual.
     *
     * @return object|null
     *   Entidad de perfil o NULL si no existe.
     */
    protected function getCurrentUserProfile(): ?object
    {
        $uid = $this->currentUser->id();
        if (!$uid) {
            return NULL;
        }

        try {
            $profiles = $this->entityTypeManager
                ->getStorage('entrepreneur_profile')
                ->loadByProperties(['user_id' => $uid]);

            return $profiles ? reset($profiles) : NULL;
        } catch (\Exception $e) {
            $this->logger->warning('No se pudo cargar el perfil de emprendedor: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Obtiene el estado completo de desbloqueo para renderizar UI.
     *
     * @param object|null $profile
     *   Perfil del emprendedor.
     *
     * @return array
     *   Array con estado completo de desbloqueo.
     */
    public function getUnlockStatus(?object $profile = NULL): array
    {
        $profile = $profile ?? $this->getCurrentUserProfile();
        $currentWeek = $this->getProfileWeek($profile);

        return [
            'current_week' => $currentWeek,
            'unlocked_features' => $this->getUnlockedFeatures($profile),
            'copilot_modes' => $this->getAvailableCopilotModes($profile),
            'experiment_types' => [
                'DISCOVERY' => $this->isExperimentTypeAvailable('DISCOVERY', $profile),
                'INTEREST' => $this->isExperimentTypeAvailable('INTEREST', $profile),
                'PREFERENCE' => $this->isExperimentTypeAvailable('PREFERENCE', $profile),
                'COMMITMENT' => $this->isExperimentTypeAvailable('COMMITMENT', $profile),
            ],
            'next_unlock' => $this->getNextUnlock($currentWeek),
        ];
    }

    /**
     * Obtiene información sobre el próximo desbloqueo.
     *
     * @param int $currentWeek
     *   Semana actual.
     *
     * @return array|null
     *   Info del próximo desbloqueo o NULL si todo desbloqueado.
     */
    protected function getNextUnlock(int $currentWeek): ?array
    {
        foreach (self::UNLOCK_MAP as $week => $features) {
            if ($week > $currentWeek) {
                return [
                    'week' => $week,
                    'features' => $features,
                    'features_count' => count($features),
                ];
            }
        }
        return NULL;
    }

}
