<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for Tenant Policy entities.
 *
 * Includes a template selector for new policies.
 */
class TenantPolicyForm extends PremiumEntityFormBase {

  /**
   * Tenant context service.
   */
  protected ?TenantContextService $tenantContext = NULL;

  /**
   * Templates for predefined policy types.
   */
  protected const TEMPLATES = [
    'returns' => [
      'title' => 'Política de Devoluciones',
      'content' => "## Política de Devoluciones\n\n### Plazo de Devolución\nAceptamos devoluciones dentro de los [X] días posteriores a la compra.\n\n### Condiciones\n- El producto debe estar sin usar y en su embalaje original\n- Debe incluir todos los accesorios y etiquetas\n- Se requiere el ticket de compra o factura\n\n### Proceso\n1. Contacta a nuestro servicio de atención al cliente\n2. Recibirás un número de autorización de devolución\n3. Envía el producto a nuestra dirección\n4. El reembolso se procesará en [X] días hábiles\n\n### Excepciones\n- Productos personalizados no admiten devolución\n- Artículos en oferta tienen condiciones especiales",
    ],
    'shipping' => [
      'title' => 'Política de Envíos',
      'content' => "## Política de Envíos\n\n### Tiempos de Entrega\n- **Nacional**: 2-5 días hábiles\n- **Internacional**: 7-15 días hábiles\n\n### Costes de Envío\n- Pedidos superiores a [X]€: Envío gratuito\n- Resto de pedidos: Tarifa según peso y destino\n\n### Seguimiento\nRecibirás un email con el número de seguimiento una vez enviado tu pedido.\n\n### Zonas de Cobertura\nRealizamos envíos a todo el territorio nacional y países de la UE.",
    ],
    'privacy' => [
      'title' => 'Política de Privacidad',
      'content' => "## Política de Privacidad\n\n### Responsable del Tratamiento\n[Nombre de la Empresa]\n\n### Datos Recopilados\n- Datos de identificación (nombre, email)\n- Datos de facturación\n- Historial de compras\n\n### Finalidad\n- Gestión de pedidos y entregas\n- Comunicaciones comerciales (si consientes)\n- Mejora de nuestros servicios\n\n### Derechos\nPuedes ejercer tus derechos de acceso, rectificación, cancelación y oposición contactando a [email].",
    ],
  ];

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
    return ['category' => 'ui', 'name' => 'shield'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Policy type, title, and full content.'),
        'fields' => ['policy_type', 'title', 'content', 'summary'],
      ],
      'settings' => [
        'label' => $this->t('Advanced Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Version notes, publishing, and effective date.'),
        'fields' => ['version_notes', 'is_published', 'effective_date'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $entity = $this->getEntity();

    // Assign tenant automatically for new entities.
    if ($entity->isNew()) {
      $tenantId = $this->getCurrentTenantId();
      if ($tenantId) {
        $entity->set('tenant_id', $tenantId);
      }
    }

    $form = parent::buildForm($form, $form_state);

    // Template selector for new policies.
    if ($entity->isNew()) {
      $form['template_selector'] = [
        '#type' => 'select',
        '#title' => $this->t('Use template'),
        '#options' => [
          '' => $this->t('- No template -'),
          'returns' => $this->t('Returns Policy'),
          'shipping' => $this->t('Shipping Policy'),
          'privacy' => $this->t('Privacy Policy'),
        ],
        '#weight' => -1001,
        '#ajax' => [
          'callback' => '::applyTemplateAjax',
          'wrapper' => 'policy-form-wrapper',
        ],
      ];

      // Wrapper for AJAX.
      $form['#prefix'] = '<div id="policy-form-wrapper">';
      $form['#suffix'] = '</div>';
    }

    // Hide system fields.
    foreach (['tenant_id', 'content_hash', 'qdrant_point_id', 'version_number'] as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * AJAX callback to apply a template.
   */
  public function applyTemplateAjax(array &$form, FormStateInterface $form_state): array {
    $template = $form_state->getValue('template_selector');

    if (!empty($template) && isset(self::TEMPLATES[$template])) {
      $templateData = self::TEMPLATES[$template];

      // Set template values on the form.
      if (isset($form['premium_section_content']['policy_type']['widget'][0]['value'])) {
        $form['premium_section_content']['policy_type']['widget'][0]['value']['#value'] = $template;
      }
      if (isset($form['premium_section_content']['title']['widget'][0]['value'])) {
        $form['premium_section_content']['title']['widget'][0]['value']['#value'] = $templateData['title'];
      }
      if (isset($form['premium_section_content']['content']['widget'][0]['value'])) {
        $form['premium_section_content']['content']['widget'][0]['value']['#value'] = $templateData['content'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $isNew = $entity->isNew();

    // Increment version if content changed (existing entities only).
    if (!$isNew && $entity->needsRegeneration()) {
      $entity->incrementVersion();
    }

    // Update content hash before saving.
    $entity->updateContentHash();

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.policies'));
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
