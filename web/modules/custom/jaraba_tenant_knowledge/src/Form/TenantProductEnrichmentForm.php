<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for Tenant Product Enrichment entities.
 */
class TenantProductEnrichmentForm extends PremiumEntityFormBase {

  /**
   * Tenant context service.
   */
  protected ?TenantContextService $tenantContext = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'commerce', 'name' => 'tag'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Product SKU, name, and category.'),
        'fields' => ['product_sku', 'product_name', 'category'],
      ],
      'description' => [
        'label' => $this->t('Description & Specifications'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Detailed product information for the copilot.'),
        'fields' => ['description', 'specifications', 'benefits', 'use_cases'],
      ],
      'details' => [
        'label' => $this->t('Pricing & FAQs'),
        'icon' => ['category' => 'commerce', 'name' => 'tag'],
        'description' => $this->t('Pricing information and product-specific FAQs.'),
        'fields' => ['price_info', 'product_faqs'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Publishing options.'),
        'fields' => ['is_published'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Assign tenant automatically for new entities.
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      $tenantId = $this->getCurrentTenantId();
      if ($tenantId) {
        $entity->set('tenant_id', $tenantId);
      }
    }

    $form = parent::buildForm($form, $form_state);

    // Hide system fields.
    foreach (['tenant_id', 'content_hash', 'qdrant_point_id'] as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    // Update content hash before saving.
    $entity->updateContentHash();

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.products'));
    return $result;
  }

  /**
   * Gets the current tenant ID.
   */
  protected function getCurrentTenantId(): ?int {
    if ($this->tenantContext !== NULL) {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    return NULL;
  }

}
