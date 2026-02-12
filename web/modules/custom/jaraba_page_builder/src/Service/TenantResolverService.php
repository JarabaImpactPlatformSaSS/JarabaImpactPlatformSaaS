<?php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Servicio para resolver el tenant (Group) del usuario actual.
 *
 * PROPÓSITO:
 * Centraliza la lógica para determinar a qué tenant pertenece el usuario
 * actual. Se utiliza para:
 * - Asignar tenant_id a nuevas páginas
 * - Filtrar contenido por tenant
 * - Verificar planes/límites del tenant
 *
 * DEPENDENCIA:
 * Requiere que el usuario esté asignado a un Group (multi-tenant).
 */
class TenantResolverService
{

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Cargador de membresías de grupo.
     *
     * @var \Drupal\group\GroupMembershipLoaderInterface|null
     */
    protected ?GroupMembershipLoaderInterface $membershipLoader;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   El usuario actual.
     * @param \Drupal\group\GroupMembershipLoaderInterface|null $membership_loader
     *   Cargador de membresías (opcional, si el módulo Group está habilitado).
     */
    public function __construct(
        AccountProxyInterface $current_user,
        ?GroupMembershipLoaderInterface $membership_loader = NULL
    ) {
        $this->currentUser = $current_user;
        $this->membershipLoader = $membership_loader;
    }

    /**
     * Obtiene el ID del tenant del usuario actual.
     *
     * @return int|null
     *   El ID del grupo/tenant, o NULL si no tiene.
     */
    public function getCurrentTenantId(): ?int
    {
        $tenant = $this->getCurrentTenant();
        return $tenant?->id();
    }

    /**
     * Obtiene la entidad Group del tenant actual.
     *
     * @return \Drupal\group\Entity\GroupInterface|null
     *   La entidad Group, o NULL.
     */
    public function getCurrentTenant(): ?\Drupal\group\Entity\GroupInterface
    {
        if (!$this->membershipLoader) {
            return NULL;
        }

        $user = $this->currentUser->getAccount();
        if ($user->isAnonymous()) {
            return NULL;
        }

        // Cargar las membresías del usuario.
        $memberships = $this->membershipLoader->loadByUser($user);

        if (empty($memberships)) {
            return NULL;
        }

        // Devolver el primer grupo (asumiendo tenant único por usuario).
        // En un sistema multi-tenant complejo, podría ser necesario
        // una lógica más sofisticada (ej: selección de contexto).
        $first_membership = reset($memberships);

        if ($first_membership instanceof GroupRelationshipInterface) {
            return $first_membership->getGroup();
        }

        return NULL;
    }

    /**
     * Obtiene el plan del tenant actual.
     *
     * @return string
     *   ID del plan ('starter', 'professional', 'enterprise'),
     *   o 'starter' por defecto.
     */
    public function getCurrentTenantPlan(): string
    {
        $tenant = $this->getCurrentTenant();

        if (!$tenant) {
            return 'starter';
        }

        // Verificar si el grupo tiene campo de plan.
        if ($tenant->hasField('field_saas_plan') && !$tenant->get('field_saas_plan')->isEmpty()) {
            $plan_entity = $tenant->get('field_saas_plan')->entity;
            if ($plan_entity) {
                return $plan_entity->id() ?? 'starter';
            }
        }

        // Campo alternativo: plan directo.
        if ($tenant->hasField('plan') && !$tenant->get('plan')->isEmpty()) {
            return $tenant->get('plan')->value ?? 'starter';
        }

        return 'starter';
    }

    /**
     * Verifica si el tenant actual puede crear más páginas.
     *
     * @param int $current_count
     *   Número actual de páginas del tenant.
     *
     * @return bool
     *   TRUE si puede crear más páginas.
     */
    public function canCreateMorePages(int $current_count): bool
    {
        $limit = $this->getPageLimit();

        // -1 significa ilimitado.
        if ($limit === -1) {
            return TRUE;
        }

        return $current_count < $limit;
    }

    /**
     * Obtiene el límite de páginas para el tenant actual.
     *
     * @return int
     *   Límite de páginas (-1 = ilimitado).
     */
    public function getPageLimit(): int
    {
        $plan = $this->getCurrentTenantPlan();

        // Cargar configuración del módulo.
        $config = \Drupal::config('jaraba_page_builder.settings');

        // Obtener límite según plan.
        $limits = $config->get('page_limits') ?? [
            'starter' => 5,
            'professional' => 25,
            'enterprise' => -1,
        ];

        return $limits[$plan] ?? 5;
    }

    /**
     * Verifica si el tenant actual puede usar plantillas premium.
     *
     * @return bool
     *   TRUE si puede usar premium.
     */
    public function canUsePremiumTemplates(): bool
    {
        $plan = $this->getCurrentTenantPlan();

        // Solo Professional y Enterprise tienen acceso a premium.
        return in_array($plan, ['professional', 'enterprise'], TRUE);
    }

    /**
     * Verifica si el usuario tiene acceso a una plantilla específica.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $template
     *   La plantilla a verificar.
     *
     * @return bool
     *   TRUE si tiene acceso.
     */
    public function hasAccessToTemplate(\Drupal\jaraba_page_builder\PageTemplateInterface $template): bool
    {
        $plan = $this->getCurrentTenantPlan();

        // Verificar si el plan está en la lista de planes requeridos.
        $required_plans = $template->getPlansRequired();

        return in_array($plan, $required_plans, TRUE);
    }

    /**
     * Obtiene el número de páginas del tenant actual.
     *
     * @return int
     *   Número de páginas.
     */
    public function getCurrentTenantPageCount(): int
    {
        $tenant_id = $this->getCurrentTenantId();

        if (!$tenant_id) {
            // Si no hay tenant, contar las del usuario directamente.
            return $this->getUserPageCount();
        }

        $query = \Drupal::entityQuery('page_content')
            ->condition('tenant_id', $tenant_id)
            ->accessCheck(FALSE);

        return (int) $query->count()->execute();
    }

    /**
     * Obtiene el número de páginas del usuario actual (sin tenant).
     *
     * @return int
     *   Número de páginas del usuario.
     */
    protected function getUserPageCount(): int
    {
        $uid = $this->currentUser->id();

        if (!$uid || $this->currentUser->isAnonymous()) {
            return 0;
        }

        $query = \Drupal::entityQuery('page_content')
            ->condition('uid', $uid)
            ->accessCheck(FALSE);

        return (int) $query->count()->execute();
    }

}
