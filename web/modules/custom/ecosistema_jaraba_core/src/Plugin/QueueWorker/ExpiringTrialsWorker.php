<?php

namespace Drupal\ecosistema_jaraba_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BE-05 + LOW-10: Processes expiring trial notifications asynchronously.
 *
 * Sends reminder emails to tenant admins when their trial is expiring.
 *
 * @QueueWorker(
 *   id = "ecosistema_jaraba_expiring_trials",
 *   title = @Translation("Process expiring trial notifications"),
 *   cron = {"time" = 30}
 * )
 */
class ExpiringTrialsWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected MailManagerInterface $mailManager,
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
            $container->get('logger.channel.ecosistema_jaraba_core'),
            $container->get('plugin.manager.mail'),
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
        if (!$tenant || $tenant->get('trial_reminder_sent')->value) {
            return;
        }

        // LOW-10: Send trial expiring email to tenant admin.
        $adminUser = $tenant->getAdminUser();
        if ($adminUser && $adminUser->getEmail()) {
            $trialEnds = $tenant->getTrialEndsAt();
            $daysLeft = $trialEnds
                ? max(0, (int) ((strtotime($trialEnds) - time()) / 86400))
                : 0;

            $params = [
                'tenant_name' => $tenant->getName(),
                'admin_name' => $adminUser->getDisplayName(),
                'days_left' => $daysLeft,
                'trial_ends' => $trialEnds,
            ];

            $result = $this->mailManager->mail(
                'ecosistema_jaraba_core',
                'trial_expiring',
                $adminUser->getEmail(),
                $adminUser->getPreferredLangcode(),
                $params,
            );

            if ($result['result']) {
                $this->logger->info(
                    'Recordatorio de trial enviado a tenant @tenant (@email), @days días restantes.',
                    [
                        '@tenant' => $tenant->getName(),
                        '@email' => $adminUser->getEmail(),
                        '@days' => $daysLeft,
                    ]
                );
            } else {
                $this->logger->warning(
                    'Fallo al enviar email de trial a tenant @tenant (@email).',
                    [
                        '@tenant' => $tenant->getName(),
                        '@email' => $adminUser->getEmail(),
                    ]
                );
            }
        }

        $tenant->set('trial_reminder_sent', TRUE);
        $tenant->save();
    }

}
