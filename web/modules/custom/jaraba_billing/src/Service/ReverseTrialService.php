<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;

/**
 * Service para gestionar el modelo de Reverse Trial.
 *
 * En el modelo Reverse Trial, los nuevos tenants obtienen acceso completo
 * (plan Pro) durante 14 días. Si no realizan pago, se hace downgrade
 * automático al plan Starter (gratuito).
 *
 * Beneficios:
 * - Usuario experimenta valor máximo desde día 1
 * - Mayor conversión al mostrar todas las capacidades
 * - Mejor Time-to-Value (TTFV < 60s)
 */
class ReverseTrialService
{

    /**
     * Duración del reverse trial en días.
     */
    public const TRIAL_DURATION_DAYS = 14;

    /**
     * Días antes del fin del trial para enviar notificaciones.
     */
    public const NOTIFICATION_DAYS = [7, 3, 1, 0];

    /**
     * Machine name del plan Pro (trial).
     */
    public const PRO_PLAN_NAME = 'profesional';

    /**
     * Machine name del plan Starter (post-trial).
     */
    public const STARTER_PLAN_NAME = 'starter';

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger channel factory.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected LoggerChannelFactoryInterface $loggerFactory;

    /**
     * Mail manager.
     *
     * @var \Drupal\Core\Mail\MailManagerInterface
     */
    protected MailManagerInterface $mailManager;

    /**
     * State service.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected StateInterface $state;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelFactoryInterface $loggerFactory,
        MailManagerInterface $mailManager,
        StateInterface $state
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->loggerFactory = $loggerFactory;
        $this->mailManager = $mailManager;
        $this->state = $state;
    }

    /**
     * Inicia un Reverse Trial para un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant que inicia el trial.
     *
     * @return bool
     *   TRUE si se inició correctamente, FALSE en caso de error.
     */
    public function startReverseTrial(TenantInterface $tenant): bool
    {
        try {
            // Obtener plan Pro.
            $proPlan = $this->getPlanByName(self::PRO_PLAN_NAME);
            $starterPlan = $this->getPlanByName(self::STARTER_PLAN_NAME);

            if (!$proPlan) {
                $this->loggerFactory->get('reverse_trial')->error(
                    'No se encontró el plan Pro para iniciar reverse trial'
                );
                return FALSE;
            }

            // Configurar tenant con reverse trial.
            $trialEnds = (new \DateTime())->add(new \DateInterval('P' . self::TRIAL_DURATION_DAYS . 'D'));

            $tenant->set('subscription_status', 'trial');
            $tenant->set('subscription_plan', $proPlan->id());
            $tenant->set('trial_ends', $trialEnds->format('Y-m-d\TH:i:s'));
            $tenant->set('is_reverse_trial', TRUE);

            if ($starterPlan) {
                $tenant->set('downgrade_plan', $starterPlan->id());
            }

            $tenant->save();

            $this->loggerFactory->get('reverse_trial')->info(
                '🚀 Reverse Trial iniciado para @tenant. Expira: @date',
                [
                    '@tenant' => $tenant->getName(),
                    '@date' => $trialEnds->format('Y-m-d'),
                ]
            );

            return TRUE;

        } catch (\Exception $e) {
            $this->loggerFactory->get('reverse_trial')->error(
                'Error al iniciar reverse trial: @message',
                ['@message' => $e->getMessage()]
            );
            return FALSE;
        }
    }

    /**
     * Procesa todos los reverse trials expirados.
     *
     * Busca tenants con reverse trial que han expirado y ejecuta el downgrade.
     *
     * @return int
     *   Número de tenants procesados (downgraded).
     */
    public function processExpiredTrials(): int
    {
        $downgraded = 0;
        $now = new \DateTime();

        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');

            // Buscar tenants en reverse trial expirado.
            $query = $tenantStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('subscription_status', 'trial')
                ->condition('is_reverse_trial', TRUE)
                ->condition('trial_ends', $now->format('Y-m-d\TH:i:s'), '<');

            $tenantIds = $query->execute();

            foreach ($tenantIds as $tenantId) {
                $tenant = $tenantStorage->load($tenantId);
                if ($tenant && $this->executeDowngrade($tenant)) {
                    $downgraded++;
                }
            }

        } catch (\Exception $e) {
            $this->loggerFactory->get('reverse_trial')->error(
                'Error procesando trials expirados: @message',
                ['@message' => $e->getMessage()]
            );
        }

        return $downgraded;
    }

    /**
     * Ejecuta el downgrade de un tenant a su plan post-trial.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant a degradar.
     *
     * @return bool
     *   TRUE si se ejecutó correctamente.
     */
    public function executeDowngrade(TenantInterface $tenant): bool
    {
        try {
            // Obtener plan de downgrade.
            $downgradePlanId = $tenant->get('downgrade_plan')->target_id ?? NULL;
            $downgradePlan = NULL;

            if ($downgradePlanId) {
                $downgradePlan = $this->entityTypeManager->getStorage('saas_plan')->load($downgradePlanId);
            }

            // Fallback al plan Starter.
            if (!$downgradePlan) {
                $downgradePlan = $this->getPlanByName(self::STARTER_PLAN_NAME);
            }

            if (!$downgradePlan) {
                $this->loggerFactory->get('reverse_trial')->error(
                    'No hay plan de downgrade disponible para @tenant',
                    ['@tenant' => $tenant->getName()]
                );
                return FALSE;
            }

            // Ejecutar downgrade.
            $previousPlan = $tenant->getSubscriptionPlan();
            $tenant->set('subscription_status', 'active');
            $tenant->set('subscription_plan', $downgradePlan->id());
            $tenant->set('is_reverse_trial', FALSE);
            $tenant->set('trial_ends', NULL);
            $tenant->save();

            // Enviar notificación.
            $this->sendDowngradeNotification($tenant, $previousPlan, $downgradePlan);

            $this->loggerFactory->get('reverse_trial')->info(
                '📉 Downgrade ejecutado: @tenant de @old a @new',
                [
                    '@tenant' => $tenant->getName(),
                    '@old' => $previousPlan ? $previousPlan->getName() : 'Pro',
                    '@new' => $downgradePlan->getName(),
                ]
            );

            return TRUE;

        } catch (\Exception $e) {
            $this->loggerFactory->get('reverse_trial')->error(
                'Error al ejecutar downgrade para @tenant: @message',
                [
                    '@tenant' => $tenant->getName(),
                    '@message' => $e->getMessage(),
                ]
            );
            return FALSE;
        }
    }

    /**
     * Obtiene los días restantes de trial para un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return int
     *   Días restantes (0 si expirado o no en trial).
     */
    public function getTrialDaysRemaining(TenantInterface $tenant): int
    {
        if (!$tenant->isOnTrial()) {
            return 0;
        }

        $trialEnds = $tenant->getTrialEndsAt();
        if (!$trialEnds) {
            return 0;
        }

        $now = new \DateTime();
        $diff = $now->diff($trialEnds);

        if ($diff->invert) {
            return 0; // Ya expiró.
        }

        return $diff->days;
    }

    /**
     * Procesa notificaciones de trial expirando.
     *
     * Busca tenants con reverse trial que necesitan notificación según
     * los días restantes (7, 3, 1, 0).
     *
     * @return int
     *   Número de notificaciones enviadas.
     */
    public function processTrialNotifications(): int
    {
        $sent = 0;
        $now = new \DateTime();

        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');

            // Buscar tenants en reverse trial activo.
            $query = $tenantStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('subscription_status', 'trial')
                ->condition('is_reverse_trial', TRUE);

            $tenantIds = $query->execute();

            foreach ($tenantIds as $tenantId) {
                $tenant = $tenantStorage->load($tenantId);
                if (!$tenant) {
                    continue;
                }

                $daysRemaining = $this->getTrialDaysRemaining($tenant);

                // Verificar si debemos enviar notificación.
                if (in_array($daysRemaining, self::NOTIFICATION_DAYS)) {
                    if ($this->sendTrialExpiringNotification($tenant, $daysRemaining)) {
                        $sent++;
                    }
                }
            }

        } catch (\Exception $e) {
            $this->loggerFactory->get('reverse_trial')->error(
                'Error procesando notificaciones de trial: @message',
                ['@message' => $e->getMessage()]
            );
        }

        return $sent;
    }

    /**
     * Envía notificación de trial expirando.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param int $daysRemaining
     *   Días restantes de trial.
     *
     * @return bool
     *   TRUE si se envió correctamente.
     */
    public function sendTrialExpiringNotification(TenantInterface $tenant, int $daysRemaining): bool
    {
        // Evitar duplicados usando state.
        $notificationKey = 'reverse_trial_notification_' . $tenant->id() . '_' . $daysRemaining;
        $lastSent = $this->state->get($notificationKey, 0);
        $oneDayAgo = time() - 86400;

        if ($lastSent > $oneDayAgo) {
            return FALSE; // Ya se envió hoy.
        }

        $adminUser = $tenant->getAdminUser();
        if (!$adminUser || !$adminUser->getEmail()) {
            return FALSE;
        }

        $params = [
            'tenant_name' => $tenant->getName(),
            'admin_name' => $adminUser->getDisplayName(),
            'days_remaining' => $daysRemaining,
            'upgrade_url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant.change_plan', [], ['absolute' => TRUE])->toString(),
        ];

        $result = $this->mailManager->mail(
            'ecosistema_jaraba_core',
            'reverse_trial_expiring',
            $adminUser->getEmail(),
            $adminUser->getPreferredLangcode(),
            $params,
            NULL,
            TRUE
        );

        if ($result['result']) {
            $this->state->set($notificationKey, time());
            $this->loggerFactory->get('reverse_trial')->info(
                '📧 Notificación enviada a @tenant: @days días restantes',
                ['@tenant' => $tenant->getName(), '@days' => $daysRemaining]
            );
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Envía notificación de downgrade ejecutado.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface|null $previousPlan
     *   Plan anterior.
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $newPlan
     *   Nuevo plan.
     */
    protected function sendDowngradeNotification(
        TenantInterface $tenant,
        ?SaasPlanInterface $previousPlan,
        SaasPlanInterface $newPlan
    ): void {
        $adminUser = $tenant->getAdminUser();
        if (!$adminUser || !$adminUser->getEmail()) {
            return;
        }

        $params = [
            'tenant_name' => $tenant->getName(),
            'admin_name' => $adminUser->getDisplayName(),
            'previous_plan' => $previousPlan ? $previousPlan->getName() : 'Pro',
            'new_plan' => $newPlan->getName(),
            'upgrade_url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant.change_plan', [], ['absolute' => TRUE])->toString(),
        ];

        $this->mailManager->mail(
            'ecosistema_jaraba_core',
            'reverse_trial_downgraded',
            $adminUser->getEmail(),
            $adminUser->getPreferredLangcode(),
            $params,
            NULL,
            TRUE
        );
    }

    /**
     * Obtiene un plan por su nombre.
     *
     * @param string $name
     *   Nombre del plan (machine name).
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface|null
     *   El plan o NULL si no existe.
     */
    protected function getPlanByName(string $name): ?SaasPlanInterface
    {
        try {
            $storage = $this->entityTypeManager->getStorage('saas_plan');
            $plans = $storage->loadByProperties(['name' => $name]);
            return !empty($plans) ? reset($plans) : NULL;
        } catch (\Exception $e) {
            return NULL;
        }
    }

    /**
     * Verifica si un tenant está en reverse trial.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     *
     * @return bool
     *   TRUE si está en reverse trial.
     */
    public function isInReverseTrial(TenantInterface $tenant): bool
    {
        return $tenant->isOnTrial() &&
            $tenant->get('is_reverse_trial')->value == TRUE;
    }

    /**
     * Obtiene estadísticas de reverse trials.
     *
     * @return array
     *   Array con estadísticas.
     */
    public function getStatistics(): array
    {
        try {
            $tenantStorage = $this->entityTypeManager->getStorage('tenant');

            // Tenants en reverse trial activo.
            $activeTrials = $tenantStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('subscription_status', 'trial')
                ->condition('is_reverse_trial', TRUE)
                ->count()
                ->execute();

            // Tenants que convirtieron (pagaron antes de expirar).
            // Esto requeriría tracking adicional - placeholder.
            $converted = 0;

            // Tenants que fueron downgraded.
            $downgraded = $this->state->get('reverse_trial_total_downgraded', 0);

            return [
                'active_trials' => $activeTrials,
                'converted' => $converted,
                'downgraded' => $downgraded,
                'conversion_rate' => $activeTrials > 0 ? round(($converted / $activeTrials) * 100, 1) : 0,
            ];

        } catch (\Exception $e) {
            return [
                'active_trials' => 0,
                'converted' => 0,
                'downgraded' => 0,
                'conversion_rate' => 0,
            ];
        }
    }

}
