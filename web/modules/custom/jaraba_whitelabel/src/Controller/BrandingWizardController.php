<?php

declare(strict_types=1);

namespace Drupal\jaraba_whitelabel\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whitelabel\Service\ConfigResolverService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the branding wizard page.
 *
 * Provides a multi-step wizard UI that allows tenant administrators
 * to configure logo, colours, custom CSS, footer HTML and preview
 * their whitelabel branding in real time.
 */
class BrandingWizardController extends ControllerBase {

  /**
   * The whitelabel config resolver service.
   */
  protected ConfigResolverService $configResolver;

  /**
   * The logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jaraba_whitelabel\Service\ConfigResolverService $config_resolver
   *   The config resolver service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigResolverService $config_resolver,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configResolver = $config_resolver;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_whitelabel.config_resolver'),
      $container->get('logger.channel.jaraba_whitelabel'),
    );
  }

  /**
   * Renders the branding wizard page.
   *
   * @return array
   *   A render array with the branding wizard template.
   */
  public function wizard(): array {
    try {
      $tenantId = $this->getTenantIdFromRequest();
      $config = $tenantId ? $this->configResolver->resolveConfig($tenantId) : NULL;

      $steps = [
        ['key' => 'logo', 'label' => $this->t('Logo & Favicon')],
        ['key' => 'colors', 'label' => $this->t('Brand Colours')],
        ['key' => 'css', 'label' => $this->t('Custom CSS')],
        ['key' => 'footer', 'label' => $this->t('Footer HTML')],
        ['key' => 'preview', 'label' => $this->t('Preview')],
      ];

      return [
        '#theme' => 'jaraba_branding_wizard',
        '#config' => $config,
        '#tenant_id' => $tenantId,
        '#steps' => $steps,
        '#current_step' => 0,
        '#attached' => [
          'library' => ['jaraba_whitelabel/branding-wizard'],
        ],
        '#cache' => [
          'contexts' => ['user', 'url.query_args'],
          'tags' => ['whitelabel_config_list'],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading branding wizard: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        '#markup' => $this->t('An error occurred loading the branding wizard. Please try again later.'),
      ];
    }
  }

  /**
   * Attempts to resolve the current tenant ID from the request.
   *
   * @return int|null
   *   The tenant ID or NULL if not determinable.
   */
  protected function getTenantIdFromRequest(): ?int {
    $request = \Drupal::request();

    // Check for explicit tenant_id query parameter.
    $tenantId = $request->query->get('tenant_id');
    if ($tenantId !== NULL && is_numeric($tenantId)) {
      return (int) $tenantId;
    }

    // Check for whitelabel config attached by the event subscriber.
    $whitelabelConfig = $request->attributes->get('whitelabel_config');
    if (!empty($whitelabelConfig['tenant_id'])) {
      return (int) $whitelabelConfig['tenant_id'];
    }

    return NULL;
  }

}
