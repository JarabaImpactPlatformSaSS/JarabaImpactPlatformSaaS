<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_whitelabel\Service\ResellerManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the reseller/partner portal.
 *
 * Provides the partner dashboard with metrics, managed tenants and
 * commission history, as well as the onboarding flow for new partners.
 */
class ResellerPortalController extends ControllerBase {

  /**
   * The reseller manager service.
   */
  protected ResellerManagerService $resellerManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jaraba_whitelabel\Service\ResellerManagerService $reseller_manager
   *   The reseller manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ResellerManagerService $reseller_manager,
    AccountProxyInterface $account,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->resellerManager = $reseller_manager;
    $this->account = $account;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_whitelabel.reseller_manager'),
      $container->get('current_user'),
      $container->get('logger.channel.jaraba_whitelabel'),
    );
  }

  /**
   * Renders the partner portal dashboard.
   *
   * @return array
   *   A render array with the reseller dashboard template.
   */
  public function dashboard(): array {
    try {
      $email = $this->account->getEmail() ?? '';
      $reseller = $this->resellerManager->getResellerByEmail($email);

      $managedTenants = [];
      $commissions = [];
      $metrics = $this->getDefaultMetrics();

      if ($reseller) {
        $resellerId = (int) $reseller['id'];
        $managedTenants = $this->resellerManager->getManagedTenants($resellerId);

        $currentPeriod = date('Y-m');
        $commissions = $this->resellerManager->calculateCommissions($resellerId, $currentPeriod);

        $metrics = [
          'total_tenants' => $commissions['total_tenants'] ?? 0,
          'total_revenue' => $commissions['total_revenue'] ?? 0.0,
          'commission_earned' => $commissions['commission_earned'] ?? 0.0,
          'pending_payout' => $commissions['pending_payout'] ?? 0.0,
        ];
      }

      return [
        '#theme' => 'jaraba_reseller_dashboard',
        '#reseller' => $reseller,
        '#managed_tenants' => $managedTenants,
        '#commissions' => $commissions,
        '#metrics' => $metrics,
        '#attached' => [
          'library' => ['jaraba_whitelabel/reseller-portal'],
        ],
        '#cache' => [
          'contexts' => ['user'],
          'tags' => ['whitelabel_reseller_list'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading reseller dashboard: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('An error occurred loading the partner portal. Please try again later.'),
      ];
    }
  }

  /**
   * Renders the partner onboarding page.
   *
   * @return array
   *   A render array with the reseller onboarding template.
   */
  public function onboarding(): array {
    try {
      $email = $this->account->getEmail() ?? '';
      $reseller = $this->resellerManager->getResellerByEmail($email);

      $steps = [
        ['key' => 'company_info', 'label' => $this->t('Company Information'), 'completed' => FALSE],
        ['key' => 'agreement', 'label' => $this->t('Partner Agreement'), 'completed' => FALSE],
        ['key' => 'training', 'label' => $this->t('Platform Training'), 'completed' => FALSE],
        ['key' => 'launch', 'label' => $this->t('Launch'), 'completed' => FALSE],
      ];

      // Determine current step based on reseller status.
      $currentStep = 0;
      if ($reseller) {
        $status = $reseller['reseller_status'] ?? '';
        if ($status === 'active') {
          $currentStep = 3;
          foreach ($steps as &$step) {
            $step['completed'] = TRUE;
          }
          unset($step);
        }
        elseif ($status === 'pending') {
          // Company info is complete if the reseller record exists.
          $steps[0]['completed'] = TRUE;
          $currentStep = 1;
        }
      }

      return [
        '#theme' => 'jaraba_reseller_onboarding',
        '#steps' => $steps,
        '#current_step' => $currentStep,
        '#reseller' => $reseller,
        '#attached' => [
          'library' => ['jaraba_whitelabel/reseller-portal'],
        ],
        '#cache' => [
          'contexts' => ['user'],
          'tags' => ['whitelabel_reseller_list'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading reseller onboarding: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('An error occurred loading the onboarding page. Please try again later.'),
      ];
    }
  }

  /**
   * Returns default metrics when no reseller data is available.
   *
   * @return array
   *   Default metrics array.
   */
  protected function getDefaultMetrics(): array {
    return [
      'total_tenants' => 0,
      'total_revenue' => 0.0,
      'commission_earned' => 0.0,
      'pending_payout' => 0.0,
    ];
  }

}
