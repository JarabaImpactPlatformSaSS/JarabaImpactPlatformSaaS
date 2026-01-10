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
 * Servicio para gestión de Tenants.
 *
 * Maneja CRUD de tenants, negociación de tema por dominio
 * y operaciones relacionadas con el ciclo de vida del tenant.
 */
class TenantManager
{

    /**
     * El entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Validador de planes.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\PlanValidator
     */
    protected PlanValidator $planValidator;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Cache del tenant actual.
     *
     * @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
     */
    protected ?TenantInterface $currentTenant = NULL;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   El entity type manager.
     * @param \Drupal\Core\Session\AccountProxyInterface $current_user
     *   El usuario actual.
     * @param \Drupal\ecosistema_jaraba_core\Service\PlanValidator $plan_validator
     *   El validador de planes.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        PlanValidator $plan_validator,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->planValidator = $plan_validator;
        $this->logger = $logger;
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
        $tenants = $this->entityTypeManager
            ->getStorage('tenant')
            ->loadByProperties(['domain' => $domain]);

        return !empty($tenants);
    }

    /**
     * Obtiene el Tenant por dominio.
     *
     * @param string $domain
     *   El dominio a buscar.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|null
     *   El tenant o NULL si no existe.
     */
    public function getTenantByDomain(string $domain): ?TenantInterface
    {
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
    public function startTrial(TenantInterface $tenant, int $days = 14): TenantInterface
    {
        $trial_ends = new \DateTime("+{$days} days");

        $tenant->setSubscriptionStatus(TenantInterface::STATUS_TRIAL);
        $tenant->set('trial_ends', $trial_ends->format('Y-m-d\TH:i:s'));
        $tenant->save();

        $this->logger->info('Trial iniciado para tenant @id: @days días', [
            '@id' => $tenant->id(),
            '@days' => $days,
        ]);

        return $tenant;
    }

    /**
     * Activa la suscripción de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   El tenant actualizado.
     */
    public function activateSubscription(TenantInterface $tenant): TenantInterface
    {
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_ACTIVE);
        $tenant->set('trial_ends', NULL);
        $tenant->save();

        $this->logger->info('Suscripción activada para tenant @id', [
            '@id' => $tenant->id(),
        ]);

        return $tenant;
    }

    /**
     * Suspende un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param string $reason
     *   Razón de la suspensión.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   El tenant actualizado.
     */
    public function suspendTenant(TenantInterface $tenant, string $reason = ''): TenantInterface
    {
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_SUSPENDED);
        $tenant->save();

        $this->logger->warning('Tenant @id suspendido: @reason', [
            '@id' => $tenant->id(),
            '@reason' => $reason ?: 'Sin especificar',
        ]);

        return $tenant;
    }

    /**
     * Cancela la suscripción de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param bool $immediate
     *   Si la cancelación es inmediata o al final del período.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface
     *   El tenant actualizado.
     */
    public function cancelSubscription(TenantInterface $tenant, bool $immediate = FALSE): TenantInterface
    {
        if ($immediate) {
            $tenant->setSubscriptionStatus(TenantInterface::STATUS_CANCELLED);
        }
        // Si no es inmediata, Stripe manejará la cancelación al final del período.

        $tenant->save();

        $this->logger->info('Suscripción cancelada para tenant @id (inmediata: @immediate)', [
            '@id' => $tenant->id(),
            '@immediate' => $immediate ? 'sí' : 'no',
        ]);

        return $tenant;
    }

    /**
     * Cambia el plan de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $new_plan
     *   El nuevo plan.
     *
     * @return array
     *   Array con 'success' y 'tenant' o 'errors'.
     */
    public function changePlan(TenantInterface $tenant, SaasPlanInterface $new_plan): array
    {
        // Validar que el cambio es posible.
        $validation = $this->planValidator->validatePlanChange($tenant, $new_plan);

        if (!$validation['valid']) {
            return [
                'success' => FALSE,
                'errors' => $validation['errors'],
            ];
        }

        $old_plan = $tenant->getSubscriptionPlan();
        $tenant->setSubscriptionPlan($new_plan);
        $tenant->save();

        $this->logger->info('Plan cambiado para tenant @id: @old -> @new', [
            '@id' => $tenant->id(),
            '@old' => $old_plan ? $old_plan->getName() : 'ninguno',
            '@new' => $new_plan->getName(),
        ]);

        return [
            'success' => TRUE,
            'tenant' => $tenant,
        ];
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
