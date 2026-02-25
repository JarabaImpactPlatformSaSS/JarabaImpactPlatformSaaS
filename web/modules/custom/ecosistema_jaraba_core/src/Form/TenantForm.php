<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Premium form for creating/editing Tenant entities.
 *
 * MAXIMUM CAUTION: This is the core tenant management form.
 * Preserves: Stripe admin-only fields, status info display,
 * domain validation, theme_overrides JSON validation, logging.
 */
class TenantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información del Tenant'),
        'icon' => ['category' => 'business', 'name' => 'building'],
        'description' => $this->t('Nombre, vertical, dominio y administrador.'),
        'fields' => ['name', 'vertical', 'domain', 'admin_user'],
      ],
      'subscription' => [
        'label' => $this->t('Suscripción'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Plan, estado y período de suscripción.'),
        'fields' => ['subscription_plan', 'subscription_status', 'trial_ends', 'current_period_end'],
      ],
      'stripe' => [
        'label' => $this->t('Configuración Stripe'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('IDs de integración con Stripe.'),
        'fields' => ['stripe_customer_id', 'stripe_subscription_id', 'stripe_connect_id'],
      ],
      'theming' => [
        'label' => $this->t('Personalización de Marca'),
        'icon' => ['category' => 'ui', 'name' => 'palette'],
        'description' => $this->t('Personalizaciones de tema en JSON.'),
        'fields' => ['theme_overrides'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'building'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $entity */
    $entity = $this->entity;

    // Stripe section: admin-only access.
    if (!$this->currentUser()->hasPermission('administer tenants')) {
      if (isset($form['premium_section_stripe'])) {
        $form['premium_section_stripe']['#access'] = FALSE;
      }
    }

    // Theme overrides description.
    $section = 'premium_section_theming';
    if (isset($form[$section]['theme_overrides']['widget'][0]['value'])) {
      $form[$section]['theme_overrides']['widget'][0]['value']['#description'] = $this->t('JSON con personalizaciones. Ejemplo: {"color_primary": "#10B981", "logo": "/path/to/logo.png"}');
    }

    // Status info display for existing entities.
    if (!$entity->isNew()) {
      $status = $entity->getSubscriptionStatus();
      $status_labels = [
        TenantInterface::STATUS_PENDING => $this->t('Pendiente de activación'),
        TenantInterface::STATUS_TRIAL => $this->t('En período de prueba'),
        TenantInterface::STATUS_ACTIVE => $this->t('Activo'),
        TenantInterface::STATUS_PAST_DUE => $this->t('Pago pendiente'),
        TenantInterface::STATUS_SUSPENDED => $this->t('Suspendido'),
        TenantInterface::STATUS_CANCELLED => $this->t('Cancelado'),
      ];

      $form['status_info'] = [
        '#type' => 'container',
        '#weight' => -100,
        '#attributes' => [
          'class' => ['messages', 'messages--status'],
        ],
        'content' => [
          '#markup' => '<strong>' . $this->t('Estado actual:') . '</strong> ' . ($status_labels[$status] ?? $status),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate domain format.
    $domain = $form_state->getValue(['domain', 0, 'value']);
    if ($domain) {
      if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/i', $domain)) {
        $form_state->setErrorByName('domain', $this->t('El dominio no tiene un formato válido.'));
      }
    }

    // Validate theme_overrides JSON.
    $theme_overrides = $form_state->getValue(['theme_overrides', 0, 'value']);
    if ($theme_overrides && json_decode($theme_overrides) === NULL) {
      $form_state->setErrorByName('theme_overrides', $this->t('Las personalizaciones de tema deben ser un JSON válido.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($result === SAVED_NEW) {
      $this->logger('ecosistema_jaraba_core')->notice('Nuevo tenant creado: %label', ['%label' => $entity->label()]);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
