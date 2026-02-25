<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for KB Video entities.
 */
class KbVideoForm extends PremiumEntityFormBase {

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
    return ['category' => 'media', 'name' => 'video'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'media', 'name' => 'video'],
        'description' => $this->t('Video details and media.'),
        'fields' => ['title', 'video_url', 'thumbnail_url', 'description', 'duration_seconds'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Category, linked article, and status.'),
        'fields' => ['category_id', 'article_id', 'video_status'],
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
    foreach (['tenant_id', 'view_count'] as $field) {
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
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
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
