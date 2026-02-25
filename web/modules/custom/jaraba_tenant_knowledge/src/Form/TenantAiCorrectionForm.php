<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for AI Correction entities.
 *
 * Allows tenants to register when the copilot gives an incorrect response.
 * Generates rules to improve future responses.
 */
class TenantAiCorrectionForm extends PremiumEntityFormBase {

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
    return ['category' => 'ai', 'name' => 'brain'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Register a correction so the copilot learns from mistakes.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('What happened?'),
        'icon' => ['category' => 'ui', 'name' => 'search'],
        'description' => $this->t('Identify the type and context of the error.'),
        'fields' => ['title', 'correction_type', 'related_topic', 'priority'],
      ],
      'context' => [
        'label' => $this->t('Conversation context'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('The original query and the incorrect vs correct responses.'),
        'fields' => ['original_query', 'incorrect_response', 'correct_response'],
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

    // Show generated rule if it exists (edit mode only).
    if (!$entity->isNew() && $entity->isApplied()) {
      $form['generated_rule_display'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['premium-form__section', 'glass-card'],
        ],
        '#weight' => 50,
      ];
      $form['generated_rule_display']['rule'] = [
        '#type' => 'markup',
        '#markup' => '<h3>' . $this->t('Generated Rule') . '</h3><pre>' . htmlspecialchars($entity->getGeneratedRule()) . '</pre>',
      ];
    }

    // Add "Apply Correction" button if not yet applied.
    if (!$entity->isNew() && !$entity->isApplied()) {
      $form['actions']['apply'] = [
        '#type' => 'submit',
        '#value' => $this->t('Apply Correction'),
        '#submit' => ['::applyCorrection'],
        '#weight' => 5,
        '#button_type' => 'primary',
      ];
    }

    // Hide system fields.
    foreach (['tenant_id', 'status', 'applied_at', 'hit_count', 'generated_rule'] as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * Submit handler to apply the correction.
   */
  public function applyCorrection(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantAiCorrection $entity */
    $entity = $this->getEntity();

    // Save form changes first.
    $this->copyFormValuesToEntity($entity, $form, $form_state);
    $entity->save();

    // Apply the correction (generates the rule).
    $entity->apply();

    $this->messenger()->addStatus($this->t('Correction applied. The copilot has learned from this error.'));
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.corrections'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.corrections'));
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
