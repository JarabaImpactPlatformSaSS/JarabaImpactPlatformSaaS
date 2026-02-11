<?php

namespace Drupal\ecosistema_jaraba_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BE-05: Processes usage limit checks per tenant asynchronously.
 *
 * @QueueWorker(
 *   id = "ecosistema_jaraba_usage_limits",
 *   title = @Translation("Check tenant usage limits"),
 *   cron = {"time" = 60}
 * )
 */
class UsageLimitsWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('entity_type.manager'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('logger.channel.ecosistema_jaraba_core'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function processItem($data): void
    {
        $tenantId = $data['tenant_id'] ?? NULL;
        if (!$tenantId) {
            return;
        }

        $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
        if (!$tenant) {
            return;
        }

        $plan = $tenant->getSubscriptionPlan();
        if (!$plan) {
            return;
        }

        $limits = $plan->getLimits();
        $metrics = $this->tenantContext->getUsageMetrics($tenant);

        // Check producers limit.
        $maxProductores = $limits['productores'] ?? 0;
        if ($maxProductores > 0) {
            $current = $metrics['productores']['count'] ?? 0;
            $ratio = $current / $maxProductores;

            if ($ratio >= 1.0) {
                $this->sendAlert($tenant, 'producers', 100, $current, $maxProductores);
            } elseif ($ratio >= 0.80) {
                $this->sendAlert($tenant, 'producers', 80, $current, $maxProductores);
            }
        }

        // Check storage limit.
        $maxStorageMb = $limits['almacenamiento_mb'] ?? 0;
        if ($maxStorageMb > 0) {
            $current = $metrics['almacenamiento']['used_mb'] ?? 0;
            $ratio = $current / $maxStorageMb;

            if ($ratio >= 1.0) {
                $this->sendAlert($tenant, 'storage', 100, $current, $maxStorageMb);
            } elseif ($ratio >= 0.80) {
                $this->sendAlert($tenant, 'storage', 80, $current, $maxStorageMb);
            }
        }
    }

    /**
     * Sends a usage alert for a tenant.
     */
    protected function sendAlert($tenant, string $type, int $percentage, int $current, int $max): void
    {
        $this->logger->warning(
            'Tenant @tenant: @type usage at @pct% (@current/@max)',
            [
                '@tenant' => $tenant->getName(),
                '@type' => $type,
                '@pct' => $percentage,
                '@current' => $current,
                '@max' => $max,
            ]
        );
        // TODO: Send email alert via mail manager.
    }

}
