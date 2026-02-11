<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de contexto para Copilotos.
 *
 * PROPÓSITO:
 * Detecta dinámicamente el contexto del usuario logado para personalizar
 * los copilotos según: avatar, vertical, plan, tenant.
 *
 * ESTRATEGIA DE DETECCIÓN:
 * 1. Si está logado, detectar avatar por roles del usuario
 * 2. Si tiene tenant asociado, obtener vertical y plan
 * 3. Si no está logado, detectar por ruta actual (landing pages)
 *
 * @see \Drupal\ecosistema_jaraba_core\Plugin\Block\ContextualCopilotBlock
 */
class CopilotContextService
{

    /**
     * Mapeo de roles a avatares.
     */
    protected const ROLE_TO_AVATAR = [
        'candidate' => 'jobseeker',
        'candidato' => 'jobseeker',
        'jobseeker' => 'jobseeker',
        'employer' => 'recruiter',
        'recruiter' => 'recruiter',
        'empleador' => 'recruiter',
        'entrepreneur' => 'entrepreneur',
        'emprendedor' => 'entrepreneur',
        'producer' => 'producer',
        'productor' => 'producer',
        'comercio' => 'producer',
        'mentor' => 'mentor',
        'institution' => 'institution',
        'admin' => 'admin',
        'tenant_admin' => 'admin',
    ];

    /**
     * Mapeo de rutas a avatares.
     */
    protected const ROUTE_TO_AVATAR = [
        'jaraba_candidate.dashboard' => 'jobseeker',
        'jaraba_job_board.jobseeker_dashboard' => 'jobseeker',
        'jaraba_employer.dashboard' => 'recruiter',
        'jaraba_employer.recruiter_dashboard' => 'recruiter',
        'jaraba_copilot_v2.entrepreneur_dashboard' => 'entrepreneur',
        'ecosistema_jaraba_core.producer_dashboard' => 'producer',
        'jaraba_mentoring.mentor_dashboard' => 'mentor',
        'ecosistema_jaraba_core.tenant_dashboard' => 'admin',
    ];

    /**
     * Mapeo de rutas de landing a verticales.
     */
    protected const ROUTE_TO_VERTICAL = [
        'ecosistema_jaraba_core.landing_empleo' => 'empleabilidad',
        'ecosistema_jaraba_core.landing_talento' => 'empleabilidad',
        'ecosistema_jaraba_core.landing_emprender' => 'emprendimiento',
        'ecosistema_jaraba_core.landing_comercio' => 'comercio',
        'ecosistema_jaraba_core.landing_instituciones' => 'instituciones',
    ];

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected RouteMatchInterface $routeMatch,
        protected LoggerChannelInterface $logger,
    ) {
    }

    /**
     * Obtiene el contexto completo para el copiloto.
     *
     * @return array
     *   Array con avatar, vertical, plan, tenant, user_name.
     */
    public function getContext(): array
    {
        $context = [
            'avatar' => 'general',
            'vertical' => NULL,
            'plan' => NULL,
            'tenant_id' => NULL,
            'tenant_name' => NULL,
            'user_id' => (int) $this->currentUser->id(),
            'user_name' => '',
            'is_authenticated' => $this->currentUser->isAuthenticated(),
            'current_route' => $this->routeMatch->getRouteName(),
        ];

        // 1. Detección por usuario logado
        if ($this->currentUser->isAuthenticated()) {
            $context = $this->enrichWithUserContext($context);
        }

        // 2. Detección por ruta actual (override si no hay avatar detectado)
        if ($context['avatar'] === 'general') {
            $context = $this->enrichWithRouteContext($context);
        }

        return $context;
    }

    /**
     * Enriquece contexto con información del usuario logado.
     */
    protected function enrichWithUserContext(array $context): array
    {
        try {
            $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

            if (!$user) {
                return $context;
            }

            $context['user_name'] = $user->getDisplayName();

            // Detectar avatar por roles
            $roles = $user->getRoles();
            foreach ($roles as $role) {
                $roleLower = strtolower($role);
                if (isset(self::ROLE_TO_AVATAR[$roleLower])) {
                    $context['avatar'] = self::ROLE_TO_AVATAR[$roleLower];
                    break;
                }
            }

            // Obtener tenant asociado
            $context = $this->enrichWithTenantContext($context, $user);

        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo contexto de usuario: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return $context;
    }

    /**
     * Enriquece contexto con información del tenant.
     */
    protected function enrichWithTenantContext(array $context, $user): array
    {
        try {
            // Buscar tenant donde el usuario es admin
            $tenants = $this->entityTypeManager->getStorage('tenant')->loadByProperties([
                'admin_user_id' => $user->id(),
            ]);

            if (!empty($tenants)) {
                $tenant = reset($tenants);
                $context['tenant_id'] = $tenant->id();
                $context['tenant_name'] = $tenant->label();

                // Obtener plan del tenant
                if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
                    $plan = $tenant->get('plan')->entity;
                    if ($plan) {
                        $context['plan'] = $plan->label();
                    }
                }

                // Obtener vertical del tenant
                if ($tenant->hasField('vertical') && !$tenant->get('vertical')->isEmpty()) {
                    $vertical = $tenant->get('vertical')->entity;
                    if ($vertical) {
                        $context['vertical'] = $vertical->label();
                    }
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo contexto de tenant: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return $context;
    }

    /**
     * Enriquece contexto basado en la ruta actual.
     */
    protected function enrichWithRouteContext(array $context): array
    {
        $routeName = $this->routeMatch->getRouteName() ?? '';

        // Detección de avatar por ruta de dashboard
        if (isset(self::ROUTE_TO_AVATAR[$routeName])) {
            $context['avatar'] = self::ROUTE_TO_AVATAR[$routeName];
        }

        // Detección de vertical por ruta de landing
        if (isset(self::ROUTE_TO_VERTICAL[$routeName])) {
            $context['vertical'] = self::ROUTE_TO_VERTICAL[$routeName];
        }

        return $context;
    }

    /**
     * Obtiene el avatar detectado.
     */
    public function getAvatar(): string
    {
        return $this->getContext()['avatar'];
    }

    /**
     * Construye el prompt de contexto para la IA.
     */
    public function buildContextPrompt(): string
    {
        $context = $this->getContext();
        $parts = [];

        if ($context['is_authenticated'] && $context['user_name']) {
            $parts[] = "Usuario: {$context['user_name']}";
        }

        if ($context['avatar'] !== 'general') {
            $avatarLabels = [
                'jobseeker' => 'Candidato buscando empleo',
                'recruiter' => 'Reclutador/Empleador',
                'entrepreneur' => 'Emprendedor',
                'producer' => 'Productor/Comercio local',
                'mentor' => 'Mentor',
                'admin' => 'Administrador del tenant',
                'institution' => 'Institución/ONG',
            ];
            $parts[] = "Perfil: " . ($avatarLabels[$context['avatar']] ?? $context['avatar']);
        }

        if ($context['tenant_name']) {
            $parts[] = "Organización: {$context['tenant_name']}";
        }

        if ($context['plan']) {
            $parts[] = "Plan: {$context['plan']}";
        }

        if ($context['vertical']) {
            $parts[] = "Vertical: {$context['vertical']}";
        }

        return implode(' | ', $parts);
    }

}
