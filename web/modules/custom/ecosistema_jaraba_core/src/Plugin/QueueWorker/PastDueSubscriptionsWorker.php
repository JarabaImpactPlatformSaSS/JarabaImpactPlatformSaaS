<?php

namespace Drupal\ecosistema_jaraba_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BE-05: Processes past-due subscription suspensions asynchronously.
 *
 * @QueueWorker(
 *   id = "ecosistema_jaraba_past_due_subscriptions",
 *   title = @Translation("Process past-due subscription suspensions"),
 *   cron = {"time" = 30}
 * )
 */
class PastDueSubscriptionsWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantManager $tenantManager,
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
            $container->get('ecosistema_jaraba_core.tenant_manager'),
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

        $this->tenantManager->suspendTenant($tenant, 'payment_overdue');

        $this->logger->warning(
            'Tenant @tenant suspendido por pago pendiente',
            ['@tenant' => $tenant->getName()]
        );
    }

}
