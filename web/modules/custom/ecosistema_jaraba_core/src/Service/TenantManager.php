<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\Tenant;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * BE-03: Fachada para gestión de Tenants.
 *
 * Delega a servicios especializados manteniendo BC:
 * - TenantSubscriptionService: trial, activate, suspend, cancel, changePlan
 * - TenantDomainService: domainExists, getTenantByDomain
 * - TenantThemeService: getCurrentThemeSettings
 */
class TenantManager
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;
    protected PlanValidator $planValidator;
    protected LoggerInterface $logger;
    protected ?TenantInterface $currentTenant = NULL;
    protected ?TenantSubscriptionService $subscriptionService = NULL;
    protected ?TenantDomainService $domainService = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        PlanValidator $plan_validator,
        LoggerInterface $logger,
        ?TenantSubscriptionService $subscription_service = NULL,
        ?TenantDomainService $domain_service = NULL
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->planValidator = $plan_validator;
        $this->logger = $logger;
        $this->subscriptionService = $subscription_service;
        $this->domainService = $domain_service;
    }

    /**
     * Crea un nuevo Tenant.
     *
     * @param string $name
     *   Nombre comercial del tenant.
     * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
     *   Vertical a la que pertenece.
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
     *   Plan de suscripción.
     * @param \Drupal\user\UserInterface $admin
     *   Usuario administrador.
     * @param string $domain
     *   Dominio del tenant.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   El tenant creado.
     *
     * @throws \Exception
     *   Si hay error en la creación.
     */
    public function createTenant(
        string $name,
        VerticalInterface $vertical,
        SaasPlanInterface $plan,
        UserInterface $admin,
        string $domain
    ): TenantInterface {
        // Validar dominio único.
        if ($this->domainExists($domain)) {
            throw new \InvalidArgumentException("El dominio '$domain' ya está en uso.");
        }

        // Obtener configuración de tema por defecto de la vertical.
        $theme_settings = $vertical->getThemeSettings();

        $tenant = Tenant::create([
            'name' => $name,
            'vertical' => $vertical->id(),
            'subscription_plan' => $plan->id(),
            'admin_user' => $admin->id(),
            'domain' => $domain,
            'subscription_status' => TenantInterface::STATUS_PENDING,
            'theme_overrides' => json_encode($theme_settings),
        ]);

        $tenant->save();

        $this->logger->info('Tenant creado: @name (ID: @id)', [
            '@name' => $name,
            '@id' => $tenant->id(),
        ]);

        return $tenant;
    }

    /**
     * Verifica si un dominio ya existe.
     *
     * @param string $domain
     *   El dominio a verificar.
     *
     * @return bool
     *   TRUE si el dominio ya está en uso.
     */
    public function domainExists(string $domain): bool
    {
        if ($this->domainService) {
            return $this->domainService->domainExists($domain);
        }
        $tenants = $this->entityTypeManager
            ->getStorage('tenant')
            ->loadByProperties(['domain' => $domain]);
        return !empty($tenants);
    }

    /**
     * Obtiene el Tenant por dominio.
     *
     * @deprecated Use TenantDomainService::getTenantByDomain() directly.
     */
    public function getTenantByDomain(string $domain): ?TenantInterface
    {
        if ($this->domainService) {
            return $this->domainService->getTenantByDomain($domain);
        }
        $tenants = $this->entityTypeManager
            ->getStorage('tenant')
            ->loadByProperties(['domain' => $domain]);
        if (!empty($tenants)) {
            $tenant = reset($tenants);
            return $tenant instanceof TenantInterface ? $tenant : NULL;
        }
        return NULL;
    }

    /**
     * Obtiene el Tenant del usuario actual.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
     *   El tenant del usuario actual o NULL.
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        if ($this->currentTenant !== NULL) {
            return $this->currentTenant;
        }

        // Primero intentar por dominio actual.
        $host = \Drupal::request()->getHost();
        $tenant = $this->getTenantByDomain($host);

        if ($tenant) {
            $this->currentTenant = $tenant;
            return $tenant;
        }

        // Si no, buscar por usuario.
        $user = $this->entityTypeManager
            ->getStorage('user')
            ->load($this->currentUser->id());

        if ($user && $user->hasField('field_tenant')) {
            $tenant_ref = $user->get('field_tenant')->entity;
            if ($tenant_ref instanceof TenantInterface) {
                $this->currentTenant = $tenant_ref;
                return $tenant_ref;
            }
        }

        return NULL;
    }

    /**
     * Obtiene la configuración de tema para el tenant actual.
     *
     * @return array
     *   Configuración de tema (colores, logo, etc.)
     */
    public function getCurrentThemeSettings(): array
    {
        $tenant = $this->getCurrentTenant();

        if ($tenant) {
            $overrides = $tenant->getThemeOverrides();
            if (!empty($overrides)) {
                return $overrides;
            }

            // Fallback a la configuración de la vertical.
            $vertical = $tenant->getVertical();
            if ($vertical) {
                return $vertical->getThemeSettings();
            }
        }

        // Configuración por defecto.
        return [
            'color_primary' => '#FF8C42',
            'color_secondary' => '#2D3436',
            'font_family' => 'Inter',
        ];
    }

    /**
     * Activa el período de prueba de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param int $days
     *   Días de prueba.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   El tenant actualizado.
     */
    /**
     * @deprecated Use TenantSubscriptionService::startTrial() directly.
     */
    public function startTrial(TenantInterface $tenant, int $days = 14): TenantInterface
    {
        if ($this->subscriptionService) {
            return $this->subscriptionService->startTrial($tenant, $days);
        }
        $trial_ends = new \DateTime("+{$days} days");
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_TRIAL);
        $tenant->set('trial_ends', $trial_ends->format('Y-m-d\TH:i:s'));
        $tenant->save();
        $this->logger->info('Trial iniciado para tenant @id: @days días', [
            '@id' => $tenant->id(), '@days' => $days,
        ]);
        return $tenant;
    }

    /**
     * @deprecated Use TenantSubscriptionService::activateSubscription() directly.
     */
    public function activateSubscription(TenantInterface $tenant): TenantInterface
    {
        if ($this->subscriptionService) {
            return $this->subscriptionService->activateSubscription($tenant);
        }
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_ACTIVE);
        $tenant->set('trial_ends', NULL);
        $tenant->save();
        $this->logger->info('Suscripción activada para tenant @id', ['@id' => $tenant->id()]);
        return $tenant;
    }

    /**
     * @deprecated Use TenantSubscriptionService::suspendTenant() directly.
     */
    public function suspendTenant(TenantInterface $tenant, string $reason = ''): TenantInterface
    {
        if ($this->subscriptionService) {
            return $this->subscriptionService->suspendTenant($tenant, $reason);
        }
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_SUSPENDED);
        $tenant->save();
        $this->logger->warning('Tenant @id suspendido: @reason', [
            '@id' => $tenant->id(), '@reason' => $reason ?: 'Sin especificar',
        ]);
        return $tenant;
    }

    /**
     * @deprecated Use TenantSubscriptionService::cancelSubscription() directly.
     */
    public function cancelSubscription(TenantInterface $tenant, bool $immediate = FALSE): TenantInterface
    {
        if ($this->subscriptionService) {
            return $this->subscriptionService->cancelSubscription($tenant, $immediate);
        }
        if ($immediate) {
            $tenant->setSubscriptionStatus(TenantInterface::STATUS_CANCELLED);
        }
        $tenant->save();
        $this->logger->info('Suscripción cancelada para tenant @id (inmediata: @immediate)', [
            '@id' => $tenant->id(), '@immediate' => $immediate ? 'sí' : 'no',
        ]);
        return $tenant;
    }

    /**
     * @deprecated Use TenantSubscriptionService::changePlan() directly.
     *
     * @throws \InvalidArgumentException
     */
    public function changePlan(TenantInterface $tenant, SaasPlanInterface $new_plan): TenantInterface
    {
        if ($this->subscriptionService) {
            return $this->subscriptionService->changePlan($tenant, $new_plan);
        }
        $validation = $this->planValidator->validatePlanChange($tenant, $new_plan);
        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                sprintf('Plan change validation failed for tenant %s: %s',
                    $tenant->id(), implode(', ', $validation['errors']))
            );
        }
        $old_plan = $tenant->getSubscriptionPlan();
        $tenant->setSubscriptionPlan($new_plan);
        $tenant->save();
        $this->logger->info('Plan cambiado para tenant @id: @old -> @new', [
            '@id' => $tenant->id(),
            '@old' => $old_plan ? $old_plan->getName() : 'ninguno',
            '@new' => $new_plan->getName(),
        ]);
        return $tenant;
    }

    /**
     * Obtiene todos los tenants de una vertical.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
     *   La vertical.
     * @param bool $active_only
     *   Si solo devolver activos.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface[]
     *   Array de tenants.
     */
    public function getTenantsByVertical(VerticalInterface $vertical, bool $active_only = TRUE): array
    {
        $query = $this->entityTypeManager
            ->getStorage('tenant')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('vertical', $vertical->id());

        if ($active_only) {
            $query->condition('subscription_status', [
                TenantInterface::STATUS_ACTIVE,
                TenantInterface::STATUS_TRIAL,
            ], 'IN');
        }

        $ids = $query->execute();

        if (empty($ids)) {
            return [];
        }

        return $this->entityTypeManager
            ->getStorage('tenant')
            ->loadMultiple($ids);
    }

}
