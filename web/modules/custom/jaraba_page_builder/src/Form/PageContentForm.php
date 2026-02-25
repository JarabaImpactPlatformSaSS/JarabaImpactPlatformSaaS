<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\jaraba_page_builder\Service\FormBuilderService;
use Drupal\jaraba_page_builder\Service\TenantResolverService;
use Drupal\jaraba_page_builder\Service\QuotaManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for creating/editing Page Builder pages.
 *
 * Combines static fields (title, URL, SEO) with dynamic fields generated
 * from the template JSON Schema. Extends PremiumEntityFormBase for
 * glassmorphism sections, pill nav, and premium UX.
 *
 * Flow:
 * 1. User selects template in Template Picker
 * 2. Redirected here with ?template=<template_id>
 * 3. Static + dynamic fields from schema are loaded
 * 4. On save, validated and stored in content_data (JSON)
 */
class PageContentForm extends PremiumEntityFormBase {

  /**
   * The form builder service for dynamic template fields.
   */
  protected FormBuilderService $formBuilder;

  /**
   * The tenant resolver service.
   */
  protected TenantResolverService $tenantResolver;

  /**
   * The quota manager service.
   */
  protected QuotaManagerService $quotaManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->formBuilder = $container->get('jaraba_page_builder.form_builder');
    $instance->tenantResolver = $container->get('jaraba_page_builder.tenant_resolver');
    $instance->quotaManager = $container->get('jaraba_page_builder.quota_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Basic info'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Page title and URL path.'),
        'fields' => ['title', 'path_alias'],
      ],
      'seo' => [
        'label' => $this->t('SEO'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Meta title and description for search engines.'),
        'fields' => ['meta_title', 'meta_description'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Publication status and menu link.'),
        'fields' => ['status', 'menu_link'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'meta_title' => 70,
      'meta_description' => 160,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
    $page = $this->entity;

    // ── Quota check for new pages ───────────────────────────────────────
    if ($page->isNew()) {
      $quota_check = $this->quotaManager->checkCanCreatePage();
      if (!$quota_check['allowed']) {
        $form['quota_exceeded'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['messages', 'messages--error']],
          'message' => ['#markup' => $quota_check['message']],
          '#weight' => -1001,
        ];
        $form['#disabled'] = TRUE;
      }
      elseif (isset($quota_check['remaining'])) {
        $form['quota_info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['messages', 'messages--status']],
          'message' => [
            '#markup' => $this->t('You can create @count more pages with your current plan.', [
              '@count' => $quota_check['remaining'],
            ]),
          ],
          '#weight' => -1001,
        ];
      }
    }

    // ── Dynamic template fields ─────────────────────────────────────────
    $template = $this->getSelectedTemplate($form_state);

    if ($template) {
      $schema = $template->getFieldsSchema();

      if (!empty($schema)) {
        $current_values = $page->get('content_data')->value;
        $values = is_string($current_values) ? json_decode($current_values, TRUE) : [];

        // Template info badge — insert before sections.
        $form['template_info'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['template-info-box']],
          '#weight' => -998,
          'info' => [
            '#markup' => '<div class="template-badge">'
              . '<strong>' . $this->t('Template:') . '</strong> '
              . $template->label()
              . ($template->isPremium() ? ' <span class="premium-badge">★ Premium</span>' : '')
              . '</div>',
          ],
        ];

        // Dynamic content section as a premium glass card.
        $form['premium_section_content'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['premium-form__section', 'glass-card'],
            'id' => 'premium-section-content',
            'data-premium-section' => 'content',
          ],
          '#weight' => -5,
          '#tree' => TRUE,
        ];

        $form['premium_section_content']['section_header'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="premium-form__section-header">
            <span class="premium-form__section-icon">{{ jaraba_icon("ui", "edit", { variant: "duotone", size: "20px", color: "corporate" }) }}</span>
            <div>
              <h3 class="premium-form__section-title">{{ label }}</h3>
              <p class="premium-form__section-desc">{{ description }}</p>
            </div>
          </div>',
          '#context' => [
            'label' => $this->t('Page Content'),
            'description' => $this->t('Dynamic fields from the selected template.'),
          ],
          '#weight' => -100,
        ];

        // Build dynamic fields inside the content section.
        $dynamic_fields = $this->formBuilder->buildForm($schema, $values ?? []);
        $form['premium_section_content'] += $dynamic_fields;

        // Inject the content pill into nav.
        $this->injectContentPillIntoNav($form);
      }
    }

    // ── Advanced mode (admin only) ──────────────────────────────────────
    $is_admin = $this->currentUser()->hasPermission('administer page builder');

    if ($is_admin) {
      // Move technical fields into the "Other" section created by parent.
      // They'll be in premium_section_other automatically.
    }
    else {
      // Hide technical fields from normal users.
      foreach (['template_id', 'content_data', 'tenant_id'] as $field) {
        if (isset($form['premium_section_other'][$field])) {
          $form['premium_section_other'][$field]['#access'] = FALSE;
        }
      }
    }

    // ── Extra libraries ─────────────────────────────────────────────────
    $form['#attached']['library'][] = 'jaraba_page_builder/builder';
    $form['#attached']['library'][] = 'jaraba_page_builder/ai-content-generator';
    $form['#attributes']['class'][] = 'page-builder-form';
    $form['#attributes']['class'][] = 'jaraba-form-builder';

    return $form;
  }

  /**
   * Injects a "Content" pill into the premium nav for dynamic fields.
   */
  protected function injectContentPillIntoNav(array &$form): void {
    if (!isset($form['premium_nav']['#context']['items'])) {
      return;
    }
    // Insert after the first pill (Basic Info).
    $items = &$form['premium_nav']['#context']['items'];
    $content_pill = [
      'key' => 'content',
      'label' => (string) $this->t('Page Content'),
      'icon_cat' => 'ui',
      'icon_name' => 'edit',
    ];
    array_splice($items, 1, 0, [$content_pill]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate quota.
    if ($this->entity->isNew()) {
      $quota_check = $this->quotaManager->checkCanCreatePage();
      if (!$quota_check['allowed']) {
        $form_state->setErrorByName('', $quota_check['message']);
      }
    }

    // Validate dynamic fields against schema.
    $template = $this->getSelectedTemplate($form_state);
    if ($template) {
      $schema = $template->getFieldsSchema();
      $content_values = $form_state->getValue('premium_section_content') ?? $form_state->getValue('content') ?? [];
      $this->validateAgainstSchema($schema, $content_values, $form_state);
    }
  }

  /**
   * Validates values against the JSON Schema.
   */
  protected function validateAgainstSchema(array $schema, array $values, FormStateInterface $form_state): void {
    if (!isset($schema['properties'])) {
      return;
    }

    $required = $schema['required'] ?? [];

    foreach ($schema['properties'] as $field_name => $field_schema) {
      $value = $values[$field_name] ?? NULL;

      if (in_array($field_name, $required, TRUE) && empty($value)) {
        $label = $field_schema['title'] ?? $field_name;
        $form_state->setErrorByName(
          "premium_section_content][$field_name",
          $this->t('The field @field is required.', ['@field' => $label]),
        );
      }

      if (isset($field_schema['minLength']) && is_string($value)) {
        if (strlen($value) < $field_schema['minLength']) {
          $form_state->setErrorByName(
            "premium_section_content][$field_name",
            $this->t('The field @field must be at least @min characters.', [
              '@field' => $field_schema['title'] ?? $field_name,
              '@min' => $field_schema['minLength'],
            ]),
          );
        }
      }

      if (isset($field_schema['pattern']) && is_string($value)) {
        if (!preg_match('/' . $field_schema['pattern'] . '/', $value)) {
          $form_state->setErrorByName(
            "premium_section_content][$field_name",
            $this->t('The field @field does not have a valid format.', [
              '@field' => $field_schema['title'] ?? $field_name,
            ]),
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
    $page = $this->entity;

    // Assign current tenant if not set.
    if (!$page->getTenantId()) {
      $tenant_id = $this->tenantResolver->getCurrentTenantId();
      if ($tenant_id) {
        $page->set('tenant_id', $tenant_id);
      }
    }

    // Extract and store dynamic content.
    $template = $this->getSelectedTemplate($form_state);
    if ($template) {
      $schema = $template->getFieldsSchema();
      $content_values = $form_state->getValue('premium_section_content') ?? $form_state->getValue('content') ?? [];
      $extracted_values = $this->formBuilder->extractValues($schema, $content_values);
      $page->set('content_data', json_encode($extracted_values, JSON_UNESCAPED_UNICODE));
    }

    $status = parent::save($form, $form_state);
    $form_state->setRedirectUrl($page->toUrl('collection'));

    return $status;
  }

  /**
   * Gets the selected template for the current page.
   */
  protected function getSelectedTemplate(FormStateInterface $form_state): ?\Drupal\jaraba_page_builder\PageTemplateInterface {
    /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
    $page = $this->entity;

    $template_id = $page->get('template_id')->value;

    if (!$template_id) {
      $template_id = \Drupal::request()->query->get('template');
    }

    if (!$template_id) {
      return NULL;
    }

    $template = \Drupal::entityTypeManager()
      ->getStorage('page_template')
      ->load($template_id);

    return $template instanceof \Drupal\jaraba_page_builder\PageTemplateInterface ? $template : NULL;
  }

}
