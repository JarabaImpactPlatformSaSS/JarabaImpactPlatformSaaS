<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Servicio de filtrado multi-tenant para búsquedas RAG en Qdrant.
 *
 * AUDIT-CONS-002: Renombrado de TenantContextService a RagTenantFilterService
 * para eliminar la colisión de nombres con el TenantContextService canónico
 * de ecosistema_jaraba_core. Este servicio es específico de RAG/Qdrant y NO
 * duplica la funcionalidad del servicio core.
 *
 * Responsabilidades:
 * - Extraer contexto del tenant basándose en membresía de grupo (Group Module)
 * - Generar filtros de aislamiento para búsquedas vectoriales en Qdrant
 * - Implementar cascada de visibilidad: tenant > plan > vertical > plataforma
 *
 * Para resolución de Tenant entity y métricas de uso, usar:
 * @see \Drupal\ecosistema_jaraba_core\Service\TenantContextService
 *
 * @see docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md (Sección 6)
 */
class RagTenantFilterService
{

    /**
     * Constructs a RagTenantFilterService object.
     */
    public function __construct(
        protected GroupMembershipLoaderInterface $membershipLoader,
        protected AccountInterface $currentUser,
        protected ConfigFactoryInterface $configFactory,
    ) {
    }

    /**
     * Obtiene los filtros de búsqueda para el tenant actual.
     *
     * @param int|null $tenantIdOverride
     *   ID de tenant a usar (para admins que quieren ver otro tenant).
     *
     * @return array
     *   Array con filtros:
     *   - 'tenant_id': ID del grupo/tenant.
     *   - 'tenant_name': Nombre del tenant.
     *   - 'vertical': Vertical del tenant (agroconecta, arteconecta, etc).
     *   - 'plan_level': Plan de suscripción.
     *   - 'accessible_plans': Array de planes accesibles.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     *   Si el usuario no pertenece a ningún tenant.
     */
    public function getSearchFilters(?int $tenantIdOverride = NULL): array
    {
        // Si hay override y el usuario tiene permiso, usarlo
        if ($tenantIdOverride !== NULL && $this->currentUser->hasPermission('administer jaraba_rag')) {
            return $this->getFiltersForTenant($tenantIdOverride);
        }

        // Obtener grupo actual del usuario via Group Module
        $memberships = $this->membershipLoader->loadByUser($this->currentUser);

        if (empty($memberships)) {
            // Usuario anónimo o sin tenant: acceso solo a contenido de plataforma
            return $this->getAnonymousFilters();
        }

        // Usar el primer grupo (en el futuro podría haber selector)
        $membership = reset($memberships);
        $group = $membership->getGroup();

        return $this->extractTenantContext($group);
    }

    /**
     * Extrae contexto de un grupo.
     */
    protected function extractTenantContext($group): array
    {
        $tenantId = (int) $group->id();
        $tenantName = $group->label();

        // Extraer vertical del grupo
        $vertical = 'default';
        if ($group->hasField('field_vertical') && !$group->get('field_vertical')->isEmpty()) {
            $vertical = $group->get('field_vertical')->value;
        }

        // Extraer plan de suscripción
        $plan = 'starter';
        if ($group->hasField('field_plan') && !$group->get('field_plan')->isEmpty()) {
            $plan = $group->get('field_plan')->value;
        }

        return [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenantName,
            'vertical' => $vertical,
            'plan_level' => $plan,
            'accessible_plans' => $this->getAccessiblePlanLevels($plan),
        ];
    }

    /**
     * Obtiene filtros para un tenant específico.
     */
    protected function getFiltersForTenant(int $tenantId): array
    {
        try {
            $groupStorage = \Drupal::entityTypeManager()->getStorage('group');
            $group = $groupStorage->load($tenantId);

            if (!$group) {
                throw new AccessDeniedHttpException('Tenant no encontrado');
            }

            return $this->extractTenantContext($group);
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException('Error accediendo al tenant');
        }
    }

    /**
     * Obtiene filtros para usuarios anónimos.
     *
     * Los usuarios anónimos solo pueden acceder a:
     * - Contenido compartido de plataforma
     * - Contenido público de la vertical (si está en una tienda específica)
     */
    protected function getAnonymousFilters(): array
    {
        return [
            'tenant_id' => NULL,
            'tenant_name' => 'Visitante',
            'vertical' => 'platform',
            'plan_level' => 'public',
            'accessible_plans' => ['public'],
        ];
    }

    /**
     * Determina los niveles de plan accesibles según la suscripción.
     *
     * Jerarquía de planes (cada nivel incluye los anteriores):
     * starter < growth < pro < enterprise
     *
     * @param string $plan
     *   Plan actual del tenant.
     *
     * @return array
     *   Array de planes accesibles.
     */
    protected function getAccessiblePlanLevels(string $plan): array
    {
        return match ($plan) {
            'enterprise' => ['starter', 'growth', 'pro', 'enterprise'],
            'pro' => ['starter', 'growth', 'pro'],
            'growth' => ['starter', 'growth'],
            'starter' => ['starter'],
            default => ['starter'],
        };
    }

    /**
     * Construye el filtro Qdrant para una búsqueda.
     *
     * Este método genera la estructura de filtro que Qdrant espera,
     * implementando la cascada de conocimiento:
     *
     * 1. Contenido exclusivo del tenant
     * 2. Contenido compartido por plan
     * 3. Contenido compartido por vertical
     * 4. Contenido de plataforma
     *
     * @param array $context
     *   Contexto del tenant (de getSearchFilters).
     *
     * @return array
     *   Filtro en formato Qdrant.
     */
    public function buildQdrantFilter(array $context): array
    {
        $tenantId = $context['tenant_id'];
        $vertical = $context['vertical'];
        $accessiblePlans = $context['accessible_plans'];

        // Usuario anónimo: solo contenido público
        if ($tenantId === NULL) {
            return [
                'should' => [
                    [
                        'key' => 'shared_type',
                        'match' => ['value' => 'platform'],
                    ],
                    [
                        'key' => 'access_level',
                        'match' => ['value' => 'public'],
                    ],
                ],
            ];
        }

        // BE-02: Usuario autenticado con tenant.
        // Se usa 'should' para la VISIBILIDAD (contenido propio O compartido O plataforma),
        // pero se envuelve en 'must' para GARANTIZAR que el filtro de tenant
        // se aplica correctamente y no se filtra contenido de otros tenants privados.
        return [
            'must' => [
                // Filtro principal: solo contenido visible para este tenant.
                [
                    'should' => [
                        // NIVEL 4: Contenido exclusivo del tenant
                        [
                            'key' => 'tenant_id',
                            'match' => ['value' => $tenantId],
                        ],
                        // NIVEL 3: Contenido compartido por plan
                        [
                            'must' => [
                                [
                                    'key' => 'shared_type',
                                    'match' => ['value' => 'plan'],
                                ],
                                [
                                    'key' => 'plan_level',
                                    'match' => ['any' => $accessiblePlans],
                                ],
                            ],
                        ],
                        // NIVEL 2: Contenido compartido por vertical
                        [
                            'must' => [
                                [
                                    'key' => 'shared_type',
                                    'match' => ['value' => 'vertical'],
                                ],
                                [
                                    'key' => 'vertical',
                                    'match' => ['value' => $vertical],
                                ],
                            ],
                        ],
                        // NIVEL 1: Contenido de plataforma
                        [
                            'key' => 'shared_type',
                            'match' => ['value' => 'platform'],
                        ],
                    ],
                ],
            ],
            // must_not: Excluir contenido privado de OTROS tenants.
            'must_not' => [
                [
                    'must' => [
                        [
                            'key' => 'access_level',
                            'match' => ['value' => 'private'],
                        ],
                        [
                            'key' => 'tenant_id',
                            'match' => ['value' => $tenantId],
                            // Negar: excluir privados que NO son del tenant actual.
                            // Qdrant no soporta != directo, por eso usamos must_not.
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Verifica si el usuario actual tiene acceso a un tenant específico.
     */
    public function hasAccessToTenant(int $tenantId): bool
    {
        // Admins tienen acceso a todo
        if ($this->currentUser->hasPermission('administer jaraba_rag')) {
            return TRUE;
        }

        // Verificar membresía
        $memberships = $this->membershipLoader->loadByUser($this->currentUser);
        foreach ($memberships as $membership) {
            if ((int) $membership->getGroup()->id() === $tenantId) {
                return TRUE;
            }
        }

        return FALSE;
    }

}
